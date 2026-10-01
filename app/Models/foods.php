<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class foods extends Model
{
    /** @use HasFactory<\Database\Factories\FoodsFactory> */
    use HasFactory;

    protected $table = 'foods';
    protected $guarded = ['id'];
}
