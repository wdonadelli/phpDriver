<?php
if (!isset($driver)) {
	header('Location: ../');
	exit;
}
?>

<main>

	<header>
		<h3>Hello!</h3>
	</header>

	<section>
		<p>This page was displayed from the trigger defined in the <code>LOG =&gt; LOAD</code> key, it is not included in the configuration data.</p>

		<p><a href="?">Click here</a> to continue.</p>
	</section>

</main>
