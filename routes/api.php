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
        $user->api_token = Str::random(60);
        $user->save();
        return $user->makeVisible('api_token');
    } else {
        abort(403);
    }
});

Route::post('group_members', function (Request $request) {
    return App\GroupMember::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($request->password),
        'api_token' => Str::random(60),
    ])->makeVisible('api_token');
});

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('protected', function (Request $request) {
        return $request->user();
    });

    Route::get('me', function (Request $request) {
        return App\GroupMember::with('groups')->find($request->user())->first();
    });

    Route::post('groups', function (Request $request) {
        $group = App\Group::create($request->all());

        $user = $request->user();
        $group->members()->save($user);
        $nonMemberEmails = [];
        foreach ($request->members as $email) {
            $member = App\GroupMember::where('email', $email)->first();
            if ($member) {
                $group->members()->save($member);
            } else {
                $nonMemberEmails[] = $email;
            }
        }
        $group->save();

        // I wish I didn't have to make another DB query here
        $group = App\Group::with('members')->findOrFail($group->id);
        return collect([
            'group' => $group,
            'nonMemberEmails' => $nonMemberEmails,
        ]);
    });

    Route::post('groups/{id}/transactions', function (Request $request, $id) {
        $group = App\Group::findOrFail($id);
        $user = $request->user();

        $txn = new App\Transaction();
        $txn->fill([
            'full_amount' => $request->full_amount,
            'description' => $request->description,
        ]);

        // It seems like the relationship stuff should deal with
        // extracting the IDs for me, instead of me having to
        // do it myself here?
        $txn->creator = $user->id;
        $txn->group = $group->id;

        $txn->save();

        $splits = $group->members()->get()->map(function ($member) use ($txn, $user, $request, $group) {
            if ($member->is($user)) {
                return null;
            }

            return [
                'transaction' => $txn->id,
                'amount' => (int)($request->full_amount) / ($group->members()->count() - 1),
                'percentage' => 100 / ($group->members()->count() - 1),
                'debtor' => $member->id,
            ];
        })->filter()->toArray();

        $txn->splits()->createMany($splits);

        // I wish I didn't have to make another DB query here
        return App\Transaction::with('splits')->findOrFail($txn->id);
    });

    Route::get('groups/{id}', function (Request $request, $id) {
        $group = App\Group::with('members')
            ->with('transactions')
            ->findOrFail($id);
        $user = $request->user();
        if ($group->members()->get()->contains($user)) {
            return $group;
        } else {
            abort(403);
        }
    });

    Route::put('splits/{id}', function (Request $request, $id) {
        $split = App\Split::findOrFail($id);
        if (!$split->debtor()->first()->is($request->user())) {
            abort(403);
        }

        $input = $request->only(['reconciled']);
        $split->reconciled = $input['reconciled'];
        $split->save();
        return $split;
    });
});
