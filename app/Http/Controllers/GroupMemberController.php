<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\GroupMember;
use Hash;
use Str;

class GroupMemberController extends Controller
{
    public function create() {
        if(strlen(request('password')) < 8) {
            abort(400, 'Password is too short. Must be 8 or more characters.');
        }
    
        if(strlen(request('name')) < 1) {
            abort(400, 'Name must be longer than 0 characters.');
        }
        
        if(GroupMember::where('email', request('email'))->first()) {
            abort(400, 'A user with this email address already exists.');
        }
    
        return GroupMember::create([
            'name' => request('name'),
            'email' => request('email'),
            'password' => Hash::make(request('password')),
            'api_token' => Str::random(60),
        ])->makeVisible('api_token');
    }
    
    public function me()
    {
        return GroupMember::with('groups')->find(request()->user())->first();
    }
}
