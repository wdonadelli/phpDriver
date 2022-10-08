# Driver

It is a PHP library with the purpose of managing navigation routes from the URL using a pre-defined configuration structure and a constructor object.

By adjusting the configuration structure, it is possible to create free routes (without password) or restricted routes (with password); set maximum time between navigation; and add trigger for changing routes and checking authentication and access.

The page to be displayed is defined through an identifier contained in the URL that will establish the route to the target file, without displaying its path.

Authentication, access checking and route redirection are external functions, defined by the developer, which are called at certain moments of navigation that will subsidize the decision on the route to be taken.

It is up to the developer to establish security regarding access to files and application data, it is up to the library only to indicate the route to be taken as configured.

The tool is activated through the object named Driver.

## Links

- [Manual page](https://github.com/wdonadelli/phpDriver/wiki)
- <a href="https://wdonadelli.github.io/phpDriver/Driver.php" download >Download</a>
- <a href="https://wdonadelli.github.io/phpDriver/example.zip" download >Example</a>
