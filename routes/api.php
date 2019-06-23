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

Route::post('login', function(Request $request) {
    $credentials = $request->only('email', 'password');
    if (Auth::guard('web')->attempt($credentials)) {
        $user = Auth::guard('web')->user();
        $user->api_token = Str::random(60);
        $user->save();
        return $user->makeVisible('api_token');
    } else {
        abort(403);
    }
});

Route::post('group_members', function(Request $request) {
    return App\GroupMember::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($request->password),
        'api_token' => Str::random(60),
    ])->makeVisible('api_token');
});

Route::group(['middleware' => ['auth:api']], function() {
    Route::get('protected', function(Request $request) { return $request->user(); });
    
    Route::post('groups', function(Request $request) {
        $group = App\Group::create($request->all());
        
        $user = $request->user();
        $group->members()->save($user);
        // $group->members()->findOrNew($request->users);
        $group->save();
        
        // I wish I didn't have to make another DB query here
        return App\Group::with('members')->findOrFail($group->id);
    });

    Route::put('groups/{id}', function(Request $request) {
        $group = App\Group::with('members')->findOrFail($id);
        $user = $request->user();
        if ($group->members()->get()->contains($user)) {
            // make the changes & save the group
            return $group;
        } else {
            abort(403);
        }
    });

    Route::get('groups/{id}', function(Request $request, $id) {
        $group = App\Group::with('members')->findOrFail($id);
        $user = $request->user();
        if ($group->members()->get()->contains($user)) {
            return $group;
        } else {
            abort(403);
        }
    });

    // Route::post('reset_token', function(Request $request) {
    //     $user = $request->user();
    //     $user->api_token = Str::random(60);
    //     $user->save();
    //     return $user->makeVisible('api_token');
    // })->name('reset_token');

    // Route::get('groups', function() {
    //     return App\Group::all();
    // });
});