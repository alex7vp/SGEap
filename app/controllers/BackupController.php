<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\BackupService;

class BackupController extends Controller
{
    public function index(): void
    {
        $user = $this->requireAuth();
        $backups = [];
        $error = sessionFlash('error');

        try {
            $backups = (new BackupService())->all();
        } catch (\Throwable $exception) {
            $error = $exception->getMessage();
        }

        $this->view('configuracion.backups', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => 'Backups',
            'currentModule' => 'configuracion',
            'currentSection' => 'backups',
            'user' => $user,
            'backups' => $backups,
            'success' => sessionFlash('success'),
            'error' => $error,
        ]);
    }

    public function create(): void
    {
        $user = $this->requireAuth();

        try {
            $backup = (new BackupService())->create((int) ($user['usuid'] ?? 0));
            sessionFlash('success', 'Backup generado correctamente: ' . $backup['name']);
        } catch (\Throwable $exception) {
            sessionFlash('error', $exception->getMessage());
        }

        $this->redirect('/configuracion/backups');
    }

    public function download(): void
    {
        $this->requireAuth();
        $fileName = (string) ($_GET['file'] ?? '');

        try {
            $path = (new BackupService())->resolve($fileName);
        } catch (\Throwable $exception) {
            sessionFlash('error', $exception->getMessage());
            $this->redirect('/configuracion/backups');
        }

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . basename($path) . '"');
        header('Content-Length: ' . (string) filesize($path));
        header('X-Content-Type-Options: nosniff');
        readfile($path);
        exit;
    }

    public function delete(): void
    {
        $this->requireAuth();
        $fileName = (string) ($_POST['file'] ?? '');

        try {
            (new BackupService())->delete($fileName);
            sessionFlash('success', 'Backup eliminado correctamente.');
        } catch (\Throwable $exception) {
            sessionFlash('error', $exception->getMessage());
        }

        $this->redirect('/configuracion/backups');
    }
}
