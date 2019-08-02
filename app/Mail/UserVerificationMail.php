<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class UserVerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    protected $user;
    protected $validation;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($user, $validation)
    {
        $this->user = $user;
        $this->validation = $validation;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from('no-reply@splitt.xyz', 'Splitt')
            ->subject('Validate your Splitt account')
            ->markdown('mails.userverification')
            ->with([
                'name' => $this->user->name,
                'link' => action('GroupMemberController@verify', [$this->validation->id])
            ]);
    }
}
