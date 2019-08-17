<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('group_members/verification/{token}', 'GroupMemberController@verify');
Route::match(['get', 'post'], 'group_members/activation/{user_id}/{password}', 'GroupMemberController@activate')->name('activation');

Route::get('/verified', function (Request $request) {
    return "Email verified!";
})->name('postEmailVerification');
