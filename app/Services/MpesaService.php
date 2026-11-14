<?php

namespace App\Services;

use App\Core\Database;
use App\Models\User;
use PDO;
use RuntimeException;

class MpesaService
{
    private array $config;
    private PDO $db;

    public function __construct()
    {
        $this->config = require dirname(__DIR__) . '/Config/mpesa.php';
        $this->db = Database::getInstance();
    }

    private function baseUrl(): string
    {
        return $this->config['env'] === 'live'
            ? 'https://api.safaricom.co.ke'
            : 'https://sandbox.safaricom.co.ke';
    }

    public function getAccessToken(): string
    {
        $credentials = base64_encode($this->config['consumer_key'] . ':' . $this->config['consumer_secret']);
        $ch = curl_init($this->baseUrl() . '/oauth/v1/generate?grant_type=client_credentials');
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER     => ['Authorization: Basic ' . $credentials],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode((string) $response, true);
        if ($httpCode !== 200 || empty($data['access_token'])) {
            throw new RuntimeException('Failed to authenticate with Safaricom Daraja API.');
        }

        return $data['access_token'];
    }

    public function stkPush(int $orderId, string $phone, float $amount, string $accountRef): array
    {
        $normalizedPhone = User::normalizePhone($phone);
        $intAmount = (int) ceil($amount);

        // Local sandbox fallback when Daraja placeholder keys are still in .env
        if ($this->config['consumer_key'] === 'your_sandbox_consumer_key' || empty($this->config['consumer_key'])) {
            $checkoutId = 'ws_CO_SIM_' . date('YmdHis') . '_' . random_int(1000, 9999);
            $merchantId = 'SIM-' . random_int(10000, 99999);
            $receipt    = 'SIM' . strtoupper(bin2hex(random_bytes(4)));

            $this->logRequest($orderId, $merchantId, $checkoutId, $normalizedPhone, $amount);

            // Auto-complete simulated sandbox payment & lock escrow
            $upd = $this->db->prepare(
                "UPDATE mpesa_logs
                 SET result_code = 0, result_desc = 'Simulated Sandbox STK Push Accepted', mpesa_receipt_number = :receipt
                 WHERE checkout_request_id = :cid"
            );
            $upd->execute(['receipt' => $receipt, 'cid' => $checkoutId]);

            (new EscrowService())->lockFunds($orderId, $receipt);

            return [
                'simulated'          => true,
                'CheckoutRequestID'  => $checkoutId,
                'ResponseCode'       => '0',
                'CustomerMessage'    => 'Sandbox Simulation: Payment confirmed & locked in FarmLink Escrow.',
            ];
        }

        $token = $this->getAccessToken();
        $timestamp = date('YmdHis');
        $password = base64_encode($this->config['shortcode'] . $this->config['passkey'] . $timestamp);

        $payload = [
            'BusinessShortCode' => $this->config['shortcode'],
            'Password'          => $password,
            'Timestamp'         => $timestamp,
            'TransactionType'   => 'CustomerPayBillOnline',
            'Amount'            => $intAmount,
            'PartyA'            => $normalizedPhone,
            'PartyB'            => $this->config['shortcode'],
            'PhoneNumber'       => $normalizedPhone,
            'CallBackURL'       => $this->config['callback_url'],
            'AccountReference'  => $accountRef,
            'TransactionDesc'   => 'FarmLink Escrow ' . $accountRef,
        ];

        $ch = curl_init($this->baseUrl() . '/mpesa/stkpush/v1/processrequest');
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ],
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode((string) $response, true) ?? [];
        if (($result['ResponseCode'] ?? '') === '0') {
            $this->logRequest(
                $orderId,
                $result['MerchantRequestID'] ?? '',
                $result['CheckoutRequestID'] ?? '',
                $normalizedPhone,
                $amount
            );
        }

        return $result;
    }

    private function logRequest(int $orderId, string $merchantId, string $checkoutId, string $phone, float $amount): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO mpesa_logs (order_id, merchant_request_id, checkout_request_id, phone_number, amount)
             VALUES (:order_id, :mid, :cid, :phone, :amount)"
        );
        $stmt->execute([
            'order_id' => $orderId,
            'mid'      => $merchantId,
            'cid'      => $checkoutId,
            'phone'    => $phone,
            'amount'   => $amount,
        ]);
    }
}
