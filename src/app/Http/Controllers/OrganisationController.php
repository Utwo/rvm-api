<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use App\Mail\SetUpPassword;
use App\PasswordReset;
use App\Organisation;
use App\Volunteer;
use App\Resource;
use App\User;
use App\County;
use App\City;

class OrganisationController extends Controller
{
        /**
     * @SWG\Get(
     *   tags={"Organisations"},
     *   path="/api/organisations",
     *   summary="Return all organisations",
     *   operationId="index",
     *   @SWG\Response(response=200, description="successful operation"),
     *   @SWG\Response(response=406, description="not acceptable"),
     *   @SWG\Response(response=500, description="internal server error")
     * )
     *
     */
    public function index(Request $request)
    {
        $params = $request->query();
        $organisations = Organisation::query();

        applyFilters($organisations, $params, array(
            '1' => array( 'county', 'ilike' ),
            // '2' => array( 'county', 'ilike' ),
            // '3' => array( 'organisation.name', 'ilike')
        ));

        applySort($organisations, $params, array(
            '1' => 'name',
            '2' => 'county',
            // '2' => 'type_name',
            // '3' => 'quantity',
            // '4' => 'organisation', //change to nr_org
        ));

        $pager = applyPaginate($organisations, $params);

        return response()->json(array(
            "pager" => $pager,
            "data" => $organisations->get()
        ), 200); 
    }
     /**
     * @SWG\Get(
     *   tags={"Organisations"},
     *   path="/api/organisations/{id}",
     *   summary="Show organisation info ",
     *   operationId="show",
     *   @SWG\Response(response=200, description="successful operation"),
     *   @SWG\Response(response=404, description="not acceptable"),
     * )
     *
     */

    public function show($id)
    {
        return response()->json(Organisation::findOrFail($id), 200); 

    }

    /**
    * @SWG\Get(
    *   tags={"Organisations"},
    *   path="/api/organisations/{id}/volunteers",
    *   summary="Show all volunteers of an Organisation ",
    *   operationId="show",
    *   @SWG\Response(response=200, description="successful operation"),
    *   @SWG\Response(response=404, description="not found")
    * )
    *
    */

    public function showVolunteers(Request $request, $id)
    {
        $params = $request->query();
        $volunteers = Volunteer::query()
            ->where('organisation._id', '=', $id);

        // applyFilters($volunteers, $params, array(
        //     '1' => array( 'type_name', 'ilike' ),
        //     '2' => array( 'county', 'ilike' ),
        //     '3' => array( 'organisation.name', 'ilike')
        // ));

        applySort($volunteers, $params, array(
            '1' => 'name',
            '2' => 'county',
           // '3' => 'specialization', //Specialization
          //  '4' => 'training', //data ultimului training
        ));

        $pager = applyPaginate($volunteers, $params);

        return response()->json(array(
            "pager" => $pager,
            "data" => $volunteers->get()
        ), 200); 
    }

    /**
    * @SWG\Get(
    *   tags={"Organisations"},
    *   path="/api/organisations/{id}/resources",
    *   summary="Show all resources of an Organisation ",
    *   operationId="show",
    *   @SWG\Response(response=200, description="successful operation"),
    *   @SWG\Response(response=404, description="not found")
    * )
    *
    */

    public function showResources(Request $request, $id)
    {
        $params = $request->query();
        $resources = Resource::query()
            ->where('organisation._id', '=', $id);

        applyFilters($resources, $params, array(
            '1' => array( 'type_name', 'ilike' ),
            '2' => array( 'county', 'ilike' ),
        ));

        applySort($resources, $params, array(
            '1' => 'name',
            '2' => 'quantity',
            '3' => 'county',
        ));

        $pager = applyPaginate($resources, $params);

        return response()->json(array(
            "pager" => $pager,
            "data" => $resources->get()
        ), 200); 
    }


    /**
     * @SWG\Post(
     *   tags={"Organisations"},
     *   path="/api/organisations",
     *   summary="Create organisation",
     *   operationId="store",
     *   @SWG\Parameter(
     *     name="name",
     *     in="query",
     *     description="Organisation name.",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="website",
     *     in="query",
     *     description="Organisation website.",
     *     required=true,
     *     type="string"
     *   ),
     *  @SWG\Parameter(
     *     name="contact_person",
     *     in="query",
     *     description="Organisation Contact Person.",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="email",
     *     in="query",
     *     description="Organisation email.",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="phone",
     *     in="query",
     *     description="Organisation phone.",
     *     required=true,
     *     type="string"
     *   ),
     *  @SWG\Parameter(
     *     name="county",
     *     in="query",
     *     description="Organisation county.",
     *     required=true,
     *     type="string"
     *   ),
     *  @SWG\Parameter(
     *     name="city",
     *     in="query",
     *     description="Organisation city.",
     *     required=true,
     *     type="string"
     *   ),
     *  @SWG\Parameter(
     *     name="address",
     *     in="query",
     *     description="Organisation address.",
     *     required=false,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="comments",
     *     in="query",
     *     description="Organisation comments.",
     *     required=false,
     *     type="string"
     *   ),
     *   @SWG\Response(response=200, description="successful operation"),
     *   @SWG\Response(response=406, description="not acceptable"),
     *   @SWG\Response(response=500, description="internal server error")
     * )
     *
     */
    public function store(Request $request)
    {
        $data = $request->all();
        $rules = [
            'name' => 'required|string|max:255',
            'website' => 'required|max:255',
            'contact_person' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:organisations.organisations',
            'phone' => 'required|string|min:6|'
        ];
        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 400);
        }

        $data = convertData($validator->validated(), $rules);

        if ($request->has('county')) {
            $county = County::query()
                ->get(['_id', 'name', 'slug'])
                ->where('_id', '=', $request->county)
                ->toArray();
            $data['county'] = $county[0];
        }
        if ($request->has('city')) {            
            $city = City::query()
                ->get(['_id', 'name', 'slug'])
                ->where('_id', '=', $request->city)
                ->toArray();
            $data['city'] = $city[0];
        }
        
        $request->has('comments') ? $data['comments'] = $request->comments : '';
        \Auth::check() ? $data['added_by'] = \Auth::user()->_id : '';
        $passwordReset = PasswordReset::updateOrCreate(
            ['email' => $data['email']],
            [
                'email' => $data['email'],
                'token' => str_random(60)
            ]
        );
        $url = url('/auth/reset/'.$passwordReset->token);
        $set_password_data = array(
            'name' => $data['contact_person'],
            'url' => $url
        );
        $data['password'] = Hash::make(str_random(16));
        Mail::to($data['email'])->send(new SetUpPassword($set_password_data));
        $organisation = Organisation::create($data);

        $newNgoAdmin = User::firstOrNew([
            'email' => $data['email'],
        ]);
        $newNgoAdmin->name = $data['contact_person'];
        $newNgoAdmin->role = config('roles.role.ngo');
        $newNgoAdmin->phone = $data['phone'];
        $newNgoAdmin->organisation = array('_id' => $organisation->_id, 'name' => $organisation->name);
        $newNgoAdmin->added_by = $data['added_by'];
        $newNgoAdmin->password = $data['password'];
        $newNgoAdmin->save();

        $response = array(
            "message" => 'Password sent to email.',
            "organisation" => $organisation
        );
        return response()->json($response, 201); 
    }

    /**
     * @SWG\put(
     *   tags={"Organisations"},
     *   path="/api/organisations/{id}",
     *   summary="Update organisation",
     *   operationId="update",
     *   @SWG\Response(response=200, description="successful operation"),
     *   @SWG\Response(response=406, description="not acceptable"),
     *   @SWG\Response(response=500, description="internal server error")
     * )
     *
     */

    public function update(Request $request, $id)
    {
        $organisation = Organisation::findOrFail($id);
        $organisation->update($request->all());

        return $organisation;
    }

    /**
     * @SWG\Delete(
     *   tags={"Organisations"},
     *   path="/api/organisations/{id}",
     *   summary="Delete organisation",
     *   operationId="delete",
     *   @SWG\Response(response=200, description="successful operation"),
     *   @SWG\Response(response=406, description="not acceptable"),
     *   @SWG\Response(response=500, description="internal server error")
     * )
     *
     */

    public function delete(Request $request, $id)
    {
        $organisation = Organisation::findOrFail($id);
        $organisation->delete();

        $response = array("message" => 'Organisation deleted.');

        return response()->json($response, 200);
    }
}
