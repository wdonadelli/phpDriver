<?php

/* Show errors */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/* Invoking the libraries: */
include "library/Driver.php";
include "library/user.php";

/* Calling the constructor: */
$driver = new Driver("library/config.json");

/* Getting the file path: */
$path = $driver->path();

/* Getting the status message and debug data */
$status = $driver->status(true);
$debug  = $driver->debug(true);

?>
<!DOCTYPE html>
<html>

	<head>
		<title> -- phpDriver Example -- </title>
		<meta charset="UTF-8"/>
		<meta name="viewport" content="width=device-width, initial-scale=1"/>
		<meta name="keywords" content="phpDriver" />
		<meta name="author" content="Willian Donadelli"/>
		<meta name="description" content="phpDriver" />
		<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
	</head>

	<body style="background-color: #f0f0f0;">

		<header style="color: royalblue;">
			<h3><a href="?" title="Click here to go to the home page." >phpDriver Example</a></h3>
		</header>

		<section style="color: red; padding: 5px; background-color: snow;">
			<samp>STATUS: <?php echo $status; ?></samp>
		</section>

<?php

/* Displaying the file defined by the object: */
include $path;

?>

	</body>
</html>
