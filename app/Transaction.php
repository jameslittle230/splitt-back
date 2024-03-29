<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use UsesUuid;
    use SoftDeletes;

    protected $fillable = ['full_amount', 'description', 'long_description', 'altered_date'];

    public function creator()
    {
        return $this->belongsTo('App\GroupMember', 'creator');
    }

    public function group()
    {
        return $this->belongsTo('App\Group', 'group');
    }

    public function splits()
    {
        return $this->hasMany('App\Split', 'transaction');
    }
}
