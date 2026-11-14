<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Order;
use App\Services\EscrowService;
use App\Services\MpesaService;
use Throwable;

class PaymentController extends Controller
{
    public function initiateStkPush(): void
    {
        $this->verifyCsrf();

        $orderId = (int) ($_POST['order_id'] ?? 0);
        $phone = trim($_POST['mpesa_phone'] ?? '');
        $userId = (int) $_SESSION['user_id'];

        $order = (new Order())->find($orderId);
        if (!$order || (int) $order['buyer_id'] !== $userId || $order['status'] !== 'awaiting_payment') {
            $this->json(['status' => 'error', 'message' => 'Invalid or already paid order.'], 422);
        }

        try {
            $mpesa = new MpesaService();
            $result = $mpesa->stkPush($orderId, $phone, (float) $order['total_amount'], $order['order_reference']);

            if (($result['ResponseCode'] ?? '') === '0') {
                $this->json([
                    'status'    => 'success',
                    'simulated' => !empty($result['simulated']),
                    'message'   => $result['CustomerMessage'] ?? 'STK Push sent. Enter your M-Pesa PIN on your phone.',
                ]);
            }

            $this->json([
                'status'  => 'error',
                'message' => $result['errorMessage'] ?? 'Safaricom M-Pesa request failed.',
            ], 400);
        } catch (Throwable $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Public Webhook called by Safaricom Daraja Servers upon STK Push completion.
     */
    public function mpesaCallback(): void
    {
        $rawBody = file_get_contents('php://input');
        file_put_contents(ROOT_PATH . '/storage/logs/mpesa_callbacks.log', date('c') . ' ' . $rawBody . PHP_EOL, FILE_APPEND);

        $data = json_decode($rawBody, true);
        $callback = $data['Body']['stkCallback'] ?? null;

        if (!$callback) {
            http_response_code(400);
            exit;
        }

        $checkoutRequestId = $callback['CheckoutRequestID'] ?? '';
        $resultCode = (int) ($callback['ResultCode'] ?? -1);
        $resultDesc = $callback['ResultDesc'] ?? '';

        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM mpesa_logs WHERE checkout_request_id = :cid LIMIT 1");
        $stmt->execute(['cid' => $checkoutRequestId]);
        $log = $stmt->fetch();

        if ($log) {
            $receipt = null;
            if ($resultCode === 0 && !empty($callback['CallbackMetadata']['Item'])) {
                foreach ($callback['CallbackMetadata']['Item'] as $item) {
                    if (($item['Name'] ?? '') === 'MpesaReceiptNumber') {
                        $receipt = (string) $item['Value'];
                    }
                }
            }

            $upd = $db->prepare(
                "UPDATE mpesa_logs
                 SET result_code = :code,
                     result_desc = :desc,
                     mpesa_receipt_number = :receipt,
                     raw_callback_json = :json
                 WHERE id = :id"
            );
            $upd->execute([
                'code'    => $resultCode,
                'desc'    => $resultDesc,
                'receipt' => $receipt,
                'json'    => $rawBody,
                'id'      => (int) $log['id'],
            ]);

            if ($resultCode === 0 && !empty($log['order_id']) && $receipt) {
                (new EscrowService())->lockFunds((int) $log['order_id'], $receipt);
            }
        }

        header('Content-Type: application/json');
        echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
        exit;
    }

    public function checkStatus(string $orderId): void
    {
        $order = (new Order())->find((int) $orderId);
        $this->json([
            'status'       => 'success',
            'order_status' => $order['status'] ?? 'unknown',
        ]);
    }
}
