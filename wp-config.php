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

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'hitachi');

/** MySQL database username */
define('DB_USER', 'root');

/** MySQL database password */
define('DB_PASSWORD', 'root');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8mb4');

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
define('AUTH_KEY',         ';/5@~FP4#3U5x0P*>W1CD4^1}$Su(`KR*V,:64Y~oPjj!7SAh`X7GO Bdj,^`gw+');
define('SECURE_AUTH_KEY',  'D>9J|1xX|YY(+&Bdt(,d;(^Yv)X/@t #:Hjh&%x$6j`Ftn0G{!XY-3-&zt3Fxk;W');
define('LOGGED_IN_KEY',    '[?{{JivASrlh+^r8$~0PXU]lFu:,Q9ASmYwoTq@Oi0E8.%Hl#3%roNELC[#]w`B*');
define('NONCE_KEY',        '?:](-]m|yu4@$lq8>=1gVb,FbCU![cExX-Mr/ZG@9j9c?bl<D,a!a/?O/v](K>*d');
define('AUTH_SALT',        '/^nV*hV`1^}qT.$-c?|qg2Ziuav8!1ua>/a  lIt_%cfcS~bOBti`SYZ.+JWe/PU');
define('SECURE_AUTH_SALT', '1{<?o%g(*o~+xWnOm_nUdGVko;/+(7oP%(p:B(};pS*:fx:[[E=Gx_BlzhT%KCyx');
define('LOGGED_IN_SALT',   'YH&G0e~!@=kH IbN|:vw-#~cAJb(QbLt;-<#57V}Qo06V>goM0Ew|Tjy=Zfg_yQP');
define('NONCE_SALT',       '86N)0~b58yH+OQyuKMQ(A91<QQ~yirrF?HupLbUeiRxQyL_l&LL`ZO^&Xal)ip-~');

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
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
