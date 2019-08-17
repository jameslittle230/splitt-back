<h1>Splitt - Activate your Account</h1>
<p>Welcome, <b>{{$user->email}}</b>!</p>
<hr>

<form action="" method="POST">
@csrf
<p><label for="name">Name: </label><input type="text" id="name" name="name"></p>
<p>You must reset your password to continue.<br>
<label for="password">Password: </label><input type="password" id="password" name="password"><br>
<label for="password-c">Confirm: </label><input type="password" id="password-c" name="password-c"></p>
<button type="submit">Activate</button>
</form>