<?php

declare(strict_types=1);

namespace TaskService\Infrastructure\Http\Controller;

use TaskService\Application\UseCase\ExportTasksService;

/**
 * Export Controller
 * Handles export requests for tasks
 */
final class ExportController
{
    private ExportTasksService $exportTasksService;

    public function __construct(ExportTasksService $exportTasksService)
    {
        $this->exportTasksService = $exportTasksService;
    }

    public function exportCSV(int $userId, array $filters = []): array
    {
        try {
            $csv = $this->exportTasksService->exportToCSV($userId, $filters);

            return [
                'status' => 200,
                'data' => $csv,
                'headers' => [
                    'Content-Type' => 'text/csv',
                    'Content-Disposition' => 'attachment; filename="tareas_' . date('Y-m-d_H-i-s') . '.csv"',
                ],
            ];
        } catch (\Exception $e) {
            return $this->handleError($e);
        }
    }

    public function exportPDF(int $userId, array $filters = []): array
    {
        try {
            $pdf = $this->exportTasksService->exportToPDF($userId, $filters);

            return [
                'status' => 200,
                'data' => $pdf,
                'headers' => [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="tareas_' . date('Y-m-d_H-i-s') . '.pdf"',
                ],
            ];
        } catch (\Exception $e) {
            return $this->handleError($e);
        }
    }

    private function handleError(\Exception $e): array
    {
        $errorData = json_decode($e->getMessage(), true);

        if (is_array($errorData)) {
            return [
                'status' => 400,
                'data' => ['error' => $errorData],
            ];
        }

        return [
            'status' => 500,
            'data' => [
                'error' => [
                    'code' => 'INTERNAL_ERROR',
                    'message' => 'An error occurred while exporting',
                ],
            ],
        ];
    }
}
