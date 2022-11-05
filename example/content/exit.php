<?php
if (!isset($driver)) {
	header('Location: ../');
	exit;
}
?>

<main>

	<header>
		<h3>Bye!</h3>
	</header>

	<p><a href="?">Click here</a> to continue.</p>

	<pre class="wd-code" style="overflow: auto;"><?php print_r($driver->debug()); ?></pre>

</main>
