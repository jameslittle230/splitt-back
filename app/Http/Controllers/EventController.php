<?php

namespace App\Http\Controllers;

use App\Event;
use App\Group;
use App\Reconciliation;
use App\Split;
use App\Transaction;

class EventController extends Controller
{
    public function all($group_id)
    {
        $group = Group::findOrFail($group_id);
        $user = request()->user();
        if (!$group->members()->get()->contains($user)) {
            abort(403);
        }

        return Event::where('group_id', $group_id)
         ->with(['subject', 'object'])
         ->orderBy('created_at', 'desc')
         ->get();
    }

    public function undoableVerbs()
    {
        return ["createdTransaction", "createdReconciliation"];
    }

    public function undo($event_id)
    {
        $event = Event::findOrFail($event_id);
        $event_group = Group::findOrFail($event->group_id);
        $user = request()->user();
        if (!$event_group->members()->get()->contains($user)) {
            abort(403);
        }

        switch ($event->verb) {
            case "createdTransaction":
                $txn = Transaction::findOrFail($event->object);
                Split::where('transaction', $txn->id)->delete();
                $txn->delete();
                break;
            case "createdReconciliation":
                $reconciliation = Reconciliation::findOrFail($event->object);
                Split::where('reconciliation', $reconciliation->id)->update(['reconciliation' => null]);
                $reconciliation->delete();
                break;
            default:
                abort(403, "Event with verb $event->verb cannot be undone.");
        }

        $newEvent = new Event();
        $newEvent->fill([
        'group_id' => $event->group_id,
            'subject' => $user->id,
            'verb' => 'undidEvent',
            'object' => $event_id,
        ]);
        $newEvent->save();
    }
}
