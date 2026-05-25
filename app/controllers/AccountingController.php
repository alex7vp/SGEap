<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\AccountingModel;

class AccountingController extends Controller
{
    public function index(): void
    {
        $user = $this->requireAuth();
        $period = currentAcademicPeriod();
        $periodId = $period !== null ? (int) ($period['pleid'] ?? 0) : 0;
        $accountingModel = new AccountingModel();

        $this->view('contabilidad.index', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => 'Gestion Contable',
            'currentModule' => 'contabilidad',
            'currentSection' => 'contabilidad_dashboard',
            'user' => $user,
            'currentPeriod' => $period,
            'summary' => $accountingModel->dashboardSummary($periodId),
            'pendingReceipts' => $accountingModel->recentPendingReceipts($periodId),
            'success' => sessionFlash('success'),
            'error' => sessionFlash('error'),
        ]);
    }

}
