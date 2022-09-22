<?php

/* Invoking the libraries: */
include "library/Driver.php";
include "library/user.php";

/* Calling the constructor: */
$driver = new Driver("library/config.json");

/* Getting the file path: */
$path = $driver->path();

//$driver->path();

/* Getting the status message and debug data */
$status = $driver->status(true);
$debug  = $driver->debug();
$driver->json(true);

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
		<style>
		*, *:before, *:after {box-sizing: border-box;}
		body {
			background-color: snow;
			font-size: 15px;
		}
		#login {
			margin: 100px auto;
			width: 50%
		}
		#login label, #login input, #login button {
			display: block;
			width: 100%;
			padding: 5px;
			text-align: center;
			font-size: 15px;
		}
		#login input, #login button {
			margin: 0 0 5px 0;
			background-color: silver;
			color: green;
		}
		#header, #header a {
			background-color: royalblue;
			color: white;
			padding: 5px;
			font-size: 1.2em
		}
		#status {
			color: purple;
			padding: 5px;
			background-color: yellow;
			margin: 10px;
			border: 1px solid;
			border-radius: 0.5em;
		}

		</style>
	</head>

	<body>

		<header id="header">
			<h3><a href="?" title="Home Page" >phpDriver</a></h3>
		</header>

		<section id="status">
			<samp>STATUS: <?php echo $status; ?></samp>
		</section>

<?php

/* Displaying the file defined by the object: */
include $path;

?>

	</body>
</html>
