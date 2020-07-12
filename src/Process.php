<?php

namespace Modstore\Cronski;

use Illuminate\Database\Eloquent\Model;

class Process extends Model
{
    const ENDPOINT_START = 'start';
    const ENDPOINT_FAIL = 'fail';
    const ENDPOINT_FINISH = 'finish';

    protected $table = 'cronski_processes';

    protected $guarded = [];

    protected $casts = [
        'data' => 'array',
    ];
}
