# Driver

It is a PHP library with the purpose of managing navigation routes from the URL using a pre-defined configuration structure and a constructor object.

By adjusting the configuration structure, it is possible to create free routes (without password) or restricted routes (with password); set maximum time between navigation; and add trigger for changing routes and checking authentication and access.

The page to be displayed is defined through an identifier contained in the URL that will establish the route to the target file, without displaying its path.

Authentication, access checking and route redirection are external functions, defined by the developer, which are called at certain moments of navigation that will subsidize the decision on the route to be taken.

It is up to the developer to establish security regarding access to files and application data, it is up to the library only to indicate the route to be taken as configured.

The tool is activated through the object named Driver.

## Constructor

The constructor is defined as follows:

```php
Driver($config)
```

The `config` argument contains the configuration data for the navigation. This data can be in the form of an array or contained in a JSON file. Therefore, accepted argument types are a list of data (array) or the address of a JSON file (string).

Below are configuration examples in array and JSON formats:

**JSON**

```json
{
	"CHECK": true,
	"HOME":  "content/home.php",
	"ID": {
		"config": "content/config.php",
		"debug":  "content/debug.php",
		"only1":  "content/only1.php",
		"only2":  "content/only2.php"
	},
	"LOG": {
		"GATEWAY": "content/login.php",
		"DATA":    ["usr", "pwd"],
		"LOGIN":   "credentialChecker",
		"ALLOW":   "accessChecker",
		"LOAD":    "loadChecker",
		"TIME":    180
	}
}
```

**Array**

```php

$config = array(
	"CHECK" => true,
	"HOME"  => "content/home.php",
	"ID"    => array(
		"config" => "content/config.php",
		"debug"  => "content/debug.php",
		"only1"  => "content/only1.php",
		"only2"  => "content/only2.php"
	),
	"LOG"   => array(
		"GATEWAY" => "content/login.php",
		"DATA"    => ["usr", "pwd"],
		"LOGIN"   => "credentialChecker",
		"ALLOW"   => "accessChecker",
		"LOAD"    => "loadChecker",
		"TIME"    => 180
	)
);
```

## Configuration Data

Configuration data has the following properties:

|Key|Subkey|Type|Optional|Description|
|:.:|:----:|:--:|:------:|:----------|
|CHECK|-|Boolean|Yes|Indicates whether the tool should carry out a previous data check.|
|HOME|-|String|No|Defines the path of the application's main page.|
|ID|-|Array/Object|No|Contains the list of paths to application files defined from identifiers.|
|LOG|-|Array/Obeject|Yes|Informs if the application will require authentication.|
|LOG|GATEWAY|String|No|Sets the authentication page path.|
|LOG|DATA|Array|No|Informs the list of data that will be submitted in authentication.|
|LOG|LOGIN|String|No|Name of the function that will receive the authentication data and return the result.|
|LOG|ALLOW|String|Yes|Name of the function that will check the user's access to the given route.|
|LOG|LOAD|String|Yes|Route redirector function name.|
|LOG|TIME|Integer|Yes|Information in seconds about the maximum time allowed between navigations.|

Optional keys, when unnecessary, must be set to null.

### CHECK











### GATEWAY

|Key|Type|Required|Default|Description|
|:--:|:--:|:------:|:-----:|:----------|
|GATEWAY|Boolean|No|False|Defines whether the application will require user authentication.|

This key will define the need to check the user's authentication for browsing the application pages.

Its value will define the obligation to inform or not other keys or subkeys.

If `force` is set to true, `GATEWAY` must be informed.

### TARGET

|Key|Type|Required|Default|Description|
|:-:|:--:|:------:|:-----:|:----------|
|TARGET|Array|Yes|None|Defines the identifiers and their respective pages to be accessed during navigation.|

Each page will contain an identifier and each identifier will be associated with a file containing the content to be displayed.

Navigation will take place through the URL, using data from the GET method (QUERY), which will identify the page to be accessed from the _id_ key which must contain the identifier of the corresponding file.

Below is an example of configuring the `TARGET` key with some random identifiers:

```php
$config = array(
	...
	"TARGET" => array(
		...
		"page1" => "content/file1.html",
		"page2" => "content/file2.html",
		...
	),
	...
);
```

File paths must be string and be a valid address.

If you want to create a link that directs to _file1.html_, proceed as follows:

```html
<a href="?id=page1">Page 1</a>
```

When clicking on the link above, the URL will contain the value of type _http://mypage/?id=page1_. In this situation, **Driver** will understand that the path to be accessed is from _file1.html_. Likewise, if the URL is _http://mypage/?id=page2_, the path to be accessed will be from _file2.html_.

There are three special subkeys that need to be defined: `HOME`, `LOGIN` and `LOGOUT`.

|Subkey|Type|Required|Default|Description|
|:----:|:--:|:------:|:-----:|:----------|
|HOME|String|Yes|None|Sets the main page path.|
|LOGIN|String|No/Yes|HOME|Sets the user authentication page path.|
|LOGOUT|String|No|LOGIN|Sets the exit page path.|

If the `force` argument is true, the three subkeys are mandatory information, regardless of whether or not the application will require user authentication.

`HOME` is always required. Whenever there is an attempt to access an undefined _id_, the application will redirect to the main page.

`LOGIN` is required if the system requires authentication. Otherwise, it must be equal to `HOME`.

`LOGOUT` is not mandatory. If the system requires authentication, a special page can be created to inform the end of the session or it must be the same as `LOGIN`. Otherwise, it must be equal to `HOME`.

### LOG

|Key|Type|Required|Default|Description|
|:-:|:--:|:------:|:-----:|:----------|
|LOG|Array|Yes/No|Empty Array|Defines which keys will be sent for authentication.|

If the application requires authentication, `LOG` is mandatory. If `force` is true, it is also mandatory, although not relevant.

Authentication is checked by the POST method, and `LOG` must be an array whose items will correspond to the names of the form elements used by the user to authenticate.

Below is an example of an authentication page and the corresponding value of `LOG`:

```html
<form method="post" action="?" >
	<label for="usr">User identifier:</label>
	<input type="text" name="usr" id="usr" required="" autofocus="" autocomplete="off" />
	<label for="pwd">Password:</label>
	<input type="password" name="pwd" id="pwd" required="" autofocus="" autocomplete="off" />
	<button type="submit">Go</button>
</form>
```

```php
$config = array(
	...
	"LOG" => array("usr", "pwd"),
	...
);
```

### AUTH

|Key|Type|Required|Default|Description|
|:-:|:--:|:------:|:-----:|:----------|
|AUTH|String|Yes/No|Null|Defines the name of the function that will check user data for authentication.|

If the `force` argument is true, the information is mandatory. If the system requires authentication, the function name must be informed, otherwise it can be set to `null`, as it will be irrelevant.

The function that will authenticate will receive the collected POST data. If the permission is denied, the function will return `null`, otherwise it must return an array with the authenticated user's data. __Driver__ will not check user data, an external function is required.

Below is an example of an authenticator function:

```php
function credentialChecker($post) {
	/* Establish the form of checking according to convenience */
	return $check === true ? $dataUser : null;
}

$config = array(
	...
	"AUTH" => "credentialChecker",
	...
);
```

### CHECK

|Key|Type|Required|Default|Description|
|:-:|:--:|:------:|:-----:|:----------|
|CHECK|String|Yes/No|Null|Defines the name of the role that will check the user's access permission to the page.|

If the `force` argument is true, the information is mandatory. If the system requires authentication, the function name can be informed if you want to perform such verification, otherwise it can be set to `null`.

The function that will check the user's access to the page to be accessed will receive the user's data, received during authentication, the page id and the file path. If the permission is denied, the function must return false, otherwise true. __Driver__ will not check user access, an external function is required.

Below is an example of an authenticator function:

```php
function accessChecker($user, &id, &path) {
	/* Establish the form of checking according to convenience */
	return $access === true ? true : false;
}

$config = array(
	...
	"CHECK" => "accessChecker",
	...
);
```

If the identifier is `HOME`, `LOGIN` or `LOGOUT`, access will not be verified.


### TIMEOUT

|Key|Type|Required|Default|Description|
|:-:|:--:|:------:|:-----:|:----------|
|TIMEOUT|Integer|No|Null|Defines the maximum time, in seconds, between requests.|

If the `force` argument is true, the information is mandatory. If the system requires authentication, a maximum time allowed between requests can be defined, otherwise it can be defined as `null`. If the maximum time is exceeded, the session will be terminated.

## JSON x ARRAY

Below are two configuration examples, the first in the form of an array in PHP and the other in JSON format.

```php
$config = array(
	"GATEWAY" => true,
	"TARGET" => array(
		"HOME"   => "system/home.html",
		"LOGIN"  => "system/login.html",
		"LOGOUT" => "system/logout.html",
		"page1"  => "content/file1.html",
		"page2"  => "content/file2.html",
		"page3"  => "content/file3.html"
	),
	"LOG"     => array("usr", "pwd"),
	"AUTH"    => "credentialChecker",
	"CHECK"   => "accessChecker",
	"TIMEOUT" => 180
);
```

```json
{
	"GATEWAY": true,
	"TARGET": {
		"HOME":   "system/home.html",
		"LOGIN":  "system/login.html",
		"LOGOUT": "system/logout.html",
		"page1":  "content/file1.html",
		"page2":  "content/file2.html",
		"page3":  "content/file3.html"
	},
	"LOG":     ["usr", "pwd"],
	"AUTH":    "credentialChecker",
	"CHECK":   "accessChecker",
	"TIMEOUT": 180
}
```

## Methods

|Method|Returns|Argument|Default|Description|
|:----:|:-----:|:------:|:-----:|:----------|
|path|String|None|None|Returns the file path defined by the **Driver** during the request.|
|status|Integer/String|Boolean|False|Returns the status of the behavior defined by the **Driver** during the request.|
|debug|Array|Boolean|False|Returns an array with characteristics defined/considered by the **Driver**|
|version|String|None|None|Returns the library version.|

### path

```php
path()
```
The method has no argument.

### status

```php
status($text)
```

The method returns the following identifiers:

|Numerical Value|Text Value|
|:-------------:|:---------|
|0|SESSION STARTED|
|1|AUTHENTICATION REQUIRED|
|2|AUTHENTICATION FAILED|
|3|SUCCESSFULLY AUTHENTICATED|
|4|PERMITTED ACCESS|
|5|ACCESS DENIED|
|6|PAGE NOT FOUND|
|7|SESSION CLOSED|
|8|SESSION EXPIRED|

The method has an optional argument which, if true, will return the textual value in place of the numeric value.

### version

```php
version()
```
The method has no argument.

### debug

```php
debug($print)
```

The method has an optional argument which, if true, will print to the screen the data it returns.

## Session

The PHP session will be started by the **Driver** constructor. If you want to save some information in the `$_SESSION` variable, you can do it after the constructor.

The following identifiers are for the private use of `Driver`, and their manipulation is not recommended:


|ID|Description|
|:-:|:----------|
|&#95;&#95;USER&#95;&#95;|Logs authenticated user data.|
|&#95;&#95;TIME&#95;&#95;|Records the authentication time (`YYYY-MM-DD HH:MM:SS`).|
|&#95;&#95;INIT&#95;&#95;|Records the time of the request (in seconds).|
|&#95;&#95;HASH&#95;&#95;|Registers the session identifier.|

## Security

**Driver** does not guarantee the security of the data contained in the server, this task is up to the programmer (images may reveal secrets). Your concern lies in directing the page according to the configuration passed to the builder at each request.

### Basic Usage

The example below exemplifies the basic use of the library on a web page:

```php
<?php

/* Invoking the library: */
include "library/Driver.php";

/* Calling the constructor: */
$driver = new Driver("system/config.json");

/* Displaying the file defined by the object: */
include $driver->path();

?>
```

## Versioning

Versioning is defined by three integers separated by dots with the following definitions:

|Level|Definition|Description|
|:---:|:--------:|:----------|
|1|Compatibility|It is increased when the library loses compatibility with the previous version.|
|2|Innovation|Is increased when the library gains new tools.|
|3|Maintenance|Is increased when the library undergoes corrections or enhancements.|

### Versions

Version|Description|
|:----:|:----------|
|v1.0.0|Initial release.|


