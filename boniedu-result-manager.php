<?php
/**
 * Plugin Name:       BoniEdu Result Manager
 * Plugin URI:        https://boniedu.com/
 * Description:       A complete solution to manage primary school student results, certificates, and academic records.
 * Version:           1.0.0
 * Author:            Antigravity
 * Author URI:        https://boniedu.com/
 * Text Domain:       boniedu
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 */
define( 'BONIEDU_VERSION', '1.0.0' );

/**
 * Plugin Root Path
 */
define( 'BONIEDU_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'BONIEDU_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Autoloader
 */
require_once BONIEDU_PLUGIN_DIR . 'includes/Core/Autoloader.php';

/**
 * The code that runs during plugin activation.
 */
function activate_boniedu_result_manager() {
	BoniEdu\Core\Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_boniedu_result_manager() {
	BoniEdu\Core\Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_boniedu_result_manager' );
register_deactivation_hook( __FILE__, 'deactivate_boniedu_result_manager' );

/**
 * Begins execution of the plugin.
 */
function run_boniedu_result_manager() {
	$plugin = new BoniEdu\Core\BoniEdu();
	$plugin->run();
}
run_boniedu_result_manager();
