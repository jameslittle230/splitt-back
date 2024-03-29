<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Split extends Model
{
    use UsesUuid;
    use SoftDeletes;

    protected $fillable = ['amount', 'percentage', 'debtor', 'reconciled'];

    public function transaction()
    {
        return $this->belongsTo('App\Transaction', 'transaction');
    }

    public function debtor()
    {
        return $this->belongsTo('App\GroupMember', 'debtor');
    }

    public function reconciliation()
    {
        return $this->belongsTo('App\Reconciliation', 'reconciliation');
    }
}
