<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'dob', 'notes', 'userId'];

    public function user() {
        return $this->belongsTo(User::class, 'userId');
    }

    public function address() {
        return $this->hasOne(Address::class, 'contactId');
    }

    public function emails() {
        return $this->hasMany(Email::class, 'contactId');
    }

    public function phones() {
        return $this->hasMany(Phone::class, 'contactId');
    }

    public function contactPicture() {
        return $this->hasOne(ContactPicture::class, 'contactId');
    }

    public function picturePath() {

        if ($this->contactPicture->count() > 0) {

            return url()->secure($this->contactPicture->filePath );
        }

        return nulll;
    }

    public static function boot()
    {
        parent::boot(); // TODO: Change the autogenerated stub
        self::deleting(function ($contact){
            $contact->phones()->delete();
            $contact->address()->delete();
            $contact->emails()->delete();
            $contact->social()->delete();
            $contact->contactPicture()->delete();
        });
    }
}
