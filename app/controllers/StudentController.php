<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\PersonModel;
use App\Models\StudentModel;

class StudentController extends Controller
{
    public function index(): void
    {
        $user = $this->requireAuth();
        $studentModel = new StudentModel();

        $this->view('estudiantes.index', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => 'Estudiantes',
            'currentSection' => 'estudiantes',
            'user' => $user,
            'students' => $studentModel->allWithPerson(),
            'success' => sessionFlash('success'),
            'error' => sessionFlash('error'),
        ]);
    }

    public function create(): void
    {
        $user = $this->requireAuth();
        $personModel = new PersonModel();

        $this->view('estudiantes.create', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => 'Nuevo estudiante',
            'currentSection' => 'estudiantes',
            'user' => $user,
            'persons' => $personModel->allWithoutStudent(),
            'error' => sessionFlash('error'),
            'old' => [
                'perid' => sessionFlash('old_perid') ?? '',
                'estlugarnacimiento' => sessionFlash('old_estlugarnacimiento') ?? '',
                'estdireccion' => sessionFlash('old_estdireccion') ?? '',
                'estparroquia' => sessionFlash('old_estparroquia') ?? '',
                'estestado' => sessionFlash('old_estestado') ?? '1',
            ],
        ]);
    }

    public function store(): void
    {
        $this->requireAuth();

        $data = $this->studentFormData();

        if ($data['perid'] <= 0) {
            $this->flashStudentFormData($data);
            sessionFlash('error', 'Debe seleccionar una persona.');
            $this->redirect('/estudiantes/crear');
        }

        $personModel = new PersonModel();
        $studentModel = new StudentModel();
        $person = $personModel->find($data['perid']);

        if ($person === false) {
            $this->flashStudentFormData($data);
            sessionFlash('error', 'La persona seleccionada no existe.');
            $this->redirect('/estudiantes/crear');
        }

        if ($studentModel->existsByPersonId($data['perid'])) {
            $this->flashStudentFormData($data);
            sessionFlash('error', 'La persona seleccionada ya tiene un registro como estudiante.');
            $this->redirect('/estudiantes/crear');
        }

        $studentModel->create($data);
        sessionFlash('success', 'Estudiante registrado correctamente.');
        $this->redirect('/estudiantes');
    }

    private function studentFormData(): array
    {
        return [
            'perid' => (int) ($_POST['perid'] ?? 0),
            'estlugarnacimiento' => trim($_POST['estlugarnacimiento'] ?? ''),
            'estdireccion' => trim($_POST['estdireccion'] ?? ''),
            'estparroquia' => trim($_POST['estparroquia'] ?? ''),
            'estestado' => ($_POST['estestado'] ?? '1') === '1',
        ];
    }

    private function flashStudentFormData(array $data): void
    {
        sessionFlash('old_perid', (string) $data['perid']);
        sessionFlash('old_estlugarnacimiento', (string) $data['estlugarnacimiento']);
        sessionFlash('old_estdireccion', (string) $data['estdireccion']);
        sessionFlash('old_estparroquia', (string) $data['estparroquia']);
        sessionFlash('old_estestado', $data['estestado'] ? '1' : '0');
    }
}
