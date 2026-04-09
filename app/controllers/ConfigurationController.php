<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\CatalogModel;

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
}
