<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\AttendanceModel;
use App\Models\CourseModel;
use App\Models\GradebookModel;
use App\Models\NoveltyModel;
use App\Models\PersonalModel;
use App\Models\StudentModel;
use App\Services\PdfReportService;
use Throwable;

class AttendanceController extends Controller
{
    public function configuration(): void
    {
        $user = $this->requireAuth();
        $period = currentAcademicPeriod();
        $attendanceModel = new AttendanceModel();

        $periodId = $period !== null ? (int) $period['pleid'] : 0;
        $attendanceConfiguration = $periodId > 0
            ? $attendanceModel->attendanceConfigurationByPeriod($periodId)
            : false;

        $this->view('asistencia.configuracion', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => 'Configuracion de asistencia',
            'currentSection' => 'asistencia_configuracion',
            'user' => $user,
            'currentPeriod' => $period,
            'attendanceConfiguration' => $attendanceConfiguration,
            'success' => sessionFlash('success'),
            'error' => sessionFlash('error'),
        ]);
    }

    public function academicAreas(): void
    {
        $this->requireAuth();
        $this->redirect('/configuracion/academica?view=areas');
    }

    public function academicSubjects(): void
    {
        $this->requireAuth();
        $this->redirect('/configuracion/academica?view=asignaturas');
    }

    public function academicCourseSubjects(): void
    {
        $this->requireAuth();
        $this->redirect('/configuracion/academica?view=materias');
    }

    public function searchCourseSubjects(): void
    {
        $this->requireAuth();

        $period = currentAcademicPeriod();
        $periodId = $period !== null ? (int) $period['pleid'] : 0;
        $courseId = max(0, (int) ($_GET['curid'] ?? 0));
        $areaId = max(0, (int) ($_GET['areaid'] ?? 0));
        $subjectId = max(0, (int) ($_GET['asgid'] ?? 0));
        $courseSubjects = $periodId > 0
            ? (new AttendanceModel())->courseSubjectsByPeriod($periodId)
            : [];

        if ($courseId > 0 || $areaId > 0 || $subjectId > 0) {
            $courseSubjects = array_values(array_filter(
                $courseSubjects,
                static function (array $subject) use ($courseId, $areaId, $subjectId): bool {
                    if ($courseId > 0 && (int) $subject['curid'] !== $courseId) {
                        return false;
                    }

                    if ($areaId > 0 && (int) $subject['areaid'] !== $areaId) {
                        return false;
                    }

                    if ($subjectId > 0 && (int) $subject['asgid'] !== $subjectId) {
                        return false;
                    }

                    return true;
                }
            ));
        }

        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'html' => $this->renderCourseSubjectRows($courseSubjects),
            'isEmpty' => empty($courseSubjects),
            'emptyHtml' => '<div class="empty-state">No se encontraron materias con esos filtros.</div>',
            'count' => count($courseSubjects),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function academicTeachers(): void
    {
        $this->requireAuth();
        $this->redirect('/configuracion/academica?view=docentes');
    }

    public function saveConfiguration(): void
    {
        $user = $this->requireAuth();
        $period = currentAcademicPeriod();

        if ($period === null) {
            sessionFlash('error', 'Debe seleccionar un periodo lectivo antes de configurar asistencia.');
            $this->redirect('/asistencia/configuracion');
        }

        $startDate = trim((string) ($_POST['coafecha_inicio_clases'] ?? ''));
        $endDate = trim((string) ($_POST['coafecha_fin_clases'] ?? ''));
        $note = trim((string) ($_POST['coaobservacion'] ?? ''));

        try {
            (new AttendanceModel())->saveAttendanceConfiguration(
                (int) $period['pleid'],
                $startDate,
                $endDate,
                $note,
                (int) ($user['usuid'] ?? 0)
            );
            sessionFlash('success', 'Configuracion de asistencia actualizada correctamente.');
        } catch (Throwable $exception) {
            sessionFlash('error', $exception->getMessage());
        }

        $this->redirect('/asistencia/configuracion');
    }

    public function register(): void
    {
        $user = $this->requireAuth();
        $period = currentAcademicPeriod();
        $attendanceModel = new AttendanceModel();
        $periodId = $period !== null ? (int) $period['pleid'] : 0;
        $selectedCourseId = max(0, (int) ($_GET['curid'] ?? 0));
        $classDateRange = $periodId > 0 ? $attendanceModel->classDateRangeByPeriod($periodId) : null;
        $availableMonths = $classDateRange !== null
            ? $this->monthsBetween((string) $classDateRange['start'], (string) $classDateRange['end'])
            : [];
        $month = $this->validMonthOrCurrent((string) ($_GET['mes'] ?? substr((string) ($_GET['fecha'] ?? date('Y-m-d')), 0, 7)));

        if ($availableMonths !== [] && !in_array($month, $availableMonths, true)) {
            $month = $this->nearestMonthInRange($month, $availableMonths);
        }

        $monthStart = $month . '-01';
        $monthEnd = date('Y-m-t', strtotime($monthStart));
        $availabilityRows = $periodId > 0
            ? $attendanceModel->teacherCalendarAvailabilityByRange((int) ($user['perid'] ?? 0), $periodId, $monthStart, $monthEnd)
            : [];
        $availabilityRows = $this->filterRowsByCourse($availabilityRows, $selectedCourseId);
        $teacherCalendarDays = $this->teacherCalendarDaysFromAvailability($availabilityRows);
        $teacherSubjectHours = [];
        $date = $this->validDateOrToday((string) ($_GET['fecha'] ?? $this->firstTeacherSelectableDateForMonth($teacherCalendarDays, $monthStart, $classDateRange)));
        $teacherSessionDays = $periodId > 0
            ? $attendanceModel->teacherSessionSummaryByRange((int) ($user['perid'] ?? 0), $periodId, $monthStart, $monthEnd)
            : [];
        $sessionId = (int) ($_GET['sclid'] ?? 0);
        $session = false;
        $students = [];
        $attendance = [];

        if ($sessionId > 0) {
            $session = $attendanceModel->sessionForTeacher($sessionId, (int) ($user['perid'] ?? 0));

            if ($session !== false) {
                if ($selectedCourseId > 0 && (int) ($session['curid'] ?? 0) !== $selectedCourseId) {
                    $session = false;
                } else {
                    $selectedCourseId = (int) ($session['curid'] ?? $selectedCourseId);
                }
            }

            if ($session !== false) {
                $students = $attendanceModel->activeStudentsForSession($sessionId);
                $attendance = $attendanceModel->attendanceBySession($sessionId);
                $date = (string) $session['cafecha'];
            }
        }

        if (substr($date, 0, 7) !== $month) {
            $month = substr($date, 0, 7);
            $monthStart = $month . '-01';
            $monthEnd = date('Y-m-t', strtotime($monthStart));
            $availabilityRows = $periodId > 0
                ? $attendanceModel->teacherCalendarAvailabilityByRange((int) ($user['perid'] ?? 0), $periodId, $monthStart, $monthEnd)
                : [];
            $availabilityRows = $this->filterRowsByCourse($availabilityRows, $selectedCourseId);
            $teacherCalendarDays = $this->teacherCalendarDaysFromAvailability($availabilityRows);
            $teacherSessionDays = $periodId > 0
                ? $attendanceModel->teacherSessionSummaryByRange((int) ($user['perid'] ?? 0), $periodId, $monthStart, $monthEnd)
                : [];
        }

        $teacherSubjectHours = $this->teacherSubjectHoursForDate($availabilityRows, $date);
        $teacherDaySessions = $periodId > 0 && $date !== ''
            ? $this->filterRowsByCourse(
                $attendanceModel->teacherSessionsByDate((int) ($user['perid'] ?? 0), $periodId, $date),
                $selectedCourseId
            )
            : [];
        $canRegisterNovelties = $this->hasPermission('novedades.registrar', $user)
            || $this->hasPermission('novedades.supervisar', $user);
        $noveltyModel = $canRegisterNovelties ? new NoveltyModel() : null;
        $noveltyTeacherScope = $this->hasPermission('novedades.supervisar', $user)
            ? null
            : ($this->hasPermission('novedades.registrar', $user) ? (int) ($user['perid'] ?? 0) : null);

        $viewData = [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => 'Registro de asistencia y novedades',
            'currentSection' => 'asistencia_registro',
            'user' => $user,
            'currentPeriod' => $period,
            'selectedDate' => $date,
            'selectedMonth' => $month,
            'monthStart' => $monthStart,
            'monthEnd' => $monthEnd,
            'classDateRange' => $classDateRange,
            'availableMonths' => $availableMonths,
            'teacherCalendarDays' => $teacherCalendarDays,
            'teacherSubjectHours' => $teacherSubjectHours,
            'teacherDayHourSubjects' => $this->teacherDayHourSubjectsFromAvailability($availabilityRows),
            'teacherSessionDays' => $teacherSessionDays,
            'teacherDaySessions' => $teacherDaySessions,
            'selectedHour' => (string) ($_GET['hora'] ?? ''),
            'selectedCourseId' => $selectedCourseId,
            'teacherSubjects' => array_values($teacherSubjectHours),
            'session' => $session,
            'students' => $students,
            'attendance' => $attendance,
            'canRegisterNovelties' => $canRegisterNovelties,
            'noveltyTypes' => $noveltyModel !== null ? $noveltyModel->activeTypes() : [],
            'noveltyStudents' => $periodId > 0 && $noveltyModel !== null
                ? $this->filterRowsByCourse($noveltyModel->activeMatriculationsForPeriod($periodId, $noveltyTeacherScope), $selectedCourseId)
                : [],
            'noveltySessions' => $periodId > 0 && $noveltyModel !== null
                ? $this->filterRowsByCourse($noveltyModel->sessionsForDate($periodId, $date, $noveltyTeacherScope), $selectedCourseId)
                : [],
            'recentNovelties' => $periodId > 0 && $noveltyModel !== null
                ? $this->filterRowsByCourse($noveltyModel->byPeriod($periodId, $date, 0, $noveltyTeacherScope), $selectedCourseId)
                : [],
            'success' => sessionFlash('success'),
            'error' => sessionFlash('error'),
        ];

        if ((string) ($_GET['pdf'] ?? '') === '1') {
            if ($session === false) {
                sessionFlash('error', 'Debe seleccionar una sesion de asistencia para generar el PDF.');
                $this->redirect('/asistencia/registro?mes=' . $month . '&fecha=' . $date . ($selectedCourseId > 0 ? '&curid=' . $selectedCourseId : '') . '#calendario-docente');
            }

            (new PdfReportService())->streamView(
                'pdf.asistencia_sesion_docente',
                $viewData,
                'asistencia-sesion.pdf',
                'A4',
                'portrait'
            );
        }

        $this->view('asistencia.registro', $viewData);
    }

    public function calendar(): void
    {
        $user = $this->requireAuth();
        $period = currentAcademicPeriod();
        $attendanceModel = new AttendanceModel();
        $courseModel = new CourseModel();
        $periodId = $period !== null ? (int) $period['pleid'] : 0;
        $classDateRange = $periodId > 0 ? $attendanceModel->classDateRangeByPeriod($periodId) : null;
        $availableMonths = $classDateRange !== null
            ? $this->monthsBetween((string) $classDateRange['start'], (string) $classDateRange['end'])
            : [];
        $month = $this->validMonthOrCurrent((string) ($_GET['mes'] ?? date('Y-m')));

        if ($availableMonths !== [] && !in_array($month, $availableMonths, true)) {
            $month = $this->nearestMonthInRange($month, $availableMonths);
        }

        $monthStart = $month . '-01';
        $monthEnd = date('Y-m-t', strtotime($monthStart));
        $date = $this->validDateOrToday((string) ($_GET['fecha'] ?? $this->firstSelectableDateForMonth($monthStart, $monthEnd, $classDateRange)));

        if (!$this->dateInsideClassRange($date, $classDateRange)) {
            $date = $this->firstSelectableDateForMonth($monthStart, $monthEnd, $classDateRange);
        }

        $calendarMonthDays = $periodId > 0 ? $attendanceModel->calendarDaysByRange($periodId, $monthStart, $monthEnd) : [];
        $calendarDay = $periodId > 0 ? $attendanceModel->calendarDayByDate($periodId, $date) : false;
        $calendarDetails = $calendarDay !== false
            ? $attendanceModel->calendarDetailsByDay((int) $calendarDay['caid'])
            : [];
        $selectedDateHasAttendance = !empty($calendarMonthDays[$date]['asistencia_registrada']);

        $this->view('asistencia.calendario', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => 'Calendario de asistencia',
            'currentSection' => 'asistencia_calendario',
            'user' => $user,
            'currentPeriod' => $period,
            'selectedDate' => $date,
            'selectedMonth' => $month,
            'monthStart' => $monthStart,
            'monthEnd' => $monthEnd,
            'classDateRange' => $classDateRange,
            'availableMonths' => $availableMonths,
            'calendarDay' => $calendarDay,
            'calendarDetails' => $calendarDetails,
            'calendarMonthDays' => $calendarMonthDays,
            'selectedDateHasAttendance' => $selectedDateHasAttendance,
            'calendarDays' => $periodId > 0 ? $attendanceModel->calendarDaysByPeriod($periodId) : [],
            'courses' => $periodId > 0 ? array_values(array_filter(
                $courseModel->allByPeriod($periodId),
                static fn (array $course): bool => !empty($course['curestado'])
            )) : [],
            'success' => sessionFlash('success'),
            'error' => sessionFlash('error'),
        ]);
    }

    public function justifications(): void
    {
        $user = $this->requireAuth();
        $period = currentAcademicPeriod();
        $attendanceModel = new AttendanceModel();
        $courseModel = new CourseModel();
        $periodId = $period !== null ? (int) $period['pleid'] : 0;
        $courseId = (int) ($_GET['curid'] ?? 0);
        $studentId = (int) ($_GET['estid'] ?? 0);

        $this->view('asistencia.justificaciones', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => 'Justificaciones',
            'currentSection' => 'asistencia_justificaciones',
            'user' => $user,
            'currentPeriod' => $period,
            'selectedCourseId' => $courseId,
            'selectedStudentId' => $studentId,
            'courses' => $periodId > 0 ? array_values(array_filter(
                $courseModel->allByPeriod($periodId),
                static fn (array $course): bool => !empty($course['curestado'])
            )) : [],
            'students' => $periodId > 0 ? $attendanceModel->studentsForJustification($periodId, $courseId) : [],
            'unjustifiedAbsences' => $periodId > 0
                ? $attendanceModel->unjustifiedAbsencesForJustification($periodId, $courseId, $studentId)
                : [],
            'justifications' => $periodId > 0 ? $attendanceModel->justificationsByPeriod($periodId) : [],
            'success' => sessionFlash('success'),
            'error' => sessionFlash('error'),
        ]);
    }

    public function supervision(): void
    {
        $user = $this->requireAuth();
        $period = currentAcademicPeriod();
        $attendanceModel = new AttendanceModel();
        $courseModel = new CourseModel();
        $date = $this->validDateOrToday((string) ($_GET['fecha'] ?? date('Y-m-d')));
        $courseId = (int) ($_GET['curid'] ?? 0);
        $sessionId = (int) ($_GET['sclid'] ?? 0);
        $periodId = $period !== null ? (int) $period['pleid'] : 0;
        $session = false;
        $attendanceDetail = [];

        if ($periodId > 0 && $sessionId > 0) {
            $session = $attendanceModel->sessionForSupervision($sessionId, $periodId);

            if ($session !== false) {
                $attendanceDetail = $attendanceModel->attendanceDetailBySession($sessionId);
                $date = (string) $session['cafecha'];
                $courseId = (int) $session['curid'];
            }
        }

        $this->view('asistencia.supervision', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => 'Supervision de asistencia',
            'currentSection' => 'asistencia_supervision',
            'user' => $user,
            'currentPeriod' => $period,
            'selectedDate' => $date,
            'selectedCourseId' => $courseId,
            'courses' => $periodId > 0 ? array_values(array_filter(
                $courseModel->allByPeriod($periodId),
                static fn (array $course): bool => !empty($course['curestado'])
            )) : [],
            'sessions' => $periodId > 0 ? $attendanceModel->supervisedSessions($periodId, $date, $courseId) : [],
            'session' => $session,
            'attendanceDetail' => $attendanceDetail,
            'success' => sessionFlash('success'),
            'error' => sessionFlash('error'),
        ]);
    }

    public function ownAttendance(): void
    {
        $user = $this->requireAuth();
        $period = currentAcademicPeriod();
        $student = (new StudentModel())->findByPersonId((int) ($user['perid'] ?? 0));

        if ($student === false) {
            sessionFlash('error', 'Tu usuario no tiene un estudiante asociado.');
            $this->redirect('/dashboard');
        }

        $this->studentAttendanceView(
            'asistencia.ver_propia',
            'Mi asistencia y novedades',
            'asistencia_propia',
            (int) $student['estid'],
            $period,
            $user,
            []
        );
    }

    public function representativeAttendance(): void
    {
        $user = $this->requireAuth();
        $period = currentAcademicPeriod();
        $periodId = $period !== null ? (int) $period['pleid'] : null;
        $studentModel = new StudentModel();
        $students = $studentModel->allByRepresentativePerson((int) ($user['perid'] ?? 0), $periodId);
        $selectedStudentId = (int) ($_GET['estid'] ?? 0);

        if ($selectedStudentId <= 0 && $students !== []) {
            $selectedStudentId = (int) $students[0]['estid'];
        }

        if ($selectedStudentId > 0 && !$studentModel->representativeCanAccessStudent((int) ($user['perid'] ?? 0), $selectedStudentId)) {
            sessionFlash('error', 'No tienes acceso a la asistencia solicitada.');
            $this->redirect('/dashboard');
        }

        $this->studentAttendanceView(
            'asistencia.representante.ver',
            'Asistencia y novedades de representados',
            'asistencia_representante',
            $selectedStudentId,
            $period,
            $user,
            $students
        );
    }

    public function reports(): void
    {
        $user = $this->requireAuth();
        $period = currentAcademicPeriod();
        $periodId = $period !== null ? (int) $period['pleid'] : 0;
        $attendanceModel = new AttendanceModel();
        $canSuperviseAttendance = $this->hasPermission('asistencia.supervisar', $user);
        $teacherReportCourses = !$canSuperviseAttendance && $periodId > 0
            ? (new GradebookModel())->teacherCourses((int) ($user['perid'] ?? 0), $periodId)
            : [];
        $teacherReportCourseIds = array_values(array_filter(
            array_map(static fn (array $course): int => (int) ($course['curid'] ?? 0), $teacherReportCourses),
            static fn (int $courseId): bool => $courseId > 0
        ));
        $classDateRange = $periodId > 0 ? $attendanceModel->classDateRangeByPeriod($periodId) : null;
        $availableMonths = $classDateRange !== null
            ? $this->monthsBetween((string) $classDateRange['start'], (string) $classDateRange['end'])
            : [];
        $selectedMonth = $this->validMonthOrCurrent((string) ($_GET['mes'] ?? date('Y-m')));

        if ($availableMonths !== [] && !in_array($selectedMonth, $availableMonths, true)) {
            $selectedMonth = $this->nearestMonthInRange($selectedMonth, $availableMonths);
        }

        $customRange = (string) ($_GET['rango_manual'] ?? '') === '1';
        $startDate = $customRange
            ? $this->validDateOrToday((string) ($_GET['desde'] ?? $selectedMonth . '-01'))
            : $selectedMonth . '-01';
        $endDate = $customRange
            ? $this->validDateOrToday((string) ($_GET['hasta'] ?? date('Y-m-t', strtotime($selectedMonth . '-01'))))
            : date('Y-m-t', strtotime($selectedMonth . '-01'));

        if ($classDateRange !== null) {
            $rangeStart = (string) ($classDateRange['start'] ?? '');
            $rangeEnd = (string) ($classDateRange['end'] ?? '');

            if ($rangeStart !== '' && $startDate < $rangeStart) {
                $startDate = $rangeStart;
            }

            if ($rangeEnd !== '' && $startDate > $rangeEnd) {
                $startDate = $rangeEnd;
            }

            if ($rangeStart !== '' && $endDate < $rangeStart) {
                $endDate = $rangeStart;
            }

            if ($rangeEnd !== '' && $endDate > $rangeEnd) {
                $endDate = $rangeEnd;
            }
        }

        $courseId = (int) ($_GET['curid'] ?? 0);
        $studentId = (int) ($_GET['estid'] ?? 0);
        $courseSubjectId = (int) ($_GET['mtcid'] ?? 0);
        $teacherPersonId = (int) ($_GET['perid_docente'] ?? 0);

        if (!$canSuperviseAttendance) {
            $teacherPersonId = (int) ($user['perid'] ?? 0);

            if ($teacherReportCourseIds !== []) {
                if ($courseId <= 0 || !in_array($courseId, $teacherReportCourseIds, true)) {
                    $courseId = $teacherReportCourseIds[0];
                }
            } else {
                $courseId = 0;
                $studentId = 0;
                $courseSubjectId = 0;
            }
        }

        if ($courseId <= 0) {
            $studentId = 0;
        }

        if ($endDate < $startDate) {
            $endDate = $startDate;
        }

        $courseModel = new CourseModel();
        $studentModel = new StudentModel();
        $courses = $periodId > 0 ? array_values(array_filter(
            $courseModel->allByPeriod($periodId),
            static fn (array $course): bool => !empty($course['curestado'])
        )) : [];
        $courseSubjects = $periodId > 0 ? $attendanceModel->reportCourseSubjects($periodId) : [];
        $students = $periodId > 0 ? $studentModel->allWithPerson($periodId, $courseId > 0 ? ['curid' => $courseId] : []) : [];
        $teachers = $periodId > 0 ? $attendanceModel->reportTeachers($periodId) : [];

        if (!$canSuperviseAttendance) {
            $allowedCourseIds = array_flip($teacherReportCourseIds);
            $courses = array_values(array_filter(
                $courses,
                static fn (array $course): bool => isset($allowedCourseIds[(int) ($course['curid'] ?? 0)])
            ));
            $courseSubjects = array_values(array_filter(
                $courseSubjects,
                static fn (array $subject): bool => isset($allowedCourseIds[(int) ($subject['curid'] ?? 0)])
                    && ($courseId <= 0 || (int) ($subject['curid'] ?? 0) === $courseId)
            ));
            $teachers = [];

            if ($courseSubjectId > 0) {
                $selectedSubjectBelongsToTeacher = false;

                foreach ($courseSubjects as $subject) {
                    if ((int) ($subject['mtcid'] ?? 0) === $courseSubjectId) {
                        $selectedSubjectBelongsToTeacher = true;
                        break;
                    }
                }

                if (!$selectedSubjectBelongsToTeacher) {
                    $courseSubjectId = 0;
                }
            }
        }

        $matrixReport = $periodId > 0
            ? $attendanceModel->attendanceMatrixReport(
                $periodId,
                $startDate,
                $endDate,
                $courseId,
                $studentId,
                $courseSubjectId,
                $teacherPersonId
            )
            : ['dates' => [], 'rows' => []];
        $studentHourlyMatrix = $periodId > 0 && $studentId > 0
            ? $attendanceModel->studentAttendanceHourlyMatrixReport(
                $periodId,
                $startDate,
                $endDate,
                $studentId,
                $courseId,
                $courseSubjectId,
                $teacherPersonId
            )
            : [];
        $reportDates = $matrixReport['dates'] ?? [];
        $reportRows = $matrixReport['rows'] ?? [];

        if ($periodId > 0 && !$customRange && $studentId <= 0) {
            $reportDates = $attendanceModel->enabledAttendanceDatesByRange($periodId, $startDate, $endDate, $courseId);

            $shouldCompleteMonthlyRows = $courseSubjectId <= 0
                && ($canSuperviseAttendance ? $teacherPersonId <= 0 : $courseId > 0);

            if ($shouldCompleteMonthlyRows) {
                $reportRows = $this->completeMonthlyAttendanceRows(
                    $reportRows,
                    $attendanceModel->activeStudentsForAttendanceReport($periodId, $courseId)
                );
            }
        }

        $viewData = [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => 'Reporte de asistencia',
            'currentModule' => 'academico',
            'currentSection' => 'reporte_asistencia',
            'user' => $user,
            'currentPeriod' => $period,
            'selectedMonth' => $selectedMonth,
            'availableMonths' => $availableMonths,
            'classDateRange' => $classDateRange,
            'customRange' => $customRange,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'selectedCourseId' => $courseId,
            'selectedStudentId' => $studentId,
            'selectedCourseSubjectId' => $courseSubjectId,
            'selectedTeacherPersonId' => $teacherPersonId,
            'courses' => $courses,
            'students' => $students,
            'courseSubjects' => $courseSubjects,
            'teachers' => $teachers,
            'canSuperviseAttendance' => $canSuperviseAttendance,
            'reportDates' => $reportDates,
            'reportRows' => $reportRows,
            'studentHourlyMatrix' => $studentHourlyMatrix,
            'success' => sessionFlash('success'),
            'error' => sessionFlash('error'),
        ];

        if ((string) ($_GET['pdf'] ?? '') === '1') {
            (new PdfReportService())->streamView(
                'pdf.reporte_asistencia',
                $viewData,
                'reporte-asistencia.pdf',
                'A4',
                count((array) $reportDates) > 12 ? 'landscape' : 'portrait'
            );
        }

        $this->view('asistencia.reportes', $viewData);
    }

    public function annulSession(): void
    {
        $user = $this->requireAuth();
        $period = currentAcademicPeriod();
        $sessionId = (int) ($_POST['sclid'] ?? 0);
        $reason = trim((string) ($_POST['sclmotivo_anulacion'] ?? ''));
        $date = $this->validDateOrToday((string) ($_POST['fecha'] ?? date('Y-m-d')));

        if ($period === null || $sessionId <= 0 || $reason === '') {
            sessionFlash('error', 'Debe seleccionar una sesion e ingresar el motivo de anulacion.');
            $this->redirect('/asistencia/supervision?fecha=' . $date);
        }

        $attendanceModel = new AttendanceModel();
        $session = $attendanceModel->sessionForSupervision($sessionId, (int) $period['pleid']);

        if ($session === false) {
            sessionFlash('error', 'La sesion seleccionada no pertenece al periodo actual.');
            $this->redirect('/asistencia/supervision?fecha=' . $date);
        }

        try {
            $attendanceModel->annulSession($sessionId, (int) ($user['usuid'] ?? 0), $reason);
            sessionFlash('success', 'Sesion anulada correctamente.');
        } catch (Throwable $exception) {
            sessionFlash('error', $exception->getMessage());
        }

        $this->redirect('/asistencia/supervision?fecha=' . (string) $session['cafecha'] . '&curid=' . (string) $session['curid']);
    }

    public function storeJustification(): void
    {
        $user = $this->requireAuth();
        $period = currentAcademicPeriod();

        if ($period === null) {
            sessionFlash('error', 'Debe seleccionar un periodo lectivo antes de registrar justificaciones.');
            $this->redirect('/asistencia/justificaciones');
        }

        $mode = (string) ($_POST['modo_justificacion'] ?? 'anticipada');
        $reason = trim((string) ($_POST['jamotivo'] ?? ''));
        $note = trim((string) ($_POST['jaobservacion'] ?? ''));
        $courseId = (int) ($_POST['curid'] ?? 0);
        $redirectQuery = $courseId > 0 ? '?curid=' . $courseId : '';

        if ($reason === '') {
            sessionFlash('error', 'Debe ingresar el motivo de la justificacion.');
            $this->redirect('/asistencia/justificaciones' . $redirectQuery);
        }

        $documentPath = null;

        try {
            $attendanceModel = new AttendanceModel();

            if ($mode === 'posterior') {
                $absenceIds = is_array($_POST['faltas'] ?? null) ? $_POST['faltas'] : [];
                $absences = $attendanceModel->unjustifiedAbsencesByIds((int) $period['pleid'], $absenceIds);

                if ($absences === []) {
                    sessionFlash('error', 'Debe seleccionar al menos una falta injustificada.');
                    $this->redirect('/asistencia/justificaciones' . $redirectQuery);
                }

                $studentIds = array_unique(array_map(static fn (array $row): int => (int) $row['estid'], $absences));

                if (count($studentIds) !== 1) {
                    sessionFlash('error', 'Seleccione faltas de un solo estudiante para una misma justificacion.');
                    $this->redirect('/asistencia/justificaciones' . $redirectQuery);
                }

                $studentId = (int) $studentIds[0];
                $matriculationId = (int) ($absences[0]['matid'] ?? 0);
                $dates = array_map(static fn (array $row): string => (string) $row['cafecha'], $absences);
                sort($dates);
                $startDate = (string) reset($dates);
                $endDate = (string) end($dates);
            } else {
                $studentKey = explode('|', (string) ($_POST['estudiante'] ?? ''));
                $studentId = (int) ($studentKey[0] ?? 0);
                $matriculationId = (int) ($studentKey[1] ?? 0);
                $startDate = $this->validDateOrToday((string) ($_POST['jafecha_inicio'] ?? ''));
                $endDate = $this->validDateOrToday((string) ($_POST['jafecha_fin'] ?? $startDate));

                if ($studentId <= 0 || $endDate < $startDate) {
                    sessionFlash('error', 'Debe seleccionar estudiante y fechas validas.');
                    $this->redirect('/asistencia/justificaciones' . $redirectQuery);
                }
            }

            $documentPath = storeJustificationDocumentFile(
                is_array($_FILES['jaarchivo'] ?? null) ? $_FILES['jaarchivo'] : ['error' => UPLOAD_ERR_NO_FILE],
                'estudiante-' . (string) $studentId
            );

            $attendanceModel->createJustification([
                'estid' => $studentId,
                'matid' => $matriculationId,
                'jafecha_inicio' => $startDate,
                'jafecha_fin' => $endDate,
                'jatipo' => $startDate === $endDate ? 'DIA' : 'RANGO',
                'jamotivo' => $reason,
                'jaobservacion' => $note,
                'jaarchivo' => $documentPath ?? '',
                'jaestado' => 'APROBADA',
                'usuid' => (int) ($user['usuid'] ?? 0),
            ]);
            sessionFlash('success', 'Justificacion registrada y aplicada correctamente.');
        } catch (Throwable $exception) {
            if ($documentPath !== null) {
                deleteManagedJustificationDocumentFile($documentPath);
            }

            sessionFlash('error', 'No se pudo registrar la justificacion: ' . $exception->getMessage());
        }

        $this->redirect('/asistencia/justificaciones' . $redirectQuery);
    }

    public function reviewJustification(): void
    {
        $user = $this->requireAuth();
        $justificationId = (int) ($_POST['jaid'] ?? 0);
        $status = (string) ($_POST['jaestado'] ?? '');
        $note = trim((string) ($_POST['jaobservacion_revision'] ?? ''));

        if ($justificationId <= 0 || !in_array($status, ['APROBADA', 'RECHAZADA'], true)) {
            sessionFlash('error', 'La revision solicitada no es valida.');
            $this->redirect('/asistencia/justificaciones');
        }

        try {
            (new AttendanceModel())->reviewJustification($justificationId, $status, (int) ($user['usuid'] ?? 0), $note);
            sessionFlash('success', 'Justificacion revisada correctamente.');
        } catch (Throwable $exception) {
            sessionFlash('error', $exception->getMessage());
        }

        $this->redirect('/asistencia/justificaciones');
    }

    public function confirmJustification(): void
    {
        $user = $this->requireAuth();
        $justificationId = (int) ($_POST['jaid'] ?? 0);

        if ($justificationId <= 0) {
            sessionFlash('error', 'La justificacion seleccionada no es valida.');
            $this->redirect('/asistencia/justificaciones');
        }

        try {
            (new AttendanceModel())->confirmJustification($justificationId, (int) ($user['usuid'] ?? 0));
            sessionFlash('success', 'Justificacion confirmada correctamente.');
        } catch (Throwable $exception) {
            sessionFlash('error', $exception->getMessage());
        }

        $this->redirect('/asistencia/justificaciones');
    }

    public function annulJustification(): void
    {
        $user = $this->requireAuth();
        $justificationId = (int) ($_POST['jaid'] ?? 0);
        $reason = trim((string) ($_POST['jamotivo_anulacion'] ?? ''));

        if ($justificationId <= 0 || $reason === '') {
            sessionFlash('error', 'Debe ingresar un motivo para anular la justificacion.');
            $this->redirect('/asistencia/justificaciones');
        }

        try {
            (new AttendanceModel())->annulJustification($justificationId, (int) ($user['usuid'] ?? 0), $reason);
            sessionFlash('success', 'Justificacion anulada correctamente.');
        } catch (Throwable $exception) {
            sessionFlash('error', $exception->getMessage());
        }

        $this->redirect('/asistencia/justificaciones');
    }

    public function saveCalendar(): void
    {
        $user = $this->requireAuth();
        $period = currentAcademicPeriod();

        if ($period === null) {
            sessionFlash('error', 'Debe seleccionar un periodo lectivo antes de configurar el calendario.');
            $this->redirect('/asistencia/calendario');
        }

        $date = $this->validDateOrToday((string) ($_POST['cafecha'] ?? ''));
        $dates = is_array($_POST['fechas'] ?? null)
            ? array_values(array_unique(array_map(
                fn ($value): string => $this->validDateOrToday((string) $value),
                $_POST['fechas']
            )))
            : [];

        if ($dates === []) {
            $dates = [$date];
        }

        $month = substr($date, 0, 7);
        $type = (string) ($_POST['catipo_jornada'] ?? 'NORMAL');
        $hourLimitInput = trim((string) ($_POST['cahora_limite'] ?? ''));
        $hourLimit = $hourLimitInput !== '' ? (int) $hourLimitInput : null;
        $note = trim((string) ($_POST['caobservacion'] ?? ''));
        $details = is_array($_POST['detalle'] ?? null) ? $_POST['detalle'] : [];

        try {
            $attendanceModel = new AttendanceModel();

            foreach ($dates as $selectedDate) {
                if (!$attendanceModel->dateIsInsideClassRange((int) $period['pleid'], $selectedDate)) {
                    sessionFlash('error', 'Una de las fechas seleccionadas esta fuera del rango de clases configurado.');
                    $this->redirect('/asistencia/calendario?mes=' . $month . '#calendario-mes');
                }

                if ($attendanceModel->calendarDateHasAttendance((int) $period['pleid'], $selectedDate)) {
                    sessionFlash('error', 'No se puede editar una fecha que ya tiene asistencia registrada.');
                    $this->redirect('/asistencia/calendario?mes=' . $month . '#calendario-mes');
                }
            }

            foreach ($dates as $selectedDate) {
                $attendanceModel->saveCalendarDay(
                    (int) $period['pleid'],
                    $selectedDate,
                    $type,
                    $hourLimit,
                    $note,
                    (int) ($user['usuid'] ?? 0),
                    $details
                );
            }

            if (count($dates) > 1) {
                sessionFlash('success', 'Calendario de asistencia actualizado para las fechas seleccionadas.');
                $this->redirect('/asistencia/calendario?mes=' . $month . '#calendario-mes');
            }

            sessionFlash('success', 'Calendario de asistencia actualizado correctamente.');
        } catch (Throwable $exception) {
            sessionFlash('error', 'No se pudo guardar el calendario de asistencia: ' . $exception->getMessage());
        }

        $this->redirect('/asistencia/calendario?mes=' . $month . '&fecha=' . $date . '#calendario-mes');
    }

    public function openSession(): void
    {
        $user = $this->requireAuth();
        $period = currentAcademicPeriod();

        if ($period === null) {
            sessionFlash('error', 'Debe seleccionar un periodo lectivo antes de registrar asistencia.');
            $this->redirect('/asistencia/registro');
        }

        $assignmentKey = explode('|', (string) ($_POST['asignacion'] ?? ''));
        $assignmentId = (int) ($assignmentKey[0] ?? 0);
        $courseSubjectId = (int) ($assignmentKey[1] ?? 0);
        $courseId = max(0, (int) ($_POST['curid'] ?? 0));
        $hour = (int) ($_POST['sclnumero_hora'] ?? 0);
        $date = $this->validDateOrToday((string) ($_POST['cafecha'] ?? ''));
        $nextAction = (string) ($_POST['next_action'] ?? 'attendance');
        $courseQuery = $courseId > 0 ? '&curid=' . $courseId : '';

        if ($courseSubjectId <= 0 || $assignmentId <= 0 || $hour < 1 || $hour > 7) {
            sessionFlash('error', 'Debe seleccionar una materia asignada y una hora valida.');
            $this->redirect('/asistencia/registro?fecha=' . $date . $courseQuery);
        }

        $attendanceModel = new AttendanceModel();
        $assignment = $attendanceModel->findTeacherCourseSubject(
            $courseSubjectId,
            $assignmentId,
            (int) ($user['perid'] ?? 0),
            (int) $period['pleid'],
            $date
        );

        if ($assignment === false) {
            sessionFlash('error', 'La materia seleccionada no esta asignada a su usuario para esa fecha.');
            $this->redirect('/asistencia/registro?fecha=' . $date . $courseQuery);
        }

        if ($courseId > 0 && (int) ($assignment['curid'] ?? 0) !== $courseId) {
            sessionFlash('error', 'La materia seleccionada no pertenece al curso solicitado.');
            $this->redirect('/asistencia/registro?fecha=' . $date . $courseQuery);
        }

        $calendarId = $attendanceModel->findCalendarDayId((int) $period['pleid'], $date);

        if ($calendarId === null) {
            sessionFlash('error', 'El dia no esta habilitado para registrar asistencia.');
            $this->redirect('/asistencia/registro?fecha=' . $date . $courseQuery);
        }

        if (!$attendanceModel->dateIsInsideClassRange((int) $period['pleid'], $date)) {
            sessionFlash('error', 'La fecha seleccionada esta fuera del rango de clases configurado.');
            $this->redirect('/asistencia/registro?fecha=' . $date . $courseQuery);
        }

        if (!$attendanceModel->calendarAllowsSession($calendarId, (int) $assignment['curid'], $hour)) {
            sessionFlash('error', 'La jornada no permite registrar asistencia para esa hora y curso.');
            $this->redirect('/asistencia/registro?fecha=' . $date . $courseQuery);
        }

        try {
            $sessionId = $attendanceModel->createOrFindSession(
                $calendarId,
                $courseSubjectId,
                $assignmentId,
                $hour,
                (int) ($user['usuid'] ?? 0)
            );
        } catch (Throwable) {
            sessionFlash('error', 'No se pudo abrir la sesion de asistencia.');
            $this->redirect('/asistencia/registro?fecha=' . $date . $courseQuery);
        }

        if ($nextAction === 'novelty') {
            $this->redirect('/asistencia/registro?sclid=' . $sessionId . $courseQuery . '&accion=novedad#novedad-dialog');
        }

        $this->redirect('/asistencia/registro?sclid=' . $sessionId . $courseQuery . '#registro');
    }

    public function saveRegister(): void
    {
        $user = $this->requireAuth();
        $sessionId = (int) ($_POST['sclid'] ?? 0);
        $attendanceModel = new AttendanceModel();
        $session = $attendanceModel->sessionForTeacher($sessionId, (int) ($user['perid'] ?? 0));

        if ($session === false) {
            sessionFlash('error', 'La sesion de asistencia seleccionada no es valida.');
            $this->redirect('/asistencia/registro');
        }

        $sessionCourseQuery = (int) ($session['curid'] ?? 0) > 0 ? '&curid=' . (int) $session['curid'] : '';

        if (($session['sclestado'] ?? '') === 'CERRADA') {
            sessionFlash('error', 'La sesion ya esta cerrada y no permite cambios.');
            $this->redirect('/asistencia/registro?sclid=' . $sessionId . $sessionCourseQuery . '#registro');
        }

        if (!$attendanceModel->calendarAllowsSession((int) $session['caid'], (int) $session['curid'], (int) $session['sclnumero_hora'])) {
            sessionFlash('error', 'La jornada ya no permite registrar asistencia para esa hora y curso.');
            $this->redirect('/asistencia/registro?sclid=' . $sessionId . $sessionCourseQuery . '#registro');
        }

        $students = $attendanceModel->activeStudentsForSession($sessionId);

        if ($students === []) {
            sessionFlash('error', 'No hay estudiantes activos para registrar en esta sesion.');
            $this->redirect('/asistencia/registro?sclid=' . $sessionId . $sessionCourseQuery . '#registro');
        }

        $allowedStudentIds = array_map(static fn (array $student): int => (int) $student['estid'], $students);
        $states = is_array($_POST['estado'] ?? null) ? $_POST['estado'] : [];
        $notes = is_array($_POST['observacion'] ?? null) ? $_POST['observacion'] : [];
        $records = [];

        foreach ($allowedStudentIds as $studentId) {
            $records[] = [
                'estid' => $studentId,
                'aesestado' => (string) ($states[$studentId] ?? 'ASISTENCIA'),
                'aesobservacion' => (string) ($notes[$studentId] ?? ''),
            ];
        }

        try {
            $attendanceModel->saveAttendance($sessionId, $records, (int) ($user['usuid'] ?? 0));
            sessionFlash('success', 'Asistencia guardada correctamente.');
        } catch (Throwable $exception) {
            sessionFlash('error', $exception->getMessage());
            $this->redirect('/asistencia/registro?sclid=' . $sessionId . $sessionCourseQuery . '#registro');
        }

        $date = (string) ($session['cafecha'] ?? date('Y-m-d'));
        $courseId = (int) ($session['curid'] ?? 0);
        $this->redirect('/asistencia/registro?mes=' . substr($date, 0, 7) . '&fecha=' . $date . ($courseId > 0 ? '&curid=' . $courseId : '') . '#calendario-docente');
    }

    public function closeSession(): void
    {
        $user = $this->requireAuth();
        $sessionId = (int) ($_POST['sclid'] ?? 0);

        if ($sessionId <= 0) {
            sessionFlash('error', 'La sesion de asistencia seleccionada no es valida.');
            $this->redirect('/asistencia/registro');
        }

        $attendanceModel = new AttendanceModel();
        $session = $attendanceModel->sessionForTeacher($sessionId, (int) ($user['perid'] ?? 0));

        if ($session === false) {
            sessionFlash('error', 'La sesion de asistencia seleccionada no pertenece a sus asignaciones.');
            $this->redirect('/asistencia/registro');
        }

        $sessionCourseQuery = (int) ($session['curid'] ?? 0) > 0 ? '&curid=' . (int) $session['curid'] : '';
        $students = $attendanceModel->activeStudentsForSession($sessionId);
        $attendance = $attendanceModel->attendanceBySession($sessionId);

        if ($students === []) {
            sessionFlash('error', 'No hay estudiantes activos para cerrar esta sesion.');
            $this->redirect('/asistencia/registro?sclid=' . $sessionId . $sessionCourseQuery . '#registro');
        }

        if (count($attendance) < count($students)) {
            sessionFlash('error', 'Debe guardar asistencia para todos los estudiantes activos antes de cerrar la sesion.');
            $this->redirect('/asistencia/registro?sclid=' . $sessionId . $sessionCourseQuery . '#registro');
        }

        try {
            $attendanceModel->closeTeacherSession($sessionId, (int) ($user['perid'] ?? 0));
            sessionFlash('success', 'Sesion de asistencia cerrada correctamente.');
        } catch (Throwable $exception) {
            sessionFlash('error', $exception->getMessage());
        }

        $this->redirect('/asistencia/registro?sclid=' . $sessionId . $sessionCourseQuery . '#registro');
    }

    public function storeArea(): void
    {
        $this->requireAuth();

        $name = trim((string) ($_POST['areanombre'] ?? ''));

        if ($name === '') {
            sessionFlash('error', 'El nombre del area es obligatorio.');
            $this->redirect('/configuracion/academica?view=areas');
        }

        try {
            (new AttendanceModel())->createArea($name);
            sessionFlash('success', 'Area academica registrada correctamente.');
        } catch (Throwable) {
            sessionFlash('error', 'No se pudo registrar el area. Revise si ya existe con el mismo nombre.');
        }

        $this->redirect('/configuracion/academica?view=areas');
    }

    public function updateArea(): void
    {
        $this->requireAuth();

        $areaId = (int) ($_POST['areaid'] ?? 0);
        $name = trim((string) ($_POST['areanombre'] ?? ''));

        if ($areaId <= 0 || $name === '') {
            sessionFlash('error', 'Los datos del area no son validos.');
            $this->redirect('/configuracion/academica?view=areas');
        }

        try {
            (new AttendanceModel())->updateArea($areaId, $name);
            sessionFlash('success', 'Area academica actualizada correctamente.');
        } catch (Throwable) {
            sessionFlash('error', 'No se pudo actualizar el area. Revise si ya existe con el mismo nombre.');
        }

        $this->redirect('/configuracion/academica?view=areas');
    }

    public function toggleArea(): void
    {
        $this->requireAuth();

        $areaId = (int) ($_POST['areaid'] ?? 0);
        $status = ($_POST['areaestado'] ?? '0') === '1';

        if ($areaId <= 0) {
            sessionFlash('error', 'El area seleccionada no es valida.');
            $this->redirect('/configuracion/academica?view=areas');
        }

        (new AttendanceModel())->updateAreaStatus($areaId, $status);
        sessionFlash('success', 'Estado del area actualizado correctamente.');
        $this->redirect('/configuracion/academica?view=areas');
    }

    public function storeSubject(): void
    {
        $this->requireAuth();

        $areaId = (int) ($_POST['areaid'] ?? 0);
        $name = trim((string) ($_POST['asgnombre'] ?? ''));

        if ($areaId <= 0 || $name === '') {
            sessionFlash('error', 'Debe seleccionar area e ingresar el nombre de la asignatura.');
            $this->redirect('/configuracion/academica?view=asignaturas');
        }

        try {
            (new AttendanceModel())->createSubject($areaId, $name);
            sessionFlash('success', 'Asignatura registrada correctamente.');
        } catch (Throwable) {
            sessionFlash('error', 'No se pudo registrar la asignatura. Revise si ya existe en esa area.');
        }

        $this->redirect('/configuracion/academica?view=asignaturas');
    }

    public function updateSubject(): void
    {
        $this->requireAuth();

        $subjectId = (int) ($_POST['asgid'] ?? 0);
        $areaId = (int) ($_POST['areaid'] ?? 0);
        $name = trim((string) ($_POST['asgnombre'] ?? ''));

        if ($subjectId <= 0 || $areaId <= 0 || $name === '') {
            sessionFlash('error', 'Los datos de la asignatura no son validos.');
            $this->redirect('/configuracion/academica?view=asignaturas');
        }

        try {
            (new AttendanceModel())->updateSubject($subjectId, $areaId, $name);
            sessionFlash('success', 'Asignatura actualizada correctamente.');
        } catch (Throwable) {
            sessionFlash('error', 'No se pudo actualizar la asignatura. Revise si ya existe en esa area.');
        }

        $this->redirect('/configuracion/academica?view=asignaturas');
    }

    public function toggleSubject(): void
    {
        $this->requireAuth();

        $subjectId = (int) ($_POST['asgid'] ?? 0);
        $status = ($_POST['asgestado'] ?? '0') === '1';

        if ($subjectId <= 0) {
            sessionFlash('error', 'La asignatura seleccionada no es valida.');
            $this->redirect('/configuracion/academica?view=asignaturas');
        }

        (new AttendanceModel())->updateSubjectStatus($subjectId, $status);
        sessionFlash('success', 'Estado de la asignatura actualizado correctamente.');
        $this->redirect('/configuracion/academica?view=asignaturas');
    }

    public function storeCourseSubject(): void
    {
        $this->requireAuth();

        $period = currentAcademicPeriod();

        if ($period === null) {
            sessionFlash('error', 'Debe seleccionar un periodo lectivo antes de registrar materias.');
            $this->redirect('/configuracion/academica?view=materias');
        }

        $courseId = (int) ($_POST['curid'] ?? 0);
        $subjectIds = is_array($_POST['asgid'] ?? null)
            ? array_values(array_filter(array_map('intval', $_POST['asgid']), static fn (int $id): bool => $id > 0))
            : [(int) ($_POST['asgid'] ?? 0)];
        $startDate = $this->validDateOrToday((string) ($_POST['mtcfecha_inicio'] ?? ''));
        $order = trim((string) ($_POST['mtcorden'] ?? '')) !== '' ? max(1, (int) $_POST['mtcorden']) : null;

        if ($courseId <= 0) {
            sessionFlash('error', 'Debe seleccionar un curso valido.');
            $this->redirect('/configuracion/academica?view=materias');
        }

        $course = (new CourseModel())->findDetailed($courseId);

        if ($course === false || (int) $course['pleid'] !== (int) $period['pleid']) {
            sessionFlash('error', 'El curso seleccionado no pertenece al periodo actual.');
            $this->redirect('/configuracion/academica?view=materias');
        }

        $attendanceModel = new AttendanceModel();

        try {
            $result = $attendanceModel->syncCourseSubjects($courseId, $subjectIds, $startDate, $order);
            $message = 'Materias registradas: ' . (string) $result['created'] . '.';

            if ((int) $result['removed'] > 0) {
                $message .= ' Materias retiradas: ' . (string) $result['removed'] . '.';
            }

            if ((int) $result['created'] === 0 && (int) $result['removed'] === 0) {
                $message .= ' Sin cambios.';
            }

            sessionFlash('success', $message);
        } catch (Throwable) {
            sessionFlash('error', 'No se pudieron actualizar las materias del curso.');
        }

        $this->redirect('/configuracion/academica?view=materias');
    }

    public function toggleCourseSubject(): void
    {
        $this->requireAuth();

        $period = currentAcademicPeriod();
        $courseSubjectId = (int) ($_POST['mtcid'] ?? 0);
        $status = ($_POST['mtcestado'] ?? '0') === '1';
        $attendanceModel = new AttendanceModel();
        $courseSubject = $attendanceModel->findCourseSubject($courseSubjectId);

        if ($period === null || $courseSubject === false || (int) $courseSubject['pleid'] !== (int) $period['pleid']) {
            sessionFlash('error', 'La materia seleccionada no es valida para el periodo actual.');
            $this->redirect('/configuracion/academica?view=materias');
        }

        try {
            $attendanceModel->updateCourseSubjectStatus($courseSubjectId, $status);
            sessionFlash('success', 'Estado de la materia actualizado correctamente.');
        } catch (Throwable) {
            sessionFlash('error', 'No se pudo actualizar la materia. Revise si ya existe otra materia activa igual.');
        }

        $this->redirect('/configuracion/academica?view=materias');
    }

    public function assignTeacher(): void
    {
        $this->requireAuth();

        $period = currentAcademicPeriod();
        $courseSubjectIds = is_array($_POST['mtcid'] ?? null)
            ? array_values(array_filter(array_map('intval', $_POST['mtcid']), static fn (int $id): bool => $id > 0))
            : [(int) ($_POST['mtcid'] ?? 0)];
        $personId = (int) ($_POST['perid'] ?? 0);
        $startDate = $this->validDateOrToday((string) ($_POST['mcdfecha_inicio'] ?? ''));
        $attendanceModel = new AttendanceModel();

        if ($period === null || $courseSubjectIds === []) {
            sessionFlash('error', 'Seleccione al menos una materia valida para el periodo actual.');
            $this->redirect('/configuracion/academica?view=docentes');
        }

        if ($personId <= 0 || !(new PersonalModel())->personHasActiveStaffType($personId, 'Docente')) {
            sessionFlash('error', 'Debe seleccionar un docente activo.');
            $this->redirect('/configuracion/academica?view=docentes');
        }

        foreach ($courseSubjectIds as $courseSubjectId) {
            $courseSubject = $attendanceModel->findCourseSubject($courseSubjectId);

            if ($courseSubject === false || (int) $courseSubject['pleid'] !== (int) $period['pleid']) {
                sessionFlash('error', 'Una materia seleccionada no es valida para el periodo actual.');
                $this->redirect('/configuracion/academica?view=docentes');
            }
        }

        try {
            $result = $attendanceModel->assignTeacherBulk($courseSubjectIds, $personId, $startDate);
            $message = 'Designaciones registradas: ' . (string) $result['created'] . '.';

            if ((int) $result['skipped'] > 0) {
                $message .= ' Ya existian activas: ' . (string) $result['skipped'] . '.';
            }

            sessionFlash((int) $result['created'] > 0 ? 'success' : 'error', $message);
        } catch (Throwable) {
            sessionFlash('error', 'No se pudieron asignar las materias. Revise si ya existen designaciones activas.');
        }

        $this->redirect('/configuracion/academica?view=docentes');
    }

    public function removeTeacher(): void
    {
        $this->requireAuth();

        $period = currentAcademicPeriod();
        $assignmentId = (int) ($_POST['mcdid'] ?? 0);
        $attendanceModel = new AttendanceModel();
        $assignment = $attendanceModel->findTeacherAssignment($assignmentId);

        if ($period === null || $assignment === false || (int) $assignment['pleid'] !== (int) $period['pleid']) {
            sessionFlash('error', 'La asignacion seleccionada no es valida para el periodo actual.');
            $this->redirect('/configuracion/academica?view=docentes');
        }

        $attendanceModel->removeTeacher($assignmentId);
        sessionFlash('success', 'Docente retirado de la materia correctamente.');
        $this->redirect('/configuracion/academica?view=docentes');
    }

    private function validDateOrToday(string $date): string
    {
        $date = trim($date);

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1) {
            return $date;
        }

        return date('Y-m-d');
    }

    private function validMonthOrCurrent(string $month): string
    {
        $month = trim($month);

        if (preg_match('/^\d{4}-\d{2}$/', $month) === 1) {
            return $month;
        }

        return date('Y-m');
    }

    private function monthsBetween(string $startDate, string $endDate): array
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
            return [];
        }

        $start = new \DateTimeImmutable(substr($startDate, 0, 7) . '-01');
        $end = new \DateTimeImmutable(substr($endDate, 0, 7) . '-01');

        if ($end < $start) {
            return [];
        }

        $months = [];

        for ($cursor = $start; $cursor <= $end; $cursor = $cursor->modify('+1 month')) {
            $months[] = $cursor->format('Y-m');
        }

        return $months;
    }

    private function nearestMonthInRange(string $month, array $availableMonths): string
    {
        $firstMonth = (string) reset($availableMonths);
        $lastMonth = (string) end($availableMonths);

        if ($month < $firstMonth) {
            return $firstMonth;
        }

        if ($month > $lastMonth) {
            return $lastMonth;
        }

        return $firstMonth;
    }

    private function firstSelectableDateForMonth(string $monthStart, string $monthEnd, ?array $classDateRange): string
    {
        if ($classDateRange === null) {
            return $monthStart;
        }

        $rangeStart = (string) ($classDateRange['start'] ?? '');
        $rangeEnd = (string) ($classDateRange['end'] ?? '');

        if ($rangeStart === '' || $rangeEnd === '') {
            return $monthStart;
        }

        if ($rangeStart >= $monthStart && $rangeStart <= $monthEnd) {
            return $rangeStart;
        }

        if ($rangeEnd >= $monthStart && $rangeEnd <= $monthEnd) {
            return min($monthStart, $rangeEnd);
        }

        return $monthStart;
    }

    private function firstTeacherSelectableDateForMonth(array $teacherCalendarDays, string $monthStart, ?array $classDateRange): string
    {
        if ($teacherCalendarDays !== []) {
            return (string) array_key_first($teacherCalendarDays);
        }

        return $this->firstSelectableDateForMonth($monthStart, date('Y-m-t', strtotime($monthStart)), $classDateRange);
    }

    private function teacherCalendarDaysFromAvailability(array $availabilityRows): array
    {
        $days = [];

        foreach ($availabilityRows as $row) {
            $date = (string) ($row['cafecha'] ?? '');
            $hour = (int) ($row['sclnumero_hora'] ?? 0);

            if ($date === '' || $hour <= 0) {
                continue;
            }

            if (!isset($days[$date])) {
                $days[$date] = [
                    'cafecha' => $date,
                    'catipo_jornada' => (string) ($row['catipo_jornada'] ?? ''),
                    'hours' => [],
                ];
            }

            $days[$date]['hours'][$hour] = $hour;
        }

        foreach ($days as &$day) {
            $day['hours'] = array_values($day['hours']);
        }
        unset($day);

        return $days;
    }

    private function teacherSubjectHoursForDate(array $availabilityRows, string $date): array
    {
        $subjects = [];

        foreach ($availabilityRows as $row) {
            if ((string) ($row['cafecha'] ?? '') !== $date) {
                continue;
            }

            $assignmentId = (int) ($row['mcdid'] ?? 0);
            $courseSubjectId = (int) ($row['mtcid'] ?? 0);
            $key = $assignmentId . '|' . $courseSubjectId;
            $hour = (int) ($row['sclnumero_hora'] ?? 0);

            if ($assignmentId <= 0 || $courseSubjectId <= 0 || $hour <= 0) {
                continue;
            }

            if (!isset($subjects[$key])) {
                $subjects[$key] = [
                    'mcdid' => $assignmentId,
                    'mtcid' => $courseSubjectId,
                    'curid' => (int) ($row['curid'] ?? 0),
                    'mtcnombre_mostrar' => (string) ($row['mtcnombre_mostrar'] ?? ''),
                    'granombre' => (string) ($row['granombre'] ?? ''),
                    'prlnombre' => (string) ($row['prlnombre'] ?? ''),
                    'hours' => [],
                ];
            }

            $subjects[$key]['hours'][$hour] = $hour;
        }

        foreach ($subjects as &$subject) {
            $subject['hours'] = array_values($subject['hours']);
        }
        unset($subject);

        return $subjects;
    }

    private function teacherDayHourSubjectsFromAvailability(array $availabilityRows): array
    {
        $map = [];

        foreach ($availabilityRows as $row) {
            $date = (string) ($row['cafecha'] ?? '');
            $hour = (int) ($row['sclnumero_hora'] ?? 0);
            $assignmentId = (int) ($row['mcdid'] ?? 0);
            $courseSubjectId = (int) ($row['mtcid'] ?? 0);

            if ($date === '' || $hour <= 0 || $assignmentId <= 0 || $courseSubjectId <= 0) {
                continue;
            }

            $map[$date][$hour][] = [
                'value' => $assignmentId . '|' . $courseSubjectId,
                'label' => (string) ($row['mtcnombre_mostrar'] ?? ''),
            ];
        }

        return $map;
    }

    private function filterRowsByCourse(array $rows, int $courseId): array
    {
        if ($courseId <= 0) {
            return $rows;
        }

        return array_values(array_filter(
            $rows,
            static fn (array $row): bool => (int) ($row['curid'] ?? 0) === $courseId
        ));
    }

    private function completeMonthlyAttendanceRows(array $reportRows, array $students): array
    {
        $rowsByKey = [];

        foreach ($reportRows as $row) {
            $key = (string) ($row['estid'] ?? 0) . '-' . (string) ($row['curid'] ?? 0);
            $rowsByKey[$key] = $row;
        }

        foreach ($students as $student) {
            $key = (string) ($student['estid'] ?? 0) . '-' . (string) ($student['curid'] ?? 0);

            if (isset($rowsByKey[$key])) {
                continue;
            }

            $rowsByKey[$key] = [
                'estid' => (int) ($student['estid'] ?? 0),
                'curid' => (int) ($student['curid'] ?? 0),
                'percedula' => (string) ($student['percedula'] ?? ''),
                'perapellidos' => (string) ($student['perapellidos'] ?? ''),
                'pernombres' => (string) ($student['pernombres'] ?? ''),
                'granombre' => (string) ($student['granombre'] ?? ''),
                'prlnombre' => (string) ($student['prlnombre'] ?? ''),
                'dias' => [],
                'total_asistencias' => 0,
                'total_atrasos' => 0,
                'total_faltas_justificadas' => 0,
                'total_faltas_injustificadas' => 0,
            ];
        }

        usort(
            $rowsByKey,
            static fn (array $a, array $b): int => strcmp(
                (string) ($a['granombre'] ?? '') . (string) ($a['prlnombre'] ?? '') . (string) ($a['perapellidos'] ?? '') . (string) ($a['pernombres'] ?? ''),
                (string) ($b['granombre'] ?? '') . (string) ($b['prlnombre'] ?? '') . (string) ($b['perapellidos'] ?? '') . (string) ($b['pernombres'] ?? '')
            )
        );

        return array_values($rowsByKey);
    }

    private function dateInsideClassRange(string $date, ?array $classDateRange): bool
    {
        if ($classDateRange === null) {
            return true;
        }

        $rangeStart = (string) ($classDateRange['start'] ?? '');
        $rangeEnd = (string) ($classDateRange['end'] ?? '');

        return $rangeStart === '' || $rangeEnd === '' || ($date >= $rangeStart && $date <= $rangeEnd);
    }

    private function renderCourseSubjectRows(array $courseSubjects): string
    {
        $h = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

        ob_start();
        require BASE_PATH . '/app/views/configuracion/_materias_curso_rows.php';

        return (string) ob_get_clean();
    }

    private function studentAttendanceView(
        string $permission,
        string $title,
        string $section,
        int $studentId,
        ?array $period,
        array $user,
        array $availableStudents
    ): void {
        $periodId = $period !== null ? (int) $period['pleid'] : 0;
        $attendanceModel = new AttendanceModel();
        $noveltyModel = new NoveltyModel();
        $classDateRange = $periodId > 0 ? $attendanceModel->classDateRangeByPeriod($periodId) : null;
        $availableMonths = $classDateRange !== null
            ? $this->monthsBetween((string) $classDateRange['start'], (string) $classDateRange['end'])
            : [];
        $month = $this->validMonthOrCurrent((string) ($_GET['mes'] ?? date('Y-m')));

        if ($availableMonths !== [] && !in_array($month, $availableMonths, true)) {
            $month = $this->nearestMonthInRange($month, $availableMonths);
        }

        $selectedDate = trim((string) ($_GET['fecha'] ?? ''));
        $selectedDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate) === 1 ? $selectedDate : '';
        $monthStart = $month . '-01';
        $monthEnd = date('Y-m-t', strtotime($monthStart));

        $this->view('asistencia.consulta_estudiante', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => $title,
            'currentSection' => $section,
            'user' => $user,
            'currentPeriod' => $period,
            'permission' => $permission,
            'selectedMonth' => $month,
            'monthStart' => $monthStart,
            'monthEnd' => $monthEnd,
            'classDateRange' => $classDateRange,
            'availableMonths' => $availableMonths,
            'selectedDate' => $selectedDate,
            'selectedStudentId' => $studentId,
            'availableStudents' => $availableStudents,
            'summaryDays' => $periodId > 0 && $studentId > 0
                ? $attendanceModel->studentDailySummary($studentId, $periodId, $monthStart, $monthEnd)
                : [],
            'attendanceDetail' => $periodId > 0 && $studentId > 0 && $selectedDate !== ''
                ? $attendanceModel->studentAttendanceDetail($studentId, $periodId, $selectedDate)
                : [],
            'novelties' => $periodId > 0 && $studentId > 0
                ? ($section === 'asistencia_representante'
                    ? $noveltyModel->byRepresentative((int) ($user['perid'] ?? 0), $periodId, $studentId)
                    : $noveltyModel->byStudent($studentId, $periodId))
                : [],
            'success' => sessionFlash('success'),
            'error' => sessionFlash('error'),
        ]);
    }
}
