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
                    'filePath' => env('CONTACT_PICTURES_FOLDER'). '/' . sha1(Auth::id() + self::SALT) . '/' . $fileName,
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

    public function update(Request $request, int $id)
    {
        //return $request->get('name');
        try {
            $contact = Contact::find($id);
            $contact->update($request->all());
        } catch (\Exception $exception) {
            return \response(['message' => 'Not found - 404'], \Symfony\Component\HttpFoundation\Response::HTTP_NOT_FOUND);
        }

        // UPDATE PHONES
        $crudPhones = $this->crudPartition($contact->phones, $request->get('phones'));

        // create new ones
        $contact->phones()->saveMany(collect($crudPhones['create'])->map(function ($phone) {
            return new Phone(['type' => $phone['type'], 'phoneNumber' => $phone['phoneNumber']]);
        }));

        foreach ($crudPhones['update'] as $toUpdate) {
            Phone::where('id', $toUpdate['id'])->update($toUpdate);
        }

        // delete phones
        Phone::destroy(collect($crudPhones['delete']));


        // UPDATE EMAILS
        $crudEmails = $this->crudPartition($contact->emails, $request->get('emails'));

        // create new ones
        $contact->emails()->saveMany(collect($crudEmails['create'])->map(function ($email) {
            return new Email(['type' => $email['type'], 'emailAddress' => $email['emailAddress']]);
        }));

        foreach ($crudEmails['update'] as $toUpdate) {
            Email::where('id', $toUpdate['id'])->update($toUpdate);
        }

        // delete emails
        Email::destroy(collect($crudEmails['delete']));

        // UPDATE ADDRESS
        if ($request->get('address')) {
            foreach ($request->get('address') as $addressItem) {
                if (!empty($addressItem)) {
                    $contact->address()->firstOrCreate($request->get('address'));
                }
            }
        }

        // HANDLE PICTURE
        $file = $request->file('picture');
        if ($file) {
            $fileName = sha1(Carbon::now()) . '.' . $file->getClientOriginalExtension();

            //$contact = Contact::find($id);

            if ($contact->contactPicture != null) {
                $fileToUpdate = $contact->contactPicture->fileName;


                // delete from db
                $contact->contactPicture()->update([
                    'origFileName' => $file->getClientOriginalName(),
                    'fileName' => $fileName,
                    'filePath' => env('CONTACT_PICTURES_FOLDER') . '/' . sha1(Auth::id() + self::SALT) . '/' . $fileName,
                ]);

                // delete from storage
                Storage::delete(env('CONTACT_PICTURES_FOLDER') . '/' . sha1(Auth::id() + self::SALT) . '/' . $fileToUpdate);

            } else {

                $contact->contactPicture()->create([
                    'origFileName' => $file->getClientOriginalName(),
                    'fileName' => $fileName,
                    'filePath' => env('CONTACT_PICTURES_FOLDER') . '/' . sha1(Auth::id() + self::SALT) . '/' . $fileName,
                    'contactId' => $contact->id
                ]);
            }

            $request->file('picture')->storeAs(env('CONTACT_PICTURES_FOLDER') . '/' . sha1(Auth::id() + self::SALT) . '/', $fileName);
        } else {
            if ($contact->contactPicture) {
                $contact->contactPicture->delete();

                // delete from storage
                Storage::delete(env('CONTACT_PICTURES_FOLDER') . '/' . sha1(Auth::id() + self::SALT) . '/' . $contact->contactPicture->fileName);
            }
        }

        return $contact;
    }

    public function destroy(Request $request, int $id)
    {
        $contact = Contact::find($id);
        if ($contact->contactPicture()->count() > 0) {
            $fileToDelete = $contact->contactPicture->fileName;

            // delete from storage
            Storage::delete(env('CONTACT_PICTURES_FOLDER') . '/' . sha1(Auth::id() + self::SALT) . '/' . $fileToDelete);
        }
        Contact::destroy($id);
    }

    private function crudPartition($oldData, $newData)
    {
        $oldIds = collect($oldData)->pluck('id')->toArray();
        $newIds = collect($newData)->pluck('id')->toArray();

        $delete = array_diff($oldIds, array_intersect($oldIds, $newIds));

        $create = collect($newData)
            ->filter(function ($model) use ($oldIds) {
                return !array_key_exists('id', $model);
            });

        $update = collect($newData)
            ->filter(function ($model) use ($oldIds) {
                return array_key_exists('id', $model) && in_array($model['id'], $oldIds);
            });
        return compact('delete', 'update', 'create');
    }
}
