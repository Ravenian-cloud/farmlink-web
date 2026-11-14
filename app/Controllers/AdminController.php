<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Alert;

class AdminController extends Controller
{
    public function index(): void
    {
        $db = Database::getInstance();

        $pendingKyc = $db->query(
            "SELECT id, full_name, email, phone, role, county, kyc_status, kyc_document_path, created_at
             FROM users
             WHERE kyc_status = 'pending'
             ORDER BY updated_at ASC"
        )->fetchAll();

        $escrowStats = $db->query(
            "SELECT status, COUNT(*) AS total_orders, COALESCE(SUM(gross_amount), 0) AS gross_kes, COALESCE(SUM(commission_fee), 0) AS commission_kes
             FROM escrow_ledger
             GROUP BY status"
        )->fetchAll();

        $this->view('dashboards/admin', [
            'title'       => 'Admin Governance & KYC',
            'pendingKyc'  => $pendingKyc,
            'escrowStats' => $escrowStats,
        ]);
    }

    public function reviewKyc(string $userId): void
    {
        $this->verifyCsrf();

        $decision = ($_POST['decision'] ?? '') === 'verified' ? 'verified' : 'rejected';
        $db = Database::getInstance();

        $stmt = $db->prepare("UPDATE users SET kyc_status = :status WHERE id = :id");
        $stmt->execute([
            'status' => $decision,
            'id'     => (int) $userId,
        ]);

        $_SESSION['flash_success'] = "User KYC status updated to " . strtoupper($decision) . ".";
        $this->redirect('/admin');
    }

    public function publishAlert(): void
    {
        $this->verifyCsrf();

        (new Alert())->create([
            'county'     => $_POST['county'] ?? 'All',
            'alert_type' => $_POST['alert_type'] ?? 'weather',
            'severity'   => $_POST['severity'] ?? 'info',
            'title'      => $_POST['title'] ?? '',
            'message'    => $_POST['message'] ?? '',
            'expires_at' => !empty($_POST['expires_at']) ? $_POST['expires_at'] : null,
        ]);

        $_SESSION['flash_success'] = 'County advisory alert broadcast successfully.';
        $this->redirect('/admin');
    }
}
