<?php

namespace App\DTOs;

use App\Enums\Visibility;

final readonly class FileData
{
    public function __construct(
        public string $disk,
        public string $path,
        public string $originalName,
        public ?int $tenantId = null,
        public ?int $uploadedBy = null,
        public ?string $mimeType = null,
        public ?string $extension = null,
        public int $sizeBytes = 0,
        public ?string $checksum = null,
        public Visibility $visibility = Visibility::Private,
    ) {}
}