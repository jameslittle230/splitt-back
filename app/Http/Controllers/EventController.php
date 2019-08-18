<?php

namespace App\Http\Controllers;

use App\Event;
use App\Group;
use App\GroupMember;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Relations\MorphTo;

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
         ->with(['subject', 'object'])->get();
    }
}
