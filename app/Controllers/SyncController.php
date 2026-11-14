<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ForumPost;
use App\Models\Listing;
use Throwable;

class SyncController extends Controller
{
    public function push(): void
    {
        $this->verifyCsrf();

        $raw = file_get_contents('php://input');
        $body = json_decode($raw, true);
        $items = $body['items'] ?? [];

        if (!is_array($items)) {
            $this->json(['status' => 'error', 'message' => 'Invalid sync payload.'], 422);
        }

        $userId = (int) $_SESSION['user_id'];
        $role   = $_SESSION['user_role'] ?? 'farmer';
        $county = $_SESSION['user_county'] ?? 'Nakuru';

        $listingModel = new Listing();
        $forumModel   = new ForumPost();
        $syncedUuids  = [];

        foreach ($items as $item) {
            $uuid = trim((string) ($item['uuid'] ?? ''));
            $type = $item['action_type'] ?? '';
            $payload = $item['payload'] ?? [];

            if ($uuid === '' || !is_array($payload)) {
                continue;
            }

            try {
                if ($type === 'create_listing' && in_array($role, ['farmer', 'supplier'], true)) {
                    $listingType = ($role === 'supplier') ? 'input' : 'produce';
                    $listingModel->create([
                        'seller_id'              => $userId,
                        'category_id'            => (int) ($payload['category_id'] ?? 1),
                        'listing_type'           => $listingType,
                        'title'                  => $payload['title'] ?? 'Offline Listing',
                        'description'            => $payload['description'] ?? '',
                        'unit'                   => $payload['unit'] ?? 'kg',
                        'unit_price'             => (float) ($payload['unit_price'] ?? 0),
                        'quantity_available'     => (float) ($payload['quantity_available'] ?? 1),
                        'min_order_qty'          => (float) ($payload['min_order_qty'] ?? 1),
                        'is_negotiable'          => !empty($payload['is_negotiable']) ? 1 : 0,
                        'county'                 => $payload['county'] ?? $county,
                        'latitude'               => $payload['latitude'] ?? '',
                        'longitude'              => $payload['longitude'] ?? '',
                        'harvest_or_expiry_date' => $payload['harvest_or_expiry_date'] ?? null,
                        'offline_sync_uuid'      => $uuid,
                    ]);
                    $syncedUuids[] = $uuid;
                } elseif ($type === 'create_forum_post') {
                    $forumModel->createPost([
                        'author_id'         => $userId,
                        'post_type'         => ($payload['post_type'] ?? '') === 'knowledge_article' ? 'knowledge_article' : 'community_question',
                        'category_tag'      => $payload['category_tag'] ?? 'General Farming',
                        'title'             => $payload['title'] ?? 'Offline Discussion',
                        'body'              => $payload['body'] ?? '',
                        'offline_sync_uuid' => $uuid,
                    ]);
                    $syncedUuids[] = $uuid;
                }
            } catch (Throwable $e) {
                file_put_contents(ROOT_PATH . '/storage/logs/sync_errors.log', date('c') . ' ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
            }
        }

        $this->json([
            'status'       => 'success',
            'synced_uuids' => $syncedUuids,
        ]);
    }
}
