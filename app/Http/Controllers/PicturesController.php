<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadRequest;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PicturesController extends Controller
{
    const SALT = 896542;

    public function upload (UploadRequest $request) {

        try {
            //throw new \Exception('balfasz ADDING');

            $file = $request->file('picture');
            $contactId = $request->get('contactId');
            if ($file) {
                $fileName = sha1(Carbon::now()) . '.' . $file->getClientOriginalExtension();

                $contact = Contact::where(['id' => $contactId, 'userId' => Auth::id()])->firstOrfail();

                if ($contact->contactPicture != null) {
                    $fileToUpdate = $contact->contactPicture->fileName;


                    // delete from db
                    $contact->contactPicture()->update([
                        'origFileName' => $file->getClientOriginalName(),
                        'fileName' => $fileName,
                        'filePath' => 'storage/contactpictures/' . sha1(Auth::id() + self::SALT) . '/' . $fileName,
                    ]);

                    // delete from storage
                    Storage::delete('public/contactpictures/' . sha1(Auth::id() + self::SALT) . '/' . $fileToUpdate);

                } else {

                    $contact->contactPicture()->create([
                        'origFileName' => $file->getClientOriginalName(),
                        'fileName' => $fileName,
                        'filePath' => 'storage/contactpictures/' . sha1(Auth::id() + self::SALT) . '/' . $fileName,
                        'contactId' => $contactId
                    ]);
                }

                //$request->file('picture')->storeAs(env('CONTACT_PICTURES_FOLDER') . '/' . sha1(Auth::id() + self::SALT) . '/', $fileName);
                $request->file('picture')->storeAs('public/' . env('CONTACT_PICTURES_FOLDER') . '/' . sha1(Auth::id() + self::SALT) . '/', $fileName);
            }
        } catch (\Exception $ex) {
            return ['error' => true, 'message' => $ex->getMessage()];
        }
    }

//    public function serve($folder, $picture){
//        $path = storage_path('app\\'. env('CONTACT_PICTURES_FOLDER') . '\\'.$folder.'\\'.$picture );
//        return \response()->file($path);
//    }

    public function deleteContactPicture(Request $request) {
        throw new \Exception('balfasz ADDING');
        try {

            $contactId = $request->get('contactId');
            $contact = Contact::where(['id' => $contactId, 'userId' => Auth::id()])->firstOrfail();

            if ($contact->contactPicture != null) {
                $fileToUpdate = $contact->contactPicture->fileName;

                // delete from db
                $contact->contactPicture()->delete();
                // delete from storage
                Storage::delete('public/contactpictures/' . sha1(Auth::id() + self::SALT) . '/' . $fileToUpdate);
            }
        }catch (\Exception $ex) {
            return 'iddddd';
            return ['error' => true, 'message' => $ex->getMessage()];
        }
    }
}
