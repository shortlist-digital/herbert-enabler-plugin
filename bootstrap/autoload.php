<?php

require __DIR__ . '/helpers.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';

/**
 * Get Herbert.
 */

$herbert = Herbert\Framework\Application::getInstance();

/**
 * Load all herbert.php files in plugin roots.
 * Makes sure that paths are not throwing errors.
 */


$iterator1 = glob( WP_PLUGIN_DIR . '/*' );

$iterator2 = glob( WPMU_PLUGIN_DIR . '/*' );

if ( ! $iterator1 ) {
	$iterator1 = [];
}
if ( ! $iterator2 ) {
	$iterator2 = [];
}

/**
 * Following bedrock we will load mu-plugins first
 *
 * @var $iterator1 []
 * @var $iterator2 []
 */
$iterator = array_merge( $iterator2, $iterator1 );
foreach ( $iterator as $directory ) {
	/**
	 * @var $directory string
	 */


	if ( ! file_exists( $directory . '/herbert.config.php' ) ) {

		continue;
	}

	$config = $herbert->getPluginConfig( $directory );

	$plugin = basename( $directory );

	register_activation_hook( $plugin, function () use ( $herbert, $config, $directory, $plugin ) {

		$herbert->loadPlugin( $config );
		$herbert->activatePlugin( $directory );

		// Ugly hack to make the install hook work correctly
		// as WP doesn't allow closures to be passed here
		// Makes sure hook is only added when plugin is activated not on every run.
		register_uninstall_hook( $plugin, create_function( '', 'herbert()->deletePlugin(\'' . $directory . '\');' ) );
	} );

	register_deactivation_hook( $plugin, function () use ( $herbert, $directory ) {
		$herbert->deactivatePlugin( $directory );
	} );

	if ( strpos( $directory, WPMU_PLUGIN_DIR ) === false && ! ( is_plugin_active( $plugin . '/' . $plugin . '.php' ) || is_plugin_active( $plugin . '/plugin.php' ) ) ) {
		continue;
	}

	$herbert->pluginMatched( $directory );

	$herbert->loadPlugin( $config );
}

/**
 * Boot Herbert.
 */
$herbert->boot();
