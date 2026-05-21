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
        $teacherSubjects = $periodId > 0
            ? $model->teacherSubjects((int) ($user['perid'] ?? 0), $periodId)
            : [];
        $canEditAnyGradebook = $this->hasPermission('calificaciones.editar', $user);
        $canBrowseAllGradebook = $this->hasAnyPermission([
            'calificaciones.editar',
            'calificaciones.configurar',
            'calificaciones.validar',
            'calificaciones.publicar',
            'calificaciones.auditoria.ver',
        ], $user);
        $useAdministrativeSelection = $teacherSubjects === [] && $canBrowseAllGradebook;
        $courses = $useAdministrativeSelection && $periodId > 0 ? $model->coursesByPeriod($periodId) : [];
        $selectedCourseId = (int) ($_GET['curid'] ?? 0);
        $selectedCourse = $useAdministrativeSelection && $selectedCourseId > 0 && $periodId > 0
            ? $model->courseByPeriod($selectedCourseId, $periodId)
            : false;
        $subjects = $useAdministrativeSelection && $selectedCourse !== false
            ? $model->courseSubjects((int) $selectedCourse['curid'], $periodId)
            : $teacherSubjects;
        $selectedSubjectId = (int) ($_GET['mtcid'] ?? 0);
        $selectedSubperiodId = (int) ($_GET['spcid'] ?? 0);
        $selectedComponentId = (int) ($_GET['cpcid'] ?? 0);
        $showFinalAverages = (string) ($_GET['final'] ?? '') === '1';
        $selectedSubject = false;

        if ($selectedSubjectId > 0 && $periodId > 0 && (!$useAdministrativeSelection || $selectedCourse !== false)) {
            $selectedSubject = $useAdministrativeSelection
                ? $model->selectedCourseSubject($periodId, $selectedSubjectId)
                : $model->selectedTeacherSubject((int) ($user['perid'] ?? 0), $periodId, $selectedSubjectId);

            if ($useAdministrativeSelection && $selectedSubject !== false && (int) $selectedSubject['curid'] !== (int) $selectedCourse['curid']) {
                $selectedSubject = false;
            }
        }

        $canEditSelectedSubject = $selectedSubject !== false
            && (
                $canEditAnyGradebook
                || $model->selectedTeacherSubject((int) ($user['perid'] ?? 0), $periodId, (int) $selectedSubject['mtcid']) !== false
            );
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
            'courses' => $courses,
            'selectedCourse' => $selectedCourse,
            'selectedCourseId' => $selectedCourseId,
            'useAdministrativeSelection' => $useAdministrativeSelection,
            'canEditSelectedSubject' => $canEditSelectedSubject,
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
        $courseId = (int) ($_POST['curid'] ?? 0);
        $name = trim((string) ($_POST['acinombre'] ?? ''));
        $redirect = '/calificaciones/registro';

        if ($courseSubjectId > 0) {
            $redirect .= '?mtcid=' . $courseSubjectId;

            if ($subperiodId > 0) {
                $redirect .= '&spcid=' . $subperiodId;
            }

            if ($courseId > 0) {
                $redirect .= '&curid=' . $courseId;
            }
        }

        if ($periodId <= 0 || $courseSubjectId <= 0 || $subperiodId <= 0 || $componentId <= 0 || $name === '') {
            sessionFlash('error', 'Complete los datos de la actividad.');
            $this->redirect($redirect);
        }

        $selectedSubject = $model->selectedTeacherSubject((int) ($user['perid'] ?? 0), $periodId, $courseSubjectId);
        $selectedSubject = $selectedSubject !== false
            ? $selectedSubject
            : ($this->hasPermission('calificaciones.editar', $user) ? $model->selectedCourseSubject($periodId, $courseSubjectId) : false);

        if ($courseId <= 0 && $selectedSubject !== false) {
            $courseId = (int) ($selectedSubject['curid'] ?? 0);
            $redirect = $this->gradebookRedirect($courseSubjectId, $subperiodId, $courseId);
        }

        if ($selectedSubject === false || empty($selectedSubject['pcaid'])) {
            sessionFlash('error', 'La materia seleccionada no esta disponible para registrar calificaciones.');
            $this->redirect($courseId > 0 ? '/calificaciones/registro?curid=' . $courseId : '/calificaciones/registro');
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

        $activityId = $model->createActivity($courseSubjectId, $componentId, $name, date('Y-m-d'), (int) $user['usuid']);
        sessionFlash('success', 'Actividad agregada correctamente.');
        $this->redirect($this->gradebookRedirect($courseSubjectId, $subperiodId, $courseId, $activityId));
    }

    public function saveGrades(): void
    {
        $user = $this->requireAuth();
        $period = currentAcademicPeriod();
        $periodId = $period !== null ? (int) $period['pleid'] : 0;
        $model = new GradebookModel();
        $courseSubjectId = (int) ($_POST['mtcid'] ?? 0);
        $subperiodId = (int) ($_POST['spcid'] ?? 0);
        $courseId = (int) ($_POST['curid'] ?? 0);
        $redirect = '/calificaciones/registro';

        if ($courseSubjectId > 0) {
            $redirect .= '?mtcid=' . $courseSubjectId;

            if ($subperiodId > 0) {
                $redirect .= '&spcid=' . $subperiodId;
            }

            if ($courseId > 0) {
                $redirect .= '&curid=' . $courseId;
            }
        }

        if ($periodId <= 0 || $courseSubjectId <= 0 || $subperiodId <= 0) {
            sessionFlash('error', 'No se pudo identificar la materia o el subperiodo.');
            $this->redirect($redirect);
        }

        $selectedSubject = $model->selectedTeacherSubject((int) ($user['perid'] ?? 0), $periodId, $courseSubjectId);
        $selectedSubject = $selectedSubject !== false
            ? $selectedSubject
            : ($this->hasPermission('calificaciones.editar', $user) ? $model->selectedCourseSubject($periodId, $courseSubjectId) : false);

        if ($courseId <= 0 && $selectedSubject !== false) {
            $courseId = (int) ($selectedSubject['curid'] ?? 0);
            $redirect = $this->gradebookRedirect($courseSubjectId, $subperiodId, $courseId);
        }

        if ($selectedSubject === false || empty($selectedSubject['pcaid'])) {
            sessionFlash('error', 'La materia seleccionada no esta disponible para registrar calificaciones.');
            $this->redirect($courseId > 0 ? '/calificaciones/registro?curid=' . $courseId : '/calificaciones/registro');
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

    public function reportCard(): void
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
        $selectedMatriculationId = (int) ($_GET['matid'] ?? 0);
        $selectedStudent = false;
        $subjects = [];
        $subperiods = [];
        $selectedSubperiodId = (int) ($_GET['spcid'] ?? 0);
        $selectedSubperiod = false;
        $visibleSubperiods = [];
        $rows = [];
        $scaleRows = [];
        $componentColumns = [];
        $termGeneralAverages = [];
        $attendanceSummary = [
            'total_registros' => 0,
            'total_asistencias' => 0,
            'total_atrasos' => 0,
            'total_faltas_justificadas' => 0,
            'total_faltas_injustificadas' => 0,
            'dias_asistidos' => 0,
            'dias_con_falta' => 0,
        ];
        $attendanceBySubperiod = [];
        $generalAverage = null;
        $annualGeneralAverage = null;

        if ($selectedCourse !== false) {
            $students = $model->studentsByCourse((int) $selectedCourse['curid']);

            if ($selectedMatriculationId <= 0 && !empty($students)) {
                $selectedMatriculationId = (int) $students[0]['matid'];
            }

            foreach ($students as $student) {
                if ((int) $student['matid'] === $selectedMatriculationId) {
                    $selectedStudent = $student;
                    break;
                }
            }

            $subjects = $model->finalReportSubjectDefinitions((int) $selectedCourse['curid'], $periodId);
            $firstProfileId = 0;

            foreach ($subjects as $subject) {
                foreach ($subject['items'] as $item) {
                    $firstProfileId = (int) ($item['pcaid'] ?? 0);
                    break 2;
                }
            }

            if ($firstProfileId > 0) {
                $subperiods = $model->subperiods($firstProfileId);
                $scaleRows = $model->qualitativeScale($firstProfileId);
            }

            if ($selectedSubperiodId <= 0 && !empty($subperiods)) {
                $selectedSubperiodId = (int) $subperiods[0]['spcid'];
            }

            foreach ($subperiods as $subperiod) {
                if ((int) $subperiod['spcid'] === $selectedSubperiodId) {
                    $selectedSubperiod = $subperiod;
                    break;
                }
            }

            if ($selectedSubperiod !== false) {
                $selectedOrder = (int) ($selectedSubperiod['spcorden'] ?? 0);

                foreach ($subperiods as $subperiod) {
                    if ((int) ($subperiod['spcorden'] ?? 0) <= $selectedOrder) {
                        $visibleSubperiods[] = $subperiod;
                    }
                }

                foreach (($model->components($firstProfileId)[$selectedSubperiodId] ?? []) as $component) {
                    $componentColumns[] = [
                        'id' => (int) $component['cpcid'],
                        'name' => (string) $component['cpcnombre'],
                        'weight' => is_numeric($component['cpcpeso'] ?? null) ? (float) $component['cpcpeso'] : null,
                    ];
                }
            }

            if ($selectedStudent !== false && $selectedSubperiod !== false) {
                $generalSum = 0.0;
                $generalCount = 0;
                $annualGeneralSum = 0.0;
                $annualGeneralCount = 0;
                $scaleCache = [];
                $profileSubperiodCache = [];

                foreach ($subjects as $subject) {
                    $periodScores = [];
                    $finalParts = [];
                    $selectedComponents = [];
                    $profileId = 0;

                    foreach ($visibleSubperiods as $subperiod) {
                        $subperiodId = (int) $subperiod['spcid'];
                        $itemScores = [];
                        $componentBuckets = [];
                        $componentResultBuckets = [];

                        foreach ($subject['items'] as $item) {
                            $profileId = (int) ($item['pcaid'] ?? $profileId);
                            $subperiodOrder = (int) ($subperiod['spcorden'] ?? 0);

                            if ($profileId <= 0) {
                                $itemScores[] = 0.0;
                                continue;
                            }

                            if (!isset($profileSubperiodCache[$profileId])) {
                                $profileSubperiodCache[$profileId] = [];

                                foreach ($model->subperiods($profileId) as $profileSubperiod) {
                                    $profileSubperiodCache[$profileId][(int) $profileSubperiod['spcorden']] = $profileSubperiod;
                                }
                            }

                            $itemSubperiod = $profileSubperiodCache[$profileId][$subperiodOrder] ?? false;

                            if ($itemSubperiod === false) {
                                $itemScores[] = 0.0;
                                continue;
                            }

                            $itemScore = $this->reportCardItemScore(
                                $model,
                                (int) $item['mtcid'],
                                $profileId,
                                (int) $itemSubperiod['spcid'],
                                (int) $selectedStudent['matid']
                            );
                            $itemScores[] = (float) $itemScore['average'];

                            if ((int) ($subperiod['spcorden'] ?? 0) === (int) ($selectedSubperiod['spcorden'] ?? 0)) {
                                foreach ($itemScore['components'] as $component) {
                                    $componentBuckets[(int) $component['order']][] = (float) $component['average'];
                                    $componentResultBuckets[(int) $component['order']][] = (float) $component['result'];
                                }
                            }
                        }

                        $periodScore = $itemScores !== [] ? round(array_sum($itemScores) / count($itemScores), 2) : 0.0;
                        $periodScores[$subperiodId] = $periodScore;

                        if ((int) ($subperiod['spcorden'] ?? 0) === (int) ($selectedSubperiod['spcorden'] ?? 0)) {
                            $selectedComponents = [];

                            foreach ($componentColumns as $index => $componentColumn) {
                                $bucket = $componentBuckets[$index + 1] ?? [];
                                $resultBucket = $componentResultBuckets[$index + 1] ?? [];
                                $selectedComponents[(int) $componentColumn['id']] = $bucket !== []
                                    ? [
                                        'average' => round(array_sum($bucket) / count($bucket), 2),
                                        'result' => $resultBucket !== [] ? round(array_sum($resultBucket) / count($resultBucket), 2) : 0.0,
                                    ]
                                    : [
                                        'average' => 0.0,
                                        'result' => 0.0,
                                    ];
                            }
                        }

                        $participatesFinalValue = strtolower((string) ($subperiod['spcparticipa_final'] ?? '1'));
                        $participatesFinal = !in_array($participatesFinalValue, ['0', 'false', 'f', 'no'], true);

                        if ($participatesFinal) {
                            $weight = is_numeric($subperiod['spcpeso_final'] ?? null) ? (float) $subperiod['spcpeso_final'] : null;
                            $finalParts[] = [
                                'score' => $periodScore,
                                'weight' => $weight,
                            ];
                        }
                    }

                    if ($profileId > 0 && !isset($scaleCache[$profileId])) {
                        $scaleCache[$profileId] = $model->qualitativeScale($profileId);
                    }

                    $weightedParts = array_values(array_filter(
                        $finalParts,
                        static fn (array $part): bool => $part['weight'] !== null
                    ));
                    $weightTotal = array_sum(array_map(static fn (array $part): float => (float) $part['weight'], $weightedParts));
                    $score = 0.0;

                    if ($weightedParts !== [] && $weightTotal > 0) {
                        foreach ($weightedParts as $part) {
                            $score += (float) $part['score'] * ((float) $part['weight'] / $weightTotal);
                        }

                        $score = round($score, 2);
                    } elseif ($finalParts !== []) {
                        $score = round(array_sum(array_map(static fn (array $part): float => (float) $part['score'], $finalParts)) / count($finalParts), 2);
                    }

                    $selectedScore = $periodScores[$selectedSubperiodId] ?? 0.0;

                    if (!empty($subject['promediable'])) {
                        $generalSum += $selectedScore;
                        $generalCount++;
                        $annualGeneralSum += $score;
                        $annualGeneralCount++;

                        foreach ($visibleSubperiods as $subperiod) {
                            $termId = (int) $subperiod['spcid'];
                            $termGeneralAverages[$termId]['sum'] = ($termGeneralAverages[$termId]['sum'] ?? 0.0) + (float) ($periodScores[$termId] ?? 0.0);
                            $termGeneralAverages[$termId]['count'] = ($termGeneralAverages[$termId]['count'] ?? 0) + 1;
                        }
                    }

                    $rows[] = [
                        'name' => (string) $subject['name'],
                        'subperiods' => $periodScores,
                        'components' => $selectedComponents ?? [],
                        'selected_score' => $selectedScore,
                        'score' => $score,
                        'equivalence' => !empty($subject['uses_equivalence'])
                            ? $this->qualitativeEquivalent($selectedScore, $scaleCache[$profileId] ?? [])
                            : '',
                        'promediable' => !empty($subject['promediable']),
                    ];
                }

                $generalAverage = $generalCount > 0 ? round($generalSum / $generalCount, 2) : null;
                $annualGeneralAverage = $annualGeneralCount > 0 ? round($annualGeneralSum / $annualGeneralCount, 2) : null;

                foreach ($termGeneralAverages as $termId => $termAverage) {
                    $termGeneralAverages[$termId] = ((int) ($termAverage['count'] ?? 0)) > 0
                        ? round((float) $termAverage['sum'] / (int) $termAverage['count'], 2)
                        : 0.0;
                }

                if ($visibleSubperiods !== []) {
                    $attendanceSummary = $model->attendanceSummaryForReportCard(
                        $periodId,
                        (int) $selectedStudent['matid'],
                        (string) $visibleSubperiods[0]['spcfecha_inicio'],
                        (string) $selectedSubperiod['spcfecha_fin']
                    );

                    foreach ($visibleSubperiods as $subperiod) {
                        $attendanceBySubperiod[(int) $subperiod['spcid']] = $model->attendanceSummaryForReportCard(
                            $periodId,
                            (int) $selectedStudent['matid'],
                            (string) $subperiod['spcfecha_inicio'],
                            (string) $subperiod['spcfecha_fin']
                        );
                    }
                }
            }
        }

        $this->view('calificaciones.libreta', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => 'Libreta de calificaciones',
            'currentModule' => 'reportes',
            'currentSection' => 'reporte_libreta',
            'user' => $user,
            'currentPeriod' => $period,
            'courses' => $courses,
            'selectedCourseId' => $selectedCourseId,
            'selectedCourse' => $selectedCourse,
            'students' => $students,
            'selectedMatriculationId' => $selectedMatriculationId,
            'selectedStudent' => $selectedStudent,
            'subperiods' => $subperiods,
            'selectedSubperiodId' => $selectedSubperiodId,
            'selectedSubperiod' => $selectedSubperiod,
            'visibleSubperiods' => $visibleSubperiods,
            'componentColumns' => $componentColumns,
            'termGeneralAverages' => $termGeneralAverages,
            'rows' => $rows,
            'scaleRows' => $scaleRows,
            'attendanceSummary' => $attendanceSummary,
            'attendanceBySubperiod' => $attendanceBySubperiod,
            'generalAverage' => $generalAverage,
            'annualGeneralAverage' => $annualGeneralAverage,
            'success' => sessionFlash('success'),
            'error' => sessionFlash('error'),
        ]);
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

    private function reportCardItemScore(
        GradebookModel $model,
        int $courseSubjectId,
        int $profileId,
        int $subperiodId,
        int $matriculationId
    ): array {
        $components = $model->components($profileId)[$subperiodId] ?? [];
        $activities = $model->activitiesBySubperiod($courseSubjectId, $subperiodId);
        $activityIds = [];

        foreach ($activities as $componentActivities) {
            foreach ($componentActivities as $activity) {
                $activityIds[] = (int) $activity['aciid'];
            }
        }

        $grades = $model->gradesByActivities($activityIds);
        $componentScores = [];
        $subperiodParts = [];
        $componentOrder = 0;

        foreach ($components as $component) {
            $componentOrder++;
            $componentId = (int) $component['cpcid'];
            $componentActivities = $activities[$componentId] ?? [];
            $componentSum = 0.0;

            foreach ($componentActivities as $activity) {
                $grade = $grades[(int) $activity['aciid']][$matriculationId] ?? null;
                $componentSum += $grade !== null && $grade['cesnota'] !== null ? (float) $grade['cesnota'] : 0.0;
            }

            $componentAverage = $componentActivities !== [] ? round($componentSum / count($componentActivities), 2) : 0.0;
            $componentResult = !empty($component['cpcpeso'])
                ? round($componentAverage * ((float) $component['cpcpeso'] / 100), 2)
                : $componentAverage;
            $componentScores[] = [
                'id' => $componentId,
                'order' => $componentOrder,
                'name' => (string) $component['cpcnombre'],
                'average' => $componentAverage,
                'result' => $componentResult,
                'weight' => $component['cpcpeso'] ?? null,
            ];
            $subperiodParts[] = $componentResult;
        }

        return [
            'components' => $componentScores,
            'average' => $subperiodParts !== [] ? round(array_sum($subperiodParts), 2) : 0.0,
        ];
    }

    private function qualitativeEquivalent(?float $score, array $scaleRows): string
    {
        if ($score === null) {
            return '';
        }

        foreach ($scaleRows as $scale) {
            $minimum = $scale['ecavalor_minimo'] ?? null;
            $maximum = $scale['ecavalor_maximo'] ?? null;

            if ($minimum === null || $maximum === null) {
                continue;
            }

            if ($score >= (float) $minimum && $score <= (float) $maximum) {
                return (string) ($scale['ecacodigo'] ?: $scale['ecanombre']);
            }
        }

        return '';
    }

    private function gradebookRedirect(int $courseSubjectId, int $subperiodId = 0, int $courseId = 0, int $editActivityId = 0): string
    {
        $redirect = '/calificaciones/registro';

        if ($courseSubjectId > 0) {
            $redirect .= '?mtcid=' . $courseSubjectId;

            if ($subperiodId > 0) {
                $redirect .= '&spcid=' . $subperiodId;
            }

            if ($courseId > 0) {
                $redirect .= '&curid=' . $courseId;
            }

            if ($editActivityId > 0) {
                $redirect .= '&edit_aciid=' . $editActivityId;
            }
        } elseif ($courseId > 0) {
            $redirect .= '?curid=' . $courseId;
        }

        return $redirect;
    }
}
