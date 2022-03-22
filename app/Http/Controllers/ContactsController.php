<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use App\Models\Address;
use App\Models\Contact;
use App\Models\Email;
use App\Models\Phone;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ContactsController extends Controller
{

    const SALT = 896542;

    public function index()
    {
        $limit = 3;
        $searchTerm = \request()->get('searchTerm');
        $currentPage = empty(\request()->get('currentPage')) ? 1 : \request()->get('currentPage');
        $offset = $limit * ($currentPage - 1);

        if ($searchTerm) {
            $contacts = Contact::with('emails', 'phones', 'address', 'contactPicture')
                ->where('name', 'like', '%'.$searchTerm.'%')
                ->orwhere('notes', 'like', '%'.$searchTerm.'%')
                ->orwhereHas('emails', function($query) use($searchTerm) {
                    $query->Where('emailAddress', 'like', '%'.$searchTerm.'%');
                })
                ->orwhereHas('phones', function($query) use($searchTerm) {
                    $query->Where('phoneNumber', 'like', '%'.$searchTerm.'%');
                })
                ->orwhereHas('address', function($query) use($searchTerm) {
                    $query->Where('city', 'like', '%'.$searchTerm.'%');
                    $query->orWhere('zipCode', 'like', '%'.$searchTerm.'%');
                    $query->orWhere('country', 'like', '%'.$searchTerm.'%');
                    $query->orWhere('misc', 'like', '%'.$searchTerm.'%');
                    $query->orWhere('street', 'like', '%'.$searchTerm.'%');
                });
            $totalContacts = $contacts->count();
            $contacts = $contacts->skip( $offset)
                ->take($limit)
                ->get();
            return \response(['contacts' => $contacts, 'totalContacts' => $totalContacts]);

        } else {
            $contacts = Contact::with(['emails', 'phones', 'address', 'contactPicture']);
            $totalContacts = $contacts->count();
            $contacts = $contacts->skip( $offset)->take($limit)->get();

            return response(['contacts' => $contacts, 'totalContacts' => $totalContacts]);
        }
    }

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
