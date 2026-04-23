<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;

class ModuleController extends Controller
{
    public function academic(): void
    {
        $this->renderModuleHome(
            'academico',
            'academico_home',
            'Gestion academica',
            'Centraliza los procesos operativos del ciclo estudiantil y la gestion diaria del area academica.',
            [
                [
                    'label' => 'Estudiantes',
                    'description' => 'Administra los registros estudiantiles vinculados a persona.',
                    'url' => baseUrl('estudiantes'),
                    'icon' => 'fa-graduation-cap',
                ],
                [
                    'label' => 'Personal',
                    'description' => 'Centraliza el registro, la asignacion de tipos y la consulta del personal institucional.',
                    'url' => baseUrl('personal'),
                    'icon' => 'fa-id-badge',
                ],
                [
                    'label' => 'Matriculas',
                    'description' => 'Gestiona nuevas matriculas y el seguimiento del proceso por periodo.',
                    'url' => baseUrl('matriculas'),
                    'icon' => 'fa-address-card',
                ],
            ]
        );
    }

    public function configuration(): void
    {
        $this->renderModuleHome(
            'configuracion',
            'configuracion_home',
            'Configuracion',
            'Agrupa los catalogos, parametros institucionales y ventanas operativas que gobiernan el sistema.',
            [
                [
                    'label' => 'Catalogos base',
                    'description' => 'Administra catalogos generales reutilizados por todo el sistema.',
                    'url' => baseUrl('configuracion/catalogos'),
                    'icon' => 'fa-list-alt',
                ],
                [
                    'label' => 'Datos institucionales',
                    'description' => 'Actualiza la informacion principal de la institucion.',
                    'url' => baseUrl('configuracion/institucion'),
                    'icon' => 'fa-university',
                ],
                [
                    'label' => 'Periodos lectivos',
                    'description' => 'Define periodos, vigencias y el periodo activo oficial.',
                    'url' => baseUrl('configuracion/periodos'),
                    'icon' => 'fa-calendar',
                ],
                [
                    'label' => 'Configuracion de matricula',
                    'description' => 'Abre o cierra la matricula ordinaria y extraordinaria por periodo.',
                    'url' => baseUrl('configuracion/matricula'),
                    'icon' => 'fa-wpforms',
                ],
                [
                    'label' => 'Grados',
                    'description' => 'Gestiona la estructura de grados usada por cursos y matriculas.',
                    'url' => baseUrl('grados'),
                    'icon' => 'fa-sitemap',
                ],
                [
                    'label' => 'Cursos por periodo',
                    'description' => 'Relaciona niveles, grados y paralelos dentro de cada periodo.',
                    'url' => baseUrl('cursos'),
                    'icon' => 'fa-book',
                ],
            ]
        );
    }

    public function reports(): void
    {
        $this->renderModuleHome(
            'reportes',
            'reportes_home',
            'Reportes',
            'Consolida salidas de informacion y consultas ejecutivas. Los reportes aun no estan implementados por completo.',
            [
                [
                    'label' => 'Reporte de estudiantes',
                    'description' => 'Punto de entrada previsto para reportes estudiantiles.',
                    'url' => null,
                    'icon' => 'fa-file-text-o',
                ],
                [
                    'label' => 'Reporte de personal',
                    'description' => 'Punto de entrada previsto para reportes de personal institucional.',
                    'url' => null,
                    'icon' => 'fa-file-text',
                ],
            ]
        );
    }

    public function security(): void
    {
        $this->renderModuleHome(
            'seguridad',
            'seguridad_home',
            'Seguridad',
            'Centraliza catalogos de seguridad, usuarios, roles y asignacion de permisos.',
            [
                [
                    'label' => 'Catalogos',
                    'description' => 'Administra roles, permisos y catalogos base de seguridad.',
                    'url' => baseUrl('seguridad/catalogos'),
                    'icon' => 'fa-tags',
                ],
                [
                    'label' => 'Usuarios',
                    'description' => 'Gestiona usuarios del sistema y su estado de acceso.',
                    'url' => baseUrl('seguridad/usuarios'),
                    'icon' => 'fa-user',
                ],
                [
                    'label' => 'Designacion de permisos',
                    'description' => 'Define permisos funcionales para cada rol del sistema.',
                    'url' => baseUrl('seguridad/roles-permisos'),
                    'icon' => 'fa-key',
                ],
                [
                    'label' => 'Roles por usuario',
                    'description' => 'Asigna uno o varios roles a cada cuenta de usuario.',
                    'url' => baseUrl('seguridad/usuarios-roles'),
                    'icon' => 'fa-users',
                ],
                [
                    'label' => 'Auditoria',
                    'description' => 'Espacio reservado para trazabilidad y bitacora del sistema.',
                    'url' => null,
                    'icon' => 'fa-search',
                ],
            ]
        );
    }

    private function renderModuleHome(
        string $module,
        string $section,
        string $title,
        string $description,
        array $cards
    ): void {
        $user = $this->requireAuth();

        $this->view('module.home', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => $title,
            'currentModule' => $module,
            'currentSection' => $section,
            'user' => $user,
            'moduleDescription' => $description,
            'moduleCards' => $cards,
        ]);
    }
}
