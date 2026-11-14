<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Listing;
use App\Models\User;

class MarketplaceController extends Controller
{
    private Listing $listingModel;

    public function __construct()
    {
        $this->listingModel = new Listing();
    }

    public function index(): void
    {
        $filters = [
            'type'        => $_GET['type'] ?? '',
            'category_id' => $_GET['category_id'] ?? '',
            'county'      => $_GET['county'] ?? '',
            'q'           => $_GET['q'] ?? '',
            'lat'         => $_GET['lat'] ?? '',
            'lng'         => $_GET['lng'] ?? '',
            'radius_km'   => $_GET['radius_km'] ?? '',
        ];

        $listings = $this->listingModel->search($filters);
        $categories = $this->listingModel->getCategories($filters['type'] !== '' ? $filters['type'] : null);

        $this->view('marketplace/index', [
            'title'      => __('marketplace'),
            'listings'   => $listings,
            'categories' => $categories,
            'filters'    => $filters,
        ]);
    }

    public function show(string $id): void
    {
        $listing = $this->listingModel->findWithSeller((int) $id);
        if (!$listing) {
            http_response_code(404);
            exit('Listing not found.');
        }

        $this->view('marketplace/show', [
            'title'   => $listing['title'],
            'listing' => $listing,
        ]);
    }

    public function create(): void
    {
        $role = $_SESSION['user_role'] ?? 'farmer';
        $listingType = ($role === 'supplier') ? 'input' : 'produce';
        $categories = $this->listingModel->getCategories($listingType);

        $userModel = new User();
        $user = $userModel->find((int) $_SESSION['user_id']);

        $this->view('marketplace/create', [
            'title'       => __('sell_produce'),
            'listingType' => $listingType,
            'categories'  => $categories,
            'user'        => $user,
        ]);
    }

    public function store(): void
    {
        $this->verifyCsrf();

        $role = $_SESSION['user_role'] ?? 'farmer';
        $listingType = ($role === 'supplier') ? 'input' : 'produce';

        $title       = trim($_POST['title'] ?? '');
        $categoryId  = (int) ($_POST['category_id'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $unit        = trim($_POST['unit'] ?? '');
        $unitPrice   = (float) ($_POST['unit_price'] ?? 0);
        $quantity    = (float) ($_POST['quantity_available'] ?? 0);
        $minOrder    = (float) ($_POST['min_order_qty'] ?? 1);
        $county      = trim($_POST['county'] ?? ($_SESSION['user_county'] ?? ''));

        if ($title === '' || $categoryId <= 0 || $unit === '' || $unitPrice <= 0 || $quantity <= 0) {
            $_SESSION['flash_error'] = 'Please fill in all required listing fields with valid numbers.';
            $this->redirect('/listings/create');
        }

        $imagePath = null;
        if (!empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['image'];
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']);
            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

            if (isset($allowed[$mime]) && $file['size'] <= 5 * 1024 * 1024) {
                $dir = ROOT_PATH . '/public/uploads/listings';
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                $filename = 'item_' . $_SESSION['user_id'] . '_' . bin2hex(random_bytes(6)) . '.' . $allowed[$mime];
                if (move_uploaded_file($file['tmp_name'], $dir . '/' . $filename)) {
                    $imagePath = 'uploads/listings/' . $filename;
                }
            }
        }

        $listingId = $this->listingModel->create([
            'seller_id'              => (int) $_SESSION['user_id'],
            'category_id'            => $categoryId,
            'listing_type'           => $listingType,
            'title'                  => $title,
            'description'            => $description,
            'unit'                   => $unit,
            'unit_price'             => $unitPrice,
            'quantity_available'     => $quantity,
            'min_order_qty'          => max(1, $minOrder),
            'is_negotiable'          => isset($_POST['is_negotiable']) ? 1 : 0,
            'county'                 => $county,
            'latitude'               => $_POST['latitude'] ?? '',
            'longitude'              => $_POST['longitude'] ?? '',
            'harvest_or_expiry_date' => $_POST['harvest_or_expiry_date'] ?? null,
            'image_path'             => $imagePath,
        ]);

        $_SESSION['flash_success'] = 'Your listing is now live on the FarmLink marketplace!';
        $this->redirect('/marketplace/listing/' . $listingId);
    }
}
