<?php

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

trait HasDateFilter
{
    /**
     * Filter the query by the current date.
     *
     * @param Builder $query  The query builder instance.
     * @param string $column  The name of the column to apply the filter on.
     * @return Builder The modified query builder instance.
     */
    public function scopeToday(Builder $query, string $column = 'created_at'): Builder
    {
        return $query->whereDate($column, Carbon::today());
    }

    /**
     * Filter the query by yesterday's date.
     *
     * @param Builder $query  The query builder instance.
     * @param string $column  The name of the column to apply the filter on.
     * @return Builder The modified query builder instance.
     */
    public function scopeYesterday(Builder $query, string $column = 'created_at'): Builder
    {
        return $query->whereDate($column, Carbon::yesterday());
    }

    /**
     * Filter the query by the current month.
     *
     * @param Builder $query  The query builder instance.
     * @param string $column  The name of the column to apply the filter on.
     * @return Builder The modified query builder instance.
     */
    public function scopeMonthToDate(Builder $query, string $column = 'created_at'): Builder
    {
        return $query->whereBetween($column, [Carbon::now()->startOfMonth(), Carbon::now()]);
    }

    /**
     * Filter the query by the current quarter.
     *
     * @param Builder $query  The query builder instance.
     * @param string $column  The name of the column to apply the filter on.
     * @return Builder The modified query builder instance.
     */
    public function scopeQuarterToDate(Builder $query, string $column = 'created_at'): Builder
    {
        $now = Carbon::now();
        return $query->whereBetween($column, [
            $now->startOfQuarter(),
            $now,
        ]);
    }

    /**
     * Filter the query by the current year.
     *
     * @param Builder $query  The query builder instance.
     * @param string $column  The name of the column to apply the filter on.
     * @return Builder The modified query builder instance.
     */
    public function scopeYearToDate(Builder $query, string $column = 'created_at'): Builder
    {
        return $query->whereBetween($column, [Carbon::now()->startOfYear(), Carbon::now()]);
    }

    /**
     * Filter the query by the last 7 days.
     *
     * @param Builder $query  The query builder instance.
     * @param string $column  The name of the column to apply the filter on.
     * @return Builder The modified query builder instance.
     */
    public function scopeLast7Days(Builder $query, string $column = 'created_at'): Builder
    {
        return $query->whereBetween($column, [
            Carbon::today()->subDays(6),
            Carbon::now(),
        ]);
    }

    /**
     * Filter the query by the last 30 days.
     *
     * @param Builder $query  The query builder instance.
     * @param string $column  The name of the column to apply the filter on.
     * @return Builder The modified query builder instance.
     */
    public function scopeLast30Days(Builder $query, string $column = 'created_at'): Builder
    {
        return $query->whereBetween($column, [Carbon::today()->subDays(29), Carbon::now()]);
    }

    /**
     * Filter the query by the last quarter.
     *
     * @param Builder $query  The query builder instance.
     * @param string $column  The name of the column to apply the filter on.
     * @return Builder The modified query builder instance.
     */
    public function scopeLastQuarter(Builder $query, string $column = 'created_at'): Builder
    {
        $now = Carbon::now();
        return $query->whereBetween($column, [$now->startOfQuarter()->subMonths(3), $now->startOfQuarter()]);
    }

    /**
     * Filter the query by the last year.
     *
     * @param Builder $query  The query builder instance.
     * @param string $column  The name of the column to apply the filter on.
     * @return Builder The modified query builder instance.
     */
    public function scopeLastYear(Builder $query, string $column = 'created_at'): Builder
    {
        return $query->whereBetween($column, [Carbon::now()->subYear(), Carbon::now()]);
    }

    /**
     * Filter the query by a custom date range.
     *
     * @param Builder $query  The query builder instance.
     * @param Carbon $startDate  The start date of the range.
     * @param Carbon $endDate  The end date of the range.
     * @param string $column  The name of the column to apply the filter on.
     * @return Builder The modified query builder instance.
     */
    public function scopeCustom(Builder $query, Carbon $startDate, Carbon $endDate, string $column = 'created_at'): Builder
    {
        return $query->whereBetween($column, [$startDate->startOfDay(), $endDate->endOfDay()]);
    }
}
