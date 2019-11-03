<?php

use Illuminate\Http\Request;

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

Route::post('login', function (Request $request) {
    $credentials = $request->only('email', 'password');
    if (Auth::guard('web')->attempt($credentials)) {
        $user = Auth::guard('web')->user();
        return $user->makeVisible('api_token');
    } else {
        abort(403, "Incorrect credentials.");
    }
});

Route::get('undoable_verbs', 'EventController@undoableVerbs');
Route::post('group_members', 'GroupMemberController@create');

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('me', 'GroupMemberController@me');
    Route::delete('group_members/{id}', 'GroupMemberController@delete');

    Route::post('groups', 'GroupController@create');
    Route::get('groups/{id}', 'GroupController@get');
    Route::put('groups/{id}', 'GroupController@update');

    Route::get('groupsearch', 'GroupController@search');

    Route::post('groups/{id}/transactions', 'TransactionController@create');

    Route::get('groups/{id}/debts', 'DebtController@get');
    Route::put('groups/{id}/debts', 'DebtController@update');

    Route::get('groups/{id}/events', 'EventController@all');

    Route::post('events/{id}/undo', 'EventController@undo');
});
