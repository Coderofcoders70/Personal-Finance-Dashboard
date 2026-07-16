<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Services\FinanceService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    private FinanceService $financeService;

    public function __construct(FinanceService $financeService)
    {
        $this->financeService = $financeService;
    }

    public function index(Request $request)
    {
        $dashboard = $this->financeService->dashboard($request->user());

        $dashboard['recent_transactions'] = TransactionResource::collection(
            $dashboard['recent_transactions']
        );
        
        return response()->json($dashboard);
    }
}
