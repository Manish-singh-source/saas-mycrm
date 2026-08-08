<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SignedFileDownloadController extends Controller
{
    public function __invoke(Request $request, string $file_uuid)
    {
        abort_unless($request->hasValidSignature(), 403);

        $file = DB::table('files')->where('uuid', $file_uuid)->whereNull('deleted_at')->first();
        abort_if(! $file, 404);

        return Storage::disk($file->disk)->download($file->path, $file->original_name);
    }
}
