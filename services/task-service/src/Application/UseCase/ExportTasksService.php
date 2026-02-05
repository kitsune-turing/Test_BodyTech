<?php

declare(strict_types=1);

namespace TaskService\Application\UseCase;

use TaskService\Domain\Repository\TaskRepositoryInterface;
use TaskService\Domain\ValueObject\TaskStatus;
use TCPDF;

/**
 * Export Tasks Service
 * Handles exporting tasks to CSV and PDF formats
 */
final class ExportTasksService
{
    private TaskRepositoryInterface $taskRepository;

    public function __construct(TaskRepositoryInterface $taskRepository)
    {
        $this->taskRepository = $taskRepository;
    }

    /**
     * Export tasks to CSV format
     */
    public function exportToCSV(int $userId, array $filters = []): string
    {
        $status = isset($filters['status'])
            ? TaskStatus::fromString($filters['status'])
            : null;

        $search = $filters['search'] ?? null;

        $tasks = $this->taskRepository->findByUserIdPaginated(
            $userId,
            $status,
            $search,
            1000, // Max 1000 tasks for export
            0,
            'created_at',
            'DESC'
        );

        // Crea el contenido del archivo CSV
        $csv = "ID,Título,Descripción,Estado,Fecha de Creación,Última Actualización\n";

        foreach ($tasks as $task) {
            $taskArray = $task->toArray();
            $csv .= sprintf(
                "%d,\"%s\",\"%s\",\"%s\",\"%s\",\"%s\"\n",
                $taskArray['id'],
                str_replace('"', '""', $taskArray['title']),
                str_replace('"', '""', $taskArray['description'] ?? ''),
                $this->translateStatus($taskArray['status']),
                $taskArray['created_at'],
                $taskArray['updated_at']
            );
        }

        return $csv;
    }

    /**
     * Export tasks to PDF format
     */
    public function exportToPDF(int $userId, array $filters = []): string
    {
        $status = isset($filters['status'])
            ? TaskStatus::fromString($filters['status'])
            : null;

        $search = $filters['search'] ?? null;

        $tasks = $this->taskRepository->findByUserIdPaginated(
            $userId,
            $status,
            $search,
            1000, // Max 1000 tasks for export
            0,
            'created_at',
            'DESC'
        );

        // Crea el documento PDF
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

        // Configura la información del documento
        $pdf->SetCreator('BodyTech Task Manager');
        $pdf->SetAuthor('Task Management System');
        $pdf->SetTitle('Reporte de Tareas');
        $pdf->SetSubject('Exportación de Tareas');

        // Elimina el header/footer predeterminado
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Configura los márgenes del documento
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 15);

        // Agrega una nueva página
        $pdf->AddPage();

        // Configura la fuente del título
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'Reporte de Tareas', 0, 1, 'C');
        $pdf->Ln(5);

        // Agrega información de filtros aplicados
        $pdf->SetFont('helvetica', '', 10);
        $filterText = 'Filtros: ';
        if ($status) {
            $filterText .= 'Estado: ' . $this->translateStatus($status->getValue()) . ' | ';
        }
        if ($search) {
            $filterText .= 'Búsqueda: ' . $search . ' | ';
        }
        $filterText .= 'Fecha: ' . date('d/m/Y H:i');
        $pdf->Cell(0, 5, $filterText, 0, 1, 'L');
        $pdf->Ln(5);

        // Encabezado de la tabla
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetFillColor(255, 149, 0); // Orange color
        $pdf->SetTextColor(255, 255, 255); // White text
        $pdf->Cell(15, 7, 'ID', 1, 0, 'C', true);
        $pdf->Cell(60, 7, 'Título', 1, 0, 'C', true);
        $pdf->Cell(30, 7, 'Estado', 1, 0, 'C', true);
        $pdf->Cell(40, 7, 'Fecha Creación', 1, 0, 'C', true);
        $pdf->Cell(35, 7, 'Actualización', 1, 1, 'C', true);

        // Datos de la tabla
        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetTextColor(0, 0, 0);
        $fill = false;

        foreach ($tasks as $task) {
            $taskArray = $task->toArray();

            $pdf->Cell(15, 6, (string) $taskArray['id'], 1, 0, 'C', $fill);
            $pdf->Cell(60, 6, substr($taskArray['title'], 0, 35), 1, 0, 'L', $fill);
            $pdf->Cell(30, 6, $this->translateStatus($taskArray['status']), 1, 0, 'C', $fill);

            $createdDate = date('d/m/Y H:i', strtotime($taskArray['created_at']));
            $updatedDate = date('d/m/Y H:i', strtotime($taskArray['updated_at']));

            $pdf->Cell(40, 6, $createdDate, 1, 0, 'C', $fill);
            $pdf->Cell(35, 6, $updatedDate, 1, 1, 'C', $fill);

            $fill = !$fill;
        }

        // Agrega resumen al final del documento
        $pdf->Ln(5);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 5, 'Total de tareas: ' . count($tasks), 0, 1, 'L');

        return $pdf->Output('', 'S');
    }

    private function translateStatus(string $status): string
    {
        $translations = [
            'pending' => 'Pendiente',
            'in_progress' => 'En Progreso',
            'done' => 'Completado',
        ];

        return $translations[$status] ?? $status;
    }
}
