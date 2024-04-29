<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Allowed_Domains;
use App\Models\Forms;
use App\Models\Questions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FormController extends Controller
{
    //Create Form Function
    public function create(Request $request)
    {
        // make validation rules
        $validate = Validator::make($request->all(), [
            'name' => 'required',
            'allowed_domains' => 'required|array',
            'slug' => 'required|unique:forms,slug|alpha_dash:ascii',
        ]);
        // check if validation fails
        if ($validate->fails()) {
            return response($validate->errors(), 422);
        }
        // create forms
        $forms = Forms::create([
            'name' => $request->name,
            'slug' => $request->slug,
            'description' => $request->description,
            'limit_one_response' => $request->limit_one_response,
            'creator_id' => $request->user()->id
        ]);

        // create allowed_domains
        Allowed_Domains::create([
            'form_id' => $forms->id,
            'domain' => json_encode($request->allowed_domains, true),
        ]);

        // return response 
        return response()->json([
            'message' => 'Create form success',
            'form' => [
                'name' => $forms->name,
                'slug' => $forms->slug,
                'description' => $forms->description,
                'limit_one_response' => $forms->limit_one_response > 0 ? true : false,
            ]
        ], 200);
    }

    // index function
    public function index()
    {
        // get all forms
        $forms = Forms::all();
        if ($forms->count() > 0) {
            // return response if success
            return response()->json([
                'message' => 'Get All forms success',
                'forms' => $forms
            ]);
        }
        // return response is fails
        return response()->json([
            'message' => 'Forms Empty',
            'forms' => []
        ]);
    }

    // get specisifik index
    public function indexDetail(Request $request, string $form_slug)
    {
        // get user email
        $user = $request->user()->email;
        // get user email domain
        $user_domain = explode('@', $user);
        // search forms slug
        $forms = Forms::where('slug', $form_slug)->first();
        // search allowed_domain
        $domain = Allowed_Domains::where('form_id', $forms->id)->first();
        // decode json string to array
        $allowed_domain = json_decode($domain->domain);
       

        // check if forms not null
        if ($forms !== null) {
             // search allowed domain
            $allowed = collect($allowed_domain)->filter(function($val) use($user_domain){
                return $user_domain[1] === $val ? true : false;
            })->all();
            // check if user domain not allowed
            if(count($allowed) === 0){
                return response()->json(['message' => 'Forbidden Access'], 403);
            }

            // search forms_id in domain if same with forms
            $domain = Allowed_Domains::where('form_id', $forms->id)->first();
            // search forms_id in questions if same with forms
            $questions = Questions::where('form_id', $forms->id)->get();
            // return response success
            return response()->json([
                'message' => 'Get form success',
                'form' => [
                    'id' => $forms->id,
                    'name' => $forms->name,
                    'slug' => $forms->slug,
                    'description' => $forms->description,
                    'limit_one_response' => $forms->limit_one_response,
                    'creator_id' => $forms->creator_id,
                    'allowed_domain' => $domain !== null ? json_decode($domain->domain) : [],
                ],
                'questions' => count($questions) > 0 ? $questions : []
            ]);
        }
        // return response if not found
        return response()->json([
            'message' => 'Form not found'
        ], 404);
    }
}
