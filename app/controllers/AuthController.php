<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
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

        $userModel->updateLastAccess((int) $user['usuid']);
        $this->redirect('/dashboard');
    }

    public function dashboard(): void
    {
        if (empty($_SESSION['auth'])) {
            sessionFlash('error', 'Debe iniciar sesion para continuar.');
            $this->redirect('/login');
        }

        $this->view('auth.dashboard', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'user' => $_SESSION['auth'],
        ]);
    }

    public function logout(): void
    {
        unset($_SESSION['auth']);
        sessionFlash('success', 'Sesion cerrada correctamente.');
        $this->redirect('/login');
    }
}
