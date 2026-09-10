<?php
namespace App\Http\Controllers\Shared;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
abstract class BaseApiController extends \App\Http\Controllers\Controller {
 public function success(mixed $data=null,string $message='Success.',int $status=200,array $meta=[]): JsonResponse { return ApiResponse::success($data,$message,$status,$meta); }
 public function list(array $items,$page,string $message='OK',array $extra=[]): JsonResponse { return ApiResponse::success(array_merge(['items'=>$items],$extra),$message,200,['current_page'=>$page->currentPage(),'per_page'=>$page->perPage(),'total'=>$page->total(),'last_page'=>$page->lastPage()]); }
 public function businessError(string $message,string $code,int $status=409): JsonResponse { return ApiResponse::businessError($message,$code,$status); }
}

