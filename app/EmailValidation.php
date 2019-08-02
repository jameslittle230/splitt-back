<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EmailValidation extends Model
{
    use UsesUuid;

    public function groupMember()
    {
        return $this->belongsTo('App\GroupMember', 'group_member');
    }
}