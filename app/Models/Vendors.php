<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendors extends Model
{
    protected $table = 'vendors';

    protected $primaryKey = 'id';

    protected $fillable = [
        'userId',
        'storeName',
        'storeDescription',
        'logo',
        'status'
    ];
    /* vendor operations here */
}
