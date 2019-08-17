<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;

use App\Mail\UserVerificationMail;
use App\EmailValidation;
use App\GroupMember;

use Hash;
use Str;

class GroupMemberController extends Controller
{
    public function create()
    {
        if (strlen(request('password')) < 8) {
            abort(400, 'Password is too short. Must be 8 or more characters.');
        }

        if (strlen(request('name')) < 1) {
            abort(400, 'Name must be longer than 0 characters.');
        }

        if (GroupMember::where('email', request('email'))->first()) {
            abort(400, 'A user with this email address already exists.');
        }

        $newGroupMember = new GroupMember();
        $newGroupMember->fill([
            'name' => request('name'),
            'email' => request('email'),
            'password' => Hash::make(request('password')),
            'api_token' => Str::random(60),
        ]);

        if (request('shortname')) {
            $newGroupMember->fill(['shortname' => request('shortname')]);
        }

        if (request('timezone')) {
            $newGroupMember->fill(['shortname' => request('timezone')]);
        }

        $newGroupMember->save();

        $newEmailValidation = new EmailValidation();
        $newGroupMember->validations()->save($newEmailValidation);

        Mail::to(request('email'))->send(new UserVerificationMail($newGroupMember, $newEmailValidation));

        return $newGroupMember->makeVisible('api_token');
    }

    public function verify($verification_id)
    {
        $verification = EmailValidation::findOrFail($verification_id);
        $groupMember = $verification->groupMember()->first();

        if ($groupMember->email_verified_at) {
            abort("410", "Verification link expired.");
        }

        if (!Carbon::now()->isBetween($verification->created_at, $verification->created_at->addHours(48))) {
            abort("410", "Verification link expired.");
        }

        return redirect()->route('postEmailVerification');
    }

    public function me()
    {
        return GroupMember::with('groups')->find(request()->user())->first();
    }

    public function delete($id)
    {
        return response("Not implemented yet", 404);
    }
}
