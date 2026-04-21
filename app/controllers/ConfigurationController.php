<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\CatalogModel;
use App\Models\InstitutionModel;
use App\Models\MatriculationConfigurationModel;
use App\Models\MatriculationDocumentModel;
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

    public function matriculationSettings(): void
    {
        $user = $this->requireAuth();
        $configurationModel = new MatriculationConfigurationModel();
        $periodModel = new PeriodModel();
        $editPeriodId = (int) ($_GET['edit'] ?? 0);
        $editConfiguration = $editPeriodId > 0 ? $configurationModel->findByPeriodId($editPeriodId) : false;

        $this->view('configuracion.matricula', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => 'Configuracion de matricula',
            'currentModule' => 'configuracion',
            'currentSection' => 'configuracion_matricula',
            'user' => $user,
            'periods' => $periodModel->allOrdered(),
            'settings' => $configurationModel->allByPeriod(),
            'matriculationConfigFeedback' => $this->matriculationConfigFeedback(),
            'old' => [
                'cmid' => sessionFlash('old_matriculation_config_id') ?? ($editConfiguration !== false ? (string) $editConfiguration['cmid'] : ''),
                'pleid' => sessionFlash('old_matriculation_config_period') ?? ($editPeriodId > 0 ? (string) $editPeriodId : ''),
                'cmhabilitada' => sessionFlash('old_matriculation_config_enabled') ?? ($editConfiguration !== false && !empty($editConfiguration['cmhabilitada']) ? '1' : '0'),
                'cmfechainicio' => sessionFlash('old_matriculation_config_start') ?? ($editConfiguration !== false ? (string) ($editConfiguration['cmfechainicio'] ?? '') : ''),
                'cmfechafin' => sessionFlash('old_matriculation_config_end') ?? ($editConfiguration !== false ? (string) ($editConfiguration['cmfechafin'] ?? '') : ''),
                'cmhabilitadaextraordinaria' => sessionFlash('old_matriculation_config_extra_enabled') ?? ($editConfiguration !== false && !empty($editConfiguration['cmhabilitadaextraordinaria']) ? '1' : '0'),
                'cmfechainicioextraordinaria' => sessionFlash('old_matriculation_config_extra_start') ?? ($editConfiguration !== false ? (string) ($editConfiguration['cmfechainicioextraordinaria'] ?? '') : ''),
                'cmfechafinextraordinaria' => sessionFlash('old_matriculation_config_extra_end') ?? ($editConfiguration !== false ? (string) ($editConfiguration['cmfechafinextraordinaria'] ?? '') : ''),
                'cmobservacion' => sessionFlash('old_matriculation_config_note') ?? ($editConfiguration !== false ? (string) ($editConfiguration['cmobservacion'] ?? '') : ''),
            ],
        ]);
    }

    public function matriculationDocuments(): void
    {
        $user = $this->requireAuth();
        $documentModel = new MatriculationDocumentModel();
        $editId = (int) ($_GET['edit'] ?? 0);
        $editDocument = $editId > 0 ? $documentModel->find($editId) : false;

        $this->view('configuracion.matricula_documentos', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => 'Documentos de matricula',
            'currentModule' => 'configuracion',
            'currentSection' => 'configuracion_matricula_documentos',
            'user' => $user,
            'documents' => $documentModel->allOrdered(),
            'documentsFeedback' => $this->matriculationDocumentsFeedback(),
            'old' => [
                'domid' => sessionFlash('old_matriculation_document_id') ?? ($editDocument !== false ? (string) $editDocument['domid'] : ''),
                'domnombre' => sessionFlash('old_matriculation_document_name') ?? ($editDocument !== false ? (string) $editDocument['domnombre'] : ''),
                'domdescripcion' => sessionFlash('old_matriculation_document_description') ?? ($editDocument !== false ? (string) ($editDocument['domdescripcion'] ?? '') : ''),
                'domurl' => sessionFlash('old_matriculation_document_url') ?? ($editDocument !== false ? (string) $editDocument['domurl'] : ''),
                'domsource' => sessionFlash('old_matriculation_document_source') ?? (
                    $editDocument !== false
                    ? (mb_strtoupper(trim((string) ($editDocument['domorigen'] ?? 'URL'))) === 'ARCHIVO' ? 'upload' : 'url')
                    : 'upload'
                ),
                'domobligatorio' => sessionFlash('old_matriculation_document_required') ?? ($editDocument !== false && !empty($editDocument['domobligatorio']) ? '1' : '0'),
                'domactivo' => sessionFlash('old_matriculation_document_active') ?? ($editDocument !== false && !empty($editDocument['domactivo']) ? '1' : '0'),
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

    public function selectViewedPeriod(): void
    {
        $this->requireAuth();

        $periodId = (int) ($_POST['pleid'] ?? 0);
        $redirectTo = trim($_POST['redirect_to'] ?? '/dashboard');
        $periodModel = new PeriodModel();
        $period = $periodModel->find($periodId);

        if ($period === false) {
            sessionFlash('error', 'El periodo seleccionado no existe.');
            $this->redirect($redirectTo);
        }

        setCurrentAcademicPeriod($period);
        $this->redirect($redirectTo);
    }

    public function storeMatriculationSetting(): void
    {
        $this->requireAuth();

        $data = $this->matriculationConfigurationFormData();
        $configurationModel = new MatriculationConfigurationModel();
        $periodModel = new PeriodModel();

        if ((int) $data['pleid'] <= 0 || $periodModel->find((int) $data['pleid']) === false) {
            $this->flashMatriculationConfigurationFormData($data);
            $this->flashMatriculationConfigFeedback('error', 'Debe seleccionar un periodo lectivo valido.');
            $this->redirect('/configuracion/matricula');
        }

        if (!$this->hasValidMatriculationRanges($data)) {
            $this->flashMatriculationConfigurationFormData($data);
            $this->flashMatriculationConfigFeedback('error', 'Las fechas de matricula ordinaria o extraordinaria no son validas.');
            $this->redirect('/configuracion/matricula');
        }

        if ($configurationModel->existsByPeriodId((int) $data['pleid'])) {
            $this->flashMatriculationConfigurationFormData($data);
            $this->flashMatriculationConfigFeedback('error', 'Ese periodo ya tiene una configuracion de matricula registrada.');
            $this->redirect('/configuracion/matricula');
        }

        $configurationModel->create($data);
        $this->flashMatriculationConfigFeedback('success', 'Configuracion de matricula registrada correctamente.');
        $this->redirect('/configuracion/matricula');
    }

    public function updateMatriculationSetting(): void
    {
        $this->requireAuth();

        $configurationId = (int) ($_POST['cmid'] ?? 0);
        $data = $this->matriculationConfigurationFormData();
        $configurationModel = new MatriculationConfigurationModel();
        $periodModel = new PeriodModel();

        if ($configurationId <= 0) {
            $this->flashMatriculationConfigFeedback('error', 'La configuracion a actualizar no es valida.');
            $this->redirect('/configuracion/matricula');
        }

        if ((int) $data['pleid'] <= 0 || $periodModel->find((int) $data['pleid']) === false) {
            $this->flashMatriculationConfigurationFormData($data + ['cmid' => (string) $configurationId]);
            $this->flashMatriculationConfigFeedback('error', 'Debe seleccionar un periodo lectivo valido.');
            $this->redirect('/configuracion/matricula');
        }

        if (!$this->hasValidMatriculationRanges($data)) {
            $this->flashMatriculationConfigurationFormData($data + ['cmid' => (string) $configurationId]);
            $this->flashMatriculationConfigFeedback('error', 'Las fechas de matricula ordinaria o extraordinaria no son validas.');
            $this->redirect('/configuracion/matricula');
        }

        if ($configurationModel->existsByPeriodId((int) $data['pleid'], $configurationId)) {
            $this->flashMatriculationConfigurationFormData($data + ['cmid' => (string) $configurationId]);
            $this->flashMatriculationConfigFeedback('error', 'Ese periodo ya tiene otra configuracion de matricula registrada.');
            $this->redirect('/configuracion/matricula');
        }

        $configurationModel->update($configurationId, $data);
        $this->flashMatriculationConfigFeedback('success', 'Configuracion de matricula actualizada correctamente.');
        $this->redirect('/configuracion/matricula');
    }

    public function toggleOrdinaryMatriculationSetting(): void
    {
        $this->requireAuth();

        $configurationId = (int) ($_POST['cmid'] ?? 0);
        $enabled = ($_POST['enabled'] ?? '0') === '1';
        $configurationModel = new MatriculationConfigurationModel();

        if ($configurationId <= 0) {
            $this->flashMatriculationConfigFeedback('error', 'La configuracion a actualizar no es valida.');
            $this->redirect('/configuracion/matricula');
        }

        $configurationModel->toggleOrdinary($configurationId, $enabled);
        $this->flashMatriculationConfigFeedback(
            'success',
            'La matricula ordinaria fue ' . ($enabled ? 'habilitada' : 'cerrada') . ' correctamente.'
        );
        $this->redirect('/configuracion/matricula#configuracion-matricula-registrada');
    }

    public function toggleExtraordinaryMatriculationSetting(): void
    {
        $this->requireAuth();

        $configurationId = (int) ($_POST['cmid'] ?? 0);
        $enabled = ($_POST['enabled'] ?? '0') === '1';
        $configurationModel = new MatriculationConfigurationModel();

        if ($configurationId <= 0) {
            $this->flashMatriculationConfigFeedback('error', 'La configuracion a actualizar no es valida.');
            $this->redirect('/configuracion/matricula');
        }

        $configurationModel->toggleExtraordinary($configurationId, $enabled);
        $this->flashMatriculationConfigFeedback(
            'success',
            'La matricula extraordinaria fue ' . ($enabled ? 'habilitada' : 'cerrada') . ' correctamente.'
        );
        $this->redirect('/configuracion/matricula#configuracion-matricula-registrada');
    }

    public function storeMatriculationDocument(): void
    {
        $this->requireAuth();

        $data = $this->matriculationDocumentFormData();
        $documentModel = new MatriculationDocumentModel();
        $uploadedPath = null;
        $data['domorigen'] = $data['domsource'] === 'upload' ? 'ARCHIVO' : 'URL';

        if ($data['domnombre'] === '') {
            $this->flashMatriculationDocumentFormData($data);
            $this->flashMatriculationDocumentsFeedback('error', 'El nombre del documento es obligatorio.');
            $this->redirect('/configuracion/matricula/documentos');
        }

        if ($documentModel->existsByName($data['domnombre'])) {
            $this->flashMatriculationDocumentFormData($data);
            $this->flashMatriculationDocumentsFeedback('error', 'Ya existe un documento registrado con ese nombre.');
            $this->redirect('/configuracion/matricula/documentos');
        }

        if ($data['domsource'] === 'upload') {
            try {
                $uploadedPath = storeMatriculationDocumentFile($_FILES['document_file'] ?? [], $data['domnombre']);
                $data['domurl'] = $uploadedPath ?? '';
            } catch (\Throwable $exception) {
                $this->flashMatriculationDocumentFormData($data);
                $this->flashMatriculationDocumentsFeedback('error', $exception->getMessage());
                $this->redirect('/configuracion/matricula/documentos');
            }
        }

        if (
            ($data['domsource'] === 'upload' && $data['domurl'] === '')
            || ($data['domsource'] === 'url' && $data['domurl'] === '')
        ) {
            $this->flashMatriculationDocumentFormData($data);
            $this->flashMatriculationDocumentsFeedback(
                'error',
                $data['domsource'] === 'upload'
                    ? 'Debe cargar un archivo PDF para el documento.'
                    : 'Debe registrar una URL para el documento.'
            );
            $this->redirect('/configuracion/matricula/documentos');
        }

        try {
            $documentModel->create($data);
        } catch (\Throwable $exception) {
            if ($uploadedPath !== null) {
                deleteManagedMatriculationDocumentFile($uploadedPath);
            }

            $this->flashMatriculationDocumentFormData($data);
            $this->flashMatriculationDocumentsFeedback('error', 'No se pudo guardar el documento de matricula.');
            $this->redirect('/configuracion/matricula/documentos');
        }

        $this->flashMatriculationDocumentsFeedback('success', 'Documento de matricula registrado correctamente.');
        $this->redirect('/configuracion/matricula/documentos#documentos-matricula-registrados');
    }

    public function updateMatriculationDocument(): void
    {
        $this->requireAuth();

        $documentId = (int) ($_POST['domid'] ?? 0);
        $data = $this->matriculationDocumentFormData();
        $documentModel = new MatriculationDocumentModel();
        $current = $documentId > 0 ? $documentModel->find($documentId) : false;
        $data['domorigen'] = $data['domsource'] === 'upload' ? 'ARCHIVO' : 'URL';

        if ($documentId <= 0 || $current === false) {
            $this->flashMatriculationDocumentsFeedback('error', 'El documento seleccionado no es valido.');
            $this->redirect('/configuracion/matricula/documentos');
        }

        if ($data['domnombre'] === '') {
            $this->flashMatriculationDocumentFormData($data + ['domid' => (string) $documentId]);
            $this->flashMatriculationDocumentsFeedback('error', 'El nombre del documento es obligatorio.');
            $this->redirect('/configuracion/matricula/documentos?edit=' . $documentId);
        }

        if ($documentModel->existsByName($data['domnombre'], $documentId)) {
            $this->flashMatriculationDocumentFormData($data + ['domid' => (string) $documentId]);
            $this->flashMatriculationDocumentsFeedback('error', 'Ya existe otro documento registrado con ese nombre.');
            $this->redirect('/configuracion/matricula/documentos?edit=' . $documentId);
        }

        $oldUrl = (string) ($current['domurl'] ?? '');
        $oldOrigin = mb_strtoupper(trim((string) ($current['domorigen'] ?? 'URL')));
        $uploadedPath = null;

        if ($data['domsource'] === 'upload') {
            try {
                $uploadedPath = storeMatriculationDocumentFile($_FILES['document_file'] ?? [], $data['domnombre']);

                if ($uploadedPath !== null) {
                    $data['domurl'] = $uploadedPath;
                } elseif ($oldOrigin === 'ARCHIVO' && isManagedMatriculationDocumentPath($oldUrl)) {
                    $data['domurl'] = $oldUrl;
                } else {
                    $data['domurl'] = '';
                }
            } catch (\Throwable $exception) {
                $this->flashMatriculationDocumentFormData($data + ['domid' => (string) $documentId]);
                $this->flashMatriculationDocumentsFeedback('error', $exception->getMessage());
                $this->redirect('/configuracion/matricula/documentos?edit=' . $documentId);
            }
        }

        if (
            ($data['domsource'] === 'upload' && $data['domurl'] === '')
            || ($data['domsource'] === 'url' && $data['domurl'] === '')
        ) {
            $this->flashMatriculationDocumentFormData($data + ['domid' => (string) $documentId]);
            $this->flashMatriculationDocumentsFeedback(
                'error',
                $data['domsource'] === 'upload'
                    ? 'Debe mantener un archivo PDF vigente o cargar uno nuevo.'
                    : 'Debe registrar una URL valida para el documento.'
            );
            $this->redirect('/configuracion/matricula/documentos?edit=' . $documentId);
        }

        try {
            $documentModel->updateDocument($documentId, $data);
        } catch (\Throwable $exception) {
            if ($uploadedPath !== null) {
                deleteManagedMatriculationDocumentFile($uploadedPath);
            }

            $this->flashMatriculationDocumentFormData($data + ['domid' => (string) $documentId]);
            $this->flashMatriculationDocumentsFeedback('error', 'No se pudo actualizar el documento de matricula.');
            $this->redirect('/configuracion/matricula/documentos?edit=' . $documentId);
        }

        if ($oldOrigin === 'ARCHIVO' && $data['domurl'] !== $oldUrl) {
            deleteManagedMatriculationDocumentFile($oldUrl);
        }

        $this->flashMatriculationDocumentsFeedback('success', 'Documento de matricula actualizado correctamente.');
        $this->redirect('/configuracion/matricula/documentos#documentos-matricula-registrados');
    }

    public function deleteMatriculationDocument(): void
    {
        $this->requireAuth();

        $documentId = (int) ($_POST['domid'] ?? 0);
        $documentModel = new MatriculationDocumentModel();
        $current = $documentId > 0 ? $documentModel->find($documentId) : false;

        if ($documentId <= 0 || $current === false) {
            $this->flashMatriculationDocumentsFeedback('error', 'El documento seleccionado no es valido.');
            $this->redirect('/configuracion/matricula/documentos');
        }

        if (!$documentModel->deleteDocument($documentId)) {
            $this->flashMatriculationDocumentsFeedback('error', 'No se pudo eliminar el documento. Revise si ya esta siendo usado por matriculas registradas.');
            $this->redirect('/configuracion/matricula/documentos#documentos-matricula-registrados');
        }

        if (mb_strtoupper(trim((string) ($current['domorigen'] ?? 'URL'))) === 'ARCHIVO') {
            deleteManagedMatriculationDocumentFile((string) ($current['domurl'] ?? ''));
        }
        $this->flashMatriculationDocumentsFeedback('success', 'Documento de matricula eliminado correctamente.');
        $this->redirect('/configuracion/matricula/documentos#documentos-matricula-registrados');
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

    private function matriculationConfigurationFormData(): array
    {
        return [
            'cmid' => trim((string) ($_POST['cmid'] ?? '')),
            'pleid' => (int) ($_POST['pleid'] ?? 0),
            'cmhabilitada' => ($_POST['cmhabilitada'] ?? '0') === '1',
            'cmfechainicio' => trim((string) ($_POST['cmfechainicio'] ?? '')),
            'cmfechafin' => trim((string) ($_POST['cmfechafin'] ?? '')),
            'cmhabilitadaextraordinaria' => ($_POST['cmhabilitadaextraordinaria'] ?? '0') === '1',
            'cmfechainicioextraordinaria' => trim((string) ($_POST['cmfechainicioextraordinaria'] ?? '')),
            'cmfechafinextraordinaria' => trim((string) ($_POST['cmfechafinextraordinaria'] ?? '')),
            'cmobservacion' => trim((string) ($_POST['cmobservacion'] ?? '')),
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

    private function matriculationDocumentFormData(): array
    {
        return [
            'domid' => trim((string) ($_POST['domid'] ?? '')),
            'domnombre' => trim((string) ($_POST['domnombre'] ?? '')),
            'domdescripcion' => trim((string) ($_POST['domdescripcion'] ?? '')),
            'domorigen' => '',
            'domurl' => trim((string) ($_POST['domurl'] ?? '')),
            'domsource' => ($_POST['domsource'] ?? 'upload') === 'url' ? 'url' : 'upload',
            'domobligatorio' => ($_POST['domobligatorio'] ?? '0') === '1',
            'domactivo' => ($_POST['domactivo'] ?? '0') === '1',
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

    private function hasValidMatriculationRanges(array $data): bool
    {
        $ordinaryStart = trim((string) ($data['cmfechainicio'] ?? ''));
        $ordinaryEnd = trim((string) ($data['cmfechafin'] ?? ''));
        $extraStart = trim((string) ($data['cmfechainicioextraordinaria'] ?? ''));
        $extraEnd = trim((string) ($data['cmfechafinextraordinaria'] ?? ''));

        if (($ordinaryStart === '') !== ($ordinaryEnd === '')) {
            return false;
        }

        if (($extraStart === '') !== ($extraEnd === '')) {
            return false;
        }

        if ($ordinaryStart !== '' && $ordinaryEnd !== '' && $ordinaryStart > $ordinaryEnd) {
            return false;
        }

        if ($extraStart !== '' && $extraEnd !== '' && $extraStart > $extraEnd) {
            return false;
        }

        return true;
    }

    private function flashMatriculationConfigurationFormData(array $data): void
    {
        sessionFlash('old_matriculation_config_id', (string) ($data['cmid'] ?? ''));
        sessionFlash('old_matriculation_config_period', (string) ($data['pleid'] ?? ''));
        sessionFlash('old_matriculation_config_enabled', !empty($data['cmhabilitada']) ? '1' : '0');
        sessionFlash('old_matriculation_config_start', (string) ($data['cmfechainicio'] ?? ''));
        sessionFlash('old_matriculation_config_end', (string) ($data['cmfechafin'] ?? ''));
        sessionFlash('old_matriculation_config_extra_enabled', !empty($data['cmhabilitadaextraordinaria']) ? '1' : '0');
        sessionFlash('old_matriculation_config_extra_start', (string) ($data['cmfechainicioextraordinaria'] ?? ''));
        sessionFlash('old_matriculation_config_extra_end', (string) ($data['cmfechafinextraordinaria'] ?? ''));
        sessionFlash('old_matriculation_config_note', (string) ($data['cmobservacion'] ?? ''));
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

    private function flashMatriculationDocumentFormData(array $data): void
    {
        sessionFlash('old_matriculation_document_id', (string) ($data['domid'] ?? ''));
        sessionFlash('old_matriculation_document_name', (string) ($data['domnombre'] ?? ''));
        sessionFlash('old_matriculation_document_description', (string) ($data['domdescripcion'] ?? ''));
        sessionFlash('old_matriculation_document_url', (string) ($data['domurl'] ?? ''));
        sessionFlash('old_matriculation_document_source', (string) ($data['domsource'] ?? 'upload'));
        sessionFlash('old_matriculation_document_required', !empty($data['domobligatorio']) ? '1' : '0');
        sessionFlash('old_matriculation_document_active', !empty($data['domactivo']) ? '1' : '0');
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

    private function flashMatriculationConfigFeedback(string $type, string $message): void
    {
        sessionFlash('matriculation_config_feedback_type', $type);
        sessionFlash('matriculation_config_feedback_message', $message);
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

    private function matriculationConfigFeedback(): ?array
    {
        $type = sessionFlash('matriculation_config_feedback_type');
        $message = sessionFlash('matriculation_config_feedback_message');

        if ($type === null || $message === null) {
            return null;
        }

        return [
            'type' => $type,
            'message' => $message,
        ];
    }

    private function flashMatriculationDocumentsFeedback(string $type, string $message): void
    {
        sessionFlash('matriculation_documents_feedback_type', $type);
        sessionFlash('matriculation_documents_feedback_message', $message);
    }

    private function matriculationDocumentsFeedback(): ?array
    {
        $type = sessionFlash('matriculation_documents_feedback_type');
        $message = sessionFlash('matriculation_documents_feedback_message');

        if ($type === null || $message === null) {
            return null;
        }

        return [
            'type' => $type,
            'message' => $message,
        ];
    }
}
