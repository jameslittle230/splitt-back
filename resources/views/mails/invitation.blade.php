@component('mail::message')
Hi, **{{$email}}**!  {{$inviter}} wants you to join their group {{$groupname}} on Splitt.

Click this link to continue:

<a href="{{$link}}">{{$link}}</a>

James
@endcomponent