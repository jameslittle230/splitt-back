<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use UsesUuid;

    protected $fillable = ['full_amount', 'description'];

    public function creator() {
        return $this->belongsTo('App\GroupMember', 'creator');
    }

    public function group() {
        return $this->belongsTo('App\Group', 'group');
    }

    public function splits() {
        return $this->hasMany('App\Split', 'transaction');
    }
}
