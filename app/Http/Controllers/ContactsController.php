<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use App\Models\Address;
use App\Models\Contact;
use App\Models\Email;
use App\Models\Phone;
use App\Models\Social;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ContactsController extends Controller
{

    const SALT = 896542;

    public function store(ContactRequest $request)
    {
        try{
            $request->request->add(['userId' => Auth::id()]);
            $contact = Contact::create($request->all());

            if(!empty($request->file('picture'))) {

                $file = $request->file('picture');

                $fileName = sha1(Carbon::now()) . '.' . $file->getClientOriginalExtension();

                $contact->contactPicture()->create([
                    'origFileName' => $file->getClientOriginalName(),
                    'fileName' => $fileName,
                    'filePath' => 'contactpictures/' . sha1(Auth::id() + self::SALT) . '/' . $fileName,
                    'contactId' => $contact->id
                ]);

                $request->file('picture')->storeAs(env('CONTACT_PICTURES_FOLDER'). '/' . sha1(Auth::id() + self::SALT) . '/', $fileName);
            }

            return $contact;

        } catch (\Exception $exception) {
            return $exception->getMessage();
        }

    }

    public function show(int $id)
    {
        try {
            $contact = Contact::where(['id' => $id, 'userId' => Auth::id()])
                ->with(['emails', 'phones', 'address', 'contactPicture'])
                ->firstOrFail();

            if ($contact->exists()) {
                return \response($contact);
            }

        } catch (\Exception $exception) {
            return \response(['message' => 'Not found - 404'], \Symfony\Component\HttpFoundation\Response::HTTP_NOT_FOUND);
        }
    }
}
