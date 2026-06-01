<?php

declare(strict_types=1);

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;

class PdfReportService
{
    public function renderView(string $view, array $data = []): string
    {
        $viewPath = BASE_PATH . '/app/views/' . str_replace('.', '/', $view) . '.php';

        if (!file_exists($viewPath)) {
            throw new \RuntimeException('La plantilla del PDF no existe.');
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $viewPath;
        $html = ob_get_clean();

        if (!is_string($html) || trim($html) === '') {
            throw new \RuntimeException('La plantilla del PDF no genero contenido.');
        }

        return $html;
    }

    public function make(string $html, string $paper = 'A4', string $orientation = 'portrait'): Dompdf
    {
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('chroot', BASE_PATH . '/public');

        $pdf = new Dompdf($options);
        $pdf->setPaper($paper, $orientation);
        $pdf->loadHtml($html, 'UTF-8');
        $pdf->render();

        return $pdf;
    }

    public function streamView(
        string $view,
        array $data,
        string $filename,
        string $paper = 'A4',
        string $orientation = 'portrait',
        bool $download = false
    ): void {
        $pdf = $this->make($this->renderView($view, $data), $paper, $orientation);
        $pdf->stream($this->safeFilename($filename), ['Attachment' => $download]);
        exit;
    }

    private function safeFilename(string $filename): string
    {
        $name = trim($filename);

        if ($name === '') {
            $name = 'reporte.pdf';
        }

        if (!str_ends_with(strtolower($name), '.pdf')) {
            $name .= '.pdf';
        }

        $base = pathinfo($name, PATHINFO_FILENAME);
        $base = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $base);
        $base = strtolower((string) $base);
        $base = preg_replace('/[^a-z0-9]+/', '-', $base) ?? '';
        $base = trim($base, '-');

        return ($base !== '' ? $base : 'reporte') . '.pdf';
    }
}
