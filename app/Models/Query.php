<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Query extends Model
{
    protected $table = 'queries';

    protected $fillable = [
        'custom_id',
        'status',
        'query_created_at',
        'count',
        'is_finished',
    ];

    const  STATUS_ATMEN = 17;

    public function isAtmen(): int
    {
        return $this->status = self::STATUS_ATMEN;
    }
}
