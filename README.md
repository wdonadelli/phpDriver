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
|:-:|:----:|:--:|:------:|:----------|
|[CHECK](#check)|-|Boolean|Yes|Indicates whether the tool should carry out a previous data check.|
|[HOME](#home)|-|String|No|Defines the path of the application's main page.|
|[ID](#id)|-|Array/Object|No|Contains the list of paths to application files defined from identifiers.|
|[LOG](#log)|-|Array/Object|Yes|Informs if the application will require authentication.|
|LOG|[GATEWAY](#log-gateway)|String|No|Sets the authentication page path.|
|LOG|[DATA](#log-data)|Array|No|Informs the list of data that will be submitted in authentication.|
|LOG|[LOGIN](#log-login)|String|No|Name of the function that will receive the authentication data and return the result.|
|LOG|[ALLOW](#log-allow)|String|Yes|Name of the function that will check the user's access to the given route.|
|LOG|[LOAD](#log-load)|String|Yes|Route redirector function name.|
|LOG|[TIME](#log-time)|Integer|Yes|Information in seconds about the maximum time allowed between navigations.|

Optional keys, when unnecessary, must be set to null.

### CHECK

By default, the tool will check the configuration data each time the builder is called.

When the CHECK attribute is set to false, configuration data checking will not be done by the constructor, saving processing. When true or not set, the check will be performed.

During the construction process, it is advisable to leave it on to examine any mistakes in the configuration, and it can be turned off later to speed up loading.

**When set to false, the other optional keys, if not used, must be set to** `null`.

### HOME

The HOME attribute will set the path to the main application file.

The route will point to the file defined in HOME when:

- the identifier is _HOME_;
- an identifier is not defined;
- an invalid identifier is defined;
- there is an attempt to access an unauthorized file;
- authentication succeeds, if required by the configuration; or when
- the identifier is _EXIT_, if authentication is not required.

### ID

The ID attribute will define the list of route identifiers and their relative paths to the destination files.

It is forbidden to define identifiers with the names _HOME_ and _EXIT_, as they are reserved.

The navigation route is defined by the URL, **using data from the GET (QUERY) method**, which will identify the destination page from the identifier associated with the _id_ key.

For example, if the file _content/page.html_ is associated with the identifier _myID_, you can create a navigation link for the mentioned route as follows:

```html
<a href="?id=myID">Target Name</a>
```

When clicking on the link above, the destination URL will point to an address similar to _http://mypage/?id=myID_. When analyzing the route, the tool will return, if allowed, the path to access the corresponding file.

### LOG

The LOG attribute will define the need for authentication to perform the navigation.

If not defined, navigation will not require authentication, otherwise the parameters to allow access must be informed.

#### LOG-GATEWAY

The GATEWAY attribute will define the path of the file corresponding to the authentication page, where the user will inform his credentials for checking.

#### LOG-DATA

The DATA attribute will inform the list containing the data that the user will inform in the authentication procedure.

The data correspond to the names (strings) of the form fields that the user will submit through the authentication page defined in the [GATEWAY](#log-gateway) attribute. **The sending of data must be by the POST method.**

#### LOG-LOGIN

The LOGIN attribute must inform the name of the function that will receive the authentication data and will return the result of the success of the operation.

The function will need to contain an argument to receive the data informed by the user, which will occur by sending the PHP variable `$_POST`.

If the function returns null, the tool will understand that the authentication has failed, otherwise it will consider that the user has been successfully authenticated and will record this result to perform the route analysis.

As long as you are not authenticated, no route other than the one informed in [GATEWAY](#log-gateway) will be provided. After authentication, the route will be directed to [HOME](#home).

#### LOG-ALLOW

The ALLOW attribute must inform the name of the function that will analyze the access permission for each route, returning true, if allowed, or false, if not allowed.

The function will need to contain three arguments that will receive **user** data, informed by the function defined in the [LOGIN](#log-login) attribute, the route **identifier** and the corresponding **path**.

If the function returns false, the route will be directed to [HOME](#home).

#### LOG-LOAD

The LOAD attribute must inform the name of the function that will be triggered before returning the route and that will have the power to redirect it, either to a pre-defined path or to another file.

The function will need to contain an attribute that will receive the data returned by the [debug](#degub) method.

The function must return a string that will correspond to a valid identifier, constant in the [ID](#id) attribute, _HOME_, _EXIT_ or a valid path to a given file. Otherwise, the route returned will be the one defined before the function was fired.

**Here the redirection will be in charge of the function, allowing behavior that contradicts that defined by the tool.**

#### LOG-TIME

The TIME attribute must inform the time, in seconds, that each request can remain active.

When the time is extrapolated, when navigating to another route, the session will be terminated and a new authentication will be required.












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


