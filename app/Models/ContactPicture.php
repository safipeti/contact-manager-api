<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactPicture extends Model
{
    use HasFactory;

    protected $fillable = ['origFileName', 'fileName', 'filePath'];

    public function contact() {
        return $this->belongsTo(Contact::class, 'contactId');
    }
}
