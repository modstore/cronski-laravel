<?php

namespace Modstore\Cronski;

use Illuminate\Database\Eloquent\Model;

class Process extends Model
{
    const ENDPOINT_START = 'start';
    const ENDPOINT_FAIL = 'fail';
    const ENDPOINT_FINISH = 'finish';

    const STATUS_COMPLETE = 1;

    protected $table = 'cronski_processes';

    protected $guarded = [];

    protected $casts = [
        'data' => 'array',
    ];

    public function parentProcess()
    {
        return $this->belongsTo(Process::class, 'parent_id');
    }
}
