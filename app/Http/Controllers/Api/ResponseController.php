<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Allowed_Domains;
use App\Models\Allowed_Users;
use App\Models\Answers;
use App\Models\Forms;
use App\Models\Questions;
use App\Models\Responses;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use function Pest\Laravel\json;

class ResponseController extends Controller
{
    //create function
    public function create(Request $request, string $form_slug)
    {
        // search limit one response
        $forms = Forms::where('slug', $form_slug)->first();

        if ($forms !== null) {

            // search questions form_id
            $questions = Questions::where('form_id', $forms->id)->get();

            // collect forms_id
            $is_required = collect($questions)->filter(function ($value) {
                return $value->is_required;
            })->all();

            // check if is_required have value
            if (count($is_required) > 0) {
                // make validation rules
                foreach ($is_required as $value) {
                    $validate = Validator::make($request->all(), [
                        'answers' => ['array', Rule::requiredif($value)]
                    ]);
                }
            } else {
                // make validation rules
                $validate = Validator::make($request->all(), [
                    'answers' => 'array'
                ]);
            }

            // check if validation fails
            if ($validate->fails()) {
                return response($validate->errors(), 422);
            }

            // check user domain
            $user = $request->user()->email;
            // user email domain
            $user_domain = explode('@', $user)[1];

            // allowed domain 
            $domain = Allowed_Domains::where('form_id', $forms->id)->first();

            // search allwed domain
            $allowed = collect(json_decode($domain->domain))->filter(function ($val) use ($user_domain) {
                return $val === $user_domain;
            })->all();

            // return response if not allowed
            if (count($allowed) === 0) {
                return response(['message' => 'Forbidden access'], 403);
            }


            // find response if user send response twice
            $response = Responses::where('user_id', $request->user()->id)->first();
            // check if response not null
            if ($response !== null) {
                return response(['You can not submit form twice'], 422);
            }

            $allowed_users = Allowed_Users::where('form_id',$forms->id)->get();

            $users = $request->user()->id;

            $filter_users = collect($allowed_users)->filter(function($val) use ($users){
                return $val->users_allowed === $users;
            });            

            // check if questions have value
            if (count($questions) > 0){
                if($forms->creator_id === $request->user()->id || count($filter_users) > 0){

                    // create response
                $newResponse = Responses::create([
                    'form_id' => $forms->id,
                    'user_id' => $request->user()->id,
                ]);

                //  filter questions id
                $questions_id = collect($questions)->filter(function ($val) use ($forms) {
                    return $val->form_id === $forms->id;
                })->all();
                // set answers
                $answer = $request->answers;
                // set value for filter
                $arr_filter = [];

                // loop
                foreach ($questions_id as $key => $value) {
                    // search filter answers
                    $filter_answers = collect($answer)->filter(function ($val) use ($value) {
                        return $val["question_id"] === $value->id;
                    });
                    // push to arr filter with filtered value answers
                    array_push($arr_filter,...$filter_answers);
                }

                // loop
                foreach($arr_filter as $value){
                    // create answers
                   Answers::create([
                        'response_id' => $newResponse->id,
                        'question_id' => $value["question_id"],
                        'value' => $value["value"]
                    ]);
                }

                // return response success
                return response()->json([
                    'message' => 'Submit response success'
                ], 200);

                }
                return response(['message' => 'Forbidden access'], 403);
            }
            // return response if question not finded
            return response(['message' => "Question Not Finded"], 404);
        }
        // return responses if forms not found
        return response(['message' => 'Forms Not Finded'], 404);
    }

    public function index(Request $request,string $form_slug)
    {
        $forms = Forms::where('slug',$form_slug)->first();
        $user = $request->user()->id;

        if($forms !== null){

            $allowed_users = Allowed_Users::where('form_id',$forms->id)->get();

            $filter_users = collect($allowed_users)->filter(function($val) use ($user){
                return $val->users_allowed === $user;
            })->all();

            if($forms->creator_id === $user || count($filter_users) > 0){
                $response = Responses::where('form_id',$forms->id)->get();
                
            $message = [];
                foreach($response as $res_val){
                    $user_response = User::find($res_val->user_id);
                    $answers = Answers::where('response_id',$res_val->id)->get();
                    foreach($answers as $value){
                        array_push($message,[ 'date' => $res_val->created_at,
                        'user' => $user_response,
                        'answers' => $value]);
                    }
                }
               
                return response()->json([
                    'message' => 'Get Responses success',
                    'responses' => $message
                ],200);
            }
            return response(['message' => 'Forbidden access'],403);
        }
        return response(['message' => "Forms Not Finded"],404);
    }
}
