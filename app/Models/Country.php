<?php

namespace App\Models;

use App\Traits\HasSqid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Country extends \Nnjeim\World\Models\Country
{
    use HasSqid;
}
