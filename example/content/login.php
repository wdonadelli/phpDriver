<?php
if (!isset($driver)) {
	header('Location: ../');
	exit;
}
?>

<main>


	<form method="post" class="wd-block-child wd-margin-xx-n" style="width: 75%; margin: auto" >

		<label for="usr">User identifier:</label>
		<input type="text" name="usr" id="usr" required="" autofocus="" autocomplete="off" />

		<label for="pwd">Password:</label>
		<input type="password" name="pwd" id="pwd" required="" autofocus="" autocomplete="off" />

		<button type="submit">Go</button>

	</form>


	<table width="75%" border="1" class="wd-margin-xx-v">
		<caption>Test user list.</caption>
		<thead>
			<tr><th>User</th><th>Password</th></tr>
		</thead>
		<tbody>
			<tr><td>user1</td><td>123456</td></tr>
			<tr><td>user2</td><td>654321</td></tr>
		</tbody>
	</table>


	<h3>Debug:</h3>
	<pre class="wd-code" style="overflow: auto;"><?php print_r($driver->debug()); ?></pre>


</main>
