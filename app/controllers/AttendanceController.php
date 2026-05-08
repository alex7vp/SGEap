<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\AttendanceModel;
use App\Models\CourseModel;
use App\Models\PersonalModel;
use App\Models\StudentModel;
use Throwable;

class AttendanceController extends Controller
{
    public function configuration(): void
    {
        $user = $this->requireAuth();
        $period = currentAcademicPeriod();
        $attendanceModel = new AttendanceModel();
        $courseModel = new CourseModel();

        $periodId = $period !== null ? (int) $period['pleid'] : 0;

        $this->view('asistencia.configuracion', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => 'Configuracion de asistencia',
            'currentSection' => 'asistencia_configuracion',
            'user' => $user,
            'currentPeriod' => $period,
            'areas' => $attendanceModel->areas(),
            'activeAreas' => $attendanceModel->activeAreas(),
            'subjects' => $attendanceModel->subjects(),
            'activeSubjects' => $attendanceModel->activeSubjects(),
            'courses' => $periodId > 0 ? array_values(array_filter(
                $courseModel->allByPeriod($periodId),
                static fn (array $course): bool => !empty($course['curestado'])
            )) : [],
            'courseSubjects' => $periodId > 0 ? $attendanceModel->courseSubjectsByPeriod($periodId) : [],
            'teachers' => $attendanceModel->activeTeachers(),
            'teacherAssignments' => $periodId > 0 ? $attendanceModel->activeTeacherAssignmentsByCourseSubject($periodId) : [],
            'success' => sessionFlash('success'),
            'error' => sessionFlash('error'),
        ]);
    }

    public function register(): void
    {
        $user = $this->requireAuth();
        $period = currentAcademicPeriod();
        $attendanceModel = new AttendanceModel();
        $date = $this->validDateOrToday((string) ($_GET['fecha'] ?? date('Y-m-d')));
        $sessionId = (int) ($_GET['sclid'] ?? 0);
        $session = false;
        $students = [];
        $attendance = [];

        if ($sessionId > 0) {
            $session = $attendanceModel->sessionForTeacher($sessionId, (int) ($user['perid'] ?? 0));

            if ($session !== false) {
                $students = $attendanceModel->activeStudentsForSession($sessionId);
                $attendance = $attendanceModel->attendanceBySession($sessionId);
                $date = (string) $session['cafecha'];
            }
        }

        $periodId = $period !== null ? (int) $period['pleid'] : 0;

        $this->view('asistencia.registro', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => 'Registrar asistencia',
            'currentSection' => 'asistencia_registro',
            'user' => $user,
            'currentPeriod' => $period,
            'selectedDate' => $date,
            'selectedHour' => (string) ($_GET['hora'] ?? ''),
            'teacherSubjects' => $periodId > 0
                ? $attendanceModel->teacherCourseSubjects((int) ($user['perid'] ?? 0), $periodId, $date)
                : [],
            'session' => $session,
            'students' => $students,
            'attendance' => $attendance,
            'success' => sessionFlash('success'),
            'error' => sessionFlash('error'),
        ]);
    }

    public function calendar(): void
    {
        $user = $this->requireAuth();
        $period = currentAcademicPeriod();
        $attendanceModel = new AttendanceModel();
        $courseModel = new CourseModel();
        $month = $this->validMonthOrCurrent((string) ($_GET['mes'] ?? date('Y-m')));
        $date = $this->validDateOrToday((string) ($_GET['fecha'] ?? $month . '-01'));
        $monthStart = $month . '-01';
        $monthEnd = date('Y-m-t', strtotime($monthStart));
        $periodId = $period !== null ? (int) $period['pleid'] : 0;
        $calendarDay = $periodId > 0 ? $attendanceModel->calendarDayByDate($periodId, $date) : false;
        $calendarDetails = $calendarDay !== false
            ? $attendanceModel->calendarDetailsByDay((int) $calendarDay['caid'])
            : [];

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
            'calendarDay' => $calendarDay,
            'calendarDetails' => $calendarDetails,
            'calendarMonthDays' => $periodId > 0 ? $attendanceModel->calendarDaysByRange($periodId, $monthStart, $monthEnd) : [],
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
        $periodId = $period !== null ? (int) $period['pleid'] : 0;

        $this->view('asistencia.justificaciones', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => 'Justificaciones',
            'currentSection' => 'asistencia_justificaciones',
            'user' => $user,
            'currentPeriod' => $period,
            'students' => $periodId > 0 ? $attendanceModel->studentsForJustification($periodId) : [],
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
            'Mi asistencia',
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
            'Asistencia de representados',
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
        $startDate = $this->validDateOrToday((string) ($_GET['desde'] ?? date('Y-m-01')));
        $endDate = $this->validDateOrToday((string) ($_GET['hasta'] ?? date('Y-m-t')));
        $courseId = (int) ($_GET['curid'] ?? 0);
        $studentId = (int) ($_GET['estid'] ?? 0);
        $courseSubjectId = (int) ($_GET['mtcid'] ?? 0);
        $teacherPersonId = (int) ($_GET['perid_docente'] ?? 0);

        if ($endDate < $startDate) {
            $endDate = $startDate;
        }

        $courseModel = new CourseModel();
        $studentModel = new StudentModel();
        $attendanceModel = new AttendanceModel();

        $this->view('asistencia.reportes', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => 'Reporte de asistencia',
            'currentSection' => 'reporte_asistencia',
            'user' => $user,
            'currentPeriod' => $period,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'selectedCourseId' => $courseId,
            'selectedStudentId' => $studentId,
            'selectedCourseSubjectId' => $courseSubjectId,
            'selectedTeacherPersonId' => $teacherPersonId,
            'courses' => $periodId > 0 ? array_values(array_filter(
                $courseModel->allByPeriod($periodId),
                static fn (array $course): bool => !empty($course['curestado'])
            )) : [],
            'students' => $periodId > 0 ? $studentModel->allWithPerson($periodId) : [],
            'courseSubjects' => $periodId > 0 ? $attendanceModel->reportCourseSubjects($periodId) : [],
            'teachers' => $periodId > 0 ? $attendanceModel->reportTeachers($periodId) : [],
            'reportRows' => $periodId > 0
                ? $attendanceModel->consolidatedAttendanceReport(
                    $periodId,
                    $startDate,
                    $endDate,
                    $courseId,
                    $studentId,
                    $courseSubjectId,
                    $teacherPersonId
                )
                : [],
            'success' => sessionFlash('success'),
            'error' => sessionFlash('error'),
        ]);
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

        $studentKey = explode('|', (string) ($_POST['estudiante'] ?? ''));
        $studentId = (int) ($studentKey[0] ?? 0);
        $matriculationId = (int) ($studentKey[1] ?? 0);
        $startDate = $this->validDateOrToday((string) ($_POST['jafecha_inicio'] ?? ''));
        $endDate = $this->validDateOrToday((string) ($_POST['jafecha_fin'] ?? $startDate));
        $reason = trim((string) ($_POST['jamotivo'] ?? ''));
        $note = trim((string) ($_POST['jaobservacion'] ?? ''));

        if ($studentId <= 0 || $reason === '' || $endDate < $startDate) {
            sessionFlash('error', 'Debe seleccionar estudiante, fechas validas y motivo.');
            $this->redirect('/asistencia/justificaciones');
        }

        try {
            (new AttendanceModel())->createJustification([
                'estid' => $studentId,
                'matid' => $matriculationId,
                'jafecha_inicio' => $startDate,
                'jafecha_fin' => $endDate,
                'jatipo' => $startDate === $endDate ? 'DIA' : 'RANGO',
                'jamotivo' => $reason,
                'jaobservacion' => $note,
                'usuid' => (int) ($user['usuid'] ?? 0),
            ]);
            sessionFlash('success', 'Justificacion registrada correctamente.');
        } catch (Throwable) {
            sessionFlash('error', 'No se pudo registrar la justificacion.');
        }

        $this->redirect('/asistencia/justificaciones');
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
        $month = substr($date, 0, 7);
        $type = (string) ($_POST['catipo_jornada'] ?? 'NORMAL');
        $hourLimitInput = trim((string) ($_POST['cahora_limite'] ?? ''));
        $hourLimit = $hourLimitInput !== '' ? (int) $hourLimitInput : null;
        $note = trim((string) ($_POST['caobservacion'] ?? ''));
        $details = is_array($_POST['detalle'] ?? null) ? $_POST['detalle'] : [];

        try {
            (new AttendanceModel())->saveCalendarDay(
                (int) $period['pleid'],
                $date,
                $type,
                $hourLimit,
                $note,
                (int) ($user['usuid'] ?? 0),
                $details
            );
            sessionFlash('success', 'Calendario de asistencia actualizado correctamente.');
        } catch (Throwable) {
            sessionFlash('error', 'No se pudo guardar el calendario de asistencia.');
        }

        $this->redirect('/asistencia/calendario?mes=' . $month . '&fecha=' . $date);
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
        $hour = (int) ($_POST['sclnumero_hora'] ?? 0);
        $date = $this->validDateOrToday((string) ($_POST['cafecha'] ?? ''));

        if ($courseSubjectId <= 0 || $assignmentId <= 0 || $hour < 1 || $hour > 7) {
            sessionFlash('error', 'Debe seleccionar una materia asignada y una hora valida.');
            $this->redirect('/asistencia/registro?fecha=' . $date);
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
            $this->redirect('/asistencia/registro?fecha=' . $date);
        }

        $calendarId = $attendanceModel->findCalendarDayId((int) $period['pleid'], $date);

        if ($calendarId === null) {
            sessionFlash('error', 'El dia no esta habilitado para registrar asistencia.');
            $this->redirect('/asistencia/registro?fecha=' . $date);
        }

        if (!$attendanceModel->calendarAllowsSession($calendarId, (int) $assignment['curid'], $hour)) {
            sessionFlash('error', 'La jornada no permite registrar asistencia para esa hora y curso.');
            $this->redirect('/asistencia/registro?fecha=' . $date);
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
            $this->redirect('/asistencia/registro?fecha=' . $date);
        }

        $this->redirect('/asistencia/registro?sclid=' . $sessionId . '#registro');
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

        if (($session['sclestado'] ?? '') === 'CERRADA') {
            sessionFlash('error', 'La sesion ya esta cerrada y no permite cambios.');
            $this->redirect('/asistencia/registro?sclid=' . $sessionId . '#registro');
        }

        if (!$attendanceModel->calendarAllowsSession((int) $session['caid'], (int) $session['curid'], (int) $session['sclnumero_hora'])) {
            sessionFlash('error', 'La jornada ya no permite registrar asistencia para esa hora y curso.');
            $this->redirect('/asistencia/registro?sclid=' . $sessionId . '#registro');
        }

        $students = $attendanceModel->activeStudentsForSession($sessionId);

        if ($students === []) {
            sessionFlash('error', 'No hay estudiantes activos para registrar en esta sesion.');
            $this->redirect('/asistencia/registro?sclid=' . $sessionId . '#registro');
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
        }

        $this->redirect('/asistencia/registro?sclid=' . $sessionId . '#registro');
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

        $students = $attendanceModel->activeStudentsForSession($sessionId);
        $attendance = $attendanceModel->attendanceBySession($sessionId);

        if ($students === []) {
            sessionFlash('error', 'No hay estudiantes activos para cerrar esta sesion.');
            $this->redirect('/asistencia/registro?sclid=' . $sessionId . '#registro');
        }

        if (count($attendance) < count($students)) {
            sessionFlash('error', 'Debe guardar asistencia para todos los estudiantes activos antes de cerrar la sesion.');
            $this->redirect('/asistencia/registro?sclid=' . $sessionId . '#registro');
        }

        try {
            $attendanceModel->closeTeacherSession($sessionId, (int) ($user['perid'] ?? 0));
            sessionFlash('success', 'Sesion de asistencia cerrada correctamente.');
        } catch (Throwable $exception) {
            sessionFlash('error', $exception->getMessage());
        }

        $this->redirect('/asistencia/registro?sclid=' . $sessionId . '#registro');
    }

    public function storeArea(): void
    {
        $this->requireAuth();

        $name = trim((string) ($_POST['areanombre'] ?? ''));

        if ($name === '') {
            sessionFlash('error', 'El nombre del area es obligatorio.');
            $this->redirect('/asistencia/configuracion#areas');
        }

        try {
            (new AttendanceModel())->createArea($name);
            sessionFlash('success', 'Area academica registrada correctamente.');
        } catch (Throwable) {
            sessionFlash('error', 'No se pudo registrar el area. Revise si ya existe con el mismo nombre.');
        }

        $this->redirect('/asistencia/configuracion#areas');
    }

    public function updateArea(): void
    {
        $this->requireAuth();

        $areaId = (int) ($_POST['areaid'] ?? 0);
        $name = trim((string) ($_POST['areanombre'] ?? ''));

        if ($areaId <= 0 || $name === '') {
            sessionFlash('error', 'Los datos del area no son validos.');
            $this->redirect('/asistencia/configuracion#areas');
        }

        try {
            (new AttendanceModel())->updateArea($areaId, $name);
            sessionFlash('success', 'Area academica actualizada correctamente.');
        } catch (Throwable) {
            sessionFlash('error', 'No se pudo actualizar el area. Revise si ya existe con el mismo nombre.');
        }

        $this->redirect('/asistencia/configuracion#areas');
    }

    public function toggleArea(): void
    {
        $this->requireAuth();

        $areaId = (int) ($_POST['areaid'] ?? 0);
        $status = ($_POST['areaestado'] ?? '0') === '1';

        if ($areaId <= 0) {
            sessionFlash('error', 'El area seleccionada no es valida.');
            $this->redirect('/asistencia/configuracion#areas');
        }

        (new AttendanceModel())->updateAreaStatus($areaId, $status);
        sessionFlash('success', 'Estado del area actualizado correctamente.');
        $this->redirect('/asistencia/configuracion#areas');
    }

    public function storeSubject(): void
    {
        $this->requireAuth();

        $areaId = (int) ($_POST['areaid'] ?? 0);
        $name = trim((string) ($_POST['asgnombre'] ?? ''));

        if ($areaId <= 0 || $name === '') {
            sessionFlash('error', 'Debe seleccionar area e ingresar el nombre de la asignatura.');
            $this->redirect('/asistencia/configuracion#asignaturas');
        }

        try {
            (new AttendanceModel())->createSubject($areaId, $name);
            sessionFlash('success', 'Asignatura registrada correctamente.');
        } catch (Throwable) {
            sessionFlash('error', 'No se pudo registrar la asignatura. Revise si ya existe en esa area.');
        }

        $this->redirect('/asistencia/configuracion#asignaturas');
    }

    public function updateSubject(): void
    {
        $this->requireAuth();

        $subjectId = (int) ($_POST['asgid'] ?? 0);
        $areaId = (int) ($_POST['areaid'] ?? 0);
        $name = trim((string) ($_POST['asgnombre'] ?? ''));

        if ($subjectId <= 0 || $areaId <= 0 || $name === '') {
            sessionFlash('error', 'Los datos de la asignatura no son validos.');
            $this->redirect('/asistencia/configuracion#asignaturas');
        }

        try {
            (new AttendanceModel())->updateSubject($subjectId, $areaId, $name);
            sessionFlash('success', 'Asignatura actualizada correctamente.');
        } catch (Throwable) {
            sessionFlash('error', 'No se pudo actualizar la asignatura. Revise si ya existe en esa area.');
        }

        $this->redirect('/asistencia/configuracion#asignaturas');
    }

    public function toggleSubject(): void
    {
        $this->requireAuth();

        $subjectId = (int) ($_POST['asgid'] ?? 0);
        $status = ($_POST['asgestado'] ?? '0') === '1';

        if ($subjectId <= 0) {
            sessionFlash('error', 'La asignatura seleccionada no es valida.');
            $this->redirect('/asistencia/configuracion#asignaturas');
        }

        (new AttendanceModel())->updateSubjectStatus($subjectId, $status);
        sessionFlash('success', 'Estado de la asignatura actualizado correctamente.');
        $this->redirect('/asistencia/configuracion#asignaturas');
    }

    public function storeCourseSubject(): void
    {
        $this->requireAuth();

        $period = currentAcademicPeriod();

        if ($period === null) {
            sessionFlash('error', 'Debe seleccionar un periodo lectivo antes de registrar materias.');
            $this->redirect('/asistencia/configuracion#materias');
        }

        $courseId = (int) ($_POST['curid'] ?? 0);
        $subjectId = (int) ($_POST['asgid'] ?? 0);
        $startDate = $this->validDateOrToday((string) ($_POST['mtcfecha_inicio'] ?? ''));
        $order = trim((string) ($_POST['mtcorden'] ?? '')) !== '' ? max(1, (int) $_POST['mtcorden']) : null;

        if ($courseId <= 0 || $subjectId <= 0) {
            sessionFlash('error', 'Debe seleccionar curso y asignatura.');
            $this->redirect('/asistencia/configuracion#materias');
        }

        $course = (new CourseModel())->findDetailed($courseId);

        if ($course === false || (int) $course['pleid'] !== (int) $period['pleid']) {
            sessionFlash('error', 'El curso seleccionado no pertenece al periodo actual.');
            $this->redirect('/asistencia/configuracion#materias');
        }

        $attendanceModel = new AttendanceModel();

        if ($attendanceModel->courseSubjectExists($courseId, $subjectId)) {
            sessionFlash('error', 'La materia ya esta activa para ese curso.');
            $this->redirect('/asistencia/configuracion#materias');
        }

        try {
            $attendanceModel->createCourseSubject($courseId, $subjectId, $startDate, $order);
            sessionFlash('success', 'Materia del curso registrada correctamente.');
        } catch (Throwable) {
            sessionFlash('error', 'No se pudo registrar la materia del curso.');
        }

        $this->redirect('/asistencia/configuracion#materias');
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
            $this->redirect('/asistencia/configuracion#materias');
        }

        try {
            $attendanceModel->updateCourseSubjectStatus($courseSubjectId, $status);
            sessionFlash('success', 'Estado de la materia actualizado correctamente.');
        } catch (Throwable) {
            sessionFlash('error', 'No se pudo actualizar la materia. Revise si ya existe otra materia activa igual.');
        }

        $this->redirect('/asistencia/configuracion#materias');
    }

    public function assignTeacher(): void
    {
        $this->requireAuth();

        $period = currentAcademicPeriod();
        $courseSubjectId = (int) ($_POST['mtcid'] ?? 0);
        $personId = (int) ($_POST['perid'] ?? 0);
        $startDate = $this->validDateOrToday((string) ($_POST['mcdfecha_inicio'] ?? ''));
        $attendanceModel = new AttendanceModel();
        $courseSubject = $attendanceModel->findCourseSubject($courseSubjectId);

        if ($period === null || $courseSubject === false || (int) $courseSubject['pleid'] !== (int) $period['pleid']) {
            sessionFlash('error', 'La materia seleccionada no es valida para el periodo actual.');
            $this->redirect('/asistencia/configuracion#docentes');
        }

        if ($personId <= 0 || !(new PersonalModel())->personHasActiveStaffType($personId, 'Docente')) {
            sessionFlash('error', 'Debe seleccionar un docente activo.');
            $this->redirect('/asistencia/configuracion#docentes');
        }

        try {
            $attendanceModel->assignTeacher($courseSubjectId, $personId, $startDate);
            sessionFlash('success', 'Docente asignado correctamente.');
        } catch (Throwable) {
            sessionFlash('error', 'No se pudo asignar el docente. Revise si ya esta activo en esa materia.');
        }

        $this->redirect('/asistencia/configuracion#docentes');
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
            $this->redirect('/asistencia/configuracion#docentes');
        }

        $attendanceModel->removeTeacher($assignmentId);
        sessionFlash('success', 'Docente retirado de la materia correctamente.');
        $this->redirect('/asistencia/configuracion#docentes');
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
        $month = $this->validMonthOrCurrent((string) ($_GET['mes'] ?? date('Y-m')));
        $selectedDate = trim((string) ($_GET['fecha'] ?? ''));
        $selectedDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate) === 1 ? $selectedDate : '';
        $monthStart = $month . '-01';
        $monthEnd = date('Y-m-t', strtotime($monthStart));
        $attendanceModel = new AttendanceModel();

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
            'selectedDate' => $selectedDate,
            'selectedStudentId' => $studentId,
            'availableStudents' => $availableStudents,
            'summaryDays' => $periodId > 0 && $studentId > 0
                ? $attendanceModel->studentDailySummary($studentId, $periodId, $monthStart, $monthEnd)
                : [],
            'attendanceDetail' => $periodId > 0 && $studentId > 0 && $selectedDate !== ''
                ? $attendanceModel->studentAttendanceDetail($studentId, $periodId, $selectedDate)
                : [],
            'success' => sessionFlash('success'),
            'error' => sessionFlash('error'),
        ]);
    }
}
