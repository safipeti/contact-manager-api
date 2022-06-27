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
        $params = json_decode(\request()->get('params'));
        $limit = $params->limit;
        $searchTerm = $params->searchTerm;
        $currentPage = $params->page;
        $offset = $limit * ($currentPage - 1);

        try {
            if ($searchTerm) {
                $contacts = Contact::with('emails', 'phones', 'address', 'contactPicture')
                    ->where('userId', Auth::id())
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
                $contacts = Contact::where('userId', Auth::id())->with(['emails', 'phones', 'address', 'contactPicture']);
                $totalContacts = $contacts->count();
                $contacts = $contacts->skip( $offset)->take($limit)->get();

                return response(['contacts' => $contacts, 'totalContacts' => $totalContacts]);
            }
        } catch (\Exception $exception) {
            return ['error' => true, 'message' => $exception->getMessage(), 'contacts' => null];
        }
    }

    public function store(ContactRequest $request)
    {
        try{
            $contact = Contact::create(array_merge($request->all(), ['userId' => Auth::id()]));

            // create phones
            $contact->phones()->saveMany(collect($request->get('phones'))->map(function ($phone) {
                return new Phone(['type' => $phone['type'], 'phoneNumber' => $phone['phoneNumber']]);
            }));

            // create new ones
            $contact->emails()->saveMany(collect($request['emails'])->map(function ($email) {
                return new Email(['type' => $email['type'], 'emailAddress' => $email['emailAddress']]);
            }));

            // create address
            $address = $request->get('address');
            if (!empty($address['street']) || !empty($address['zipCode']) || !empty($address['city']) || !empty($address['country']) || !empty($address['misc'])) {
                foreach ($request->get('address') as $addressItem) {
                    if (!empty($addressItem)) {
                        $contact->address()->firstOrCreate($request->get('address'));
                    }
                }
            }
            return \response(['error' => false, 'contact' => $contact->where('id', $contact->id)->with('phones', 'emails', 'address', 'contactPicture')->first(), 'message' => 'Contact successfuly added!']);

        } catch (\Exception $exception) {
            return ['error' => true, 'message' => $exception->getMessage(), 'contact' => null];
        }
    }

    public function show(int $id)
    {
        try {
            $contact = Contact::where(['id' => $id, 'userId' => Auth::id()])
                ->with(['emails', 'phones', 'address', 'contactPicture'])
                ->firstOrFail();

            return \response($contact);

        } catch (\Exception $ex) {
            return \response(['error' => true, 'message' =>  $ex->getMessage(), 'contact' => null]);
        }
    }

    public function update(Request $request, int $id)
    {
        try {
            $contact = Contact::where(['id' => $id, 'userId' => Auth::id()])->firstOrfail();

            $contact->update($request->all());

            $contact->phones()->delete();
            $contact->phones()->saveMany(collect($request->get('phones'))->map(function ($phone) {
                return new Phone(['type' => $phone['type'], 'phoneNumber' => $phone['phoneNumber']]);
            }));

            $contact->emails()->delete();
            $contact->emails()->saveMany(collect($request['emails'])->map(function ($email) {
                return new Email(['type' => $email['type'], 'emailAddress' => $email['emailAddress']]);
            }));

            $contact->address()->delete();
            $address = $request->get('address');
            if (!empty($address['street']) || !empty($address['zipCode']) || !empty($address['city']) || !empty($address['country']) || !empty($address['misc'])) {
                foreach ($request->get('address') as $addressItem) {
                    if (!empty($addressItem)) {
                        $contact->address()->firstOrCreate($request->get('address'));
                    }
                }
            }

            return \response(['error' => false, 'contact' => $contact->where('id', $contact->id)->with('phones', 'emails', 'address', 'contactPicture')->first(), 'message' => 'Contact successfuly updated!']);

        } catch (\Exception $exception) {
            return \response(['error' => true, 'message' => $exception->getMessage()]);
        }

    }

    public function destroy(Request $request, int $id)
    {
        try {
            $contact = Contact::where(['id' => $id, 'userId' => Auth::id()])->firstOrfail();
            if ($contact->contactPicture()->count() > 0) {
                $fileToDelete = $contact->contactPicture->fileName;

                // delete from storage
                Storage::delete('public/' . env('CONTACT_PICTURES_FOLDER') . '/' . sha1(Auth::id() + self::SALT) . '/' . $fileToDelete);
            }
            Contact::destroy($id);

        } catch(\Exception $ex) {
            return \response(['error' => true, 'message' => $ex->getMessage()]);
        }
    }
}
