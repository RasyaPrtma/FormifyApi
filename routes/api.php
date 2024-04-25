<?php

use App\Http\Controllers\Api\AuthenticationController;
use App\Http\Controllers\Api\FormController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::group(["prefix"=> "v1"], function () {
    Route::group(["prefix"=> "auth"], function () {
        Route::post('login',[AuthenticationController::class,'login']);
        Route::post('logout',[AuthenticationController::class,'logout'])->middleware('auth:sanctum');
    });
    Route::middleware('auth:sanctum')->group(function (){
        Route::post('forms',[FormController::class,'create']);
        Route::get('forms',[FormController::class,'index']);
        Route::get('forms/{form_slug}',[FormController::class,'indexDetail']);
    });
});
