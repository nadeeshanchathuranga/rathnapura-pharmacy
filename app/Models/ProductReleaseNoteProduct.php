<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductReleaseNoteProduct extends Model
{
    protected $table = 'product_release_note_produts';  

    protected $fillable = [
        'product_release_note_id',
        'product_id',
        'unit_id',
        'quantity',
        'unit_price', 
        'total',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function unit()
    {
        return $this->belongsTo(MeasurementUnit::class, 'unit_id');
    }

    public function prn()
    {
        return $this->belongsTo(ProductReleaseNote::class, 'product_release_note_id');
    }
}
