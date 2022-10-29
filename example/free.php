<?php
/* Invoking the libraries: */
include "library/Driver.php";
include "library/user.php";

/* Calling the constructor: */
$driver = new Driver("library/config_free.json");

/* calling master page */
include "base.php";

?>
