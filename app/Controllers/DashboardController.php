<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Alert;
use App\Models\Bid;
use App\Models\Listing;
use App\Models\Order;
use App\Models\User;
use App\Services\AnalyticsService;

class DashboardController extends Controller
{
    public function index(): void
    {
        $userId = (int) $_SESSION['user_id'];
        $role   = $_SESSION['user_role'] ?? 'farmer';
        $county = $_SESSION['user_county'] ?? 'Nakuru';

        $user     = (new User())->find($userId);
        $listings = in_array($role, ['farmer', 'supplier'], true)
            ? (new Listing())->getBySeller($userId)
            : [];
        $bids     = (new Bid())->getForUser($userId);
        $orders   = (new Order())->getForUser($userId);
        $alerts   = (new Alert())->getActiveByCounty($county);

        $analytics = new AnalyticsService();
        $trends    = $analytics->getPriceTrendsAndForecast($county);
        $demand    = $analytics->getDemandInsights();
        $inputs    = $analytics->getRecommendedInputs($county);

        $this->view('dashboards/index', [
            'title'     => __('dashboard'),
            'user'      => $user,
            'listings'  => $listings,
            'bids'      => $bids,
            'orders'    => $orders,
            'alerts'    => $alerts,
            'trends'    => $trends,
            'demand'    => $demand,
            'inputs'    => $inputs,
        ]);
    }

    public function priceTrendsJson(): void
    {
        $county = trim($_GET['county'] ?? ($_SESSION['user_county'] ?? 'Nakuru'));
        $trends = (new AnalyticsService())->getPriceTrendsAndForecast($county);
        $this->json(['status' => 'success', 'data' => $trends]);
    }
}
