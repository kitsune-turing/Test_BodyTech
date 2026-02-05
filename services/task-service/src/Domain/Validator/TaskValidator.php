<?php

declare(strict_types=1);

namespace TaskService\Domain\Validator;

use TaskService\Domain\ValueObject\TaskStatus;

/**
 * Task Validator
 * Validates task data according to business rules
 */
final class TaskValidator
{
    private const MAX_DESCRIPTION_LENGTH = 1000;

    /**
     * Validate task creation/update data
     */
    public function validate(array $data): array
    {
        $errors = [];

        // Valida el título de la tarea
        if (empty($data['title'] ?? '')) {
            $errors['title'][] = 'Title is required';
        } elseif (strlen($data['title']) > 255) {
            $errors['title'][] = 'Title cannot exceed 255 characters';
        }

        // Valida la descripción (campo opcional)
        if (isset($data['description']) && strlen($data['description']) > self::MAX_DESCRIPTION_LENGTH) {
            $errors['description'][] = sprintf(
                'Description cannot exceed %d characters',
                self::MAX_DESCRIPTION_LENGTH
            );
        }

        // Valida el estado de la tarea
        if (isset($data['status'])) {
            try {
                new TaskStatus($data['status']);
            } catch (\InvalidArgumentException $e) {
                $errors['status'][] = $e->getMessage();
            }
        }

        return $errors;
    }

    /**
     * Validate filter parameters
     */
    public function validateFilters(array $filters): array
    {
        $errors = [];

        // Valida el estado si está presente en los filtros
        if (isset($filters['status'])) {
            try {
                new TaskStatus($filters['status']);
            } catch (\InvalidArgumentException $e) {
                $errors['status'][] = $e->getMessage();
            }
        }

        // Valida el número de página si está presente
        if (isset($filters['page'])) {
            $page = (int) $filters['page'];
            if ($page < 1) {
                $errors['page'][] = 'Page must be greater than 0';
            }
        }

        // Valida el límite de resultados si está presente
        if (isset($filters['limit'])) {
            $limit = (int) $filters['limit'];
            if ($limit < 1 || $limit > 100) {
                $errors['limit'][] = 'Limit must be between 1 and 100';
            }
        }

        // Valida el campo de ordenamiento si está presente
        if (isset($filters['sort'])) {
            $allowedSortFields = ['created_at', 'updated_at', 'title', 'status'];
            if (!in_array($filters['sort'], $allowedSortFields, true)) {
                $errors['sort'][] = 'Invalid sort field. Allowed: ' . implode(', ', $allowedSortFields);
            }
        }

        // Valida el orden de clasificación si está presente
        if (isset($filters['order'])) {
            $order = strtoupper($filters['order']);
            if (!in_array($order, ['ASC', 'DESC'], true)) {
                $errors['order'][] = 'Order must be ASC or DESC';
            }
        }

        // El campo de búsqueda es texto libre - no requiere validación especial.
        // Solo verifica que sea string si está presente.
        if (isset($filters['search']) && !is_string($filters['search'])) {
            $errors['search'][] = 'Search must be a string';
        }

        return $errors;
    }
}
