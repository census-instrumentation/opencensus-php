<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

require_once(__DIR__ . '/../vendor/autoload.php');

if (php_sapi_name() !== 'cli') {
    $file = sys_get_temp_dir() . '/spans.json';
    $exporter = new OpenCensus\Trace\Exporter\FileExporter($file);
    OpenCensus\Trace\Integrations\Wordpress::load();
    OpenCensus\Trace\Integrations\Mysql::load();

    OpenCensus\Trace\Tracer::start($exporter);
}

// Disable pseudo cron behavior
define('DISABLE_WP_CRON', true);

// Determine HTTP or HTTPS, then set WP_SITEURL and WP_HOME
if (isset($_SERVER['HTTP_HOST'])) {
    define('HTTP_HOST', $_SERVER['HTTP_HOST']);
} else {
    define('HTTP_HOST', 'localhost');
}
// Use https on production.
define('WP_HOME', 'http://' . HTTP_HOST);
define('WP_SITEURL', 'http://' . HTTP_HOST);

// Force SSL for admin pages
define('FORCE_SSL_ADMIN', false);

/** Production environment */
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
/** The name of the database for WordPress */
define('DB_NAME', getenv('DB_DATABASE') ?: 'wordpress');
/** MySQL database username */
define('DB_USER', getenv('DB_USERNAME') ?: 'wordpress');
/** MySQL database password */
define('DB_PASSWORD', getenv('DB_PASSWORD') ?: 'wordpress');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */

define('AUTH_KEY',         'S6sf+AtNsorbl7wECg22BIZWfk/Ye0HZjXNd+/NGGYKIE2CFSzGBRrSNmT+82t0j0YqheMk1atDU9EtN');
define('SECURE_AUTH_KEY',  'e27t0A3c5+bZMSgc8nK5DXdbPWmpYtrnOjRRaFvgE5dZ/hXkssN+6fxf0fvz/dhofydbPnW8GLzWwV5b');
define('LOGGED_IN_KEY',    '4qJxVidveTpegoLzWO4Vk6wvWeFomjlvWYFSsFkn6zMhCcrY9TI6VGf7sYLveJph/WS09ZnqVaqiZXDB');
define('NONCE_KEY',        'q8X6MzapmbBJs1AgYOSbJscmMFI3CjW/MRrRk5buvNyieYEU0Q27fwGw0FpZhftuwdXlobRRk2d5c8s2');
define('AUTH_SALT',        'x7If3xO6giDWQdzqrPZmz8F9iVeu50eZdiMpvrIjmleleFC7gR7ag06gGt/QlvoYBob/EHk46mC2rgES');
define('SECURE_AUTH_SALT', 'RfLenlKmT2uSfEQ+v75r87u6lRofKGoOZPlxq8QFiOzYiwy4i70D58Hg500fJ+s4YYXrNw1LPOjk9SKM');
define('LOGGED_IN_SALT',   'ZCmRKtiu9ExC1YGTQ1txmjGikP+DWB2zrCUWKn4iws88tKe3mtLTDV0wL9Zwl/Hn+W/r5uZ6eh7RCyqP');
define('NONCE_SALT',       '5FKi89HYMuCCBpLjjqXu9jKQdPq5nk+cCDLtu9HCdNFSL2dPz9V8L4uX/dnaNekj/mNCqEgzXK5LLKCC');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/');
}

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
