<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    use HasFactory;

    protected $table = 'product_img';

    protected $fillable = [
        'id',
        'product_id',
        'img',
        'created_at',
        'updated_at'
    ];
}
