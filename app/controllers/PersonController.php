<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\PersonModel;

class PersonController extends Controller
{
    public function index(): void
    {
        $user = $this->requireAuth();
        $personModel = new PersonModel();

        $this->view('personas.index', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => 'Personas',
            'currentSection' => 'personas',
            'user' => $user,
            'persons' => $personModel->allOrdered(),
            'success' => sessionFlash('success'),
            'error' => sessionFlash('error'),
        ]);
    }

    public function create(): void
    {
        $user = $this->requireAuth();
        $personModel = new PersonModel();

        $this->view('personas.create', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => 'Nueva persona',
            'currentSection' => 'personas',
            'user' => $user,
            'instructionLevels' => $personModel->allInstructionLevels(),
            'civilStatuses' => $personModel->allCivilStatuses(),
            'error' => sessionFlash('error'),
            'formAction' => baseUrl('personas'),
            'submitLabel' => 'Guardar persona',
            'old' => [
                'percedula' => sessionFlash('old_percedula') ?? '',
                'pernombres' => sessionFlash('old_pernombres') ?? '',
                'perapellidos' => sessionFlash('old_perapellidos') ?? '',
                'pertelefono1' => sessionFlash('old_pertelefono1') ?? '',
                'pertelefono2' => sessionFlash('old_pertelefono2') ?? '',
                'percorreo' => sessionFlash('old_percorreo') ?? '',
                'persexo' => sessionFlash('old_persexo') ?? '',
                'perfechanacimiento' => sessionFlash('old_perfechanacimiento') ?? '',
                'eciid' => sessionFlash('old_eciid') ?? '',
                'istid' => sessionFlash('old_istid') ?? '',
                'perprofesion' => sessionFlash('old_perprofesion') ?? '',
                'perocupacion' => sessionFlash('old_perocupacion') ?? '',
                'perlugardetrabajo' => sessionFlash('old_perlugardetrabajo') ?? '',
                'perhablaingles' => sessionFlash('old_perhablaingles') ?? '0',
            ],
        ]);
    }

    public function edit(): void
    {
        $user = $this->requireAuth();
        $personId = (int) ($_GET['id'] ?? 0);
        $personModel = new PersonModel();
        $person = $personModel->find($personId);

        if ($person === false) {
            sessionFlash('error', 'La persona solicitada no existe.');
            $this->redirect('/personas');
        }

        $this->view('personas.create', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => 'Editar persona',
            'currentSection' => 'personas',
            'user' => $user,
            'instructionLevels' => $personModel->allInstructionLevels(),
            'civilStatuses' => $personModel->allCivilStatuses(),
            'error' => sessionFlash('error'),
            'formAction' => baseUrl('personas/actualizar'),
            'submitLabel' => 'Actualizar persona',
            'old' => [
                'perid' => (string) $person['perid'],
                'percedula' => sessionFlash('old_percedula') ?? (string) $person['percedula'],
                'pernombres' => sessionFlash('old_pernombres') ?? (string) $person['pernombres'],
                'perapellidos' => sessionFlash('old_perapellidos') ?? (string) $person['perapellidos'],
                'pertelefono1' => sessionFlash('old_pertelefono1') ?? (string) ($person['pertelefono1'] ?? ''),
                'pertelefono2' => sessionFlash('old_pertelefono2') ?? (string) ($person['pertelefono2'] ?? ''),
                'percorreo' => sessionFlash('old_percorreo') ?? (string) ($person['percorreo'] ?? ''),
                'persexo' => sessionFlash('old_persexo') ?? (string) ($person['persexo'] ?? ''),
                'perfechanacimiento' => sessionFlash('old_perfechanacimiento') ?? (string) ($person['perfechanacimiento'] ?? ''),
                'eciid' => sessionFlash('old_eciid') ?? (string) ($person['eciid'] ?? ''),
                'istid' => sessionFlash('old_istid') ?? (string) ($person['istid'] ?? ''),
                'perprofesion' => sessionFlash('old_perprofesion') ?? (string) ($person['perprofesion'] ?? ''),
                'perocupacion' => sessionFlash('old_perocupacion') ?? (string) ($person['perocupacion'] ?? ''),
                'perlugardetrabajo' => sessionFlash('old_perlugardetrabajo') ?? (string) ($person['perlugardetrabajo'] ?? ''),
                'perhablaingles' => sessionFlash('old_perhablaingles') ?? (!empty($person['perhablaingles']) ? '1' : '0'),
            ],
        ]);
    }

    public function store(): void
    {
        $this->requireAuth();

        $data = $this->personFormData();

        if (
            $data['percedula'] === ''
            || $data['pernombres'] === ''
            || $data['perapellidos'] === ''
        ) {
            $this->flashPersonFormData($data);
            sessionFlash('error', 'Cedula, nombres y apellidos son obligatorios.');
            $this->redirect('/personas/crear');
        }

        $personModel = new PersonModel();

        if ($personModel->existsByCedula($data['percedula'])) {
            $this->flashPersonFormData($data);
            sessionFlash('error', 'La cedula ya esta registrada.');
            $this->redirect('/personas/crear');
        }

        $personModel->create($data);
        sessionFlash('success', 'Persona registrada correctamente.');
        $this->redirect('/personas');
    }

    public function update(): void
    {
        $this->requireAuth();

        $personId = (int) ($_POST['perid'] ?? 0);
        $data = $this->personFormData();

        if ($personId <= 0) {
            sessionFlash('error', 'La persona a actualizar no es valida.');
            $this->redirect('/personas');
        }

        if (
            $data['percedula'] === ''
            || $data['pernombres'] === ''
            || $data['perapellidos'] === ''
        ) {
            $this->flashPersonFormData($data);
            sessionFlash('error', 'Cedula, nombres y apellidos son obligatorios.');
            $this->redirect('/personas/editar?id=' . $personId);
        }

        $personModel = new PersonModel();

        if ($personModel->find($personId) === false) {
            sessionFlash('error', 'La persona solicitada no existe.');
            $this->redirect('/personas');
        }

        if ($personModel->existsByCedulaExceptId($data['percedula'], $personId)) {
            $this->flashPersonFormData($data);
            sessionFlash('error', 'La cedula ya esta registrada en otra persona.');
            $this->redirect('/personas/editar?id=' . $personId);
        }

        $personModel->update($personId, $data);
        sessionFlash('success', 'Persona actualizada correctamente.');
        $this->redirect('/personas');
    }

    public function destroy(): void
    {
        $this->requireAuth();

        $personId = (int) ($_POST['perid'] ?? 0);

        if ($personId <= 0) {
            sessionFlash('error', 'La persona a eliminar no es valida.');
            $this->redirect('/personas');
        }

        $personModel = new PersonModel();

        if ($personModel->find($personId) === false) {
            sessionFlash('error', 'La persona solicitada no existe.');
            $this->redirect('/personas');
        }

        if (!$personModel->deleteById($personId)) {
            sessionFlash('error', 'No se pudo eliminar la persona. Revise si esta asociada a estudiantes, usuarios u otros registros.');
            $this->redirect('/personas');
        }

        sessionFlash('success', 'Persona eliminada correctamente.');
        $this->redirect('/personas');
    }

    public function search(): void
    {
        $this->requireAuth();

        $term = trim($_GET['q'] ?? '');
        $personModel = new PersonModel();
        $persons = $personModel->search($term);

        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'html' => $this->renderRows($persons),
            'isEmpty' => empty($persons),
            'emptyHtml' => '<div class="empty-state">No se encontraron personas con ese filtro.</div>',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function personFormData(): array
    {
        return [
            'percedula' => trim($_POST['percedula'] ?? ''),
            'pernombres' => trim($_POST['pernombres'] ?? ''),
            'perapellidos' => trim($_POST['perapellidos'] ?? ''),
            'pertelefono1' => trim($_POST['pertelefono1'] ?? ''),
            'pertelefono2' => trim($_POST['pertelefono2'] ?? ''),
            'percorreo' => trim($_POST['percorreo'] ?? ''),
            'persexo' => trim($_POST['persexo'] ?? ''),
            'perfechanacimiento' => trim($_POST['perfechanacimiento'] ?? ''),
            'eciid' => (int) ($_POST['eciid'] ?? 0),
            'istid' => (int) ($_POST['istid'] ?? 0),
            'perprofesion' => trim($_POST['perprofesion'] ?? ''),
            'perocupacion' => trim($_POST['perocupacion'] ?? ''),
            'perlugardetrabajo' => trim($_POST['perlugardetrabajo'] ?? ''),
            'perhablaingles' => isset($_POST['perhablaingles']),
        ];
    }

    private function flashPersonFormData(array $data): void
    {
        foreach ($data as $key => $value) {
            sessionFlash('old_' . $key, is_bool($value) ? ($value ? '1' : '0') : (string) $value);
        }
    }

    private function renderRows(array $persons): string
    {
        ob_start();
        require BASE_PATH . '/app/views/personas/_rows.php';
        return (string) ob_get_clean();
    }
}
