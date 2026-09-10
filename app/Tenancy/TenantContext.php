<?php
namespace App\Tenancy;
final class TenantContext { public function id(): int { return (int) request()->attributes->get('tenant_id'); } }

