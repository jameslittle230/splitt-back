<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Split extends Model
{
    use UsesUuid;

    protected $fillable = ['amount', 'percentage', 'debtor'];

    public function transaction() {
        return $this->belongsTo('App\Transaction', 'transaction');
    }

    public function debtor() {
        return $this->belongsTo('App\GroupMember', 'debtor');
    }
}
