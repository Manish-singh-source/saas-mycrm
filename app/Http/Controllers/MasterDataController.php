<?php

namespace App\Http\Controllers;

use App\Models\BusinessType;
use App\Models\Currency;
use App\Models\DateFormat;
use App\Models\Industry;
use App\Models\Language;
use App\Models\TimeFormat;
use App\Models\Timezone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Throwable;

class MasterDataController extends Controller
{
    public function businessTypes(Request $request): JsonResponse
    {
        return $this->list($request, BusinessType::query(), 'Business types fetched successfully.', ['name'], [
            'code' => ['sometimes', 'string', 'max:80'],
        ]);
    }

    public function industries(Request $request): JsonResponse
    {
        return $this->list($request, Industry::query(), 'Industries fetched successfully.', ['name'], [
            'code' => ['sometimes', 'string', 'max:80'],
        ]);
    }

    public function currencies(Request $request): JsonResponse
    {
        return $this->list($request, Currency::query(), 'Currencies fetched successfully.', ['name', 'code', 'symbol'], [
            'code' => ['sometimes', 'string', 'size:3'],
            'symbol' => ['sometimes', 'string', 'max:10'],
            'decimal_places' => ['sometimes', 'integer', 'min:0', 'max:10'],
        ]);
    }

    public function languages(Request $request): JsonResponse
    {
        return $this->list($request, Language::query(), 'Languages fetched successfully.', ['name', 'code', 'native_name'], [
            'code' => ['sometimes', 'string', 'size:2'],
            'iso3' => ['sometimes', 'string', 'size:3'],
        ]);
    }

    public function timezones(Request $request): JsonResponse
    {
        return $this->list($request, Timezone::query(), 'Timezones fetched successfully.', ['name', 'identifier', 'utc_offset'], [
            'identifier' => ['sometimes', 'string', 'max:100'],
            'utc_offset' => ['sometimes', 'string', 'max:10'],
        ]);
    }

    public function dateFormats(Request $request): JsonResponse
    {
        return $this->list($request, DateFormat::query(), 'Date formats fetched successfully.', ['name', 'code', 'format'], [
            'code' => ['sometimes', 'string', 'max:50'],
            'format' => ['sometimes', 'string', 'max:50'],
        ]);
    }

    public function timeFormats(Request $request): JsonResponse
    {
        return $this->list($request, TimeFormat::query(), 'Time formats fetched successfully.', ['name', 'code', 'format'], [
            'code' => ['sometimes', 'string', 'max:50'],
            'format' => ['sometimes', 'string', 'max:50'],
        ]);
    }

    private function list(Request $request, Builder $query, string $message, array $searchColumns, array $additionalRules = []): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), array_merge([
                'search' => ['sometimes', 'string', 'max:100'],
                'status' => ['sometimes', 'in:active,inactive'],
            ], $additionalRules));

            if ($validator->fails()) {
                return $this->validationError($validator->errors()->toArray());
            }

            $filters = $validator->validated();
            $query->where('status', $filters['status'] ?? 'active');

            if (! empty($filters['search'])) {
                $query->where(function (Builder $searchQuery) use ($filters, $searchColumns): void {
                    foreach ($searchColumns as $index => $column) {
                        $method = $index === 0 ? 'where' : 'orWhere';
                        $searchQuery->{$method}($column, 'like', '%' . $filters['search'] . '%');
                    }
                });
            }

            foreach (array_diff_key($filters, array_flip(['search', 'status'])) as $column => $value) {
                $query->where($column, $value);
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $query->orderBy('sort_order')->orderBy('name')->get(),
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong. Please try again later.',
                'data' => null,
            ], 500);
        }
    }

    private function validationError(array $errors): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed.',
            'errors' => $errors,
        ], 422);
    }
}