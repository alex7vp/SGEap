<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\GradebookModel;

class GradebookController extends Controller
{
    public function register(): void
    {
        $user = $this->requireAuth();
        $period = currentAcademicPeriod();
        $periodId = $period !== null ? (int) $period['pleid'] : 0;
        $model = new GradebookModel();
        $subjects = $periodId > 0
            ? $model->teacherSubjects((int) ($user['perid'] ?? 0), $periodId)
            : [];
        $selectedSubjectId = (int) ($_GET['mtcid'] ?? 0);
        $selectedSubperiodId = (int) ($_GET['spcid'] ?? 0);
        $selectedComponentId = (int) ($_GET['cpcid'] ?? 0);
        $showFinalAverages = (string) ($_GET['final'] ?? '') === '1';
        $selectedSubject = $selectedSubjectId > 0 && $periodId > 0
            ? $model->selectedTeacherSubject((int) ($user['perid'] ?? 0), $periodId, $selectedSubjectId)
            : false;
        $subperiods = [];
        $components = [];
        $students = [];
        $activities = [];
        $grades = [];

        if ($selectedSubject !== false && !empty($selectedSubject['pcaid'])) {
            $profileId = (int) $selectedSubject['pcaid'];
            $subperiods = $model->subperiods($profileId);
            $components = $model->components($profileId);
            $students = $model->studentsByCourse((int) $selectedSubject['curid']);

            if ($selectedSubperiodId > 0) {
                $activities = $model->activitiesBySubperiod((int) $selectedSubject['mtcid'], $selectedSubperiodId);
            } elseif ($showFinalAverages) {
                $activities = $model->activitiesByProfile((int) $selectedSubject['mtcid'], $profileId);
            }

            if ($selectedSubperiodId > 0 || $showFinalAverages) {
                $activityIds = [];

                foreach ($activities as $componentActivities) {
                    foreach ($componentActivities as $activityOrActivities) {
                        if (isset($activityOrActivities['aciid'])) {
                            $activityIds[] = (int) $activityOrActivities['aciid'];
                            continue;
                        }

                        foreach ((array) $activityOrActivities as $activity) {
                            $activityIds[] = (int) $activity['aciid'];
                        }
                    }
                }

                $grades = $model->gradesByActivities($activityIds);
            }
        }

        $this->view('calificaciones.registro', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => 'Registro de calificaciones',
            'currentModule' => 'academico',
            'currentSection' => 'calificaciones_registro',
            'user' => $user,
            'currentPeriod' => $period,
            'subjects' => $subjects,
            'selectedSubjectId' => $selectedSubjectId,
            'selectedSubperiodId' => $selectedSubperiodId,
            'selectedComponentId' => $selectedComponentId,
            'showFinalAverages' => $showFinalAverages,
            'selectedSubject' => $selectedSubject,
            'subperiods' => $subperiods,
            'components' => $components,
            'activities' => $activities,
            'grades' => $grades,
            'students' => $students,
            'today' => date('Y-m-d'),
            'success' => sessionFlash('success'),
            'error' => sessionFlash('error'),
        ]);
    }

    public function storeActivity(): void
    {
        $user = $this->requireAuth();
        $period = currentAcademicPeriod();
        $periodId = $period !== null ? (int) $period['pleid'] : 0;
        $model = new GradebookModel();
        $courseSubjectId = (int) ($_POST['mtcid'] ?? 0);
        $subperiodId = (int) ($_POST['spcid'] ?? 0);
        $componentId = (int) ($_POST['cpcid'] ?? 0);
        $name = trim((string) ($_POST['acinombre'] ?? ''));
        $redirect = '/calificaciones/registro';

        if ($courseSubjectId > 0) {
            $redirect .= '?mtcid=' . $courseSubjectId;

            if ($subperiodId > 0) {
                $redirect .= '&spcid=' . $subperiodId;
            }
        }

        if ($periodId <= 0 || $courseSubjectId <= 0 || $subperiodId <= 0 || $componentId <= 0 || $name === '') {
            sessionFlash('error', 'Complete los datos de la actividad.');
            $this->redirect($redirect);
        }

        $selectedSubject = $model->selectedTeacherSubject((int) ($user['perid'] ?? 0), $periodId, $courseSubjectId);

        if ($selectedSubject === false || empty($selectedSubject['pcaid'])) {
            sessionFlash('error', 'La materia seleccionada no esta disponible para registrar calificaciones.');
            $this->redirect('/calificaciones/registro');
        }

        $subperiod = $model->subperiodByProfile((int) $selectedSubject['pcaid'], $subperiodId);

        if (
            $subperiod === false
            || date('Y-m-d') < (string) $subperiod['spcfecha_inicio']
            || date('Y-m-d') > (string) $subperiod['spcfecha_fin']
        ) {
            sessionFlash('error', 'El subperiodo esta fuera del rango permitido para registrar actividades.');
            $this->redirect($redirect);
        }

        $component = $model->componentInSubperiod((int) $selectedSubject['pcaid'], $subperiodId, $componentId);

        if ($component === false) {
            sessionFlash('error', 'El componente seleccionado no pertenece al perfil activo.');
            $this->redirect($redirect);
        }

        $model->createActivity($courseSubjectId, $componentId, $name, date('Y-m-d'), (int) $user['usuid']);
        sessionFlash('success', 'Actividad agregada correctamente.');
        $this->redirect($redirect);
    }

    public function saveGrades(): void
    {
        $user = $this->requireAuth();
        $period = currentAcademicPeriod();
        $periodId = $period !== null ? (int) $period['pleid'] : 0;
        $model = new GradebookModel();
        $courseSubjectId = (int) ($_POST['mtcid'] ?? 0);
        $subperiodId = (int) ($_POST['spcid'] ?? 0);
        $redirect = '/calificaciones/registro';

        if ($courseSubjectId > 0) {
            $redirect .= '?mtcid=' . $courseSubjectId;

            if ($subperiodId > 0) {
                $redirect .= '&spcid=' . $subperiodId;
            }
        }

        if ($periodId <= 0 || $courseSubjectId <= 0 || $subperiodId <= 0) {
            sessionFlash('error', 'No se pudo identificar la materia o el subperiodo.');
            $this->redirect($redirect);
        }

        $selectedSubject = $model->selectedTeacherSubject((int) ($user['perid'] ?? 0), $periodId, $courseSubjectId);

        if ($selectedSubject === false || empty($selectedSubject['pcaid'])) {
            sessionFlash('error', 'La materia seleccionada no esta disponible para registrar calificaciones.');
            $this->redirect('/calificaciones/registro');
        }

        $subperiod = $model->subperiodByProfile((int) $selectedSubject['pcaid'], $subperiodId);

        if (
            $subperiod === false
            || date('Y-m-d') < (string) $subperiod['spcfecha_inicio']
            || date('Y-m-d') > (string) $subperiod['spcfecha_fin']
        ) {
            sessionFlash('error', 'El subperiodo esta fuera del rango permitido para editar calificaciones.');
            $this->redirect($redirect);
        }

        $activities = $model->activitiesBySubperiod($courseSubjectId, $subperiodId);
        $activityIds = [];

        foreach ($activities as $componentActivities) {
            foreach ($componentActivities as $activity) {
                $activityIds[] = (int) $activity['aciid'];
            }
        }

        $students = $model->studentsByCourse((int) $selectedSubject['curid']);
        $matriculationIds = array_map(static fn (array $student): int => (int) $student['matid'], $students);
        $minimum = is_numeric($selectedSubject['pcaminima'] ?? null) ? (float) $selectedSubject['pcaminima'] : 0.0;
        $maximum = is_numeric($selectedSubject['pcamaxima'] ?? null) ? (float) $selectedSubject['pcamaxima'] : 10.0;

        try {
            $saved = $model->saveGrades((array) ($_POST['grades'] ?? []), $activityIds, $matriculationIds, (int) $user['usuid'], $minimum, $maximum);
        } catch (\InvalidArgumentException $exception) {
            sessionFlash('error', $exception->getMessage());
            $this->redirect($redirect);
        }

        sessionFlash('success', $saved > 0 ? 'Calificaciones guardadas correctamente.' : 'No se registraron cambios de calificaciones.');
        $this->redirect($redirect);
    }
}
