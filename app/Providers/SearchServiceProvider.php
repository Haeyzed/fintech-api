<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\ServiceProvider;

class SearchServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind('search', function ($app) {
        return new class {
            public function apply(Builder $query, $search, array $columns, array $relationships = []): Builder
            {
                return $query->where(function ($query) use ($search, $columns, $relationships) {
                    foreach ($columns as $column) {
                        $query->orWhere($column, 'LIKE', "%{$search}%");
                    }

                    foreach ($relationships as $relation => $columns) {
                        $query->orWhereHas($relation, function ($query) use ($search, $columns) {
                            $query->where(function ($query) use ($search, $columns) {
                                foreach ($columns as $column) {
                                    $query->orWhere($column, 'LIKE', "%{$search}%");
                                }
                            });
                        });
                    }
                });
            }
        };
    });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
