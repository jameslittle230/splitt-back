<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Group;
use App\GroupMember;

class GroupController extends Controller
{
    public function create()
    {
        if(request('name') == "") {
            abort(400, "Group name can't be empty.");
        }

        if(!preg_match("/^(?>[a-z]|[0-9]|-)+$/m", request('name'))) {
            abort(400, "Invalid group name. Must contain only lower case letters, dashes, and numbers.");
        }

        if(!is_array(request('members'))) {
            abort(400, "Members list is not a list.");
        }

        if(sizeof(request('members')) < 1) {
            abort(400, "Members list is too short.");
        }

        $group = Group::create(request()->all());

        $user = request()->user();
        $group->members()->save($user);
        $nonMemberEmails = [];
        foreach (request('members') as $email) {
            $member = GroupMember::where('email', $email)->with('groups')->first();
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
        $group = Group::with('members')->findOrFail($group->id);
        return collect([
            'group' => $group,
            'nonMemberEmails' => $nonMemberEmails,
        ]);
    }

    public function get($id)
    {
        $group = Group::with('members')
            ->with('transactions')
            ->findOrFail($id);
        
        $user = request()->user();

        if ($group->members()->get()->contains($user)) {
            return $group;
        } else {
            abort(403);
        }
    }

    public function update($id)
    {
        $group = Group::with('members')->findOrFail($id);

        $nonMemberEmails = [];
        foreach (request()->members as $email) {
            $member = GroupMember::where('email', $email)->with('groups')->first();
            if ($member) {
                if (!$member->groups()->get()->contains($group)) {
                    $group->members()->save($member);
                }
            } else {
                $nonMemberEmails[] = $email;
            }
        }
        $group->save();

        // I wish I didn't have to make another DB query here
        $group = Group::with('members')->findOrFail($group->id);
        return collect([
            'group' => $group,
            'nonMemberEmails' => $nonMemberEmails,
        ]);
    }

    /**
     * Requires that the request have a query key/value pair like
     * q=echinacea@6e29436e
     * where `echinacea` is the group name
     * and `6e29436e` is the first 8 characters of the UUID
     */
    public function search()
    {
        $query = request()->query('q');

        /**
         * ^: Beginning of line
         * \w: Any word character ( equal to [a-zA-Z0-9_] )
         * {3,}: Matches between 3 and unlimited times
         * @: The literal `@` character
         * [a-f0-9]: Any character in hex range
         * {8}: Matches exactly 8 times
         * $: End of line
         */
        if (!preg_match("/^\\w{3,}@[a-f0-9]{8}$/m", $query)) {
            abort(400, "Invalid join code.");
        }

        list($name, $uuidPrefix) = explode("@", $query);
        $groups = Group::where('name', 'like', $name)
            ->where('id', 'like', "$uuidPrefix%")
            ->get();

        if (sizeof($groups) != 1) {
            abort(400, "No group found.");
        }

        return $groups;
    }
}
