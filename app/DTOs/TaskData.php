<?php

namespace App\DTOs;

final readonly class TaskData
{
    public function __construct(
        public int $tenantId,
        public string $title,
        public ?int $projectId = null,
        public ?string $taskNumber = null,
        public ?int $parentTaskId = null,
        public ?string $description = null,
        public ?int $statusId = null,
        public ?int $priorityId = null,
        public ?int $assignedTo = null,
        public ?int $assignedTeamId = null,
        public ?string $startAt = null,
        public ?string $dueAt = null,
        public int $estimatedMinutes = 0,
        public bool $isRecurring = false,
        public array $recurrenceRule = [],
    ) {}
}