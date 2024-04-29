<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Allowed_Users;
use App\Models\Forms;
use App\Models\Questions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class QuestionsController extends Controller
{
    //create question function
    public function create(string $form_slug, Request $request)
    {
        // make validation rules
        $validate = Validator::make($request->all(), [
            'name' => 'required',
            'choice_type' => ['required', Rule::in(['short answer', 'paragraph', 'date', 'multiple choice', 'dropdown', 'checkboxes'])],
            'choices' => [Rule::requiredIf($request->choice_type === 'multiple choice' || $request->choice_type === 'dropdown' || $request->choice_type === 'checkboxes'), 'array'],
            'is_required' => 'required',
        ]);
        // check if validation rules fails
        if ($validate->fails()) {
            return response($validate->errors(), 422);
        }

        // search slug in forms table
        $forms = Forms::where('slug', $form_slug)->first();

        // search user id
        $user = $request->user()->id;
        // check if slug forms not null
        if ($forms !== null) {
            $allowed_users = Allowed_Users::where('form_id',$forms->id)->get();

            $filter_users = collect($allowed_users)->filter(function($val) use ($user){
                return $val->users_allowed === $user;
            })->all();

            // check if users not access another forms  
            if ($forms->creator_id === $user || count($filter_users) > 0) {
                // create questions
                $questions = Questions::create([
                    'form_id' => $forms->id,
                    'name' => $request->name,
                    'choice_type' => $request->choice_type,
                    'choices' => json_encode($request->choices, true) ?? null,
                    'is_required' => $request->is_required
                ]);

                // return response 
                return response()->json([
                    'message' => 'Add questions success',
                    'question' => [
                        'name' => $questions->name,
                        'choice_type' => $questions->choice_type,
                        'is_required' => $questions->is_required > 0 ? true : false,
                        'choices' => $questions->choices !== null ? json_decode($questions->choices) : $questions->choices,
                        'form_id' => $questions->form_id,
                        'id' => $questions->id
                    ]
                ], 200);
            }
            return response(['message' => 'Forbiden access'], 403);
        }
        return response(['message' => 'Form not found'], 404);
    }

    // remove function
    public function remove(Request $request, string $form_slug, string $questions_id)
    {
        // search forms slug 
        $forms = Forms::where('slug', $form_slug)->first();
        // search questions 
        $questions = Questions::find($questions_id);
        // user id
        $user = $request->user()->id;   

        // check if forms not null
        if ($forms !== null) {

            $allowed_users = Allowed_Users::where('form_id',$forms->id)->get();

            $filter_users = collect($allowed_users)->filter(function($val) use ($user){
                return $val->users_allowed === $user;
            })->all();


            // check if user access another forms
            if ($forms->creator_id !== $user || count($filter_users) > 0) {
                return response(['message' => 'Forbidden access'], 403);
            }

            // check if questions not null
            if ($questions !== null) {
                $questions->delete();
                return response()->json(['message' => 'Remove questions success'], 200);
            }
            // return response when question not found
            return response(['message' => 'Questions not found'], 404);
        }
        // return response when form not found
        return response(['message' => 'Form not found'], 404);
    }
}
