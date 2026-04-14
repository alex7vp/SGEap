<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\CatalogModel;
use App\Models\InstitutionModel;
use App\Models\PeriodModel;

class ConfigurationController extends Controller
{
    public function catalogs(): void
    {
        $user = $this->requireAuth();
        $catalogModel = new CatalogModel();
        $catalogFeedback = $this->catalogFeedback();

        $this->view('configuracion.catalogos', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => 'Catalogos',
            'currentModule' => 'configuracion',
            'currentSection' => 'catalogos',
            'user' => $user,
            'catalogs' => $catalogModel->allCatalogs(),
            'catalogFeedback' => $catalogFeedback,
        ]);
    }

    public function periods(): void
    {
        $user = $this->requireAuth();
        $periodModel = new PeriodModel();
        $editId = (int) ($_GET['edit'] ?? 0);
        $editPeriod = $editId > 0 ? $periodModel->find($editId) : false;

        $this->view('configuracion.periodos', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => 'Periodos lectivos',
            'currentModule' => 'configuracion',
            'currentSection' => 'periodos',
            'user' => $user,
            'periods' => $periodModel->allOrdered(),
            'success' => sessionFlash('success'),
            'error' => sessionFlash('error'),
            'periodListFeedback' => $this->periodListFeedback(),
            'old' => [
                'pleid' => sessionFlash('old_period_pleid') ?? ($editPeriod !== false ? (string) $editPeriod['pleid'] : ''),
                'pledescripcion' => sessionFlash('old_period_description') ?? ($editPeriod !== false ? (string) $editPeriod['pledescripcion'] : ''),
                'plefechainicio' => sessionFlash('old_period_start') ?? ($editPeriod !== false ? (string) $editPeriod['plefechainicio'] : ''),
                'plefechafin' => sessionFlash('old_period_end') ?? ($editPeriod !== false ? (string) $editPeriod['plefechafin'] : ''),
                'pleactivo' => sessionFlash('old_period_active') ?? ($editPeriod !== false && !empty($editPeriod['pleactivo']) ? '1' : '0'),
            ],
        ]);
    }

    public function institution(): void
    {
        $user = $this->requireAuth();
        $institutionModel = new InstitutionModel();
        $institution = $institutionModel->current();
        $error = sessionFlash('error');
        $fieldErrors = $this->institutionFieldErrors();

        $this->view('configuracion.institucion', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => 'Institucion',
            'currentModule' => 'configuracion',
            'currentSection' => 'institucion',
            'user' => $user,
            'institution' => $institution,
            'success' => sessionFlash('success'),
            'error' => empty($fieldErrors) ? $error : null,
            'fieldErrors' => $fieldErrors,
            'old' => [
                'insid' => sessionFlash('old_institution_id') ?? ($institution !== false ? (string) $institution['insid'] : ''),
                'insnombre' => sessionFlash('old_institution_name') ?? ($institution !== false ? (string) $institution['insnombre'] : ''),
                'insrazonsocial' => sessionFlash('old_institution_business_name') ?? ($institution !== false ? (string) ($institution['insrazonsocial'] ?? '') : ''),
                'insruc' => sessionFlash('old_institution_ruc') ?? ($institution !== false ? (string) ($institution['insruc'] ?? '') : ''),
                'inscodigoamie' => sessionFlash('old_institution_amie') ?? ($institution !== false ? (string) ($institution['inscodigoamie'] ?? '') : ''),
                'insdireccion' => sessionFlash('old_institution_address') ?? ($institution !== false ? (string) ($institution['insdireccion'] ?? '') : ''),
                'instelefono' => sessionFlash('old_institution_phone') ?? ($institution !== false ? (string) ($institution['instelefono'] ?? '') : ''),
                'inscorreoelectronico' => sessionFlash('old_institution_email') ?? ($institution !== false ? (string) ($institution['inscorreoelectronico'] ?? '') : ''),
                'insrepresentantelegal' => sessionFlash('old_institution_legal_rep') ?? ($institution !== false ? (string) ($institution['insrepresentantelegal'] ?? '') : ''),
            ],
        ]);
    }

    public function storeInstitution(): void
    {
        $this->requireAuth();

        $data = $this->institutionFormData();
        $institutionModel = new InstitutionModel();
        $current = $institutionModel->current();

        if ($data['insnombre'] === '') {
            $this->flashInstitutionFormData($data);
            $this->flashInstitutionFieldError('insnombre', 'El nombre de la institucion es obligatorio.');
            $this->redirectToInstitutionField('insnombre');
        }

        if (!$this->isValidInstitutionAmie($data['inscodigoamie'])) {
            $this->flashInstitutionFormData($data);
            $this->flashInstitutionFieldError('inscodigoamie', 'El codigo AMIE debe tener el formato 17H02761: 2 digitos, 1 letra mayuscula y 5 digitos.');
            $this->redirectToInstitutionField('inscodigoamie');
        }

        if (!$this->isValidInstitutionRuc($data['insruc'])) {
            $this->flashInstitutionFormData($data);
            $this->flashInstitutionFieldError('insruc', 'El RUC debe tener 13 digitos numericos.');
            $this->redirectToInstitutionField('insruc');
        }

        if (!$this->isValidInstitutionPhone($data['instelefono'])) {
            $this->flashInstitutionFormData($data);
            $this->flashInstitutionFieldError('instelefono', 'El telefono institucional debe tener 10 digitos.');
            $this->redirectToInstitutionField('instelefono');
        }

        if (!$this->isValidInstitutionEmail($data['inscorreoelectronico'])) {
            $this->flashInstitutionFormData($data);
            $this->flashInstitutionFieldError('inscorreoelectronico', 'El correo institucional no tiene un formato valido.');
            $this->redirectToInstitutionField('inscorreoelectronico');
        }

        $currentId = $current !== false ? (int) $current['insid'] : null;

        if ($institutionModel->existsByRuc($data['insruc'], $currentId)) {
            $this->flashInstitutionFormData($data);
            $this->flashInstitutionFieldError('insruc', 'El RUC ya esta registrado en otra institucion.');
            $this->redirectToInstitutionField('insruc');
        }

        if ($institutionModel->existsByAmie($data['inscodigoamie'], $currentId)) {
            $this->flashInstitutionFormData($data);
            $this->flashInstitutionFieldError('inscodigoamie', 'El codigo AMIE ya esta registrado en otra institucion.');
            $this->redirectToInstitutionField('inscodigoamie');
        }

        if ($current === false) {
            $institutionModel->create($data);
            sessionFlash('success', 'Datos institucionales registrados correctamente.');
            $this->redirect('/configuracion/institucion');
        }

        $institutionModel->updateInstitution((int) $current['insid'], $data);
        sessionFlash('success', 'Datos institucionales actualizados correctamente.');
        $this->redirect('/configuracion/institucion');
    }

    public function storePeriod(): void
    {
        $this->requireAuth();

        $data = $this->periodFormData();
        $data['pleactivo'] = false;
        $periodModel = new PeriodModel();

        if ($data['pledescripcion'] === '' || $data['plefechainicio'] === '' || $data['plefechafin'] === '') {
            $this->flashPeriodFormData($data);
            sessionFlash('error', 'Descripcion, fecha de inicio y fecha de fin son obligatorias.');
            $this->redirect('/configuracion/periodos');
        }

        if ($data['plefechainicio'] > $data['plefechafin']) {
            $this->flashPeriodFormData($data);
            sessionFlash('error', 'La fecha de inicio no puede ser mayor que la fecha de fin.');
            $this->redirect('/configuracion/periodos');
        }

        if ($periodModel->existsByDescription($data['pledescripcion'])) {
            $this->flashPeriodFormData($data);
            sessionFlash('error', 'La descripcion del periodo ya existe.');
            $this->redirect('/configuracion/periodos');
        }

        $periodModel->create($data);

        sessionFlash('success', 'Periodo lectivo registrado correctamente.');
        $this->redirect('/configuracion/periodos');
    }

    public function updatePeriod(): void
    {
        $this->requireAuth();

        $periodId = (int) ($_POST['pleid'] ?? 0);
        $data = $this->periodFormData();
        $periodModel = new PeriodModel();

        if ($periodId <= 0) {
            sessionFlash('error', 'El periodo a actualizar no es valido.');
            $this->redirect('/configuracion/periodos');
        }

        if ($data['pledescripcion'] === '' || $data['plefechainicio'] === '' || $data['plefechafin'] === '') {
            $this->flashPeriodFormData($data + ['pleid' => (string) $periodId]);
            sessionFlash('error', 'Descripcion, fecha de inicio y fecha de fin son obligatorias.');
            $this->redirect('/configuracion/periodos');
        }

        if ($data['plefechainicio'] > $data['plefechafin']) {
            $this->flashPeriodFormData($data + ['pleid' => (string) $periodId]);
            sessionFlash('error', 'La fecha de inicio no puede ser mayor que la fecha de fin.');
            $this->redirect('/configuracion/periodos');
        }

        if ($periodModel->find($periodId) === false) {
            sessionFlash('error', 'El periodo solicitado no existe.');
            $this->redirect('/configuracion/periodos');
        }

        if ($periodModel->existsByDescription($data['pledescripcion'], $periodId)) {
            $this->flashPeriodFormData($data + ['pleid' => (string) $periodId]);
            sessionFlash('error', 'La descripcion del periodo ya existe en otro registro.');
            $this->redirect('/configuracion/periodos');
        }

        $periodModel->update($periodId, $data);

        if ($data['pleactivo']) {
            $activePeriod = $periodModel->active();
            setCurrentAcademicPeriod($activePeriod !== false ? $activePeriod : null);
        }

        sessionFlash('success', 'Periodo lectivo actualizado correctamente.');
        $this->redirect('/configuracion/periodos');
    }

    public function selectCurrentPeriod(): void
    {
        $this->requireAuth();

        $periodId = (int) ($_POST['pleid'] ?? 0);
        $redirectTo = trim($_POST['redirect_to'] ?? '/dashboard');
        $periodModel = new PeriodModel();
        $period = $periodModel->find($periodId);

        if ($period === false) {
            $this->flashPeriodListFeedback('error', 'El periodo seleccionado no existe.');
            $this->redirect($redirectTo . '#periodos-registrados');
        }

        $periodModel->activate($periodId);
        $activePeriod = $periodModel->find($periodId);
        setCurrentAcademicPeriod($activePeriod !== false ? $activePeriod : null);
        $this->flashPeriodListFeedback('success', 'Periodo lectivo activado correctamente.');
        $this->redirect($redirectTo . '#periodos-registrados');
    }

    public function storeCatalogItem(): void
    {
        $this->requireAuth();

        $table = trim($_POST['catalog_table'] ?? '');
        $name = trim($_POST['catalog_name'] ?? '');
        $anchor = trim($_POST['redirect_anchor'] ?? '');
        $catalogModel = new CatalogModel();

        if ($name === '') {
            $this->flashCatalogFeedback('error', $table, 'El nombre del catalogo es obligatorio.');
            $this->redirectToCatalogs($anchor);
        }

        try {
            $catalog = $catalogModel->getCatalog($table);
        } catch (\RuntimeException) {
            $this->flashCatalogFeedback('error', $table, 'El catalogo solicitado no es valido.');
            $this->redirectToCatalogs($anchor);
            return;
        }

        if ($catalogModel->existsByName($table, $name)) {
            $this->flashCatalogFeedback('error', $table, 'Ya existe un registro con ese nombre en ' . strtolower((string) $catalog['label']) . '.');
            $this->redirectToCatalogs($anchor);
        }

        $catalogModel->createItem($table, $name);
        $this->flashCatalogFeedback('success', $table, 'Registro agregado correctamente en ' . strtolower((string) $catalog['label']) . '.');
        $this->redirectToCatalogs($anchor);
    }

    public function updateCatalogItem(): void
    {
        $this->requireAuth();

        $table = trim($_POST['catalog_table'] ?? '');
        $id = (int) ($_POST['catalog_id'] ?? 0);
        $name = trim($_POST['catalog_name'] ?? '');
        $anchor = trim($_POST['redirect_anchor'] ?? '');
        $catalogModel = new CatalogModel();

        if ($id <= 0 || $name === '') {
            $this->flashCatalogFeedback('error', $table, 'Los datos para actualizar el catalogo no son validos.');
            $this->redirectToCatalogs($anchor);
        }

        try {
            $catalog = $catalogModel->getCatalog($table);
        } catch (\RuntimeException) {
            $this->flashCatalogFeedback('error', $table, 'El catalogo solicitado no es valido.');
            $this->redirectToCatalogs($anchor);
            return;
        }

        if ($catalogModel->existsByName($table, $name, $id)) {
            $this->flashCatalogFeedback('error', $table, 'Ya existe un registro con ese nombre en ' . strtolower((string) $catalog['label']) . '.');
            $this->redirectToCatalogs($anchor);
        }

        $catalogModel->updateItem($table, $id, $name);
        $this->flashCatalogFeedback('success', $table, 'Registro actualizado correctamente en ' . strtolower((string) $catalog['label']) . '.');
        $this->redirectToCatalogs($anchor);
    }

    public function deleteCatalogItem(): void
    {
        $this->requireAuth();

        $table = trim($_POST['catalog_table'] ?? '');
        $id = (int) ($_POST['catalog_id'] ?? 0);
        $anchor = trim($_POST['redirect_anchor'] ?? '');
        $catalogModel = new CatalogModel();

        if ($id <= 0) {
            $this->flashCatalogFeedback('error', $table, 'El registro a eliminar no es valido.');
            $this->redirectToCatalogs($anchor);
        }

        try {
            $catalog = $catalogModel->getCatalog($table);
        } catch (\RuntimeException) {
            $this->flashCatalogFeedback('error', $table, 'El catalogo solicitado no es valido.');
            $this->redirectToCatalogs($anchor);
            return;
        }

        if (!$catalogModel->deleteItem($table, $id)) {
            $this->flashCatalogFeedback('error', $table, 'No se pudo eliminar el registro de ' . strtolower((string) $catalog['label']) . '. Revise si esta siendo usado por otros modulos.');
            $this->redirectToCatalogs($anchor);
        }

        $this->flashCatalogFeedback('success', $table, 'Registro eliminado correctamente de ' . strtolower((string) $catalog['label']) . '.');
        $this->redirectToCatalogs($anchor);
    }

    private function flashCatalogFeedback(string $type, string $table, string $message): void
    {
        sessionFlash('catalog_feedback_type', $type);
        sessionFlash('catalog_feedback_table', $table);
        sessionFlash('catalog_feedback_message', $message);
    }

    private function catalogFeedback(): ?array
    {
        $type = sessionFlash('catalog_feedback_type');
        $table = sessionFlash('catalog_feedback_table');
        $message = sessionFlash('catalog_feedback_message');

        if ($type === null || $table === null || $message === null) {
            return null;
        }

        return [
            'type' => $type,
            'table' => $table,
            'message' => $message,
        ];
    }

    private function redirectToCatalogs(string $anchor = ''): void
    {
        $path = '/configuracion/catalogos';

        if ($anchor !== '') {
            $path .= '#' . ltrim($anchor, '#');
        }

        $this->redirect($path);
    }

    private function periodFormData(): array
    {
        return [
            'pledescripcion' => trim($_POST['pledescripcion'] ?? ''),
            'plefechainicio' => trim($_POST['plefechainicio'] ?? ''),
            'plefechafin' => trim($_POST['plefechafin'] ?? ''),
            'pleactivo' => ($_POST['pleactivo'] ?? '0') === '1',
        ];
    }

    private function institutionFormData(): array
    {
        return [
            'insnombre' => trim($_POST['insnombre'] ?? ''),
            'insrazonsocial' => trim($_POST['insrazonsocial'] ?? ''),
            'insruc' => trim($_POST['insruc'] ?? ''),
            'inscodigoamie' => strtoupper(trim($_POST['inscodigoamie'] ?? '')),
            'insdireccion' => trim($_POST['insdireccion'] ?? ''),
            'instelefono' => trim($_POST['instelefono'] ?? ''),
            'inscorreoelectronico' => trim($_POST['inscorreoelectronico'] ?? ''),
            'insrepresentantelegal' => trim($_POST['insrepresentantelegal'] ?? ''),
        ];
    }

    private function isValidInstitutionAmie(string $amie): bool
    {
        if ($amie === '') {
            return true;
        }

        return preg_match('/^\d{2}[A-Z]\d{5}$/', $amie) === 1;
    }

    private function isValidInstitutionRuc(string $ruc): bool
    {
        if ($ruc === '') {
            return true;
        }

        return preg_match('/^\d{13}$/', $ruc) === 1;
    }

    private function isValidInstitutionPhone(string $phone): bool
    {
        if ($phone === '') {
            return true;
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        return preg_match('/^\d{10}$/', $digits) === 1;
    }

    private function isValidInstitutionEmail(string $email): bool
    {
        if ($email === '') {
            return true;
        }

        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    private function flashPeriodFormData(array $data): void
    {
        sessionFlash('old_period_pleid', (string) ($data['pleid'] ?? ''));
        sessionFlash('old_period_description', (string) ($data['pledescripcion'] ?? ''));
        sessionFlash('old_period_start', (string) ($data['plefechainicio'] ?? ''));
        sessionFlash('old_period_end', (string) ($data['plefechafin'] ?? ''));
        sessionFlash('old_period_active', !empty($data['pleactivo']) ? '1' : '0');
    }

    private function flashInstitutionFormData(array $data): void
    {
        sessionFlash('old_institution_name', (string) ($data['insnombre'] ?? ''));
        sessionFlash('old_institution_business_name', (string) ($data['insrazonsocial'] ?? ''));
        sessionFlash('old_institution_ruc', (string) ($data['insruc'] ?? ''));
        sessionFlash('old_institution_amie', (string) ($data['inscodigoamie'] ?? ''));
        sessionFlash('old_institution_address', (string) ($data['insdireccion'] ?? ''));
        sessionFlash('old_institution_phone', (string) ($data['instelefono'] ?? ''));
        sessionFlash('old_institution_email', (string) ($data['inscorreoelectronico'] ?? ''));
        sessionFlash('old_institution_legal_rep', (string) ($data['insrepresentantelegal'] ?? ''));
    }

    private function flashInstitutionFieldError(string $field, string $message): void
    {
        sessionFlash('institution_error_' . $field, $message);
    }

    private function institutionFieldErrors(): array
    {
        $fields = [
            'insnombre',
            'insruc',
            'inscodigoamie',
            'instelefono',
            'inscorreoelectronico',
        ];
        $errors = [];

        foreach ($fields as $field) {
            $message = sessionFlash('institution_error_' . $field);

            if ($message !== null) {
                $errors[$field] = $message;
            }
        }

        return $errors;
    }

    private function redirectToInstitutionField(string $field): void
    {
        $this->redirect('/configuracion/institucion#institution-field-' . $field);
    }

    private function flashPeriodListFeedback(string $type, string $message): void
    {
        sessionFlash('period_list_feedback_type', $type);
        sessionFlash('period_list_feedback_message', $message);
    }

    private function periodListFeedback(): ?array
    {
        $type = sessionFlash('period_list_feedback_type');
        $message = sessionFlash('period_list_feedback_message');

        if ($type === null || $message === null) {
            return null;
        }

        return [
            'type' => $type,
            'message' => $message,
        ];
    }
}
