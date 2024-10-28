<?php

namespace App\Http\Controllers;

use App\Exports\DynamicExport;
use App\Http\Requests\{BulkRequest, ExportRequest, ImportRequest, IndexRequest, CurrencyRequest};
use App\Http\Resources\CurrencyResource;
use App\Imports\DynamicImport;
use App\Jobs\SendExportEmail;
use App\Models\Currency;
use App\Traits\ExceptionHandlerTrait;
use App\Utils\Sqid;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\{JsonResponse, Resources\Json\AnonymousResourceCollection};
use Illuminate\Support\Facades\{DB, Hash};
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class CurrencyController
 *
 * @package App\Http\Controllers
 *
 * @author Muibi Azeez Abolade
 * @email muibi.azeezabolade@gmail.com
 * @since 2024-10-24
 * @version 1.0
 *
 * @tags Currencies
 *
 * ${Description}
 */
class CurrencyController extends Controller
{
    use ExceptionHandlerTrait;
    /**
     * Display a listing of the currencies.
     *
     * @param IndexRequest $request
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function index(IndexRequest $request)
    {
        try {
            $query = Currency::query()->with('country')
                ->when($request->with_trashed, fn($q) => $q->withTrashed())
                ->when($request->search, fn($q, $search) => app('search')->apply($q, $search, ['name', 'code']))
                ->when($request->order_by, fn($q, $orderBy) => $q->orderBy($orderBy ?? 'created_at', $request->order_direction ?? 'asc'))
                ->when($request->start_date && $request->end_date, fn($q) => $q->custom($request->start_date, $request->end_date));
            $currencies = $query->paginate($request->per_page ?? config('app.per_page'));
            return response()->paginatedSuccess(CurrencyResource::collection($currencies), 'Currencies retrieved successfully');
        } catch (Exception $e) {
            return $this->handleException($e, 'fetching currencies');
        }
    }

    /**
     * Store a newly created currency in storage.
     *
     * @param CurrencyRequest $request
     * @return JsonResponse
     */
    public function store(CurrencyRequest $request): JsonResponse
    {
        try {
            return DB::transaction(function () use ($request) {
                $currency = Currency::create($request->validated() + ['password' => Hash::make($request->password)]);
                return response()->created(new CurrencyResource($currency), 'Currency created successfully');
            });
        } catch (Exception $e) {
            return $this->handleException($e, 'creating the currency');
        }
    }

    /**
     * Display the specified currency.
     *
     * @param string $sqid
     * @return JsonResponse
     */
    public function show(string $sqid): JsonResponse
    {
        try {
            $currency = Currency::findBySqidOrFail($sqid);
            return response()->success(new CurrencyResource($currency), 'Currency retrieved successfully');
        } catch (NotFoundHttpException|ModelNotFoundException $e) {
            return $this->handleNotFoundException($e);
        } catch (Exception $e) {
            return $this->handleException($e, 'fetching the currency');
        }
    }

    /**
     * Update the specified currency in storage.
     *
     * @param CurrencyRequest $request
     * @param string $sqid
     * @return JsonResponse
     */
    public function update(CurrencyRequest $request, string $sqid): JsonResponse
    {
        try {
            return DB::transaction(function () use ($request, $sqid) {
                $currency = Currency::findBySqidOrFail($sqid);
                $currency->update($request->validated());
                return response()->success(new CurrencyResource($currency), 'Currency updated successfully');
            });
        } catch (NotFoundHttpException|ModelNotFoundException $e) {
            return $this->handleNotFoundException($e);
        } catch (Exception $e) {
            return $this->handleException($e, 'updating the currency');
        }
    }

    /**
     * Remove the specified currency from storage.
     *
     * @param string $sqid
     * @return JsonResponse
     */
    public function destroy(string $sqid): JsonResponse
    {
        try {
            return DB::transaction(function () use ($sqid) {
                $currency = Currency::findBySqidOrFail($sqid);
                $currency->delete();
                return response()->success(null, 'Currency deleted successfully');
            });
        } catch (NotFoundHttpException|ModelNotFoundException $e) {
            return $this->handleNotFoundException($e);
        } catch (Exception $e) {
            return $this->handleException($e, 'deleting the currency');
        }
    }

    /**
     * Restore the specified currency from storage.
     *
     * @param string $sqid
     * @return JsonResponse
     */
    public function restore(string $sqid): JsonResponse
    {
        try {
            return DB::transaction(function () use ($sqid) {
                $currency = Currency::withTrashed()->findOrFail(Sqid::decode($sqid));
                $currency->restore();
                return response()->success(new CurrencyResource($currency), 'Currency restored successfully');
            });
        } catch (NotFoundHttpException|ModelNotFoundException $e) {
            return $this->handleNotFoundException($e);
        } catch (Exception $e) {
            return $this->handleException($e, 'restoring the currency');
        }
    }

    /**
     * Bulk delete currencies from storage.
     *
     * @param BulkRequest $request
     * @return JsonResponse
     */
    public function bulkDelete(BulkRequest $request): JsonResponse
    {
        try {
            return DB::transaction(function () use ($request) {
                $ids = array_map([Sqid::class, 'decode'], $request->input('sqids', []));
                Currency::whereIn('id', $ids)->delete();
                return response()->success(null, 'Currencies deleted successfully');
            });
        } catch (NotFoundHttpException|ModelNotFoundException $e) {
            return $this->handleNotFoundException($e);
        } catch (Exception $e) {
            return $this->handleException($e, 'bulk deleting currencies');
        }
    }

    /**
     * Bulk restore currencies from storage.
     *
     * @param BulkRequest $request
     * @return JsonResponse
     */
    public function bulkRestore(BulkRequest $request): JsonResponse
    {
        try {
            return DB::transaction(function () use ($request) {
                $ids = array_map([Sqid::class, 'decode'], $request->input('sqids', []));
                $currencies = Currency::withTrashed()->whereIn('id', $ids)->restore();
                return response()->success(CurrencyResource::collection($currencies), 'Currencies restored successfully');
            });
        } catch (NotFoundHttpException|ModelNotFoundException $e) {
            return $this->handleNotFoundException($e);
        } catch (Exception $e) {
            return $this->handleException($e, 'bulk restoring currencies');
        }
    }

    /**
     * Force delete the specified currency from storage.
     *
     * @param string $sqid
     * @return JsonResponse
     */
    public function forceDelete(string $sqid): JsonResponse
    {
        try {
            return DB::transaction(function () use ($sqid) {
                $currency = Currency::withTrashed()->findOrFail(Sqid::decode($sqid));
                $currency->forceDelete();
                return response()->success(null, 'Currency permanently deleted successfully');
            });
        } catch (NotFoundHttpException|ModelNotFoundException $e) {
            return $this->handleNotFoundException($e);
        } catch (Exception $e) {
            return $this->handleException($e, 'permanently deleting the currency');
        }
    }

    /**
     * Import currencies from a file.
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
     * Export currencies to a file and send via email.
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
