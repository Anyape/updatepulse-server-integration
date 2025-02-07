# UpdatePulse Server Integration Examples
(Looking for the main UpdatePulse Server documentation page instead? [See here](https://github.com/anyape/updatepulse-server/blob/main/README.md))

___

## Repository content

To connect their packages and UpdatePulse Server, developers can find integration examples in this repository:
* **Dummy Plugin:** a folder `dummy-plugin` with a simple, empty plugin that includes the necessary code in the `dummy-plugin.php` main plugin file and the necessary libraries in a `lib` folder.
* **Dummy Theme:** a folder `dummy-theme` with a simple, empty child theme of Twenty Seventeen that includes the necessary code in the `functions.php` file and the necessary libraries in a `lib` folder.
* **Dummy Generic:** a folder `dummy-generic` with a simple command line program written bash, Node.js, PHP, bash, and Python. Execute by calling `./dummy-generic.[js|php|sh|py]` from the command line. See `updatepulse-api.[js|php|sh|py]` for simple examples of the API calls.

## Wordpress Packages
See `dummy-plugin` for an example of plugin, and  `dummy-theme` for an example of theme.  
They are fully functionnal and can be used to test all the features of the server with a test client installation of WordPress.  

## Other Packages

See `dummy-generic` for examples of a generic package written in Bash, NodeJS, PHP with Curl, and Python.  
The API calls made by generic packages to the license API and Update API are the same as those made by the WordPress packages.  
Unlike the upgrade library provided with plugins & themes, the code found in `updatepulse-api.[sh|php|js|py]` files is **NOT ready for production environment and MUST be adapted**.  
You may refer to the documentation found [here](https://github.com/anyape/updatepulse-server/blob/main/docs/generic.md).