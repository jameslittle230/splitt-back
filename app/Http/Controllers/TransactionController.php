<?php

namespace App\Http\Controllers;

use App\Event;
use Illuminate\Support\Carbon;

use App\Transaction;
use App\GroupMember;
use App\Group;

class TransactionController extends Controller
{
    public function create($group_id)
    {
        $group = Group::findOrFail($group_id);
        $user = request()->user();

        if (!request()->has(['full_amount', 'description', 'splits'])) {
            abort(403, "Request does not have required parameters.");
        }

        $txn = new Transaction();
        $txn->fill([
            'full_amount' => request()->full_amount,
            'description' => request()->description,
        ]);

        if (request()->altered_date) {
            $dt = Carbon::createFromFormat("Y-m-d\TH:i:s.v\Z", request()->altered_date)->floorDay();
            if (!$dt->isToday()) {
                $txn->fill(['altered_date' => $dt]);
            }
        }


        if (request()->long_description) {
            $txn->fill(['long_description' => request()->long_description]);
        }

        // It seems like the relationship stuff should deal with
        // extracting the IDs for me, instead of me having to
        // do it myself here?
        $txn->creator = $user->id;
        $txn->group = $group->id;

        $txn->save();

        $splits = collect(request()->splits)->map(function ($split) use ($txn, $user) {
            $debtor = GroupMember::where('email', $split["user"])->first();

            // You can't owe money towards yourself
            if ($debtor->is($user)) {
                return null;
            }

            return [
                'transaction' => $txn->id,
                'amount' => (int) ($split["amount"]),
                'percentage' => (float) ($split["percentage"]),
                'debtor' => $debtor->id,
            ];
        })->filter()->toArray();

        $txn->splits()->createMany($splits);

        $event = new Event();
        $event->fill([
            'group_id' => $group_id,
            'subject' => $user->id,
            'verb' => 'createdTransaction',
            'object' => $txn->id,
        ]);
        $event->save();

        return Transaction::with('splits')->findOrFail($txn->id);
    }
}
