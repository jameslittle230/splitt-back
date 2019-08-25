<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Reconciliation extends Model
{
    use UsesUuid;

    protected $with = ['splits'];

    public function splits()
    {
        return $this->hasMany('App\Split', 'reconciliation');
    }
}
