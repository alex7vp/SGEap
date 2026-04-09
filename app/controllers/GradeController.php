<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\GradeModel;

class GradeController extends Controller
{
    public function index(): void
    {
        $user = $this->requireAuth();
        $gradeModel = new GradeModel();

        $this->view('grados.index', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => 'Grados',
            'currentSection' => 'grados',
            'user' => $user,
            'grades' => $gradeModel->allOrdered(),
            'success' => null,
            'error' => null,
            'gradeListFeedback' => $this->gradeListFeedback(),
        ]);
    }

    public function create(): void
    {
        $user = $this->requireAuth();
        $gradeModel = new GradeModel();

        $this->view('grados.create', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => 'Nuevo grado',
            'currentSection' => 'grados',
            'user' => $user,
            'success' => null,
            'error' => null,
            'gradeFormFeedback' => $this->gradeFormFeedback(),
            'formAction' => baseUrl('grados'),
            'submitLabel' => 'Guardar grado',
            'levels' => $gradeModel->allLevels(),
            'old' => [
                'graid' => '',
                'nedid' => sessionFlash('old_nedid') ?? '',
                'granombre' => sessionFlash('old_granombre') ?? '',
            ],
        ]);
    }

    public function edit(): void
    {
        $user = $this->requireAuth();
        $gradeId = (int) ($_GET['id'] ?? 0);
        $gradeModel = new GradeModel();
        $grade = $gradeModel->findDetailed($gradeId);

        if ($grade === false) {
            sessionFlash('error', 'El grado solicitado no existe.');
            $this->redirect('/grados');
        }

        $this->view('grados.create', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => 'Editar grado',
            'currentSection' => 'grados',
            'user' => $user,
            'success' => null,
            'error' => null,
            'gradeFormFeedback' => $this->gradeFormFeedback(),
            'formAction' => baseUrl('grados/actualizar'),
            'submitLabel' => 'Actualizar grado',
            'levels' => $gradeModel->allLevels(),
            'old' => [
                'graid' => (string) $grade['graid'],
                'nedid' => sessionFlash('old_nedid') ?? (string) $grade['nedid'],
                'granombre' => sessionFlash('old_granombre') ?? (string) $grade['granombre'],
            ],
        ]);
    }

    public function store(): void
    {
        $this->requireAuth();
        $data = $this->gradeFormData();

        if ($data['nedid'] <= 0 || $data['granombre'] === '') {
            $this->flashGradeFormData($data);
            $this->flashGradeFormFeedback('error', 'Nivel educativo y nombre del grado son obligatorios.');
            $this->redirect('/grados/crear#grado-form');
        }

        $gradeModel = new GradeModel();

        if ($gradeModel->existsCombination($data['nedid'], $data['granombre'])) {
            $this->flashGradeFormData($data);
            $this->flashGradeFormFeedback('error', 'Ya existe un grado registrado con ese nombre en el nivel seleccionado.');
            $this->redirect('/grados/crear#grado-form');
        }

        $gradeModel->create($data);
        $this->flashGradeListFeedback('success', 'Grado registrado correctamente.');
        $this->redirect('/grados#grados-registrados');
    }

    public function update(): void
    {
        $this->requireAuth();
        $gradeId = (int) ($_POST['graid'] ?? 0);
        $data = $this->gradeFormData();

        if ($gradeId <= 0) {
            $this->flashGradeListFeedback('error', 'El grado a actualizar no es valido.');
            $this->redirect('/grados#grados-registrados');
        }

        if ($data['nedid'] <= 0 || $data['granombre'] === '') {
            $this->flashGradeFormData($data);
            $this->flashGradeFormFeedback('error', 'Nivel educativo y nombre del grado son obligatorios.');
            $this->redirect('/grados/editar?id=' . $gradeId . '#grado-form');
        }

        $gradeModel = new GradeModel();

        if ($gradeModel->findDetailed($gradeId) === false) {
            $this->flashGradeListFeedback('error', 'El grado solicitado no existe.');
            $this->redirect('/grados#grados-registrados');
        }

        if ($gradeModel->existsCombination($data['nedid'], $data['granombre'], $gradeId)) {
            $this->flashGradeFormData($data);
            $this->flashGradeFormFeedback('error', 'Ya existe otro grado con ese nombre en el nivel seleccionado.');
            $this->redirect('/grados/editar?id=' . $gradeId . '#grado-form');
        }

        $gradeModel->update($gradeId, $data);
        $this->flashGradeListFeedback('success', 'Grado actualizado correctamente.');
        $this->redirect('/grados#grados-registrados');
    }

    public function destroy(): void
    {
        $this->requireAuth();
        $gradeId = (int) ($_POST['graid'] ?? 0);

        if ($gradeId <= 0) {
            $this->flashGradeListFeedback('error', 'El grado a eliminar no es valido.');
            $this->redirect('/grados#grados-registrados');
        }

        $gradeModel = new GradeModel();

        if ($gradeModel->findDetailed($gradeId) === false) {
            $this->flashGradeListFeedback('error', 'El grado solicitado no existe.');
            $this->redirect('/grados#grados-registrados');
        }

        if (!$gradeModel->deleteById($gradeId)) {
            $this->flashGradeListFeedback('error', 'No se pudo eliminar el grado. Revise si ya esta siendo usado en cursos u otros registros academicos.');
            $this->redirect('/grados#grados-registrados');
        }

        $this->flashGradeListFeedback('success', 'Grado eliminado correctamente.');
        $this->redirect('/grados#grados-registrados');
    }

    public function search(): void
    {
        $this->requireAuth();

        $term = trim($_GET['q'] ?? '');
        $gradeModel = new GradeModel();
        $grades = $gradeModel->search($term);

        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'html' => $this->renderRows($grades),
            'isEmpty' => empty($grades),
            'emptyHtml' => '<div class="empty-state">No se encontraron grados con ese filtro.</div>',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function gradeFormData(): array
    {
        return [
            'nedid' => (int) ($_POST['nedid'] ?? 0),
            'granombre' => trim($_POST['granombre'] ?? ''),
        ];
    }

    private function flashGradeFormData(array $data): void
    {
        sessionFlash('old_nedid', (string) ($data['nedid'] ?? ''));
        sessionFlash('old_granombre', (string) ($data['granombre'] ?? ''));
    }

    private function renderRows(array $grades): string
    {
        ob_start();
        require BASE_PATH . '/app/views/grados/_rows.php';
        return (string) ob_get_clean();
    }

    private function flashGradeFormFeedback(string $type, string $message): void
    {
        sessionFlash('grade_form_feedback_type', $type);
        sessionFlash('grade_form_feedback_message', $message);
    }

    private function gradeFormFeedback(): ?array
    {
        $type = sessionFlash('grade_form_feedback_type');
        $message = sessionFlash('grade_form_feedback_message');

        if ($type === null || $message === null) {
            return null;
        }

        return [
            'type' => $type,
            'message' => $message,
        ];
    }

    private function flashGradeListFeedback(string $type, string $message): void
    {
        sessionFlash('grade_list_feedback_type', $type);
        sessionFlash('grade_list_feedback_message', $message);
    }

    private function gradeListFeedback(): ?array
    {
        $type = sessionFlash('grade_list_feedback_type');
        $message = sessionFlash('grade_list_feedback_message');

        if ($type === null || $message === null) {
            return null;
        }

        return [
            'type' => $type,
            'message' => $message,
        ];
    }
}
