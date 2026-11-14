<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Alert;

class AlertController extends Controller
{
    public function index(): void
    {
        $county = $_SESSION['user_county'] ?? 'Nakuru';
        $alertModel = new Alert();

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_SESSION['user_role'] ?? '') === 'admin') {
            $this->verifyCsrf();
            $alertModel->create($_POST);
            $_SESSION['flash_success'] = 'Advisory alert published.';
            $this->redirect('/alerts');
        }

        $localAlerts = $alertModel->getActiveByCounty($county);
        $allAlerts   = $alertModel->getAllActive();

        $this->view('dashboards/alerts', [
            'title'       => __('alerts'),
            'county'      => $county,
            'localAlerts' => $localAlerts,
            'allAlerts'   => $allAlerts,
        ]);
    }
}
