<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Bid;
use App\Models\Listing;
use Throwable;

class BidController extends Controller
{
    private Bid $bidModel;
    private Listing $listingModel;

    public function __construct()
    {
        $this->bidModel = new Bid();
        $this->listingModel = new Listing();
    }

    public function submit(): void
    {
        $this->verifyCsrf();

        if (($_SESSION['user_role'] ?? '') === 'admin') {
            $this->respondError('Admin accounts cannot place purchases or submit bids.', '/marketplace');
        }

        $buyerId = (int) $_SESSION['user_id'];
        $listingId = (int) ($_POST['listing_id'] ?? 0);
        $offeredPrice = (float) ($_POST['offered_unit_price'] ?? 0);
        $requestedQty = (float) ($_POST['requested_qty'] ?? 0);
        $buyerNote = trim($_POST['buyer_note'] ?? '');

        $listing = $this->listingModel->find($listingId);
        if (!$listing || $listing['status'] !== 'active' || (int) $listing['is_negotiable'] !== 1) {
            $this->respondError('This listing does not accept price bids.', '/marketplace');
        }

        if ((int) $listing['seller_id'] === $buyerId) {
            $this->respondError('You cannot bid on your own listing.', '/marketplace/listing/' . $listingId);
        }

        if ($offeredPrice <= 0 || $requestedQty < (float) $listing['min_order_qty'] || $requestedQty > (float) $listing['quantity_available']) {
            $this->respondError('Invalid bid price or requested quantity.', '/marketplace/listing/' . $listingId);
        }

        $bidId = $this->bidModel->create([
            'listing_id'         => $listingId,
            'buyer_id'           => $buyerId,
            'seller_id'          => (int) $listing['seller_id'],
            'offered_unit_price' => $offeredPrice,
            'requested_qty'      => $requestedQty,
            'buyer_note'         => $buyerNote,
        ]);

        if ($this->isJsonRequest()) {
            $this->json(['status' => 'success', 'bid_id' => $bidId, 'message' => 'Offer sent to seller.']);
        }

        $_SESSION['flash_success'] = 'Your price offer has been submitted to the seller.';
        $this->redirect('/marketplace/listing/' . $listingId);
    }

    public function respond(string $id): void
    {
        $this->verifyCsrf();

        $bidId = (int) $id;
        $userId = (int) $_SESSION['user_id'];
        $action = $_POST['action'] ?? '';

        $bid = $this->bidModel->find($bidId);
        if (!$bid) {
            $this->respondError('Bid not found.', '/dashboard');
        }

        $isSeller = ((int) $bid['seller_id'] === $userId);
        $isBuyer  = ((int) $bid['buyer_id'] === $userId);

        if ($action === 'accept' && $isBuyer && ($_SESSION['user_role'] ?? '') === 'admin') {
            $this->respondError('Admin accounts cannot accept purchase offers.', '/dashboard');
        }

        if (!$isSeller && !$isBuyer) {
            http_response_code(403);
            exit('Unauthorized bid action.');
        }

        try {
            if ($action === 'counter' && $isSeller && $bid['status'] === 'pending') {
                $counterPrice = (float) ($_POST['counter_unit_price'] ?? 0);
                if ($counterPrice <= 0) {
                    $this->respondError('Enter a valid counter-offer price.', '/dashboard');
                }
                $this->bidModel->counterOffer($bidId, $counterPrice, $_POST['seller_note'] ?? null);
                $_SESSION['flash_success'] = 'Counter-offer sent to buyer.';
                $this->redirect('/dashboard');
            }

            if ($action === 'reject') {
                $this->bidModel->reject($bidId, $_POST['seller_note'] ?? null);
                $_SESSION['flash_success'] = 'Offer declined.';
                $this->redirect('/dashboard');
            }

            if ($action === 'accept') {
                // Seller accepts buyer's pending offer OR buyer accepts seller's counter-offer
                $canAccept = ($isSeller && $bid['status'] === 'pending') || ($isBuyer && $bid['status'] === 'countered');
                if (!$canAccept) {
                    $this->respondError('This offer cannot be accepted in its current state.', '/dashboard');
                }

                $deliveryCounty = trim($_POST['delivery_county'] ?? ($_SESSION['user_county'] ?? 'Nairobi'));
                $deliveryAddress = trim($_POST['delivery_address'] ?? 'Agreed FarmLink Pickup / Drop-off Point');

                $orderId = $this->bidModel->acceptAndCreateOrder($bidId, $deliveryCounty, $deliveryAddress);
                $_SESSION['flash_success'] = 'Offer accepted! Order generated and ready for M-Pesa Escrow payment.';
                $this->redirect('/orders/' . $orderId);
            }
        } catch (Throwable $e) {
            $this->respondError($e->getMessage(), '/dashboard');
        }

        $this->redirect('/dashboard');
    }

    public function threadJson(string $id): void
    {
        $listingId = (int) $id;
        $userId = (int) $_SESSION['user_id'];
        $bids = $this->bidModel->getThreadForListing($listingId, $userId);
        $this->json(['status' => 'success', 'bids' => $bids]);
    }

    private function respondError(string $message, string $redirectPath): void
    {
        if ($this->isJsonRequest()) {
            $this->json(['status' => 'error', 'message' => $message], 422);
        }
        $_SESSION['flash_error'] = $message;
        $this->redirect($redirectPath);
    }
}
