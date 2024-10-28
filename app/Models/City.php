<?php

namespace App\Models;

use App\Traits\FilterableByDates;
use App\Traits\HasSqid;
use App\Utilities\Sqid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class City extends \Nnjeim\World\Models\City
{
    use HasSqid;
}
