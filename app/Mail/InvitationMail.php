<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class InvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    protected $email;
    protected $group;
    protected $invitingUser;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($newUser, $password, $group, $invitingUser)
    {
        $this->newUser = $newUser;
        $this->password = $password;
        $this->group = $group;
        $this->invitingUser = $invitingUser;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject("{$this->invitingUser->name} wants you to join Splitt.")
            ->markdown('mails.invitation')
            ->with([
                'email' => $this->newUser->email,
                'groupname' => $this->group->name,
                'inviter' => $this->invitingUser->name,
                'link' => route('activation', ['user_id' => $this->newUser->id, 'password' => $this->password])
            ]);
    }
}
