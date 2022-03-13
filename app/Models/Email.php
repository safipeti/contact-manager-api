<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Email extends Model
{
    use HasFactory;

    protected $fillable = ['type', 'emailAddress'];

    public function contact() {
        return $this->belongsTo(Contact::class, 'contactId');
    }
}
