<?php

namespace App\Http\Controllers;

use App\Exports\DynamicExport;
use App\Models\{Currency, Bank, User};
use App\Rules\SqidExists;
use App\Http\Requests\{BulkRequest, ExportRequest, ImportRequest, IndexRequest, BankAccountRequest};
use App\Http\Resources\BankAccountResource;
use App\Imports\DynamicImport;
use App\Jobs\SendExportEmail;
use App\Models\BankAccount;
use App\Traits\ExceptionHandlerTrait;
use App\Utils\Sqid;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\{JsonResponse, Resources\Json\AnonymousResourceCollection};
use Illuminate\Support\Facades\{Auth, DB, Hash};
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class BankAccountController
 *
 * @package App\Http\Controllers
 *
 * @author Muibi Azeez Abolade
 * @email muibi.azeezabolade@gmail.com
 * @since 2024-10-24
 * @version 1.0
 *
 * @tags Bank Accounts
 *
 * ${Description}
 */
class BankAccountController extends Controller
{
    use ExceptionHandlerTrait;

    /**
     * Display a listing of the bank accounts.
     *
     * @param IndexRequest $request
     * @return JsonResponse|AnonymousResourceCollection|LengthAwarePaginator
     * @response AnonymousResourceCollection<LengthAwarePaginator<BankAccountResource>>
     */
    public function index(IndexRequest $request): JsonResponse|AnonymousResourceCollection|LengthAwarePaginator
    {
        $request->validate([
            /**
             * @query
             * The user to filter by.
             * @var string|null $user_id
             * @example "sqid1"
             */
            'user_id' => ['nullable', new SqidExists('users')],
        ], [
            'user_id.sqid_exists' => 'The selected user is invalid. Please provide a valid user.',
        ]);
        try {
            $query = BankAccount::query()->with(['user', 'bank', 'currency'])
                ->when($request->with_trashed, fn($q) => $q->withTrashed())
                ->when($request->user_id ?? Auth::id(), fn($q, $userId) => $q->where('user_id', $userId))
                ->when($request->search, fn($q, $search) => app('search')->apply($q, $search, ['bank_name', 'account_number']))
                ->when($request->order_by, fn($q, $orderBy) => $q->orderBy($orderBy ?? 'created_at', $request->order_direction ?? 'asc'))
                ->when($request->start_date && $request->end_date, fn($q) => $q->custom($request->start_date, $request->end_date));
            $bankAccounts = $query->paginate($request->per_page ?? config('app.per_page'));
            return response()->paginatedSuccess(BankAccountResource::collection($bankAccounts), 'Bank accounts retrieved successfully');
        } catch (Exception $e) {
            return $this->handleException($e, 'fetching bank accounts');
        }
    }

    /**
     * Store a newly created bank account in storage.
     *
     * @param BankAccountRequest $request
     * @return BankAccountResource|JsonResponse
     */
    public function store(BankAccountRequest $request): BankAccountResource|JsonResponse
    {
        try {
            return DB::transaction(function () use ($request) {
                $data = $request->validated();

                // Find user by ID or default to authenticated user
                $user = isset($data['user_id'])
                    ? User::findBySqidOrFail($data['user_id'])
                    : auth()->user();

                // Find associated currency and bank by ID
                $currency = Currency::findBySqidOrFail($data['currency_id']);
                $bank = Bank::findBySqidOrFail($data['bank_id']);

                $data['currency_id'] = $currency->id;
                $data['bank_id'] = $bank->id;

                $bankAccount = $user->bankAccounts()->create($data);

                // Set as primary if it's the user's first bank account
                if ($user->bankAccounts()->count() === 1) {
                    $bankAccount->update(['is_primary' => true]);
                }

                return response()->created(new BankAccountResource($bankAccount), 'Bank account created successfully');
            });
        } catch (Exception $e) {
            return $this->handleException($e, 'creating the bank account');
        }
    }

    /**
     * Display the specified bank account.
     *
     * @param string $sqid
     * @return BankAccountResource|JsonResponse
     */
    public function show(string $sqid): BankAccountResource|JsonResponse
    {
        try {
            $bankAccount = BankAccount::findBySqidOrFail($sqid);
            return response()->success(new BankAccountResource($bankAccount), 'Bank account retrieved successfully');
        } catch (NotFoundHttpException|ModelNotFoundException $e) {
            return $this->handleNotFoundException($e);
        } catch (Exception $e) {
            return $this->handleException($e, 'fetching the bank account');
        }
    }

    /**
     * Update the specified bank account in storage.
     *
     * @param BankAccountRequest $request
     * @param string $sqid
     * @return BankAccountResource|JsonResponse
     */
    public function update(BankAccountRequest $request, string $sqid): BankAccountResource|JsonResponse
    {
        try {
            return DB::transaction(function () use ($request, $sqid) {
                $bankAccount = BankAccount::findBySqidOrFail($sqid);
                $data = $request->validated();

                // Validate and assign currency and bank IDs if provided
                $data['currency_id'] = isset($data['currency_id'])
                    ? Currency::findBySqidOrFail($data['currency_id'])->id
                    : $bankAccount->currency_id;

                $data['bank_id'] = isset($data['bank_id'])
                    ? Bank::findBySqidOrFail($data['bank_id'])->id
                    : $bankAccount->bank_id;

                $bankAccount->update($data);

                // Ensure only one primary account
                $bankAccount->user->bankAccounts()
                    ->where('id', '!=', $bankAccount->id)
                    ->update(['is_primary' => $bankAccount->is_primary ? false : true]);

                return response()->success(new BankAccountResource($bankAccount), 'Bank account updated successfully');
            });
        } catch (NotFoundHttpException|ModelNotFoundException $e) {
            return $this->handleNotFoundException($e);
        } catch (Exception $e) {
            return $this->handleException($e, 'updating the bank account');
        }
    }

    /**
     * Remove the specified bank account from storage.
     *
     * @param string $sqid
     * @return JsonResponse
     */
    public function destroy(string $sqid): JsonResponse
    {
        try {
            return DB::transaction(function () use ($sqid) {
                $bankAccount = BankAccount::findBySqidOrFail($sqid);

                // Check if the bank account is primary
                if ($bankAccount->is_primary) {
                    return response()->forbidden('Primary bank account cannot be deleted');
                }

                $bankAccount->delete();
                return response()->success(null, 'Bank account deleted successfully');
            });
        } catch (NotFoundHttpException|ModelNotFoundException $e) {
            return $this->handleNotFoundException($e);
        } catch (Exception $e) {
            return $this->handleException($e, 'deleting the bank account');
        }
    }


    /**
     * Restore the specified bank account from storage.
     *
     * @param string $sqid
     * @return JsonResponse
     */
    public function restore(string $sqid): JsonResponse
    {
        try {
            return DB::transaction(function () use ($sqid) {
                $bankAccount = BankAccount::withTrashed()->findOrFail(Sqid::decode($sqid));
                $bankAccount->restore();
                return response()->success(new BankAccountResource($bankAccount), 'Bank account restored successfully');
            });
        } catch (NotFoundHttpException|ModelNotFoundException $e) {
            return $this->handleNotFoundException($e);
        } catch (Exception $e) {
            return $this->handleException($e, 'restoring the bank account');
        }
    }

    /**
     * Bulk delete bank accounts from storage.
     *
     * @param BulkRequest $request
     * @return JsonResponse
     */
    public function bulkDelete(BulkRequest $request): JsonResponse
    {
        try {
            return DB::transaction(function () use ($request) {
                $ids = array_map([Sqid::class, 'decode'], $request->input('sqids', []));
                BankAccount::whereIn('id', $ids)->delete();
                return response()->success(null, 'Bank accounts deleted successfully');
            });
        } catch (NotFoundHttpException|ModelNotFoundException $e) {
            return $this->handleNotFoundException($e);
        } catch (Exception $e) {
            return $this->handleException($e, 'bulk deleting bank accounts');
        }
    }

    /**
     * Bulk restore bank accounts from storage.
     *
     * @param BulkRequest $request
     * @return JsonResponse
     */
    public function bulkRestore(BulkRequest $request): JsonResponse
    {
        try {
            return DB::transaction(function () use ($request) {
                $ids = array_map([Sqid::class, 'decode'], $request->input('sqids', []));
                $bankAccounts = BankAccount::withTrashed()->whereIn('id', $ids)->restore();
                return response()->success(BankAccountResource::collection($bankAccounts), 'Bank accounts restored successfully');
            });
        } catch (NotFoundHttpException|ModelNotFoundException $e) {
            return $this->handleNotFoundException($e);
        } catch (Exception $e) {
            return $this->handleException($e, 'bulk restoring bank accounts');
        }
    }

    /**
     * Force delete the specified bank account from storage.
     *
     * @param string $sqid
     * @return JsonResponse
     */
    public function forceDelete(string $sqid): JsonResponse
    {
        try {
            return DB::transaction(function () use ($sqid) {
                $bankAccount = BankAccount::withTrashed()->findOrFail(Sqid::decode($sqid));
                $bankAccount->forceDelete();
                return response()->success(null, 'Bank account permanently deleted successfully');
            });
        } catch (NotFoundHttpException|ModelNotFoundException $e) {
            return $this->handleNotFoundException($e);
        } catch (Exception $e) {
            return $this->handleException($e, 'permanently deleting the bank account');
        }
    }

    /**
     * Import bank accounts from a file.
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
     * Export bank accounts to a file and send via email.
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
