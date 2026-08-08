<?php

namespace App\Http\Controllers\Common;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LocationController extends Controller
{
    public function countries(Request $request): JsonResponse
    {
        $query = DB::table('countries')
            ->where('status', 'active')
            ->select(['id', 'name', 'iso2', 'iso3', 'phone_code', 'currency_code']);

        if ($request->filled('search')) {
            $search = '%'.$request->string('search')->toString().'%';
            $query->where(fn ($q) => $q->where('name', 'like', $search)->orWhere('iso2', 'like', $search)->orWhere('iso3', 'like', $search));
        }

        return ApiResponse::success(['countries' => $query->orderBy('name')->get()]);
    }

    public function states(Request $request): JsonResponse
    {
        $data = $request->validate([
            'country_id' => ['required_without:country_iso2', 'integer', 'exists:countries,id'],
            'country_iso2' => ['required_without:country_id', 'string', 'size:2'],
            'search' => ['nullable', 'string', 'max:150'],
        ]);

        $countryId = $data['country_id'] ?? DB::table('countries')->where('iso2', strtoupper($data['country_iso2']))->value('id');

        $query = DB::table('states')
            ->where('country_id', $countryId)
            ->where('status', 'active')
            ->select(['id', 'country_id', 'name', 'code']);

        if ($request->filled('search')) {
            $search = '%'.$request->string('search')->toString().'%';
            $query->where(fn ($q) => $q->where('name', 'like', $search)->orWhere('code', 'like', $search));
        }

        return ApiResponse::success(['states' => $query->orderBy('name')->get()]);
    }

    public function cities(Request $request): JsonResponse
    {
        $data = $request->validate([
            'state_id' => ['required', 'integer', 'exists:states,id'],
            'country_id' => ['nullable', 'integer', 'exists:countries,id'],
            'search' => ['nullable', 'string', 'max:150'],
        ]);

        $query = DB::table('cities')
            ->where('state_id', $data['state_id'])
            ->where('status', 'active')
            ->select(['id', 'country_id', 'state_id', 'name']);

        if (! empty($data['country_id'])) {
            $query->where('country_id', $data['country_id']);
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->string('search')->toString().'%');
        }

        return ApiResponse::success(['cities' => $query->orderBy('name')->get()]);
    }
}
