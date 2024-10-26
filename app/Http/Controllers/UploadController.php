<?php

namespace App\Http\Controllers;

use App\Exports\DynamicExport;
use App\Http\Requests\{BulkRequest, ExportRequest, ImportRequest, IndexRequest, UploadRequest};
use App\Http\Resources\UploadResource;
use App\Imports\DynamicImport;
use App\Jobs\SendExportEmail;
use App\Models\Upload;
use App\Traits\ExceptionHandlerTrait;
use App\Utils\Sqid;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\{JsonResponse, Resources\Json\AnonymousResourceCollection};
use Illuminate\Support\Facades\{DB, Hash};
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class UploadController
 *
 * @package App\Http\Controllers
 *
 * @author Muibi Azeez Abolade
 * @email muibi.azeezabolade@gmail.com
 * @since 2024-10-24
 * @version 1.0
 *
 * @tags Uploads
 *
 * ${Description}
 */
class UploadController extends Controller
{
    use ExceptionHandlerTrait;
    /**
     * Display a listing of the uploads.
     *
     * @param IndexRequest $request
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function index(IndexRequest $request)
    {
        try {
            $query = Upload::query()->with('user')
                ->when($request->with_trashed, fn($q) => $q->withTrashed())
                ->when($request->search, fn($q, $search) => app('search')->apply($q, $search, ['filename', 'original_filename', 'user.name']))
                ->when($request->order_by, fn($q, $orderBy) => $q->orderBy($orderBy ?? 'created_at', $request->order_direction ?? 'asc'))
                ->when($request->start_date && $request->end_date, fn($q) => $q->custom($request->start_date, $request->end_date));
            $uploads = $query->paginate($request->per_page ?? config('app.per_page'));
            return response()->paginatedSuccess(UploadResource::collection($uploads), 'Uploads retrieved successfully');
        } catch (Exception $e) {
            return $this->handleException($e, 'fetching uploads');
        }
    }

    /**
     * Store a newly created upload in storage.
     *
     * @param UploadRequest $request
     * @return JsonResponse
     */
    public function store(UploadRequest $request): JsonResponse
    {
        try {
            return DB::transaction(function () use ($request) {
                $upload = Upload::create($request->validated() + ['password' => Hash::make($request->password)]);
                return response()->created(new UploadResource($upload), 'Upload created successfully');
            });
        } catch (Exception $e) {
            return $this->handleException($e, 'creating the upload');
        }
    }

    /**
     * Display the specified upload.
     *
     * @param string $sqid
     * @return JsonResponse
     */
    public function show(string $sqid): JsonResponse
    {
        try {
            $upload = Upload::findBySqidOrFail($sqid);
            return response()->success(new UploadResource($upload), 'Upload retrieved successfully');
        } catch (NotFoundHttpException|ModelNotFoundException $e) {
            return $this->handleNotFoundException($e);
        } catch (Exception $e) {
            return $this->handleException($e, 'fetching the upload');
        }
    }

    /**
     * Update the specified upload in storage.
     *
     * @param UploadRequest $request
     * @param string $sqid
     * @return JsonResponse
     */
    public function update(UploadRequest $request, string $sqid): JsonResponse
    {
        try {
            return DB::transaction(function () use ($request, $sqid) {
                $upload = Upload::findBySqidOrFail($sqid);
                $upload->update($request->validated());
                return response()->success(new UploadResource($upload), 'Upload updated successfully');
            });
        } catch (NotFoundHttpException|ModelNotFoundException $e) {
            return $this->handleNotFoundException($e);
        } catch (Exception $e) {
            return $this->handleException($e, 'updating the upload');
        }
    }

    /**
     * Remove the specified upload from storage.
     *
     * @param string $sqid
     * @return JsonResponse
     */
    public function destroy(string $sqid): JsonResponse
    {
        try {
            return DB::transaction(function () use ($sqid) {
                $upload = Upload::findBySqidOrFail($sqid);
                $upload->delete();
                return response()->success(null, 'Upload deleted successfully');
            });
        } catch (NotFoundHttpException|ModelNotFoundException $e) {
            return $this->handleNotFoundException($e);
        } catch (Exception $e) {
            return $this->handleException($e, 'deleting the upload');
        }
    }

    /**
     * Restore the specified upload from storage.
     *
     * @param string $sqid
     * @return JsonResponse
     */
    public function restore(string $sqid): JsonResponse
    {
        try {
            return DB::transaction(function () use ($sqid) {
                $upload = Upload::withTrashed()->findOrFail(Sqid::decode($sqid));
                $upload->restore();
                return response()->success(new UploadResource($upload), 'Upload restored successfully');
            });
        } catch (NotFoundHttpException|ModelNotFoundException $e) {
            return $this->handleNotFoundException($e);
        } catch (Exception $e) {
            return $this->handleException($e, 'restoring the upload');
        }
    }

    /**
     * Bulk delete uploads from storage.
     *
     * @param BulkRequest $request
     * @return JsonResponse
     */
    public function bulkDelete(BulkRequest $request): JsonResponse
    {
        try {
            return DB::transaction(function () use ($request) {
                $ids = array_map([Sqid::class, 'decode'], $request->input('sqids', []));
                Upload::whereIn('id', $ids)->delete();
                return response()->success(null, 'Uploads deleted successfully');
            });
        } catch (NotFoundHttpException|ModelNotFoundException $e) {
            return $this->handleNotFoundException($e);
        } catch (Exception $e) {
            return $this->handleException($e, 'bulk deleting uploads');
        }
    }

    /**
     * Bulk restore uploads from storage.
     *
     * @param BulkRequest $request
     * @return JsonResponse
     */
    public function bulkRestore(BulkRequest $request): JsonResponse
    {
        try {
            return DB::transaction(function () use ($request) {
                $ids = array_map([Sqid::class, 'decode'], $request->input('sqids', []));
                $uploads = Upload::withTrashed()->whereIn('id', $ids)->restore();
                return response()->success(UploadResource::collection($uploads), 'Uploads restored successfully');
            });
        } catch (NotFoundHttpException|ModelNotFoundException $e) {
            return $this->handleNotFoundException($e);
        } catch (Exception $e) {
            return $this->handleException($e, 'bulk restoring uploads');
        }
    }

    /**
     * Force delete the specified upload from storage.
     *
     * @param string $sqid
     * @return JsonResponse
     */
    public function forceDelete(string $sqid): JsonResponse
    {
        try {
            return DB::transaction(function () use ($sqid) {
                $upload = Upload::withTrashed()->findOrFail(Sqid::decode($sqid));
                $upload->forceDelete();
                return response()->success(null, 'Upload permanently deleted successfully');
            });
        } catch (NotFoundHttpException|ModelNotFoundException $e) {
            return $this->handleNotFoundException($e);
        } catch (Exception $e) {
            return $this->handleException($e, 'permanently deleting the upload');
        }
    }

    /**
     * Import uploads from a file.
     *
     * @param ImportRequest $request
     * @return JsonResponse
     */
    public function import(ImportRequest $request): JsonResponse
    {
        try {
            $modelClass = 'App\\Models\\' . $request->model;
            $import = new DynamicImport($modelClass, $request->update_existing ?? false);
            Excel::import($import, $request->file('file'));
            return response()->success($request->model . 's imported successfully.');
        } catch (Exception $e) {
            return $this->handleException($e, 'importing ' . $request->model . 's');
        }
    }

    /**
     * Export uploads to a file and send via email.
     *
     * @param ExportRequest $request
     * @return JsonResponse
     */
    public function export(ExportRequest $request): JsonResponse
    {
        try {
            $modelClass = 'App\\Models\\' . $request->model;
            $fileName = strtolower($request->model) . '_export_' . now()->format('Y-m-d_H-i-s') . '.' . $request->file_type;
            $export = new DynamicExport($modelClass, $request->start_date, $request->end_date, $request->columns);
            Excel::store($export, $fileName, 'local');
            foreach ($request->emails as $email) {
                SendExportEmail::dispatch($email, $fileName, $request->columns, $request->model);
            }
            return response()->success($request->model . ' export initiated. You will receive an email shortly.');
        } catch (Exception $e) {
            return $this->handleException($e, 'exporting ' . $request->model . 's');
        }
    }
}
