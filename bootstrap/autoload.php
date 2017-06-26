<?php

/**
 * Ensure this is only ran once.
 */
if ( defined( 'HERBERT_AUTOLOAD' ) ) {
	return;
}

define( 'HERBERT_AUTOLOAD', 1 );

require 'helpers.php';

/**
 * Get Herbert.
 */
$herbert = Herbert\Framework\Application::getInstance();

/**
 * Load all herbert.php files in plugin roots.
 * Makes sure that paths are not throwing errors.
 */
try {
	$iterator1 = new DirectoryIterator( WP_CONTENT_DIR );
} catch ( \UnexpectedValueException $e ) {
	$iterator1 = [];
}

try {
	$iterator2 = new DirectoryIterator( WPMU_CONTENT_DIR );
} catch ( \UnexpectedValueException $e ) {
	$iterator2 = [];
}
/**
 * Following bedrock we will load mu-plugins first
 */
$iterator = array_merge( iterator_to_array( $iterator2 ), iterator_to_array( $iterator1 ) );


foreach ( $iterator as $directory ) {
	/**
	 * @var $directory DirectoryIterator
	 */
	if ( ! $directory->valid() || $directory->isDot() || ! $directory->isDir() ) {
		continue;
	}

	$root = $directory->getPath() . '/' . $directory->getFilename();

	if ( ! file_exists( $root . '/herbert.config.php' ) ) {
		continue;
	}

	$config = $herbert->getPluginConfig( $root );

	$plugin = $directory->getFilename();

	register_activation_hook( $plugin, function () use ( $herbert, $config, $root, $plugin ) {

		$herbert->loadPlugin( $config );
		$herbert->activatePlugin( $root );

		// Ugly hack to make the install hook work correctly
		// as WP doesn't allow closures to be passed here
		// Makes sure hook is only added when plugin is activated not on every run.
		register_uninstall_hook( $plugin, create_function( '', 'herbert()->deletePlugin(\'' . $root . '\');' ) );
	} );

	register_deactivation_hook( $plugin, function () use ( $herbert, $root ) {
		$herbert->deactivatePlugin( $root );
	} );

	if ( ! is_plugin_active( $plugin ) ) {
		continue;
	}

	$herbert->pluginMatched( $root );

	$herbert->loadPlugin( $config );
}

/**
 * Boot Herbert.
 */
$herbert->boot();
