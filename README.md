# UpdatePulse Server Integration
(Looking for the main UpdatePulse Server documentation page instead? [See here](https://github.com/anyape/updatepulse-server/blob/main/README.md))

* [UpdatePulse Server Integration](#updatepulse-server-integration)
    * [Repository Introduction](#repository-introduction)
    * [Wordpress Packages](#wordpress-packages)
    * [Other Packages](#other-packages)
    * [Assets](#assets)
    * [Optimizers](#optimizers)

___

## Repository Introduction

This repository contains:
* **Examples of packages** that can be used with UpdatePulse Server, useful for testing and development
* **Assets** used in the example packages - banners, icons, and theme details
* **Optimizers** - production-ready MU Plugins to optimize requests to APIs

## Wordpress Packages

* **Dummy Plugin:** a folder `dummy-plugin` with a simple, empty plugin that includes the necessary code in the `dummy-plugin.php` main plugin file and the necessary libraries in a `lib` folder.
* **Dummy Theme:** a folder `dummy-theme` with a simple, empty child theme of Twenty Seventeen that includes the necessary code in the `functions.php` file and the necessary libraries in a `lib` folder.

See `dummy-plugin` for an example of plugin, and  `dummy-theme` for an example of theme.  
They are fully functionnal and can be used to test all the features of the server with a test client installation of WordPress.  

## Other Packages

**Dummy Generic:** a folder `dummy-generic` with a simple command line program written bash, Node.js, PHP, bash, and Python. Execute by calling `./dummy-generic.[js|php|sh|py]` from the command line. See `updatepulse-api.[js|php|sh|py]` for simple examples of the API calls.

See `dummy-generic` for examples of a generic package written in Bash, NodeJS, PHP with Curl, and Python.  
The API calls made by generic packages to the license API and Update API are the same as those made by the WordPress packages.  
Unlike the upgrade library provided with plugins & themes, the code found in `updatepulse-api.[sh|php|js|py]` files is **NOT ready for production environment and MUST be adapted**.

You may refer to the documentation found [here](https://github.com/anyape/updatepulse-server/blob/main/docs/generic.md).

## Assets

The `assets` folder contains the files used in dummy packages:
* Banners: `banner-772x250.png` and `banner-1544x500.png` are the banners used in all the dummy packages.
* Icons: `icon-128x128.png` is the icon used in all the dummy packages.
* Theme details: `dummy-theme-details.md` is an example of details page displayed in the WordPress admin for `dummy-theme`.

## Optimizers

The `optimizers` folder contains production-ready MU Plugins to use along with UpdatePulse Server to optimize requests to APIs.

Installation:
1. Download the desired optimizer file
2. Place the file in the `wp-content/mu-plugins` folder of your WordPress installation

Available optimizers:
* `upserv-default-optimizer.php` is the default optimizer used by UpdatePulse Server.  
It is installed by default at plugin activation.  
Main effects:
    - Prevent inclusion of themes `functions.php` (parent and child)
    - Remove all core actions and filters that haven't been fired yet
    - Cache setup
    - Provide a mechanism for other optimizers to be added
* `upserv-plugins-optimizer.php` is a production-ready extra optimizer.  
Building upon the default optimizer, it leverages the `upserv_mu_optimizer_default_applied` action hook to run.  
Main effects:
    - Provide a whitelist of plugins via `upserv_mu_optimizer_active_plugins` filter.  
    Default:
        - UpdatePulse Server itself: `'updatepulse-server/updatepulse-server.php'`
        - Action Scheduler: `'action-scheduler/action-scheduler.php'`
    - Dynamically deactivate plugins not in the whitelist
    - Report to the default optimizer with `upserv_mu_optimizer_info` filter
  