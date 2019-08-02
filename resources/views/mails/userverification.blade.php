@component('mail::message')
Hi, **{{$name}}**!  {{-- use double space for line break --}}
Welcome to Splitt. We're excited to have you.

To get you up and running, please verify your account with the following button:

@component('mail::button', ['url' => $link])
Verify my email address
@endcomponent

If that button doesn't work, try clicking this link or copy-pasting this address into your browser:  
<a href="{{$link}}">{{$link}}</a>

This verification link will expire in 48 hours — best to get this over with soon.

Thanks again.  
James
@endcomponent