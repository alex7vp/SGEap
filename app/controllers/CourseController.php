<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\CourseModel;

class CourseController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $this->redirect('/configuracion/academica?view=cursos');
    }

    public function store(): void
    {
        $this->requireAuth();

        $period = currentAcademicPeriod();

        if ($period === null) {
            sessionFlash('error', 'Debe seleccionar un periodo lectivo antes de registrar cursos.');
            $this->redirect('/configuracion/academica?view=cursos');
        }

        $data = [
            'pleid' => (int) $period['pleid'],
            'graid' => (int) ($_POST['graid'] ?? 0),
            'prlid' => (int) ($_POST['prlid'] ?? 0),
            'curestado' => ($_POST['curestado'] ?? '1') === '1',
        ];

        if ($data['graid'] <= 0 || $data['prlid'] <= 0) {
            $this->flashCourseFormData($data);
            sessionFlash('error', 'Debe seleccionar grado y paralelo.');
            $this->redirect('/configuracion/academica?view=cursos');
        }

        $courseModel = new CourseModel();

        if ($courseModel->existsCombination($data['pleid'], $data['graid'], $data['prlid'])) {
            $this->flashCourseFormData($data);
            sessionFlash('error', 'Ya existe un curso registrado para ese grado y paralelo en el periodo actual.');
            $this->redirect('/configuracion/academica?view=cursos');
        }

        $courseModel->create($data);
        sessionFlash('success', 'Curso registrado correctamente para el periodo actual.');
        $this->redirect('/configuracion/academica?view=cursos#cursos-registrados');
    }

    public function toggleStatus(): void
    {
        $this->requireAuth();

        $courseId = (int) ($_POST['curid'] ?? 0);
        $status = ($_POST['curestado'] ?? '0') === '1';
        $courseModel = new CourseModel();

        if ($courseId <= 0) {
            $this->flashCourseListFeedback('error', 'El curso seleccionado no es valido.');
            $this->redirect('/configuracion/academica?view=cursos#cursos-registrados');
        }

        if ($courseModel->findDetailed($courseId) === false) {
            $this->flashCourseListFeedback('error', 'El curso solicitado no existe.');
            $this->redirect('/configuracion/academica?view=cursos#cursos-registrados');
        }

        $courseModel->updateStatus($courseId, $status);
        $this->flashCourseListFeedback('success', 'Estado del curso actualizado correctamente.');
        $this->redirect('/configuracion/academica?view=cursos#cursos-registrados');
    }

    private function flashCourseFormData(array $data): void
    {
        sessionFlash('old_course_grade', (string) ($data['graid'] ?? ''));
        sessionFlash('old_course_parallel', (string) ($data['prlid'] ?? ''));
        sessionFlash('old_course_status', !empty($data['curestado']) ? '1' : '0');
    }

    private function flashCourseListFeedback(string $type, string $message): void
    {
        sessionFlash('course_list_feedback_type', $type);
        sessionFlash('course_list_feedback_message', $message);
    }

    private function courseListFeedback(): ?array
    {
        $type = sessionFlash('course_list_feedback_type');
        $message = sessionFlash('course_list_feedback_message');

        if ($type === null || $message === null) {
            return null;
        }

        return [
            'type' => $type,
            'message' => $message,
        ];
    }
}
