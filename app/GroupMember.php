<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

class GroupMember extends Authenticatable
{
    use Notifiable;
    use UsesUuid;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'shortname', 'email', 'password', 'api_token', 'timezone', 'self_created'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'api_token'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function groups()
    {
        return $this
            ->belongsToMany('App\Group', 'group_groupmember', 'groupmember', 'group')
            ->withTimestamps();
    }

    public function transactions()
    {
        return $this->hasMany('App\Transaction', 'creator');
    }

    public function splits()
    {
        return $this->hasMany('App\Split', 'debtor');
    }

    public function validations()
    {
        return $this->hasMany('App\EmailValidation', 'group_member');
    }
}
