<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\AttendanceModel;
use App\Models\NoveltyModel;
use App\Models\StudentModel;
use RuntimeException;

class NoveltyController extends Controller
{
    private const CONTEXTS = ['CLASE', 'RECREO', 'ENTRADA', 'SALIDA', 'PATIO', 'BAR', 'EVENTO', 'OTRO'];

    public function index(): void
    {
        $user = $this->requireAuth();
        $cards = [
            [
                'label' => 'Registrar novedad',
                'description' => 'Registra novedades en hora clase o fuera de hora clase.',
                'url' => baseUrl('novedades/registro'),
                'icon' => 'fa-pencil-square-o',
                'permission' => 'novedades.registrar|novedades.supervisar',
            ],
            [
                'label' => 'Supervision de novedades',
                'description' => 'Consulta y anula novedades registradas por periodo.',
                'url' => baseUrl('novedades/supervision'),
                'icon' => 'fa-search',
                'permission' => 'novedades.supervisar',
            ],
            [
                'label' => 'Mis novedades',
                'description' => 'Consulta las novedades registradas en tu matricula.',
                'url' => baseUrl('novedades/mis-novedades'),
                'icon' => 'fa-user',
                'permission' => 'novedades.ver_propia',
            ],
            [
                'label' => 'Novedades representados',
                'description' => 'Consulta novedades de estudiantes vinculados al representante.',
                'url' => baseUrl('novedades/representante'),
                'icon' => 'fa-users',
                'permission' => 'novedades.representante.ver',
            ],
        ];
        $cards = array_values(array_filter(
            $cards,
            fn (array $card): bool => $this->hasPermission((string) $card['permission'], $user)
        ));

        $this->view('module.home', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => 'Registro de novedades',
            'currentModule' => 'academico',
            'currentSection' => 'novedades_home',
            'user' => $user,
            'moduleDescription' => 'Registra y consulta novedades ocurridas en clase, recreos u otros momentos de la jornada.',
            'moduleCards' => $cards,
        ]);
    }

    public function register(): void
    {
        $user = $this->requireAuth();
        $period = currentAcademicPeriod();
        $periodId = $period !== null ? (int) $period['pleid'] : 0;
        $model = new NoveltyModel();
        $attendanceModel = new AttendanceModel();
        $teacherPersonId = $this->teacherScope($user);
        $classDateRange = $periodId > 0 ? $attendanceModel->classDateRangeByPeriod($periodId) : null;
        $availableMonths = $classDateRange !== null
            ? $this->monthsBetween((string) $classDateRange['start'], (string) $classDateRange['end'])
            : [];
        $selectedMonth = $this->validMonthOrCurrent((string) ($_GET['mes'] ?? date('Y-m')));

        if ($availableMonths !== [] && !in_array($selectedMonth, $availableMonths, true)) {
            $selectedMonth = $this->nearestMonthInRange($selectedMonth, $availableMonths);
        }

        $selectedDate = $this->validDate((string) ($_GET['fecha'] ?? date('Y-m-d')));

        if (!str_starts_with($selectedDate, $selectedMonth . '-')) {
            $selectedDate = $selectedMonth . '-01';
        }

        $monthStart = $selectedMonth . '-01';
        $monthEnd = date('Y-m-t', strtotime($monthStart));
        $availabilityRows = $periodId > 0 && $teacherPersonId !== null
            ? $attendanceModel->teacherCalendarAvailabilityByRange($teacherPersonId, $periodId, $monthStart, $monthEnd)
            : [];
        $calendarDays = $periodId > 0
            ? ($teacherPersonId !== null
                ? $this->teacherCalendarDaysFromAvailability($availabilityRows)
                : $attendanceModel->calendarDaysByRange($periodId, $monthStart, $monthEnd))
            : [];

        $this->view('novedades.registro', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => 'Registrar novedad',
            'currentSection' => 'novedades_registro',
            'user' => $user,
            'currentPeriod' => $period,
            'contexts' => self::CONTEXTS,
            'selectedMonth' => $selectedMonth,
            'selectedDate' => $selectedDate,
            'monthStart' => $monthStart,
            'monthEnd' => $monthEnd,
            'classDateRange' => $classDateRange,
            'availableMonths' => $availableMonths,
            'calendarDays' => $calendarDays,
            'types' => $model->activeTypes(),
            'students' => $periodId > 0 ? $model->activeMatriculationsForPeriod($periodId, $teacherPersonId) : [],
            'sessions' => $periodId > 0 ? $model->sessionsForDate($periodId, $selectedDate, $teacherPersonId) : [],
            'recentNovelties' => $periodId > 0 ? $model->byPeriod($periodId, $selectedDate, 0, $teacherPersonId) : [],
            'success' => sessionFlash('success'),
            'error' => sessionFlash('error'),
        ]);
    }

    public function store(): void
    {
        $user = $this->requireAuth();
        $context = strtoupper(trim((string) ($_POST['noetipo_contexto'] ?? '')));

        if (!in_array($context, self::CONTEXTS, true)) {
            $context = 'OTRO';
        }

        try {
            (new NoveltyModel())->create([
                'matid' => (int) ($_POST['matid'] ?? 0),
                'sclid' => (int) ($_POST['sclid'] ?? 0),
                'tnoid' => (int) ($_POST['tnoid'] ?? 0),
                'noetipo_contexto' => $context,
                'noefecha' => $this->validDate((string) ($_POST['noefecha'] ?? date('Y-m-d'))),
                'noehora' => trim((string) ($_POST['noehora'] ?? '')),
                'noeubicacion' => trim((string) ($_POST['noeubicacion'] ?? '')),
                'noedescripcion' => trim((string) ($_POST['noedescripcion'] ?? '')),
                'usuid_registro' => (int) ($user['usuid'] ?? 0),
            ], $this->teacherScope($user));

            sessionFlash('success', 'Novedad registrada correctamente.');
        } catch (RuntimeException $exception) {
            sessionFlash('error', $exception->getMessage());
        }

        $date = $this->validDate((string) ($_POST['noefecha'] ?? date('Y-m-d')));
        $this->redirect('/novedades/registro?mes=' . substr($date, 0, 7) . '&fecha=' . $date);
    }

    public function supervision(): void
    {
        $user = $this->requireAuth();
        $period = currentAcademicPeriod();
        $periodId = $period !== null ? (int) $period['pleid'] : 0;
        $model = new NoveltyModel();
        $date = trim((string) ($_GET['fecha'] ?? ''));
        $date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1 ? $date : '';
        $matriculationId = (int) ($_GET['matid'] ?? 0);

        $this->view('novedades.supervision', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => 'Supervision de novedades',
            'currentSection' => 'novedades_supervision',
            'user' => $user,
            'currentPeriod' => $period,
            'selectedDate' => $date,
            'selectedMatriculationId' => $matriculationId,
            'students' => $periodId > 0 ? $model->activeMatriculationsForPeriod($periodId) : [],
            'novelties' => $periodId > 0 ? $model->byPeriod($periodId, $date, $matriculationId) : [],
            'success' => sessionFlash('success'),
            'error' => sessionFlash('error'),
        ]);
    }

    public function annul(): void
    {
        $user = $this->requireAuth();

        try {
            (new NoveltyModel())->annul(
                (int) ($_POST['noeid'] ?? 0),
                (int) ($user['usuid'] ?? 0),
                trim((string) ($_POST['noemotivo_anulacion'] ?? ''))
            );
            sessionFlash('success', 'Novedad anulada correctamente.');
        } catch (RuntimeException $exception) {
            sessionFlash('error', $exception->getMessage());
        }

        $this->redirect('/novedades/supervision');
    }

    public function own(): void
    {
        $user = $this->requireAuth();
        $period = currentAcademicPeriod();
        $periodId = $period !== null ? (int) $period['pleid'] : 0;
        $student = (new StudentModel())->findByPersonId((int) ($user['perid'] ?? 0));

        if ($student === false) {
            sessionFlash('error', 'Tu usuario no tiene un estudiante asociado.');
            $this->redirect('/dashboard');
        }

        $this->view('novedades.consulta', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => 'Mis novedades',
            'currentSection' => 'novedades_propias',
            'user' => $user,
            'currentPeriod' => $period,
            'availableStudents' => [],
            'selectedStudentId' => (int) $student['estid'],
            'novelties' => $periodId > 0 ? (new NoveltyModel())->byStudent((int) $student['estid'], $periodId) : [],
            'success' => sessionFlash('success'),
            'error' => sessionFlash('error'),
        ]);
    }

    public function representative(): void
    {
        $user = $this->requireAuth();
        $period = currentAcademicPeriod();
        $periodId = $period !== null ? (int) $period['pleid'] : 0;
        $studentModel = new StudentModel();
        $students = $studentModel->allByRepresentativePerson((int) ($user['perid'] ?? 0), $periodId);
        $selectedStudentId = (int) ($_GET['estid'] ?? 0);

        if ($selectedStudentId <= 0 && $students !== []) {
            $selectedStudentId = (int) $students[0]['estid'];
        }

        if ($selectedStudentId > 0 && !$studentModel->representativeCanAccessStudent((int) ($user['perid'] ?? 0), $selectedStudentId)) {
            sessionFlash('error', 'No tienes acceso a las novedades solicitadas.');
            $this->redirect('/dashboard');
        }

        $this->view('novedades.consulta', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => 'Novedades de representados',
            'currentSection' => 'novedades_representante',
            'user' => $user,
            'currentPeriod' => $period,
            'availableStudents' => $students,
            'selectedStudentId' => $selectedStudentId,
            'novelties' => $periodId > 0 ? (new NoveltyModel())->byRepresentative((int) ($user['perid'] ?? 0), $periodId, $selectedStudentId) : [],
            'success' => sessionFlash('success'),
            'error' => sessionFlash('error'),
        ]);
    }

    private function teacherScope(array $user): ?int
    {
        if ($this->hasPermission('novedades.supervisar', $user)) {
            return null;
        }

        return $this->hasPermission('novedades.registrar', $user) ? (int) ($user['perid'] ?? 0) : null;
    }

    private function validDate(string $date): string
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1 ? $date : date('Y-m-d');
    }

    private function validMonthOrCurrent(string $month): string
    {
        $month = trim($month);

        return preg_match('/^\d{4}-\d{2}$/', $month) === 1 ? $month : date('Y-m');
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
                    'cahabilitado' => true,
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
}
