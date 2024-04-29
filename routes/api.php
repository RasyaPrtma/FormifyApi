<?php

use App\Http\Controllers\Api\AuthenticationController;
use App\Http\Controllers\Api\FormController;
use App\Http\Controllers\Api\InviteUserController;
use App\Http\Controllers\Api\QuestionsController;
use App\Http\Controllers\Api\ResponseController;
use Illuminate\Support\Facades\Route;


Route::group(["prefix"=> "v1"], function () {
    Route::group(["prefix"=> "auth"], function () {
        Route::post('login',[AuthenticationController::class,'login']);
        Route::post('logout',[AuthenticationController::class,'logout'])->middleware('auth:sanctum');
    });
    Route::middleware('auth:sanctum')->group(function (){
        Route::post('invite/{form_slug}',[InviteUserController::class,'invite']);
        Route::post('forms',[FormController::class,'create']);
        Route::get('forms',[FormController::class,'index']);
        Route::get('forms/{form_slug}',[FormController::class,'indexDetail']);  
        Route::post('forms/{form_slug}/questions',[QuestionsController::class,'create']);
        Route::delete('forms/{form_slug}/questions/{questions_id}',[QuestionsController::class,'remove']);
        Route::post('forms/{form_slug}/responses',[ResponseController::class,'create']);
        Route::get('forms/{form_slug}/responses',[ResponseController::class,'index']);
    });
});
