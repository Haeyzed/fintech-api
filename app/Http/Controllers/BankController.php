<?php

namespace App\Http\Controllers;

use App\Exports\DynamicExport;
use App\Http\Requests\{BulkRequest, ExportRequest, ImportRequest, IndexRequest, BankRequest};
use App\Http\Resources\BankResource;
use App\Imports\DynamicImport;
use App\Jobs\SendExportEmail;
use App\Models\Bank;
use App\Traits\ExceptionHandlerTrait;
use App\Utils\Sqid;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\{JsonResponse, Resources\Json\AnonymousResourceCollection};
use Illuminate\Support\Facades\{DB, Hash};
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class BankController
 *
 * @package App\Http\Controllers
 *
 * @author Muibi Azeez Abolade
 * @email muibi.azeezabolade@gmail.com
 * @since 2024-10-24
 * @version 1.0
 *
 * @tags Banks
 *
 * ${Description}
 */
class BankController extends Controller
{
    use ExceptionHandlerTrait;
    /**
     * Display a listing of the banks.
     *
     * @param IndexRequest $request
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function index(IndexRequest $request)
    {
        try {
            $query = Bank::query()->with(['country','currency'])
                ->when($request->with_trashed, fn($q) => $q->withTrashed())
                ->when($request->search, fn($q, $search) => app('search')->apply($q, $search, ['name', 'code', 'long_code']))
                ->when($request->order_by, fn($q, $orderBy) => $q->orderBy($orderBy ?? 'created_at', $request->order_direction ?? 'asc'))
                ->when($request->start_date && $request->end_date, fn($q) => $q->custom($request->start_date, $request->end_date));
            $banks = $query->paginate($request->per_page ?? config('app.per_page'));
            return response()->paginatedSuccess(BankResource::collection($banks), 'Banks retrieved successfully');
        } catch (Exception $e) {
            return $this->handleException($e, 'fetching banks');
        }
    }

    /**
     * Store a newly created bank in storage.
     *
     * @param BankRequest $request
     * @return JsonResponse
     */
    public function store(BankRequest $request): JsonResponse
    {
        try {
            return DB::transaction(function () use ($request) {
                $bank = Bank::create($request->validated() + ['password' => Hash::make($request->password)]);
                return response()->created(new BankResource($bank), 'Bank created successfully');
            });
        } catch (Exception $e) {
            return $this->handleException($e, 'creating the bank');
        }
    }

    /**
     * Display the specified bank.
     *
     * @param string $sqid
     * @return JsonResponse
     */
    public function show(string $sqid): JsonResponse
    {
        try {
            $bank = Bank::findBySqidOrFail($sqid);
            return response()->success(new BankResource($bank), 'Bank retrieved successfully');
        } catch (NotFoundHttpException|ModelNotFoundException $e) {
            return $this->handleNotFoundException($e);
        } catch (Exception $e) {
            return $this->handleException($e, 'fetching the bank');
        }
    }

    /**
     * Update the specified bank in storage.
     *
     * @param BankRequest $request
     * @param string $sqid
     * @return JsonResponse
     */
    public function update(BankRequest $request, string $sqid): JsonResponse
    {
        try {
            return DB::transaction(function () use ($request, $sqid) {
                $bank = Bank::findBySqidOrFail($sqid);
                $bank->update($request->validated());
                return response()->success(new BankResource($bank), 'Bank updated successfully');
            });
        } catch (NotFoundHttpException|ModelNotFoundException $e) {
            return $this->handleNotFoundException($e);
        } catch (Exception $e) {
            return $this->handleException($e, 'updating the bank');
        }
    }

    /**
     * Remove the specified bank from storage.
     *
     * @param string $sqid
     * @return JsonResponse
     */
    public function destroy(string $sqid): JsonResponse
    {
        try {
            return DB::transaction(function () use ($sqid) {
                $bank = Bank::findBySqidOrFail($sqid);
                $bank->delete();
                return response()->success(null, 'Bank deleted successfully');
            });
        } catch (NotFoundHttpException|ModelNotFoundException $e) {
            return $this->handleNotFoundException($e);
        } catch (Exception $e) {
            return $this->handleException($e, 'deleting the bank');
        }
    }

    /**
     * Restore the specified bank from storage.
     *
     * @param string $sqid
     * @return JsonResponse
     */
    public function restore(string $sqid): JsonResponse
    {
        try {
            return DB::transaction(function () use ($sqid) {
                $bank = Bank::withTrashed()->findOrFail(Sqid::decode($sqid));
                $bank->restore();
                return response()->success(new BankResource($bank), 'Bank restored successfully');
            });
        } catch (NotFoundHttpException|ModelNotFoundException $e) {
            return $this->handleNotFoundException($e);
        } catch (Exception $e) {
            return $this->handleException($e, 'restoring the bank');
        }
    }

    /**
     * Bulk delete banks from storage.
     *
     * @param BulkRequest $request
     * @return JsonResponse
     */
    public function bulkDelete(BulkRequest $request): JsonResponse
    {
        try {
            return DB::transaction(function () use ($request) {
                $ids = array_map([Sqid::class, 'decode'], $request->input('sqids', []));
                Bank::whereIn('id', $ids)->delete();
                return response()->success(null, 'Banks deleted successfully');
            });
        } catch (NotFoundHttpException|ModelNotFoundException $e) {
            return $this->handleNotFoundException($e);
        } catch (Exception $e) {
            return $this->handleException($e, 'bulk deleting banks');
        }
    }

    /**
     * Bulk restore banks from storage.
     *
     * @param BulkRequest $request
     * @return JsonResponse
     */
    public function bulkRestore(BulkRequest $request): JsonResponse
    {
        try {
            return DB::transaction(function () use ($request) {
                $ids = array_map([Sqid::class, 'decode'], $request->input('sqids', []));
                $banks = Bank::withTrashed()->whereIn('id', $ids)->restore();
                return response()->success(BankResource::collection($banks), 'Banks restored successfully');
            });
        } catch (NotFoundHttpException|ModelNotFoundException $e) {
            return $this->handleNotFoundException($e);
        } catch (Exception $e) {
            return $this->handleException($e, 'bulk restoring banks');
        }
    }

    /**
     * Force delete the specified bank from storage.
     *
     * @param string $sqid
     * @return JsonResponse
     */
    public function forceDelete(string $sqid): JsonResponse
    {
        try {
            return DB::transaction(function () use ($sqid) {
                $bank = Bank::withTrashed()->findOrFail(Sqid::decode($sqid));
                $bank->forceDelete();
                return response()->success(null, 'Bank permanently deleted successfully');
            });
        } catch (NotFoundHttpException|ModelNotFoundException $e) {
            return $this->handleNotFoundException($e);
        } catch (Exception $e) {
            return $this->handleException($e, 'permanently deleting the bank');
        }
    }

    /**
     * Import banks from a file.
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
     * Export banks to a file and send via email.
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
