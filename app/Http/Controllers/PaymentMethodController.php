<?php

namespace App\Http\Controllers;

use App\Exports\DynamicExport;
use App\Http\Requests\{BulkRequest, ExportRequest, ImportRequest, IndexRequest, PaymentMethodRequest};
use App\Http\Resources\PaymentMethodResource;
use App\Imports\DynamicImport;
use App\Jobs\SendExportEmail;
use App\Models\PaymentMethod;
use App\Traits\ExceptionHandlerTrait;
use App\Utils\Sqid;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\{JsonResponse, Resources\Json\AnonymousResourceCollection};
use Illuminate\Support\Facades\{DB, Hash};
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class PaymentMethodController
 *
 * @package App\Http\Controllers
 *
 * @author Muibi Azeez Abolade
 * @email muibi.azeezabolade@gmail.com
 * @since 2024-10-24
 * @version 1.0
 *
 * @tags Payment Methods
 *
 * ${Description}
 */
class PaymentMethodController extends Controller
{
    use ExceptionHandlerTrait;
    /**
     * Display a listing of the payment methods.
     *
     * @param IndexRequest $request
     * @return JsonResponse|AnonymousResourceCollection|LengthAwarePaginator
     * @response AnonymousResourceCollection<LengthAwarePaginator<PaymentMethodResource>>
     */
    public function index(IndexRequest $request): JsonResponse|AnonymousResourceCollection|LengthAwarePaginator
    {
        try {
            $query = PaymentMethod::query()
                ->when($request->with_trashed, fn($q) => $q->withTrashed())
                ->when($request->search, fn($q, $search) => app('search')->apply($q, $search, ['type']))
                ->when($request->order_by, fn($q, $orderBy) => $q->orderBy($orderBy ?? 'created_at', $request->order_direction ?? 'asc'))
                ->when($request->start_date && $request->end_date, fn($q) => $q->custom($request->start_date, $request->end_date));
            $paymentMethods = $query->paginate($request->per_page ?? config('app.per_page'));
            return response()->paginatedSuccess(PaymentMethodResource::collection($paymentMethods), 'Payment methods retrieved successfully');
        } catch (Exception $e) {
            return $this->handleException($e, 'fetching payment methods');
        }
    }

    /**
     * Store a newly created payment method in storage.
     *
     * @param PaymentMethodRequest $request
     * @return PaymentMethodResource|JsonResponse
     */
    public function store(PaymentMethodRequest $request): PaymentMethodResource|JsonResponse
    {
        try {
            return DB::transaction(function () use ($request) {
                $paymentMethod = PaymentMethod::create($request->validated());
                return response()->created(new PaymentMethodResource($paymentMethod), 'Payment method created successfully');
            });
        } catch (Exception $e) {
            return $this->handleException($e, 'creating the payment method');
        }
    }

    /**
     * Display the specified payment method.
     *
     * @param string $sqid
     * @return PaymentMethodResource|JsonResponse
     */
    public function show(string $sqid): PaymentMethodResource|JsonResponse
    {
        try {
            $paymentMethod = PaymentMethod::findBySqidOrFail($sqid);
            return response()->success(new PaymentMethodResource($paymentMethod), 'Payment method retrieved successfully');
        } catch (NotFoundHttpException|ModelNotFoundException $e) {
            return $this->handleNotFoundException($e);
        } catch (Exception $e) {
            return $this->handleException($e, 'fetching the payment method');
        }
    }

    /**
     * Update the specified payment method in storage.
     *
     * @param PaymentMethodRequest $request
     * @param string $sqid
     * @return PaymentMethodResource|JsonResponse
     */
    public function update(PaymentMethodRequest $request, string $sqid): PaymentMethodResource|JsonResponse
    {
        try {
            return DB::transaction(function () use ($request, $sqid) {
                $paymentMethod = PaymentMethod::findBySqidOrFail($sqid);
                $paymentMethod->update($request->validated());
                return response()->success(new PaymentMethodResource($paymentMethod), 'Payment method updated successfully');
            });
        } catch (NotFoundHttpException|ModelNotFoundException $e) {
            return $this->handleNotFoundException($e);
        } catch (Exception $e) {
            return $this->handleException($e, 'updating the payment method');
        }
    }

    /**
     * Remove the specified payment method from storage.
     *
     * @param string $sqid
     * @return JsonResponse
     */
    public function destroy(string $sqid): JsonResponse
    {
        try {
            return DB::transaction(function () use ($sqid) {
                $paymentMethod = PaymentMethod::findBySqidOrFail($sqid);
                $paymentMethod->delete();
                return response()->success(null, 'Payment method deleted successfully');
            });
        } catch (NotFoundHttpException|ModelNotFoundException $e) {
            return $this->handleNotFoundException($e);
        } catch (Exception $e) {
            return $this->handleException($e, 'deleting the payment method');
        }
    }

    /**
     * Restore the specified payment method from storage.
     *
     * @param string $sqid
     * @return JsonResponse
     */
    public function restore(string $sqid): JsonResponse
    {
        try {
            return DB::transaction(function () use ($sqid) {
                $paymentMethod = PaymentMethod::withTrashed()->findOrFail(Sqid::decode($sqid));
                $paymentMethod->restore();
                return response()->success(new PaymentMethodResource($paymentMethod), 'Payment method restored successfully');
            });
        } catch (NotFoundHttpException|ModelNotFoundException $e) {
            return $this->handleNotFoundException($e);
        } catch (Exception $e) {
            return $this->handleException($e, 'restoring the payment method');
        }
    }

    /**
     * Bulk delete payment methods from storage.
     *
     * @param BulkRequest $request
     * @return JsonResponse
     */
    public function bulkDelete(BulkRequest $request): JsonResponse
    {
        try {
            return DB::transaction(function () use ($request) {
                $ids = array_map([Sqid::class, 'decode'], $request->input('sqids', []));
                PaymentMethod::whereIn('id', $ids)->delete();
                return response()->success(null, 'Payment methods deleted successfully');
            });
        } catch (NotFoundHttpException|ModelNotFoundException $e) {
            return $this->handleNotFoundException($e);
        } catch (Exception $e) {
            return $this->handleException($e, 'bulk deleting payment methods');
        }
    }

    /**
     * Bulk restore payment methods from storage.
     *
     * @param BulkRequest $request
     * @return JsonResponse
     */
    public function bulkRestore(BulkRequest $request): JsonResponse
    {
        try {
            return DB::transaction(function () use ($request) {
                $ids = array_map([Sqid::class, 'decode'], $request->input('sqids', []));
                $paymentMethods = PaymentMethod::withTrashed()->whereIn('id', $ids)->restore();
                return response()->success(PaymentMethodResource::collection($paymentMethods), 'Payment methods restored successfully');
            });
        } catch (NotFoundHttpException|ModelNotFoundException $e) {
            return $this->handleNotFoundException($e);
        } catch (Exception $e) {
            return $this->handleException($e, 'bulk restoring payment methods');
        }
    }

    /**
     * Force delete the specified payment method from storage.
     *
     * @param string $sqid
     * @return JsonResponse
     */
    public function forceDelete(string $sqid): JsonResponse
    {
        try {
            return DB::transaction(function () use ($sqid) {
                $paymentMethod = PaymentMethod::withTrashed()->findOrFail(Sqid::decode($sqid));
                $paymentMethod->forceDelete();
                return response()->success(null, 'Payment method permanently deleted successfully');
            });
        } catch (NotFoundHttpException|ModelNotFoundException $e) {
            return $this->handleNotFoundException($e);
        } catch (Exception $e) {
            return $this->handleException($e, 'permanently deleting the payment method');
        }
    }
}
