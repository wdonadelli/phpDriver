<?php

/* Invoking the libraries: */
include "library/Driver.php";
include "library/user.php";

/* Calling the constructor: */
$driver = new Driver("library/config.json");

/* Getting the file path: */
$path = $driver->path();

/* Getting the status message and debug data */
$tstatus = $driver->status(true);
$nstatus = $driver->status();

?>
<!DOCTYPE html>
<html class="wd">

	<head>
		<title> -- phpDriver -- </title>
		<meta charset="UTF-8"/>
		<meta name="viewport" content="width=device-width, initial-scale=1"/>
		<meta name="keywords" content="phpDriver" />
		<meta name="author" content="Willian Donadelli"/>
		<meta name="description" content="phpDriver" />
		<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
		<link  href="library/wd4.css" rel="stylesheet" />
		</style>
	</head>

	<body class="wd-flyer wd-bg-black">

		<header class="wd-bg-blue">
			<h1>phpDriver</h1>
<?php
if ($nstatus > 2 && $nstatus < 7) {
	echo "
		<nav class=\"wd-bg-grey\">
			<a href=\"?id=HOME\">Home</a>
			<a href=\"?id=debug\">Debug</a>
			<a href=\"?id=config\">Config</a>
			<a href=\"?id=?\">Unknown</a>
			<a href=\"?id=only1\">Only 1</a>
			<a href=\"?id=only2\">Only 2</a>
			<a href=\"?id=EXIT\" class=\"wd-nav-right wd-icon-exit\"> Exit</a>
		</nav>
	";
}
?>
		</header>
		<section class="wd-bg-yellow wd-border wd-radius wd-padd wd-size-smaller">
			<samp>STATUS: <?php echo "{$nstatus} ({$tstatus})"; ?></samp>
		</section>

<?php

/* Displaying the file defined by the object: */
include $path;

?>

	</body>
</html>
