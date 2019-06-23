<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use UsesUuid;

    protected $fillable = ['name'];

    public function members() {
        return $this->belongsToMany('App\GroupMember', 'group_groupmember', 'group', 'groupmember');
    }

    public function transactions() {
        return $this->hasMany('App\Transaction', 'group');
    }
}
