<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

class Event extends Model
{
    use UsesUuid;

    protected $fillable = ['group_id', 'subject', 'verb', 'object'];

    protected $morphs = [
        'createdTransaction' => [
            'subject' => 'App\GroupMember',
            'object' => 'App\Transaction',
        ],
        'addedGroupMember' => [
            'subject' => 'App\GroupMember',
            'object' => 'App\GroupMember',
        ],
        'createdReconciliation' => [
            'subject' => 'App\GroupMember',
            'object' => 'App\Reconciliation',
        ],
        'undidEvent' => [
            'subject' => 'App\GroupMember',
            'object' => 'App\Event',
        ]
    ];

    public function subject()
    {
        Relation::morphMap([
            'createdTransaction' => 'App\GroupMember',
            'addedGroupMember' => 'App\GroupMember',
            'createdReconciliation' => 'App\GroupMember',
            'undidEvent' => 'App\GroupMember',
        ]);

        return $this->morphTo('subject', 'verb', 'subject', 'id');
    }

    public function object()
    {
        Relation::morphMap([
            'createdTransaction' => 'App\Transaction',
            'addedGroupMember' => 'App\GroupMember',
            'createdReconciliation' => 'App\Reconciliation',
            'undidEvent' => 'App\Event',
        ]);

        return $this->morphTo('object', 'verb', 'object', 'id');
    }
}
