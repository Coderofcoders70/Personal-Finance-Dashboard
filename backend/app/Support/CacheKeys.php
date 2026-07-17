<?php

namespace App\Support;

use App\Models\User;

class CacheKeys
{
    public static function summary(User $user): string
    {
        return "summary:user:{$user->id}";
    }

    public static function monthlySummary(User $user): string
    {
        return "monthly-summary:user:{$user->id}";
    }

    public static function expenseCategory(User $user): string
    {
        return "expense-category:user:{$user->id}";
    }

    public static function incomeCategory(User $user): string
    {
        return "income-category:user:{$user->id}";
    }

    public static function monthlyReport(User $user, int $month, int $year): string
    {
        return "report:monthly:user:{$user->id}:{$year}:{$month}";
    }

    public static function weeklyReport(User $user): string
    {
        return "report:weekly:user:{$user->id}";
    }

    public static function yearlyReport(User $user, int $year): string
    {
        return "report:yearly:user:{$user->id}:{$year}";
    }

    public static function aiContext(User $user): string
    {
        return "ai:context:user:{$user->id}";
    }

    public static function aiResponse(User $user, string $hash): string
    {
        return "ai:response:user:{$user->id}:{$hash}";
    }
}
