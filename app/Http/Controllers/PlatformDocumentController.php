<?php
namespace App\Http\Controllers;

use App\Http\Requests\ListPlatformAttachmentsRequest;
use App\Http\Requests\ListPlatformFilesRequest;
use App\Http\Requests\ListPlatformNotesRequest;
use App\Http\Requests\StorePlatformAttachmentRequest;
use App\Http\Requests\StorePlatformFileRequest;
use App\Http\Requests\StorePlatformNoteRequest;
use App\Http\Requests\UpdatePlatformNoteRequest;
use App\Models\ActivityLog;
use App\Models\Attachment;
use App\Models\File;
use App\Models\Note;
use App\Support\ActivityLogger;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class PlatformDocumentController extends Controller
{
    private const ENTITY_TYPES = [
        'platform_user' => \App\Models\PlatformUser::class, 'platform_users' => \App\Models\PlatformUser::class,
        'platform_team' => \App\Models\PlatformTeam::class, 'platform_teams' => \App\Models\PlatformTeam::class,
        'platform_role' => \App\Models\PlatformRole::class, 'platform_roles' => \App\Models\PlatformRole::class,
        'platform_department' => \App\Models\PlatformDepartment::class, 'platform_departments' => \App\Models\PlatformDepartment::class,
        'platform_designation' => \App\Models\PlatformDesignation::class, 'platform_designations' => \App\Models\PlatformDesignation::class,
        'platform_announcement' => \App\Models\PlatformAnnouncement::class, 'platform_announcements' => \App\Models\PlatformAnnouncement::class,
        'platform_legal_document' => \App\Models\LegalDocument::class, 'legal_document' => \App\Models\LegalDocument::class,
        'platform_ticket' => \App\Models\PlatformTicket::class, 'platform_tickets' => \App\Models\PlatformTicket::class,
        'knowledge_base_article' => \App\Models\KnowledgeBaseArticle::class, 'knowledge_base_category' => \App\Models\KnowledgeBaseCategory::class,
    ];

    public function files(ListPlatformFilesRequest $request): mixed
    {
        $query = File::with(['platformUploader','uploader','attachments'])->whereNull('tenant_id')->latest();
        if ($request->filled('search')) $query->where('original_name', 'like', '%'.$request->string('search').'%');
        if ($request->filled('filter.visibility')) $query->where('visibility', $request->input('filter.visibility'));
        if ($request->filled('filter.mime_type')) $query->where('mime_type', $request->input('filter.mime_type'));
        $page = $query->paginate($request->integer('per_page', 25))->withQueryString();
        return ApiResponse::success(collect($page->items())->map(fn(File $file) => $this->fileData($file))->values(), 'Files fetched.', 200, $this->meta($page));
    }

    public function storeFile(StorePlatformFileRequest $request): mixed
    {
        $upload = $request->file('file'); $disk = $request->input('disk', config('filesystems.default')); $uuid = (string) Str::uuid();
        $path = 'platform/files/'.$uuid.'/'.Str::random(20).'.'.($upload->getClientOriginalExtension() ?: 'bin');
        $stored = Storage::disk($disk)->putFileAs(dirname($path), $upload, basename($path));
        if (! $stored) return ApiResponse::error('File could not be stored.', 500, null, 'FILE_STORAGE_FAILED');
        $file = File::create(['uuid'=>$uuid,'tenant_id'=>null,'platform_uploaded_by'=>$request->user()->getKey(),'disk'=>$disk,'path'=>$path,'original_name'=>$upload->getClientOriginalName(),'mime_type'=>$upload->getClientMimeType(),'extension'=>$upload->getClientOriginalExtension() ?: null,'size_bytes'=>$upload->getSize(),'checksum'=>hash_file('sha256', $upload->getRealPath()),'visibility'=>$request->input('visibility','private')]);
        ActivityLogger::record($request, 'file.created', $file, 'Platform file uploaded.', $file->only(['uuid','original_name','mime_type','size_bytes','visibility']));
        return ApiResponse::success(['file'=>$this->fileData($file->fresh()->load(['platformUploader','uploader','attachments']))], 'File uploaded successfully.', 201);
    }

    public function showFile(string $fileUuid): mixed
    { $file = $this->platformFile($fileUuid); return $file ? ApiResponse::success(['file'=>$this->fileData($file->load(['platformUploader','uploader','attachments']))], 'File fetched.') : ApiResponse::error('File not found.',404,null,'FILE_NOT_FOUND'); }

    public function downloadFile(string $fileUuid): mixed
    {
        $file = $this->platformFile($fileUuid); if (! $file) return ApiResponse::error('File not found.',404,null,'FILE_NOT_FOUND');
        $disk = Storage::disk($file->disk); $expires = $file->visibility === 'public' ? null : now()->addMinutes(10);
        try { $url = $expires ? $disk->temporaryUrl($file->path, $expires) : $disk->url($file->path); } catch (\Throwable) { $url = $disk->url($file->path); }
        return ApiResponse::success(['url'=>$url,'expires_at'=>$expires?->toISOString()], 'Download URL generated.');
    }

    public function destroyFile(Request $request, string $fileUuid): mixed
    {
        $file = $this->platformFile($fileUuid); if (! $file) return ApiResponse::error('File not found.',404,null,'FILE_NOT_FOUND');
        if ($file->attachments()->exists()) return ApiResponse::error('Attached files cannot be deleted.',409,null,'FILE_ATTACHED');
        Storage::disk($file->disk)->delete($file->path); $file->delete(); ActivityLogger::record($request, 'file.deleted', $file, 'Platform file deleted.');
        return ApiResponse::success(null, 'File deleted successfully.');
    }

    public function attachments(ListPlatformAttachmentsRequest $request): mixed
    {
        $entity = $this->resolveEntity($request->input('attachable_type'), $request->input('attachable_uuid')); if (! $entity) return ApiResponse::error('Attachable entity not found.',404,null,'ATTACHABLE_NOT_FOUND');
        $page = Attachment::with(['file','attachable','createdBy'])->whereNull('tenant_id')->where('attachable_type', $entity::class)->where('attachable_id',$entity->getKey())->whereHas('file',fn($q)=>$q->whereNull('deleted_at')->whereNull('tenant_id'))->latest('created_at')->paginate($request->integer('per_page',25))->withQueryString();
        return ApiResponse::success(collect($page->items())->map(fn(Attachment $a)=>$this->attachmentData($a))->values(), 'Attachments fetched.',200,$this->meta($page));
    }

    public function storeAttachment(StorePlatformAttachmentRequest $request): mixed
    {
        $file=$this->platformFile($request->input('file_uuid')); if(!$file) return ApiResponse::error('File not found.',404,null,'FILE_NOT_FOUND');
        $entity=$this->resolveEntity($request->input('attachable_type'),$request->input('attachable_uuid')); if(!$entity) return ApiResponse::error('Attachable entity not found.',404,null,'ATTACHABLE_NOT_FOUND');
        $attachment=Attachment::create(['tenant_id'=>null,'file_id'=>$file->getKey(),'attachable_type'=>$entity::class,'attachable_id'=>$entity->getKey(),'label'=>$request->input('label'),'created_by'=>null,'created_at'=>now()]);
        ActivityLogger::record($request,'attachment.created',$attachment,'Platform attachment created.',['file_uuid'=>$file->uuid,'attachable_type'=>$entity::class,'attachable_id'=>$entity->getKey()]);
        return ApiResponse::success(['attachment'=>$this->attachmentData($attachment->fresh()->load(['file','attachable','createdBy']))],'Attachment created successfully.',201);
    }

    public function destroyAttachment(Request $request,int $attachmentId): mixed
    { $a=Attachment::with(['file','attachable','createdBy'])->whereNull('tenant_id')->find($attachmentId); if(!$a)return ApiResponse::error('Attachment not found.',404,null,'ATTACHMENT_NOT_FOUND'); $data=$a->toArray(); $a->delete(); ActivityLogger::record($request,'attachment.deleted',$a,'Platform attachment deleted.',$data); return ApiResponse::success(null,'Attachment deleted successfully.'); }

    public function notes(ListPlatformNotesRequest $request): mixed
    {
        $entity=$this->resolveEntity($request->input('notable_type'),$request->input('notable_uuid')); if(!$entity)return ApiResponse::error('Notable entity not found.',404,null,'NOTABLE_NOT_FOUND');
        $page=Note::with(['notable','createdBy','updatedBy','platformCreatedBy','platformUpdatedBy'])->whereNull('tenant_id')->where('notable_type',$entity::class)->where('notable_id',$entity->getKey())->latest()->paginate($request->integer('per_page',25))->withQueryString();
        return ApiResponse::success(collect($page->items())->map(fn(Note $n)=>$this->noteData($n))->values(),'Notes fetched.',200,$this->meta($page));
    }

    public function storeNote(StorePlatformNoteRequest $request): mixed
    {
        $entity=$this->resolveEntity($request->input('notable_type'),$request->input('notable_uuid')); if(!$entity)return ApiResponse::error('Notable entity not found.',404,null,'NOTABLE_NOT_FOUND'); $value=$request->input('note',$request->input('body'));
        $note=Note::create(['uuid'=>(string)Str::uuid(),'tenant_id'=>null,'notable_type'=>$entity::class,'notable_id'=>$entity->getKey(),'note'=>$value,'visibility'=>$request->input('visibility','tenant'),'platform_created_by'=>$request->user()->getKey()]); ActivityLogger::record($request,'note.created',$note,'Platform note created.',$note->only(['uuid','note','visibility']));
        return ApiResponse::success(['note'=>$this->noteData($note->fresh()->load(['notable','createdBy','updatedBy','platformCreatedBy','platformUpdatedBy']))],'Note created successfully.',201);
    }

    public function updateNote(UpdatePlatformNoteRequest $request,string $noteUuid): mixed
    { $note=Note::with(['notable','createdBy','updatedBy','platformCreatedBy','platformUpdatedBy'])->whereNull('tenant_id')->where('uuid',$noteUuid)->first(); if(!$note)return ApiResponse::error('Note not found.',404,null,'NOTE_NOT_FOUND'); $old=$note->only(['note','visibility']); $data=[]; if($request->has('note')||$request->has('body'))$data['note']=$request->input('note',$request->input('body')); if($request->has('visibility'))$data['visibility']=$request->input('visibility'); $data['platform_updated_by']=$request->user()->getKey(); $note->fill($data)->save(); ActivityLogger::record($request,'note.updated',$note,'Platform note updated.',['old'=>$old,'new'=>$note->only(['note','visibility'])]); return ApiResponse::success(['note'=>$this->noteData($note->fresh()->load(['notable','createdBy','updatedBy','platformCreatedBy','platformUpdatedBy']))],'Note updated successfully.'); }

    public function destroyNote(Request $request,string $noteUuid): mixed
    { $note=Note::whereNull('tenant_id')->where('uuid',$noteUuid)->first(); if(!$note)return ApiResponse::error('Note not found.',404,null,'NOTE_NOT_FOUND'); $note->delete(); ActivityLogger::record($request,'note.deleted',$note,'Platform note deleted.'); return ApiResponse::success(null,'Note deleted successfully.'); }

    private function platformFile(string $uuid): ?File { return File::whereNull('tenant_id')->whereNull('deleted_at')->where('uuid',$uuid)->first(); }
    private function resolveEntity(string $type,string $uuid): ?Model { $class=self::ENTITY_TYPES[strtolower($type)]??(class_exists($type)&&is_subclass_of($type,Model::class)?$type:null); if(!$class)return null; $query=$class::query(); if(in_array('tenant_id',(new $class)->getConnection()->getSchemaBuilder()->getColumnListing((new $class)->getTable()),true))$query->whereNull('tenant_id'); return $query->where('uuid',$uuid)->first(); }
    private function fileData(File $f): array { return $f->makeHidden(['path'])->toArray(); }
    private function attachmentData(Attachment $a): array { return $a->toArray(); }
    private function noteData(Note $n): array { return $n->toArray(); }
    private function meta($p): array { return ['current_page'=>$p->currentPage(),'per_page'=>$p->perPage(),'total'=>$p->total(),'last_page'=>$p->lastPage()]; }
}
