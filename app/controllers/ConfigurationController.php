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

        $this->view('configuracion.catalogos', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => 'Catalogos',
            'currentModule' => 'configuracion',
            'currentSection' => 'catalogos',
            'user' => $user,
            'catalogs' => $catalogModel->allCatalogs(),
            'success' => sessionFlash('success'),
            'error' => sessionFlash('error'),
        ]);
    }

    public function storeCatalogItem(): void
    {
        $this->requireAuth();

        $table = trim($_POST['catalog_table'] ?? '');
        $name = trim($_POST['catalog_name'] ?? '');
        $catalogModel = new CatalogModel();

        if ($name === '') {
            sessionFlash('error', 'El nombre del catalogo es obligatorio.');
            $this->redirect('/configuracion/catalogos');
        }

        try {
            $catalog = $catalogModel->getCatalog($table);
        } catch (\RuntimeException) {
            sessionFlash('error', 'El catalogo solicitado no es valido.');
            $this->redirect('/configuracion/catalogos');
            return;
        }

        if ($catalogModel->existsByName($table, $name)) {
            sessionFlash('error', 'Ya existe un registro con ese nombre en ' . strtolower((string) $catalog['label']) . '.');
            $this->redirect('/configuracion/catalogos');
        }

        $catalogModel->createItem($table, $name);
        sessionFlash('success', 'Registro agregado correctamente en ' . strtolower((string) $catalog['label']) . '.');
        $this->redirect('/configuracion/catalogos');
    }

    public function updateCatalogItem(): void
    {
        $this->requireAuth();

        $table = trim($_POST['catalog_table'] ?? '');
        $id = (int) ($_POST['catalog_id'] ?? 0);
        $name = trim($_POST['catalog_name'] ?? '');
        $catalogModel = new CatalogModel();

        if ($id <= 0 || $name === '') {
            sessionFlash('error', 'Los datos para actualizar el catalogo no son validos.');
            $this->redirect('/configuracion/catalogos');
        }

        try {
            $catalog = $catalogModel->getCatalog($table);
        } catch (\RuntimeException) {
            sessionFlash('error', 'El catalogo solicitado no es valido.');
            $this->redirect('/configuracion/catalogos');
            return;
        }

        if ($catalogModel->existsByName($table, $name, $id)) {
            sessionFlash('error', 'Ya existe un registro con ese nombre en ' . strtolower((string) $catalog['label']) . '.');
            $this->redirect('/configuracion/catalogos');
        }

        $catalogModel->updateItem($table, $id, $name);
        sessionFlash('success', 'Registro actualizado correctamente en ' . strtolower((string) $catalog['label']) . '.');
        $this->redirect('/configuracion/catalogos');
    }

    public function deleteCatalogItem(): void
    {
        $this->requireAuth();

        $table = trim($_POST['catalog_table'] ?? '');
        $id = (int) ($_POST['catalog_id'] ?? 0);
        $catalogModel = new CatalogModel();

        if ($id <= 0) {
            sessionFlash('error', 'El registro a eliminar no es valido.');
            $this->redirect('/configuracion/catalogos');
        }

        try {
            $catalog = $catalogModel->getCatalog($table);
        } catch (\RuntimeException) {
            sessionFlash('error', 'El catalogo solicitado no es valido.');
            $this->redirect('/configuracion/catalogos');
            return;
        }

        if (!$catalogModel->deleteItem($table, $id)) {
            sessionFlash('error', 'No se pudo eliminar el registro de ' . strtolower((string) $catalog['label']) . '. Revise si esta siendo usado por otros modulos.');
            $this->redirect('/configuracion/catalogos');
        }

        sessionFlash('success', 'Registro eliminado correctamente de ' . strtolower((string) $catalog['label']) . '.');
        $this->redirect('/configuracion/catalogos');
    }
}
