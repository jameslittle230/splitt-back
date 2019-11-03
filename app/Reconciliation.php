<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reconciliation extends Model
{
    use UsesUuid;
    use SoftDeletes;

    protected $with = ['splits'];

    public function splits()
    {
        return $this->hasMany('App\Split', 'reconciliation');
    }
}
