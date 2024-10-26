<?php

namespace App\Traits;

use App\Utils\Sqid;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\{Model,ModelNotFoundException};
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

trait HasSqid
{
    /**
     * Get the SQID for the model.
     *
     * @return Attribute
     */
    protected function sqid(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ?? Sqid::encode($this->getKey()),
        );
    }

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName(): string
    {
        return 'sqid';
    }

    /**
     * Retrieve the model for a bound value.
     *
     * @param mixed $value
     * @param string|null $field
     * @return Model|null
     */
    public function resolveRouteBinding($value, $field = null): ?Model
    {
        return $this->resolveRouteBindingQuery($this, Sqid::decode($value), 'id')->first();
    }

    /**
     * Find a model by its SQID.
     *
     * @param string $sqid
     * @return Model|null
     */
    public static function findBySqid(string $sqid): ?Model
    {
        $id = Sqid::decode($sqid);
        return $id ? static::find($id) : null;
    }

    /**
     * Find a model by its SQID or fail.
     *
     * @param string $sqid
     * @return Model
     * @throws ModelNotFoundException|NotFoundHttpException
     */
    public static function findBySqidOrFail(string $sqid): Model
    {
        $id = Sqid::decode($sqid);
        if (!$id) {
            throw new ModelNotFoundException("No query results for model [" . static::class . "] with SQID {$sqid}");
        }
        return static::findOrFail($id);
    }
}
