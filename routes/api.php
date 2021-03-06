<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });
Route::post('v1/on-covid-19', 'EstimatorController@covid19ImpactEstimator');
Route::post('v1/on-covid-19/{type}', 'EstimatorController@covid19ImpactEstimator');
Route::get('v1/on-covid-19/logs', 'Logs@apiLogs');
