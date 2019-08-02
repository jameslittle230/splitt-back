<?php

use Illuminate\Http\Request;
use App\Mail\MailtrapExample;
use Illuminate\Support\Facades\Mail;

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

Route::get('/', function () {
    return redirect("https://splitt.xyz");
});

Route::post('login', function (Request $request) {
    $credentials = $request->only('email', 'password');
    if (Auth::guard('web')->attempt($credentials)) {
        $user = Auth::guard('web')->user();
        return $user->makeVisible('api_token');
    } else {
        abort(403, "Incorrect credentials.");
    }
});

Route::post('group_members', 'GroupMemberController@create');
Route::get('group_members/verification/{token}', 'GroupMemberController@verify');

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('me', 'GroupMemberController@me');
    Route::delete('group_member/{id}', 'GroupMemberController@delete');

    Route::post('groups', 'GroupController@create');
    Route::get('groups/{id}', 'GroupController@get');
    Route::put('groups/{id}', 'GroupController@update');

    Route::get('groupsearch', 'GroupController@search');

    Route::post('groups/{id}/transactions', 'TransactionController@create');

    Route::get('groups/{id}/debts', 'DebtController@get');
    Route::put('groups/{id}/debts', 'DebtController@update');
});






Route::get('/send-mail', function () {
    Mail::to('newuser@example.com')->send(new MailtrapExample());
    return 'A message has been sent to Mailtrap!';
});

Route::get('/mailable', function () {
    return new MailtrapExample();
});
