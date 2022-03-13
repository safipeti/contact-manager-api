<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;

    protected $fillable = ['zipCode', 'street', 'city', 'country', 'misc'];

    public function contact() {
        return $this->belongsTo(Contact::class, 'contactId');
    }
}
