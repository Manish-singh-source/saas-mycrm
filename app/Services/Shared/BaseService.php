<?php

namespace App\Services\Shared;

use Closure;
use Illuminate\Support\Facades\DB;

abstract class BaseService
{
    /**
     * Execute write operations inside a database transaction.
     *
     * @template TReturn
     * @param  Closure(): TReturn  $callback
     * @return TReturn
     */
    protected function transaction(Closure $callback): mixed
    {
        return DB::transaction($callback);
    }
}
