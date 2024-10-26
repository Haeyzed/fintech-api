<?php

namespace App\Http\Controllers;

use App\Enums\StorageProviderEnum;
use App\Exports\DynamicExport;
use App\Http\Requests\{BulkRequest, ExportRequest, ImportRequest, IndexRequest, TransactionRequest};
use App\Http\Resources\TransactionResource;
use App\Imports\DynamicImport;
use App\Jobs\SendExportEmail;
use App\Models\BlockedIp;
use App\Models\Transaction;
use App\Services\FCMService;
use App\Services\StorageProviderService;
use App\Traits\ExceptionHandlerTrait;
use App\Utils\Sqid;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\{JsonResponse, Resources\Json\AnonymousResourceCollection};
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\{Auth, DB, Hash, Log};
use Maatwebsite\Excel\Facades\Excel;
use PragmaRX\Google2FALaravel\Google2FA;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class TransactionController
 *
 * @package App\Http\Controllers
 *
 * @author Muibi Azeez Abolade
 * @email muibi.azeezabolade@gmail.com
 * @since 2024-10-24
 * @version 1.0
 *
 * @tags Transactions
 *
 * ${Description}
 */
class TransactionController extends Controller
{
    use ExceptionHandlerTrait;

    /**
     * @var FCMService
     */
    protected FCMService $fcmService;

    /**
     * @var StorageProviderService
     */
    protected StorageProviderService $storageProviderService;

    /**
     * @var Google2FA
     */
    protected Google2FA $google2fa;

    /**
     * AuthController constructor.
     *
     * @param FCMService $fcmService
     * @param StorageProviderService $storageProviderService
     * @param Google2FA $google2fa
     */
    public function __construct(FCMService $fcmService, StorageProviderService $storageProviderService, Google2FA $google2fa)
    {
        $this->fcmService = $fcmService;
        $this->storageProviderService = $storageProviderService;
        $this->google2fa = $google2fa;
    }

    /**
     * Display a listing of the transactions.
     *
     * @param IndexRequest $request
     * @return JsonResponse|AnonymousResourceCollection|LengthAwarePaginator
     * @response AnonymousResourceCollection<LengthAwarePaginator<TransactionResource>>
     */
    public function index(IndexRequest $request): JsonResponse|AnonymousResourceCollection|LengthAwarePaginator
    {
        try {
            $query = Transaction::query()->with('user')
                ->when($request->with_trashed, fn($q) => $q->withTrashed())
                ->when($request->search, fn($q, $search) => app('search')->apply($q, $search, ['user.name']))
                ->when($request->order_by, fn($q, $orderBy) => $q->orderBy($orderBy ?? 'name', $request->order_direction ?? 'asc'))
                ->when($request->start_date && $request->end_date, fn($q) => $q->custom($request->start_date, $request->end_date));
            $transactions = $query->paginate($request->per_page ?? config('app.per_page'));
            return response()->paginatedSuccess(TransactionResource::collection($transactions), 'Transactions retrieved successfully');
        } catch (Exception $e) {
            return $this->handleException($e, 'fetching transactions');
        }
    }

    /**
     * Get transactions for the authenticated user.
     *
     * @param IndexRequest $request
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function getUserTransactions(IndexRequest $request): JsonResponse|AnonymousResourceCollection
    {
        try {
            $user = Auth::user();
            Log::info($user);
            $query = $user->transactions()
                ->when($request->with_trashed, fn($q) => $q->withTrashed())
                ->when($request->search, fn($q, $search) => app('search')->apply($q, $search, ['description']))
                ->when($request->order_by, fn($q, $orderBy) => $q->orderBy($orderBy ?? 'created_at', $request->order_direction ?? 'desc'))
                ->when($request->start_date && $request->end_date, fn($q) => $q->whereBetween('created_at', [$request->start_date, $request->end_date]));

            $transactions = $query->paginate($request->per_page ?? config('app.per_page'));
            return response()->paginatedSuccess(TransactionResource::collection($transactions), 'User transactions retrieved successfully');
        } catch (Exception $e) {
            return $this->handleException($e, 'fetching user transactions');
        }
    }

    /**
     * Store a newly created transaction in storage.
     *
     * @param TransactionRequest $request
     * @return TransactionResource|JsonResponse
     */
    public function store(TransactionRequest $request): TransactionResource|JsonResponse
    {
        try {
            return DB::transaction(function () use ($request) {
                $transaction = Transaction::create($request->validated());
                return response()->created(new TransactionResource($transaction), 'Transaction created successfully');
            });
        } catch (Exception $e) {
            return $this->handleException($e, 'creating the transaction');
        }
    }

    /**
     * Display the specified transaction.
     *
     * @param string $sqid
     * @return TransactionResource|JsonResponse
     */
    public function show(string $sqid): TransactionResource|JsonResponse
    {
        try {
            $transaction = Transaction::findBySqidOrFail($sqid);
            return response()->success(new TransactionResource($transaction), 'Transaction retrieved successfully');
        } catch (NotFoundHttpException|ModelNotFoundException $e) {
            return $this->handleNotFoundException($e);
        } catch (Exception $e) {
            return $this->handleException($e, 'fetching the transaction');
        }
    }

    /**
     * Update the specified transaction in storage.
     *
     * @param TransactionRequest $request
     * @param string $sqid
     * @return TransactionResource|JsonResponse
     */
    public function update(TransactionRequest $request, string $sqid): TransactionResource|JsonResponse
    {
        try {
            return DB::transaction(function () use ($request, $sqid) {
                $transaction = Transaction::findBySqidOrFail($sqid);
                $transaction->update($request->validated());
                return response()->success(new TransactionResource($transaction), 'Transaction updated successfully');
            });
        } catch (NotFoundHttpException|ModelNotFoundException $e) {
            return $this->handleNotFoundException($e);
        } catch (Exception $e) {
            return $this->handleException($e, 'updating the transaction');
        }
    }

    /**
     * Remove the specified transaction from storage.
     *
     * @param string $sqid
     * @return JsonResponse
     */
    public function destroy(string $sqid): JsonResponse
    {
        try {
            return DB::transaction(function () use ($sqid) {
                $transaction = Transaction::findBySqidOrFail($sqid);
                $transaction->delete();
                return response()->success(null, 'Transaction deleted successfully');
            });
        } catch (NotFoundHttpException|ModelNotFoundException $e) {
            return $this->handleNotFoundException($e);
        } catch (Exception $e) {
            return $this->handleException($e, 'deleting the transaction');
        }
    }

    /**
     * Restore the specified transaction from storage.
     *
     * @param string $sqid
     * @return JsonResponse
     */
    public function restore(string $sqid): JsonResponse
    {
        try {
            return DB::transaction(function () use ($sqid) {
                $transaction = Transaction::withTrashed()->findOrFail(Sqid::decode($sqid));
                $transaction->restore();
                return response()->success(new TransactionResource($transaction), 'Transaction restored successfully');
            });
        } catch (NotFoundHttpException|ModelNotFoundException $e) {
            return $this->handleNotFoundException($e);
        } catch (Exception $e) {
            return $this->handleException($e, 'restoring the transaction');
        }
    }

    /**
     * Bulk delete transactions from storage.
     *
     * @param BulkRequest $request
     * @return JsonResponse
     */
    public function bulkDelete(BulkRequest $request): JsonResponse
    {
        try {
            return DB::transaction(function () use ($request) {
                $ids = array_map([Sqid::class, 'decode'], $request->input('sqids', []));
                Transaction::whereIn('id', $ids)->delete();
                return response()->success(null, 'Transactions deleted successfully');
            });
        } catch (NotFoundHttpException|ModelNotFoundException $e) {
            return $this->handleNotFoundException($e);
        } catch (Exception $e) {
            return $this->handleException($e, 'bulk deleting transactions');
        }
    }

    /**
     * Bulk restore transactions from storage.
     *
     * @param BulkRequest $request
     * @return JsonResponse
     */
    public function bulkRestore(BulkRequest $request): JsonResponse
    {
        try {
            return DB::transaction(function () use ($request) {
                $ids = array_map([Sqid::class, 'decode'], $request->input('sqids', []));
                $transactions = Transaction::withTrashed()->whereIn('id', $ids)->restore();
                return response()->success(TransactionResource::collection($transactions), 'Transactions restored successfully');
            });
        } catch (NotFoundHttpException|ModelNotFoundException $e) {
            return $this->handleNotFoundException($e);
        } catch (Exception $e) {
            return $this->handleException($e, 'bulk restoring transactions');
        }
    }

    /**
     * Force delete the specified transaction from storage.
     *
     * @param string $sqid
     * @return JsonResponse
     */
    public function forceDelete(string $sqid): JsonResponse
    {
        try {
            return DB::transaction(function () use ($sqid) {
                $transaction = Transaction::withTrashed()->findOrFail(Sqid::decode($sqid));
                $transaction->forceDelete();
                return response()->success(null, 'Transaction permanently deleted successfully');
            });
        } catch (NotFoundHttpException|ModelNotFoundException $e) {
            return $this->handleNotFoundException($e);
        } catch (Exception $e) {
            return $this->handleException($e, 'permanently deleting the transaction');
        }
    }

    /**
     * Import transactions from a file.
     *
     * @requestMediaType multipart/form-data
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
     * Export transactions to a file and send via email.
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
