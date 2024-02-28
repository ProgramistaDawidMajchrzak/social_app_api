<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $fillable = ['author', 'title', 'description'];

    public function likes()
    {
        return $this->hasMany(Likes::class, 'post_id');
    }
    public function comments()
    {
        return $this->hasMany(Comments::class, 'post_id');
    }
}
