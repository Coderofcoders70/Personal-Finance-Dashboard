<?php

namespace App\Services;

use App\Models\User;
use App\Support\CacheKeys;
use Illuminate\Support\Facades\Cache;

class CacheInvalidationService
{
    public function clearAnalytics(User $user): void
    {
        Cache::forget(CacheKeys::summary($user));
        Cache::forget(CacheKeys::monthlySummary($user));
        Cache::forget(CacheKeys::expenseCategory($user));
        Cache::forget(CacheKeys::incomeCategory($user));
    }

    public function clearReports(User $user): void
    {
        Cache::forget(
            CacheKeys::weeklyReport($user)
        );

        Cache::forget(
            CacheKeys::monthlyReport(
                $user,
                now()->month,
                now()->year
            )
        );

        Cache::forget(
            CacheKeys::yearlyReport(
                $user,
                now()->year
            )
        );
    }

    public function clearAI(User $user): void
    {
        Cache::forget(
            CacheKeys::aiContext($user)
        );
    }

    public function clearFinance(User $user): void
    {
        $this->clearAnalytics($user);

        $this->clearReports($user);

        $this->clearAI($user);
    }
}
