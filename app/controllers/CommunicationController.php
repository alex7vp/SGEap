<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\CommunicationModel;
use App\Services\MessageDeliveryService;
use RuntimeException;

class CommunicationController extends Controller
{
    public function index(): void
    {
        $user = $this->requireAuth();
        $model = new CommunicationModel();
        $page = max(1, (int) ($_GET['page'] ?? 1));

        $this->view('comunicados.index', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => 'Comunicados',
            'currentSection' => 'comunicados',
            'user' => $user,
            'pagination' => $model->page($page),
            'success' => sessionFlash('success'),
            'error' => sessionFlash('error'),
        ]);
    }

    public function create(): void
    {
        $user = $this->requireAuth();
        $model = new CommunicationModel();

        $this->view('comunicados.form', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => 'Nuevo comunicado',
            'currentSection' => 'comunicados',
            'user' => $user,
            'communication' => null,
            'criteria' => $this->oldCriteria(),
            'courses' => $model->activeCourses(),
            'matriculations' => $model->activeMatriculations(),
            'selectedRepresentatives' => [],
            'selectedStaff' => [],
            'roles' => $model->activeRoles(),
            'success' => sessionFlash('success'),
            'error' => sessionFlash('error'),
            'old' => $this->oldData(),
        ]);
    }

    public function edit(): void
    {
        $user = $this->requireAuth();
        $model = new CommunicationModel();
        $communicationId = (int) ($_GET['id'] ?? 0);
        $communication = $model->findDetailed($communicationId);

        if ($communication === false) {
            sessionFlash('error', 'El comunicado solicitado no existe.');
            $this->redirect('/comunicados');
        }

        if ((string) $communication['comestado'] !== 'BORRADOR') {
            sessionFlash('error', 'Solo se pueden editar comunicados en borrador.');
            $this->redirect('/comunicados');
        }

        $storedCriteria = json_decode((string) ($communication['comcriterios_json'] ?? '{}'), true);
        $oldCriteria = $this->oldCriteria();
        $viewCriteria = $oldCriteria !== [] ? $oldCriteria : (is_array($storedCriteria) ? $storedCriteria : []);

        $this->view('comunicados.form', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => 'Editar borrador',
            'currentSection' => 'comunicados',
            'user' => $user,
            'communication' => $communication,
            'criteria' => $viewCriteria,
            'courses' => $model->activeCourses(),
            'matriculations' => $model->activeMatriculations(),
            'selectedRepresentatives' => $model->selectedUsers((array) ($viewCriteria['representative_user_ids'] ?? [])),
            'selectedStaff' => $model->selectedUsers((array) ($viewCriteria['staff_user_ids'] ?? [])),
            'roles' => $model->activeRoles(),
            'success' => sessionFlash('success'),
            'error' => sessionFlash('error'),
            'old' => $this->oldData($communication),
        ]);
    }

    public function store(): void
    {
        $user = $this->requireAuth();
        $model = new CommunicationModel();
        $action = (string) ($_POST['action'] ?? 'draft');
        $communicationId = (int) ($_POST['comid'] ?? 0);
        $data = $this->formData();

        try {
            if ($communicationId > 0) {
                $model->updateDraft($communicationId, $data);

                if ($action === 'send') {
                    $model->publishDraft($communicationId, (int) ($user['usuid'] ?? 0));
                    $result = (new MessageDeliveryService())->processPendingEmailDeliveries();
                    sessionFlash('success', 'Comunicado enviado. ' . $this->deliveryMessage($result));
                    $this->redirect('/comunicados/ver?id=' . $communicationId);
                }

                sessionFlash('success', 'Borrador actualizado correctamente.');
                $this->redirect('/comunicados/editar?id=' . $communicationId);
            }

            if ($action === 'send') {
                $newId = $model->sendNew($data, (int) ($user['usuid'] ?? 0));
                $result = (new MessageDeliveryService())->processPendingEmailDeliveries();
                sessionFlash('success', 'Comunicado enviado. ' . $this->deliveryMessage($result));
                $this->redirect('/comunicados/ver?id=' . $newId);
            }

            $newId = $model->createDraft($data, (int) ($user['usuid'] ?? 0));
            sessionFlash('success', 'Borrador guardado correctamente.');
            $this->redirect('/comunicados/editar?id=' . $newId);
        } catch (RuntimeException $exception) {
            $this->flashFormData($data);
            sessionFlash('error', $exception->getMessage());
            $this->redirect($communicationId > 0 ? '/comunicados/editar?id=' . $communicationId : '/comunicados/nuevo');
        }
    }

    public function show(): void
    {
        $user = $this->requireAuth();
        $model = new CommunicationModel();
        $communicationId = (int) ($_GET['id'] ?? 0);
        $communication = $model->findDetailed($communicationId);

        if ($communication === false) {
            sessionFlash('error', 'El comunicado solicitado no existe.');
            $this->redirect('/comunicados');
        }

        $this->view('comunicados.show', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => 'Detalle de comunicado',
            'currentSection' => 'comunicados',
            'user' => $user,
            'communication' => $communication,
            'recipients' => $model->recipients($communicationId),
            'success' => sessionFlash('success'),
            'error' => sessionFlash('error'),
        ]);
    }

    public function annul(): void
    {
        $user = $this->requireAuth();
        $communicationId = (int) ($_POST['comid'] ?? 0);

        try {
            (new CommunicationModel())->annul(
                $communicationId,
                (int) ($user['usuid'] ?? 0),
                trim((string) ($_POST['motivo'] ?? ''))
            );
            sessionFlash('success', 'Comunicado anulado correctamente.');
        } catch (RuntimeException $exception) {
            sessionFlash('error', $exception->getMessage());
        }

        $this->redirect('/comunicados/ver?id=' . $communicationId);
    }

    public function delete(): void
    {
        $this->requireAuth();

        try {
            (new CommunicationModel())->deleteDraft((int) ($_POST['comid'] ?? 0));
            sessionFlash('success', 'Borrador eliminado correctamente.');
        } catch (RuntimeException $exception) {
            sessionFlash('error', $exception->getMessage());
        }

        $this->redirect('/comunicados');
    }

    public function mine(): void
    {
        $user = $this->requireAuth();
        $model = new CommunicationModel();

        $this->view('comunicados.mine', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => 'Mis comunicados',
            'currentSection' => 'mis_comunicados',
            'user' => $user,
            'communications' => $model->receivedByUser((int) ($user['usuid'] ?? 0)),
            'success' => sessionFlash('success'),
            'error' => sessionFlash('error'),
        ]);
    }

    public function read(): void
    {
        $user = $this->requireAuth();
        $communicationId = (int) ($_GET['id'] ?? 0);

        if ($communicationId > 0) {
            (new CommunicationModel())->markReadForUser((int) ($user['usuid'] ?? 0), [$communicationId]);
        }

        $this->redirect('/mis-comunicados');
    }

    public function searchStudents(): void
    {
        $this->requireAuth();
        $this->jsonResponse((new CommunicationModel())->searchStudents((string) ($_GET['q'] ?? '')));
    }

    public function searchRepresentatives(): void
    {
        $this->requireAuth();
        $this->jsonResponse((new CommunicationModel())->searchRepresentatives((string) ($_GET['q'] ?? '')));
    }

    public function searchStaff(): void
    {
        $this->requireAuth();
        $this->jsonResponse((new CommunicationModel())->searchStaff((string) ($_GET['q'] ?? '')));
    }

    private function formData(): array
    {
        return [
            'titulo' => trim((string) ($_POST['titulo'] ?? '')),
            'mensaje' => trim((string) ($_POST['mensaje'] ?? '')),
            'target_type' => trim((string) ($_POST['target_type'] ?? '')),
            'targets' => (array) ($_POST['targets'] ?? []),
            'course_ids' => (array) ($_POST['course_ids'] ?? []),
            'matriculation_ids' => (array) ($_POST['matriculation_ids'] ?? []),
            'representative_user_ids' => (array) ($_POST['representative_user_ids'] ?? []),
            'staff_user_ids' => (array) ($_POST['staff_user_ids'] ?? []),
            'role_ids' => (array) ($_POST['role_ids'] ?? []),
        ];
    }

    private function oldData(?array $communication = null): array
    {
        return [
            'titulo' => sessionFlash('old_titulo') ?? (string) ($communication['comtitulo'] ?? ''),
            'mensaje' => sessionFlash('old_mensaje') ?? (string) ($communication['commensaje'] ?? ''),
        ];
    }

    private function oldCriteria(): array
    {
        $json = sessionFlash('old_criteria');
        $criteria = is_string($json) ? json_decode($json, true) : null;

        return is_array($criteria) ? $criteria : [];
    }

    private function flashFormData(array $data): void
    {
        sessionFlash('old_titulo', (string) ($data['titulo'] ?? ''));
        sessionFlash('old_mensaje', (string) ($data['mensaje'] ?? ''));
        sessionFlash('old_criteria', json_encode([
            'targets' => (array) ($data['targets'] ?? []),
            'target_type' => (string) ($data['target_type'] ?? ''),
            'course_ids' => array_map('intval', (array) ($data['course_ids'] ?? [])),
            'matriculation_ids' => array_map('intval', (array) ($data['matriculation_ids'] ?? [])),
            'representative_user_ids' => array_map('intval', (array) ($data['representative_user_ids'] ?? [])),
            'staff_user_ids' => array_map('intval', (array) ($data['staff_user_ids'] ?? [])),
            'role_ids' => array_map('intval', (array) ($data['role_ids'] ?? [])),
        ], JSON_UNESCAPED_UNICODE) ?: '{}');
    }

    private function deliveryMessage(array $result): string
    {
        $message = trim((string) ($result['message'] ?? ''));

        return $message !== '' ? $message : 'Los correos se procesaran internamente.';
    }

    private function jsonResponse(array $payload): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['results' => $payload], JSON_UNESCAPED_UNICODE);
    }
}
