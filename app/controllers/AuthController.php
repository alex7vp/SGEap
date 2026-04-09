<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\PeriodModel;
use App\Models\UserModel;

class AuthController extends Controller
{
    public function index(): void
    {
        if (!empty($_SESSION['auth'])) {
            $this->redirect('/dashboard');
        }

        $this->view('auth.login', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'error' => sessionFlash('error'),
            'success' => sessionFlash('success'),
            'oldUsername' => sessionFlash('old_username'),
        ]);
    }

    public function authenticate(): void
    {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            sessionFlash('error', 'Usuario y contrasena son obligatorios.');
            sessionFlash('old_username', $username);
            $this->redirect('/login');
        }

        $userModel = new UserModel();
        $user = $userModel->findActiveByUsername($username);

        if ($user === false || $password !== (string) $user['usuclave']) {
            sessionFlash('error', 'Credenciales invalidas.');
            sessionFlash('old_username', $username);
            $this->redirect('/login');
        }

        $_SESSION['auth'] = [
            'usuid' => (int) $user['usuid'],
            'perid' => (int) $user['perid'],
            'username' => (string) $user['usunombre'],
        ];

        $periodModel = new PeriodModel();
        $activePeriod = $periodModel->active();
        setCurrentAcademicPeriod($activePeriod !== false ? $activePeriod : null);

        $userModel->updateLastAccess((int) $user['usuid']);
        $this->redirect('/dashboard');
    }

    public function dashboard(): void
    {
        $user = $this->requireAuth();

        $this->view('auth.dashboard', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => 'Dashboard',
            'currentSection' => 'dashboard',
            'user' => $user,
            'success' => sessionFlash('success'),
            'error' => sessionFlash('error'),
        ]);
    }

    public function logout(): void
    {
        unset($_SESSION['auth']);
        setCurrentAcademicPeriod(null);
        sessionFlash('success', 'Sesion cerrada correctamente.');
        $this->redirect('/login');
    }
}
