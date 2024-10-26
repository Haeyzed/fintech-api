<?php

namespace App\Http\Controllers;

use App\Models\{BlockedIP, User};
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\{Client, Token};

/**
 * Class DashboardController
 *
 * @package App\Http\Controllers
 *
 * @author Muibi Azeez Abolade
 * @email muibi.azeezabolade@gmail.com
 * @since 2024-10-24
 * @version 1.0
 *
 * @tags Dashboard
 *
 * ${Description}
 */
class DashboardController extends Controller
{
    /**
     * Get dashboard metrics.
     *
     * This method retrieves various metrics from different models and databases,
     * including OAuth clients, users, access tokens, blocked IPs, and vendors.
     *
     * @return JsonResponse
     */
    public function getMetrics(): JsonResponse
    {
        $metrics = [
            'total_oauth_clients' => Client::count(),
            'total_revoked_oauth_clients' => Client::where('revoked', true)->count(),
            'total_users' => User::count(),
            'total_access_tokens' => Token::count(),
            'total_blocked_ips' => BlockedIP::count(),
            'total_vendors' => DB::connection('ecommerce')->table('vendors')->count(),
        ];

        return response()->success($metrics, 'Dashboard metrics retrieved successfully');
    }
}
