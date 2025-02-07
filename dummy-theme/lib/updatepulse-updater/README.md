# UpdatePulse Updater - Plugins and themes update library

### Description

Used to enable updates for plugins and themes distributed via UpdatePulse Server.

### Requirements

The library must sit in a `lib` folder at the root of the plugin or theme directory.
A file `updatepulse.json` must be present in the root of the plugin or theme directory.


Before deploying the plugin or theme, make sure to:
- change the `$prefix_updater` with your plugin or theme prefix.
- change the `server` value in `updatepulse.json` to the URL where your UpdatePulse Server is installed.
- Optionally add headers to the main plugin file or to your theme's `style.css` file to enable license checks:  
  - The "Require License" header can be `yes`, `true`, or `1`: all other values are considered as `false`; it is used to enable license checks for your package.  
  - The "Licensed With" header is used to link packages together (for example, in the case of an extension to a main plugin the user already has a license for, if this header is present in the extension, the license check will be made against the main plugin). It must be the slug of another plugin or theme that is already present in your UpdatePulse Server.  


### Code to include in main plugin file or functions.php

```php
use Anyape\UpdatePulse\Updater\v2_0\UpdatePulse_Updater;
require_once __DIR__ . '/lib/updatepulse-updater/class-updatepulse-updater.php';

$prefix_updater = new UpdatePulse_Updater(
	wp_normalize_path( __FILE__ ),
	0 === strpos( __DIR__, WP_PLUGIN_DIR ) ? wp_normalize_path( __DIR__ ) : get_stylesheet_directory()
);
```

### Content of `updatepulse.json`

```json
{
   "server": "https://server.domain.tld/"
}
```

### License headers

```text
Require License: yes
Licensed With: another-plugin-or-theme-slug
```