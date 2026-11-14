<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;

class AuthController extends Controller
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    public function showLogin(): void
    {
        if (!empty($_SESSION['user_id'])) {
            $this->redirect('/dashboard');
        }
        $this->view('auth/login', ['title' => __('login')]);
    }

    public function login(): void
    {
        $this->verifyCsrf();

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $user = $this->userModel->findByEmail($email);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $_SESSION['flash_error'] = 'Invalid email address or password.';
            $this->redirect('/login');
        }

        session_regenerate_id(true);
        $_SESSION['user_id']    = (int) $user['id'];
        $_SESSION['user_name']  = $user['full_name'];
        $_SESSION['user_role']  = $user['role'];
        $_SESSION['user_county'] = $user['county'];
        $_SESSION['kyc_status'] = $user['kyc_status'];
        $_SESSION['lang']       = $user['preferred_lang'] ?? 'en';

        $_SESSION['flash_success'] = __('welcome_back') . ', ' . $user['full_name'] . '!';
        $this->redirect('/dashboard');
    }

    public function showRegister(): void
    {
        if (!empty($_SESSION['user_id'])) {
            $this->redirect('/dashboard');
        }
        $this->view('auth/register', ['title' => __('register')]);
    }

    public function register(): void
    {
        $this->verifyCsrf();

        $fullName  = trim($_POST['full_name'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $phone     = trim($_POST['phone'] ?? '');
        $password  = $_POST['password'] ?? '';
        $role      = $_POST['role'] ?? 'farmer';
        $county    = trim($_POST['county'] ?? '');
        $subCounty = trim($_POST['sub_county'] ?? '');
        $latitude  = trim($_POST['latitude'] ?? '');
        $longitude = trim($_POST['longitude'] ?? '');

        $allowedRoles = ['farmer', 'buyer', 'supplier'];
        if (!in_array($role, $allowedRoles, true)) {
            $role = 'farmer';
        }

        if ($fullName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8 || $county === '') {
            $_SESSION['flash_error'] = 'Please complete all required fields (password must be at least 8 characters).';
            $this->redirect('/register');
        }

        if ($this->userModel->findByEmail($email) || $this->userModel->findByPhone($phone)) {
            $_SESSION['flash_error'] = 'An account with that email or M-Pesa phone number already exists.';
            $this->redirect('/register');
        }

        $userId = $this->userModel->create([
            'full_name'      => $fullName,
            'email'          => $email,
            'phone'          => $phone,
            'password'       => $password,
            'role'           => $role,
            'county'         => $county,
            'sub_county'     => $subCounty,
            'latitude'       => $latitude,
            'longitude'      => $longitude,
            'preferred_lang' => $_SESSION['lang'] ?? 'en',
        ]);

        session_regenerate_id(true);
        $_SESSION['user_id']     = $userId;
        $_SESSION['user_name']   = $fullName;
        $_SESSION['user_role']   = $role;
        $_SESSION['user_county'] = $county;
        $_SESSION['kyc_status']  = 'unverified';

        $_SESSION['flash_success'] = 'Account created! Complete your KYC verification to unlock full trading trust.';
        $this->redirect('/kyc');
    }

    public function showKyc(): void
    {
        $user = $this->userModel->find((int) $_SESSION['user_id']);
        $this->view('auth/kyc', [
            'title' => __('kyc_verification'),
            'user'  => $user,
        ]);
    }

    public function uploadKyc(): void
    {
        $this->verifyCsrf();

        if (empty($_FILES['kyc_document']) || $_FILES['kyc_document']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['flash_error'] = 'Please select a valid ID or business certificate file.';
            $this->redirect('/kyc');
        }

        $file = $_FILES['kyc_document'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        if ($file['size'] > $maxSize) {
            $_SESSION['flash_error'] = 'File exceeds 5MB limit.';
            $this->redirect('/kyc');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        $allowedMimes = [
            'image/jpeg'      => 'jpg',
            'image/png'       => 'png',
            'application/pdf' => 'pdf',
        ];

        if (!isset($allowedMimes[$mime])) {
            $_SESSION['flash_error'] = 'Only JPG, PNG, or PDF documents are accepted.';
            $this->redirect('/kyc');
        }

        $kycDir = ROOT_PATH . '/public/uploads/kyc';
        if (!is_dir($kycDir)) {
            mkdir($kycDir, 0755, true);
        }

        $filename = 'kyc_' . $_SESSION['user_id'] . '_' . bin2hex(random_bytes(8)) . '.' . $allowedMimes[$mime];
        $destination = $kycDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            $_SESSION['flash_error'] = 'Failed to store uploaded document.';
            $this->redirect('/kyc');
        }

        $relativePath = 'uploads/kyc/' . $filename;
        $this->userModel->submitKycDocument((int) $_SESSION['user_id'], $relativePath);
        $_SESSION['kyc_status'] = 'pending';

        $_SESSION['flash_success'] = 'KYC document submitted for verification.';
        $this->redirect('/dashboard');
    }

    public function switchLanguage(string $code): void
    {
        $lang = in_array($code, ['en', 'sw'], true) ? $code : 'en';
        $_SESSION['lang'] = $lang;

        if (!empty($_SESSION['user_id'])) {
            $this->userModel->updateLanguage((int) $_SESSION['user_id'], $lang);
        }

        $referer = $_SERVER['HTTP_REFERER'] ?? base_url('/marketplace');
        $this->redirect($referer);
    }

    public function logout(): void
    {
        $this->verifyCsrf();
        $_SESSION = [];
        session_destroy();
        $this->redirect('/login');
    }
}
