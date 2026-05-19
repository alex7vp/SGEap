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

    public function finalChart(): void
    {
        $user = $this->requireAuth();
        $period = currentAcademicPeriod();
        $periodId = $period !== null ? (int) $period['pleid'] : 0;
        $model = new GradebookModel();
        $courses = $periodId > 0 ? $model->coursesByPeriod($periodId) : [];
        $selectedCourseId = (int) ($_GET['curid'] ?? ($courses[0]['curid'] ?? 0));
        $selectedCourse = $selectedCourseId > 0 && $periodId > 0
            ? $model->courseByPeriod($selectedCourseId, $periodId)
            : false;
        $students = [];
        $subjects = [];
        $rows = [];

        if ($selectedCourse !== false) {
            $students = $model->studentsByCourse((int) $selectedCourse['curid']);
            $subjects = $model->finalReportSubjectDefinitions((int) $selectedCourse['curid'], $periodId);
            $subjectResults = [];

            foreach ($subjects as $subject) {
                foreach ($subject['items'] as $item) {
                    $subjectResults[$subject['key']][(int) $item['mtcid']] = $this->finalSubjectScores($model, (int) $item['mtcid'], (int) $item['pcaid'], $students);
                }
            }

            foreach ($students as $student) {
                $subjectCells = [];
                $generalSum = 0.0;
                $generalCount = 0;

                foreach ($subjects as $subject) {
                    $itemScores = [];

                    foreach ($subject['items'] as $item) {
                        $score = $subjectResults[$subject['key']][(int) $item['mtcid']][(int) $student['matid']] ?? null;

                        if ($score !== null) {
                            $itemScores[] = $score;
                        }
                    }

                    $partial = $itemScores !== []
                        ? round(array_sum($itemScores) / count($itemScores), 2)
                        : 0.0;
                    $final = $partial;

                    if (!empty($subject['promediable'])) {
                        $generalSum += $final;
                        $generalCount++;
                    }

                    $subjectCells[$subject['key']] = [
                        'partial' => $partial,
                        'extra' => null,
                        'final' => $final,
                    ];
                }

                $generalAverage = $generalCount > 0 ? round($generalSum / $generalCount, 2) : null;
                $approval = $subjects[0]['approval'] ?? 7.0;

                $rows[] = [
                    'student' => $student,
                    'subjects' => $subjectCells,
                    'average' => $generalAverage,
                    'status' => $generalAverage !== null && $generalAverage >= $approval ? 'APROBADO' : 'NO APROBADO',
                ];
            }
        }

        $this->view('calificaciones.cuadro_final', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => 'Cuadro final',
            'currentModule' => 'reportes',
            'currentSection' => 'reporte_cuadro_final',
            'user' => $user,
            'currentPeriod' => $period,
            'courses' => $courses,
            'selectedCourseId' => $selectedCourseId,
            'selectedCourse' => $selectedCourse,
            'subjects' => $subjects,
            'rows' => $rows,
            'success' => sessionFlash('success'),
            'error' => sessionFlash('error'),
        ]);
    }

    private function finalSubjectScores(GradebookModel $model, int $courseSubjectId, int $profileId, array $students): array
    {
        $subperiods = $model->subperiods($profileId);
        $components = $model->components($profileId);
        $activities = $model->activitiesByProfile($courseSubjectId, $profileId);
        $activityIds = [];

        foreach ($activities as $componentActivitiesBySubperiod) {
            foreach ($componentActivitiesBySubperiod as $componentActivities) {
                foreach ($componentActivities as $activity) {
                    $activityIds[] = (int) $activity['aciid'];
                }
            }
        }

        $grades = $model->gradesByActivities($activityIds);
        $scores = [];

        foreach ($students as $student) {
            $finalSum = 0.0;
            $finalCount = 0;

            foreach ($subperiods as $subperiod) {
                $subperiodId = (int) $subperiod['spcid'];
                $subperiodParts = [];

                foreach (($components[$subperiodId] ?? []) as $component) {
                    $componentId = (int) $component['cpcid'];
                    $componentActivities = $activities[$subperiodId][$componentId] ?? [];

                    if ($componentActivities === []) {
                        continue;
                    }

                    $componentSum = 0.0;

                    foreach ($componentActivities as $activity) {
                        $grade = $grades[(int) $activity['aciid']][(int) $student['matid']] ?? null;
                        $componentSum += $grade !== null && $grade['cesnota'] !== null ? (float) $grade['cesnota'] : 0.0;
                    }

                    $componentAverage = round($componentSum / count($componentActivities), 2);
                    $subperiodParts[] = !empty($component['cpcpeso'])
                        ? round($componentAverage * ((float) $component['cpcpeso'] / 100), 2)
                        : $componentAverage;
                }

                $participatesFinalValue = strtolower((string) ($subperiod['spcparticipa_final'] ?? '1'));
                $participatesFinal = !in_array($participatesFinalValue, ['0', 'false', 'f', 'no'], true);

                if ($participatesFinal) {
                    $finalSum += $subperiodParts !== [] ? round(array_sum($subperiodParts), 2) : 0.0;
                    $finalCount++;
                }
            }

            $scores[(int) $student['matid']] = $finalCount > 0 ? round($finalSum / $finalCount, 2) : null;
        }

        return $scores;
    }
}
