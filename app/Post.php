<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $fillable = [
        'title',
        'body',
        'id',
        'user_id'
    ];

    public function scopeSearch($query, string $search)
    {
        if (!$search) {
            return $query;
        }

        return $query->whereRaw('fulltextsearch @@ to_tsquery(\'english\', ?)', [$search])
            ->orderByRaw('ts_rank(fulltextsearch, to_tsquery(\'english\', ?)) DESC', [$search]);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
}
