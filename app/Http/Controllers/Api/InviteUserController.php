<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Allowed_Users;
use App\Models\Forms;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InviteUserController extends Controller
{
    //function user
    public function invite(Request $request,string $form_slug){
        $forms = Forms::where('slug',$form_slug)->first();
        if($forms !== null){
            $validate = Validator::make($request->all(),[
                'users_allowed' => 'required|array'
            ]);

            if($validate->fails()){
                return response($validate->errors(),422);
            }

            $users = $request->user()->id;
            if($forms->creator_id === $users){
                $user_allow = $request->users_allowed;
                foreach($user_allow as $allowed){
                    Allowed_Users::create([
                        'form_id' => $forms->id,
                        'users_allowed' => json_encode($allowed,true)
                    ]);
                }
                return response()->json(['message' => 'success invited users'],200);
            }
            return response(['message' => 'forbidden access'],403);
        }
    }
}
