<?php
/*
Plugin Name: Cachify
Description: Smarter Cache für WordPress. Reduziert die Ladezeit der Blogseiten, indem Inhalte in statischer Form abgelegt und ausgeliefert werden.
Author: Sergej M&uuml;ller
Author URI: http://wpseo.de
Plugin URI: http://cachify.de
Version: 2.0
*/


/* Sicherheitsabfrage */
if ( !class_exists('WP') ) {
	die();
}


/* Filter */
if ( ! ( (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) or (defined('DOING_CRON') && DOING_CRON) ) ) {
	/* PHP-Check */
	if ( ! function_exists('spl_autoload_register') ) {
		wp_die('Cachify benötigt mindestens PHP 5.1.2');
	}
	
	/* Konstanten */
	define('CACHIFY_FILE', __FILE__);
	define('CACHIFY_BASE', plugin_basename(__FILE__));
	define('CACHIFY_CACHE_DIR', WP_CONTENT_DIR . '/cache/cachify');
	
	/* Autoload */
	spl_autoload_register('cachify_autoload');
	
	/* Init */
	add_action(
		'plugins_loaded',
		array(
			'Cachify',
			'init'
		)
	);
	
	/* Install */
	register_activation_hook(
		__FILE__,
		array(
			'Cachify',
			'install'
		)
	);
	
	/* Uninstall */
	register_uninstall_hook(
		__FILE__,
		array(
			'Cachify',
			'uninstall'
		)
	);
}


/* Autoload */
function cachify_autoload($class) {
	if ( in_array($class, array('Cachify', 'Cachify_APC', 'Cachify_DB', 'Cachify_HDD')) ) {
		require_once(
			sprintf(
				'%s/inc/%s.class.php',
				dirname(__FILE__),
				strtolower($class)
			)
		);
	}
}