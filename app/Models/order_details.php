<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class order_details extends Model
{
    /** @use HasFactory<\Database\Factories\OrderDetailsFactory> */
    use HasFactory;

    protected $table = 'order_details';
    // pake protected agar kolom subtotal bisa masuk ke database
    protected $guarded = ['id'];

    public function food()
    {
        return $this->belongsTo(foods::class, 'food_id');
    }

    public function order()
    {
        return $this->belongsTo(orders::class, 'order_id');
    }
}
