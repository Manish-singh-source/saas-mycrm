<?php

namespace App\DTOs;

final readonly class CalendarEventData
{
    public function __construct(
        public int $tenantId,
        public int $calendarId,
        public string $title,
        public string $startsAt,
        public ?string $endsAt = null,
        public ?string $description = null,
        public ?string $location = null,
        public string $timezone = 'UTC',
        public bool $allDay = false,
        public array $recurrenceRule = [],
        public array $attendees = [],
    ) {}
}