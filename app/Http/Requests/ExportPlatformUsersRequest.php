<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
final class ExportPlatformUsersRequest extends ApiFormRequest {
 public function authorize(): bool { return true; }
 public function rules(): array { return []; }
}