<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Listing;
use App\Models\Order;
use App\Models\Review;
use App\Services\EscrowService;
use Throwable;

class OrderController extends Controller
{
    private Order $orderModel;

    public function __construct()
    {
        $this->orderModel = new Order();
    }

    public function create(): void
    {
        if (($_SESSION['user_role'] ?? '') === 'admin') {
            $_SESSION['flash_error'] = 'Admin accounts cannot place purchases.';
            $this->redirect('/marketplace');
        }

        $this->verifyCsrf();

        $buyerId = (int) $_SESSION['user_id'];
        $listingId = (int) ($_POST['listing_id'] ?? 0);
        $quantity = (float) ($_POST['quantity'] ?? 0);
        $deliveryCounty = trim($_POST['delivery_county'] ?? '');
        $deliveryAddress = trim($_POST['delivery_address'] ?? '');

        $listing = (new Listing())->find($listingId);
        if (!$listing || $listing['status'] !== 'active') {
            $_SESSION['flash_error'] = 'Listing is unavailable.';
            $this->redirect('/marketplace');
        }

        if ((int) $listing['seller_id'] === $buyerId) {
            $_SESSION['flash_error'] = 'You cannot purchase your own listing.';
            $this->redirect('/marketplace/listing/' . $listingId);
        }

        if ($quantity < (float) $listing['min_order_qty'] || $quantity > (float) $listing['quantity_available']) {
            $_SESSION['flash_error'] = 'Requested quantity is outside allowed stock limits.';
            $this->redirect('/marketplace/listing/' . $listingId);
        }

        $orderId = $this->orderModel->createDirect([
            'listing_id'        => $listingId,
            'buyer_id'          => $buyerId,
            'seller_id'         => (int) $listing['seller_id'],
            'agreed_unit_price' => (float) $listing['unit_price'],
            'quantity'          => $quantity,
            'delivery_county'   => $deliveryCounty ?: $_SESSION['user_county'],
            'delivery_address'  => $deliveryAddress,
        ]);

        $this->redirect('/orders/' . $orderId);
    }

    public function show(string $id): void
    {
        $order = $this->orderModel->findDetailed((int) $id);
        $userId = (int) $_SESSION['user_id'];

        if (!$order || ((int) $order['buyer_id'] !== $userId && (int) $order['seller_id'] !== $userId && ($_SESSION['user_role'] ?? '') !== 'admin')) {
            http_response_code(403);
            exit('Unauthorized access to order.');
        }

        // Handle optional seller logistics dispatch update
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['logistics_provider'])) {
            $this->verifyCsrf();
            if ((int) $order['seller_id'] === $userId) {
                $this->orderModel->assignLogistics((int) $id, $_POST['logistics_provider'], $_POST['logistics_contact'] ?? '');
                $_SESSION['flash_success'] = 'Transport & logistics details updated.';
                $this->redirect('/orders/' . $id);
            }
        }

        $hasReviewed = (new Review())->existsForOrder((int) $id);

        $this->view('orders/show', [
            'title'       => 'Order ' . $order['order_reference'],
            'order'       => $order,
            'hasReviewed' => $hasReviewed,
        ]);
    }

    public function confirmDelivery(string $id): void
    {
        $this->verifyCsrf();

        $orderId = (int) $id;
        $buyerId = (int) $_SESSION['user_id'];

        try {
            (new EscrowService())->releaseFunds($orderId, $buyerId);
            $_SESSION['flash_success'] = 'Delivery confirmed! Escrow funds have been released to the seller.';
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        }

        $this->redirect('/orders/' . $orderId);
    }

    public function submitReview(string $id): void
    {
        $this->verifyCsrf();

        $orderId = (int) $id;
        $reviewerId = (int) $_SESSION['user_id'];
        $order = $this->orderModel->find($orderId);

        if (!$order || $order['status'] !== 'delivered' || (int) $order['buyer_id'] !== $reviewerId) {
            $_SESSION['flash_error'] = 'Reviews can only be submitted by the buyer after delivery.';
            $this->redirect('/orders/' . $orderId);
        }

        $reviewModel = new Review();
        if ($reviewModel->existsForOrder($orderId)) {
            $_SESSION['flash_error'] = 'You have already reviewed this order.';
            $this->redirect('/orders/' . $orderId);
        }

        $rating = (int) ($_POST['rating'] ?? 5);
        $comment = trim($_POST['comment'] ?? '');

        $reviewModel->createAndRecalculate($orderId, $reviewerId, (int) $order['seller_id'], $rating, $comment);
        $_SESSION['flash_success'] = 'Thank you! Your rating has updated the seller’s credibility score.';
        $this->redirect('/orders/' . $orderId);
    }
}
