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
        // $user->api_token = Str::random(60);
        // $user->save();
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
            $member = App\GroupMember::where('email', $email)->with('groups')->first();
            if ($member) {
                if(!$member->groups()->get()->contains($group)) {
                    $group->members()->save($member);
                }
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

        if(!$request->has(['full_amount', 'description', 'splits'])) {
            abort(403);
        }

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

        $splits = collect($request->splits)->map(function ($split) use ($txn, $user) {
            $debtor = App\GroupMember::where('email', $split["user"])->first();

            // You can't owe money towards yourself
            if ($debtor->is($user)) {
                return null;
            }

            return [
                'transaction' => $txn->id,
                'amount' => (int)($split["amount"]),
                'percentage' => (int)($split["percentage"]),
                'debtor' => $debtor->id,
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

    Route::put('groups/{id}', function (Request $request, $id) {
        $group = App\Group::with('members')->findOrFail($id);
        
        $nonMemberEmails = [];
        foreach ($request->members as $email) {
            $member = App\GroupMember::where('email', $email)->with('groups')->first();
            if ($member) {
                if(!$member->groups()->get()->contains($group)) {
                    $group->members()->save($member);
                }
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

    /**
     * Requires that the request have a query key/value pair like
     * q=echinacea@6e29436e
     * where `echinacea` is the group name
     * and `6e29436e` is the first 8 characters of the UUID
     */
    Route::get('groupsearch', function (Request $request) {
        $query = $request->query('q');

        /**
         * ^: Beginning of line
         * \w: Any word character ( equal to [a-zA-Z0-9_] )
         * {3,}: Matches between 3 and unlimited times
         * @: The literal `@` character
         * [a-f0-9]: Any character in hex range
         * {8}: Matches exactly 8 times
         * $: End of line
         */
        if(!preg_match("/^\\w{3,}@[a-f0-9]{8}$/m", $query)) {
            abort(400);
        }

        list($name, $uuidPrefix) = explode("@", $query);
        $groups = App\Group::where('name', 'like', $name)
            ->where('id', 'like', "$uuidPrefix%")
            ->get();
        return $groups;
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

    Route::get('groups/{id}/debts', function(Request $request, $id) {
        $group = App\Group::with('members')
            ->with('transactions')
            ->findOrFail($id);
        $me = $request->user();
        
        // Ensure request user is in group
        if (!$group->members()->get()->contains($me)) {
            abort(403);
        }

        // All unreconciled splits in the group
        $splits = $group
            ->transactions()->get()
            ->map(function($txn) {
                return $txn->splits()->with('transaction')->get();
            })->collapse()
            ->filter(function($split) {
                return $split->reconciled == 0;
            });

        return $group
            ->members()->get()
            ->filter(function($member) use ($me) {
                return !$member->is($me);
            })
            ->transform(function($member) use ($splits, $me) {

                // The splits I created (txn->creator is me) that $member owes me money for
                $splitsCreated = $splits
                    ->filter(function($split) use ($me, $member) {
                        return $split
                            ->transaction()->with('creator')->get()[0]
                            ->creator()->get()[0]
                            ->is($me)
                        && $split->debtor()->get()[0]->is($member);
                    });
                
                $splitsCreatedTotal = $splitsCreated
                    ->map(function($split) { return $split->amount; })
                    ->sum();
                
                $splitsOwed = $splits
                    ->filter(function($split) use ($me, $member) {
                        return $split
                            ->transaction()->with('creator')->get()[0]
                            ->creator()->get()[0]
                            ->is($member)
                        && $split->debtor()->get()[0]->is($me);
                    });
                
                $splitsOwedTotal = $splitsOwed
                    ->map(function($split) { return $split->amount; })
                    ->sum();

                return collect([
                    'member' => $member,

                    // Created: Txns that you created that $member owes you
                    // money for (money that you have coming in)
                    'createdTotal' => $splitsCreatedTotal,
                    'created' => $splitsCreated,

                    // Owed: Txns that $member created that you owe money for
                    // (money that you have going out)
                    'owedTotal' => $splitsOwedTotal,
                    'owed' => $splitsOwed,

                    'net' => $splitsCreatedTotal - $splitsOwedTotal,
                ]);
            });
    });

    Route::put('groups/{id}/debts', function(Request $request, $id) {
        $group = App\Group::with('members')
            ->with('transactions')
            ->findOrFail($id);
        $me = $request->user();
        
        // Ensure request user is in group
        if (!$group->members()->get()->contains($me)) {
            abort(403);
        }

        $reconciled = $request->input(['reconciled']);

        $splits = $group
            ->transactions()->get()
            ->map(function($txn) {
                return $txn->splits()->with('transaction')->get();
            })->collapse()
            ->filter(function($split) {
                return $split->reconciled == 0;
            });

        return collect($reconciled)
            ->transform(function($shouldReconcile, $member_id) use ($splits, $me) {
                $member = App\GroupMember::findOrFail($member_id);

                return $splits
                    ->filter(function($split) use ($me, $member) {
                        return ($split
                            ->transaction()->with('creator')->get()[0]
                            ->creator()->get()[0]
                            ->is($me)
                        && $split->debtor()->get()[0]->is($member)) 
                        || ($split
                            ->transaction()->with('creator')->get()[0]
                            ->creator()->get()[0]
                            ->is($member)
                        && $split->debtor()->get()[0]->is($me));
                    })
                    ->map(function($split) use ($shouldReconcile) {
                        $split->reconciled = $shouldReconcile;
                        $split->save();
                        return $split;
                    });
            });
    });
});
