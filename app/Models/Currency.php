<?php

namespace App\Models;

use App\Traits\HasSqid;

class Currency extends \Nnjeim\World\Models\Currency
{
    use HasSqid;

    protected $with = ['country'];
}
