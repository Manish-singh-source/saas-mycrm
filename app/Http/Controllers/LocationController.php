<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Country;
use App\Models\State;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Throwable;

class LocationController extends Controller
{
    public function countries(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'search' => ['sometimes', 'string', 'max:100'],
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors()->toArray());
            }

            $search = $validator->validated()['search'] ?? null;
            $countries = Country::query()
                ->select(['id', 'name', 'iso2', 'iso3', 'phone_code', 'currency_code', 'status'])
                ->where('status', 'active')
                ->when($search !== null && $search !== '', function ($query) use ($search): void {
                    $query->where('name', 'like', '%' . $search . '%');
                })
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();

            return $this->listResponse($countries, 'Countries fetched successfully.');
        } catch (Throwable $exception) {
            report($exception);
            return $this->serverError();
        }
    }

    public function states(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'country_id' => ['required', 'integer', 'exists:countries,id'],
                'search' => ['sometimes', 'string', 'max:100'],
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors()->toArray());
            }

            $data = $validator->validated();
            $states = State::query()
                ->select(['id', 'country_id', 'name', 'code', 'status'])
                ->where('country_id', $data['country_id'])
                ->where('status', 'active')
                ->when(! empty($data['search']), function ($query) use ($data): void {
                    $query->where('name', 'like', '%' . $data['search'] . '%');
                })
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();

            return $this->listResponse($states, 'States fetched successfully.');
        } catch (Throwable $exception) {
            report($exception);
            return $this->serverError();
        }
    }

    public function cities(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'state_id' => ['required', 'integer', 'exists:states,id'],
                'search' => ['sometimes', 'string', 'max:100'],
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors()->toArray());
            }

            $data = $validator->validated();
            $cities = City::query()
                ->select(['id', 'state_id', 'name', 'status'])
                ->where('state_id', $data['state_id'])
                ->where('status', 'active')
                ->when(! empty($data['search']), function ($query) use ($data): void {
                    $query->where('name', 'like', '%' . $data['search'] . '%');
                })
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();

            return $this->listResponse($cities, 'Cities fetched successfully.');
        } catch (Throwable $exception) {
            report($exception);
            return $this->serverError();
        }
    }

    private function listResponse($items, string $message): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $items,
        ]);
    }

    private function validationError(array $errors): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed.',
            'errors' => $errors,
        ], 422);
    }

    private function serverError(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Something went wrong. Please try again later.',
            'data' => null,
        ], 500);
    }
}