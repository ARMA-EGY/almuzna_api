<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ReceiverEmail extends Model
{
    protected $table = 'receiver_email';
    
    protected $fillable = [
        'email'
    ];
}
