<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Pgvector\Laravel\Vector;

class KnowledgeBase extends Model
{
    protected $table = 'knowledge_base';

    protected $casts = [
        'embedding' => Vector::class,
    ];

    protected $fillable = [
        'content',
        'embedding',
        'user_id',
        'source_id',
    ];
}
