<?php

namespace App\Models;

use App\Traits\FilterableByDates;
use App\Traits\HasSqid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class State extends \Nnjeim\World\Models\State
{
    use HasSqid;
}
