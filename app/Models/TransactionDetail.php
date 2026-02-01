<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TransactionDetail extends Model
{
    use HasFactory;

    protected $guarded = [];

    // Relasi ke produk (opsional, untuk report nanti)
    public function product() {
        return $this->belongsTo(Product::class);
    }
}
