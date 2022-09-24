<?php

/*============================================================================*/
/* User database (simple, it's just an example) */
$USERS = array(
	"user1"  => array (
		"pwd"  => "123456",
		"data" => array(
			"level" => array("log", "config", "debug", "only1"),
			"name"  => "User 1"
		)
	),
	"user2"  => array(
		"pwd"  => "654321",
		"data" => array(
			"level" => array("log", "config", "debug", "only2"),
			"name"  => "User 2"
		)
	)
);

/*============================================================================*/
/* Function to check credentials */
function credentialChecker($post) {

	$usr = $post["usr"];
	$psw = $post["pwd"];
	global $USERS;

	/* Failed authentication */
	if (!array_key_exists($usr, $USERS)) {return null;}
	if ($psw !== $USERS[$usr]["pwd"])    {return null;}
	/* Successful authentication */
	return $USERS[$usr]["data"];
}

/*============================================================================*/
/* Function to check access */
function accessChecker($usr, $id, $path) {

	/* Checks if the user has access to the id */
	return in_array($id, $usr["level"]) ?  true : false;
}

/*============================================================================*/
/* Function to check the need for route deviation */
function loadChecker($debug) {
	$length = count($debug["LOG"]) - 1;
	$status = $debug["LOG"][$length]["STATUS"];
	if ($status === 3) {return "content/hello.php";}
	if ($status === 7) {return "content/bye.php";}
	return null;
}


?>
