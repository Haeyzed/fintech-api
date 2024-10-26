<?php

namespace App\Http\Controllers;

use App\Http\Requests\{IndexRequest,BlockedIpRequest};
use App\Http\Resources\BlockedIpResource;
use App\Models\BlockedIp;
use App\Traits\ExceptionHandlerTrait;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class BlockedIpController
 *
 * @package App\Http\Controllers
 *
 * @author Muibi Azeez Abolade
 * @email muibi.azeezabolade@gmail.com
 * @since 2024-10-24
 * @version 1.0
 *
 * @tags Blocked IPs
 *
 * ${Description}
 */
class BlockedIpController extends Controller
{
    use ExceptionHandlerTrait;

    /**
     * Display a listing of the blocked IPs.
     *
     * @param IndexRequest $request The request object containing the parameters for listing blocked ips.
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function index(IndexRequest $request)
    {
        try {
            $query = BlockedIp::query()
                ->when($request->with_trashed, fn($q) => $q->withTrashed())
                ->when($request->search, fn($q, $search) => app('search')->apply($q, $search, ['ip_address', 'reason']))
                ->when($request->order_by, fn($q, $orderBy) => $q->orderBy($orderBy ?? 'created_at', $request->order_direction ?? 'asc'))
                ->when($request->start_date && $request->end_date, fn($q) => $q->custom(Carbon::parse($request->start_date), Carbon::parse($request->end_date)));

            $blockedIps = $query->paginate($request->per_page ?? config('app.per_page'));
            return response()->paginatedSuccess(BlockedIpResource::collection($blockedIps), 'Blocked IPs retrieved successfully');
        } catch (Exception $e) {
            return $this->handleException($e, 'fetching blocked IPs');
        }
    }

    /**
     * Store a newly created blocked IP in storage.
     *
     * @param BlockedIpRequest $request
     * @return JsonResponse
     */
    public function store(BlockedIpRequest $request): JsonResponse
    {
        try {
            return DB::transaction(function () use ($request) {
                $blockedIp = BlockedIp::create($request->validated());
                return response()->created(new BlockedIpResource($blockedIp), 'IP address blocked successfully');
            });
        } catch (Exception $e) {
            return $this->handleException($e, 'creating the blocked ip');
        }
    }

    /**
     * Display the specified blocked IP.
     *
     * @param string $sqid
     * @return JsonResponse
     */
    public function show(string $sqid): JsonResponse
    {
        try {
            $blockedIp = BlockedIp::findBySqidOrFail($sqid);
            return response()->success(new BlockedIpResource($blockedIp), 'Blocked IP retrieved successfully');
        } catch (NotFoundHttpException|ModelNotFoundException $e) {
            return $this->handleNotFoundException($e);
        } catch (Exception $e) {
            return $this->handleException($e, 'fetching the blocked ip');
        }
    }

    /**
     * Update the specified blocked IP in storage.
     *
     * @param BlockedIpRequest $request
     * @param string $sqid
     * @return JsonResponse
     */
    public function update(BlockedIpRequest $request, string $sqid): JsonResponse
    {
        try {
            return DB::transaction(function () use ($request, $sqid) {
                $blockedIp = BlockedIp::findBySqidOrFail($sqid);
                $blockedIp->update($request->validated());
                return response()->success(new BlockedIpResource($blockedIp), 'Blocked IP updated successfully');
            });
        } catch (NotFoundHttpException|ModelNotFoundException $e) {
            return $this->handleNotFoundException($e);
        } catch (Exception $e) {
            return $this->handleException($e, 'updating the blocked ip');
        }
    }

    /**
     * Remove the specified blocked IP from storage.
     *
     * @param string $sqid
     * @return JsonResponse
     */
    public function destroy(string $sqid): JsonResponse
    {
        try {
            return DB::transaction(function () use ($sqid) {
                $blockedIp = BlockedIp::findBySqidOrFail($sqid);
                $blockedIp->delete();
                return response()->success(null, 'Blocked IP removed successfully');
            });
        } catch (NotFoundHttpException|ModelNotFoundException $e) {
            return $this->handleNotFoundException($e);
        } catch (Exception $e) {
            return $this->handleException($e, 'deleting the blocked ip');
        }
    }
}
