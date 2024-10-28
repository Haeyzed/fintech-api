<?php

namespace App\Models;

use App\Traits\HasSqid;

class Timezone extends \Nnjeim\World\Models\Timezone
{
    use HasSqid;

    protected $with = ['country'];
}
