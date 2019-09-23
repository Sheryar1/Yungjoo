<?php
/**
 * Enables JS popups within Divi.
 *
 * @package     Popups_For_Divi
 * @author      Philipp Stracker
 * @license     GPL2+
 *
 * Plugin Name: Popups for Divi
 * Plugin URI:  https://philippstracker.com/divi-popup/
 * Description: Finally a simple and intuitive way to add custom popups to your Divi pages!
 * Author:      Philipp Stracker
 * Author URI:  https://philippstracker.com/
 * Created:     30.12.2017
 * Version:     1.6.3
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: divi-popup
 * Domain Path: /lang
 *
 * Popups for Divi is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * Popups for Divi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Popups for Divi. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
 */

defined( 'ABSPATH' ) || die();

define( 'DIVI_POPUP_PLUGIN', plugin_basename( __FILE__ ) );

/**
 * A new version value will force refresh of CSS and JS files for all users.
 */
define( 'DIVI_POPUP_VERSION', '1.6.3' );

add_action(
	'plugins_loaded',
	'divi_popup_init_plugin'
);

/**
 * Initialize the Popups for Divi objecand add multilanguage support for the plugin.
 *
 * @since  1.5.0
 */
function divi_popup_init_plugin() {
	load_plugin_textdomain(
		'popups-for-divi',
		false,
		dirname( DIVI_POPUP_PLUGIN ) . '/lang/'
	);

	require 'include/class-popups-for-divi.php';

	global $divi_popup;
	$divi_popup = new Popups_For_Divi();
}
