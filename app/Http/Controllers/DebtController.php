<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\GroupMember;
use App\Group;
use App\Reconciliation;
use App\Event;

class DebtController extends Controller
{
    public function get($group_id)
    {
        $group = Group::with('members')
            ->with('transactions')
            ->findOrFail($group_id);
        $me = request()->user();

        // Ensure request user is in group
        if (!$group->members()->get()->contains($me)) {
            abort(403);
        }

        // All unreconciled splits in the group
        $splits = $group
            ->transactions()->get()
            ->map(function ($txn) {
                return $txn->splits()->with('transaction')->get();
            })->collapse()
            ->filter(function ($split) {
                return $split->reconciliation == null;
            });

        return $group
            ->members()->get()
            ->filter(function ($member) use ($me) {
                return !$member->is($me);
            })
            ->transform(function ($member) use ($splits, $me) {

                // The splits I created (txn->creator is me) that $member owes me money for
                $splitsCreated = $splits
                    ->filter(function ($split) use ($me, $member) {
                        return $split
                            ->transaction()->with('creator')->get()[0]
                            ->creator()->get()[0]->is($me)
                            && $split->debtor()->get()[0]->is($member);
                    })
                    ->transform(function ($split) {
                        $altered_date = $split->transaction()->first()->altered_date;
                        $created_at = $split->transaction()->first()->created_at;
                        $split->date = $altered_date ? $altered_date : $created_at;
                        return $split;
                    })
                    ->sortByDesc('date');

                $splitsCreatedTotal = $splitsCreated
                    ->map(function ($split) {
                        return $split->amount;
                    })
                    ->sum();

                $splitsOwed = $splits
                    ->filter(function ($split) use ($me, $member) {
                        return $split
                            ->transaction()->with('creator')->get()[0]
                            ->creator()->get()[0]->is($member)
                            && $split->debtor()->get()[0]->is($me);
                    })
                    ->transform(function ($split) {
                        $altered_date = $split->transaction()->first()->altered_date;
                        $created_at = $split->transaction()->first()->created_at;
                        $split->date = $altered_date ? $altered_date : $created_at;
                        return $split;
                    })
                    ->sortByDesc('date');

                $splitsOwedTotal = $splitsOwed
                    ->map(function ($split) {
                        return $split->amount;
                    })
                    ->sum();

                return collect([
                    'member' => $member,

                    // Created: Txns that you created that $member owes you
                    // money for (money that you have coming in)
                    'createdTotal' => $splitsCreatedTotal,
                    'created' => $splitsCreated->values()->all(),

                    // Owed: Txns that $member created that you owe money for
                    // (money that you have going out)
                    'owedTotal' => $splitsOwedTotal,
                    'owed' => $splitsOwed->values()->all(),

                    'net' => $splitsCreatedTotal - $splitsOwedTotal,
                ]);
            });
    }

    public function update($group_id)
    {
        $group = Group::with('members')
            ->with('transactions')
            ->findOrFail($group_id);
        $me = request()->user();

        // Ensure request user is in group
        if (!$group->members()->get()->contains($me)) {
            abort(403);
        }

        $reconciled_members = request()->input(['reconciled_members']);

        $splits = $group
            ->transactions()->get()
            ->map(function ($txn) {
                return $txn->splits()->with('transaction')->get();
            })->collapse()
            ->filter(function ($split) {
                return $split->reconciliation == null;
            });


        return collect($reconciled_members)
            ->transform(function ($member_id) use ($splits, $me, $group_id) {
                $member = GroupMember::findOrFail($member_id);
                $reconciliation = Reconciliation::create();

                $event = new Event();
                $event->fill([
                    'group_id' => $group_id,
                    'subject' => $me->id,
                    'verb' => 'createdReconciliation',
                    'object' => $reconciliation->id,
                ]);
                $event->save();


                return $splits
                    ->filter(function ($split) use ($me, $member) {
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
                    ->map(function ($split) use ($reconciliation) {
                        $split->reconciliation = $reconciliation->id;
                        $split->save();
                        return $split;
                    });
            });
    }
}
