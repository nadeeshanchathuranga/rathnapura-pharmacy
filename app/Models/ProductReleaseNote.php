<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductReleaseNote extends Model
{
    protected $table = 'product_release_notes';
    
    protected $fillable = [
        'product_transfer_request_id',
        'user_id',
        'release_date',
        'status',
        'remark'
    ];

  
    public function ptr()
    {
        return $this->belongsTo(ProductTransferRequest::class, 'product_transfer_request_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function prn_products()
    {
        return $this->hasMany(ProductReleaseNoteProduct::class, 'product_release_note_id');
    }


      public function product_release_note_products()
    {
           return $this->hasMany(ProductReleaseNoteProduct::class, 'product_release_note_id');
    }
    
    public function product_transfer_request()
    {
        return $this->belongsTo(ProductTransferRequest::class, 'product_transfer_request_id');
    }
}
