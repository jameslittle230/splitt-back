<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Split;

class Reconciliation extends Model
{
    use UsesUuid;

    public function splits()
    {
        return $this->hasMany('Split', 'reconciliation');
    }
}
