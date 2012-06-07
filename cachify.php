<?php
/*
Plugin Name: Cachify
Description: Smarter Cache für WordPress. Reduziert die Anzahl der Datenbankabfragen und dynamischer Anweisungen. Minimiert Ladezeiten der Blogseiten.
Author: Sergej M&uuml;ller
Author URI: http://wpseo.de
Plugin URI: http://playground.ebiene.de/cachify-wordpress-cache/
Version: 1.5.1
*/


/* Sicherheitsabfrage */
if ( !class_exists('WP') ) {
	die();
}


/**
* Cachify
*/

final class Cachify {


	/* Plugin Base */
	private static $base;


	/**
	* "Konstruktor" der Klasse
	*
	* @since   1.0
	* @change  1.2
	*/

  	public static function init()
  	{
		/* Autosave? */
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
			return;
		}
		
		/* Plugin-Base */
		self::$base = plugin_basename(__FILE__);

		/* Publish post */
		add_action(
			'publish_post',
			array(
				__CLASS__,
				'publish_post'
			)
		);

		/* Publish page */
		add_action(
			'publish_page',
			array(
				__CLASS__,
				'publish_page'
			)
		);

		/* Backend */
		if ( is_admin() ) {
			add_action(
				'wpmu_new_blog',
				array(
					__CLASS__,
					'install_later'
				)
			);
			add_action(
				'delete_blog',
				array(
					__CLASS__,
					'uninstall_later'
				)
			);
			
			add_action(
				'admin_init',
				array(
					__CLASS__,
					'register_settings'
				)
			);
			add_action(
				'admin_init',
				array(
					__CLASS__,
					'receive_flush'
				)
			);
			add_action(
				'admin_menu',
				array(
					__CLASS__,
					'add_page'
				)
			);
			add_action(
					'admin_print_styles',
					array(
						__CLASS__,
						'add_css'
					)
				);

			add_action(
				'transition_comment_status',
				array(
					__CLASS__,
					'touch_comment'
				),
				10,
				3
			);
			add_action(
				'edit_comment',
				array(
					__CLASS__,
					'edit_comment'
				)
			);
			add_action(
				'admin_bar_menu',
				array(
					__CLASS__,
					'add_menu'
				),
				90
			);

			add_filter(
				'plugin_row_meta',
				array(
					__CLASS__,
					'row_meta'
				),
				10,
				2
			);
			add_filter(
				'plugin_action_links_' .self::$base,
				array(
					__CLASS__,
					'action_links'
				)
			);

		/* Frontend */
		} else {
			add_action(
				'preprocess_comment',
				array(
					__CLASS__,
					'add_comment'
				),
				1
			);
			add_action(
				'template_redirect',
				array(
					__CLASS__,
					'manage_cache'
				),
				99
			);
		}
	}
	
	
	/**
	* Plugin-Installation für MU-Blogs
	*
	* @since   1.0
	* @change  1.0
	*/

	public static function install()
	{
		/* Multisite & Network */
		if ( is_multisite() && !empty($_GET['networkwide']) ) {
			/* Blog-IDs */
			$ids = self::_get_blog_ids();

			/* Loopen */
			foreach ($ids as $id) {
				switch_to_blog( (int)$id );
				self::_install_backend();
			}

			/* Wechsel zurück */
			restore_current_blog();

		} else {
			self::_install_backend();
		}
	}


	/**
	* Plugin-Installation bei neuen MU-Blogs
	*
	* @since   1.0
	* @change  1.0
	*/

	public static function install_later($id) {
		/* Kein Netzwerk-Plugin */
		if ( !is_plugin_active_for_network(self::$base) ) {
			return;
		}

		/* Wechsel */
		switch_to_blog( (int)$id );

		/* Installieren */
		self::_install_backend();

		/* Wechsel zurück */
		restore_current_blog();
	}


	/**
	* Eigentliche Installation der Optionen
	*
	* @since   1.0
	* @change  1.3
	*/

	private static function _install_backend()
	{
		add_option(
			'cachify',
			array(
				'only_guests'	 => 1,
				'compress_html'	 => 0,
				'cache_expires'	 => 12,
				'without_ids'	 => '',
				'without_agents' => '',
				'use_apc'		 => 0
			)
		);

		/* Flush */
		self::flush_cache();
	}


	/**
	* Deinstallation des Plugins pro MU-Blog
	*
	* @since   1.0
	* @change  1.0
	*/

	public static function uninstall()
	{
		/* Global */
		global $wpdb;

		/* Multisite & Network */
		if ( is_multisite() && !empty($_GET['networkwide']) ) {
			/* Alter Blog */
			$old = $wpdb->blogid;

			/* Blog-IDs */
			$ids = self::_get_blog_ids();

			/* Loopen */
			foreach ($ids as $id) {
				switch_to_blog($id);
				self::_uninstall_backend();
			}

			/* Wechsel zurück */
			switch_to_blog($old);
		} else {
			self::_uninstall_backend();
		}
	}


	/**
	* Deinstallation des Plugins bei MU & Network
	*
	* @since   1.0
	* @change  1.0
	*/

	public static function uninstall_later($id)
	{
		/* Kein Netzwerk-Plugin */
		if ( !is_plugin_active_for_network(self::$base) ) {
			return;
		}

		/* Wechsel */
		switch_to_blog( (int)$id );

		/* Installieren */
		self::_uninstall_backend();

		/* Wechsel zurück */
		restore_current_blog();
	}


	/**
	* Eigentliche Deinstallation des Plugins
	*
	* @since   1.0
	* @change  1.0
	*/

	private static function _uninstall_backend()
	{
		/* Option */
		delete_option('cachify');

		/* Cache leeren */
		self::flush_cache();
	}


	/**
	* Update des Plugins
	*
	* @since   1.0
	* @change  1.0
	*/

	public static function update()
	{
		/* Updaten */
		self::_update_backend();
	}
	
	
	/**
	* Eigentlicher Update des Plugins
	*
	* @since   1.0
	* @change  1.0
	*/

	private static function _update_backend()
	{
		/* Cache leeren */
		self::flush_cache();
	}
	
	
	/**
	* Rückgabe der IDs installierter Blogs
	*
	* @since   1.0
	* @change  1.0
	*
	* @return  array  Blog-IDs
	*/
	
	private static function _get_blog_ids()
	{
		/* Global */
		global $wpdb;
		
		return $wpdb->get_col(
			$wpdb->prepare("SELECT blog_id FROM `$wpdb->blogs`")
		);
	}


	/**
	* Hinzufügen der Action-Links
	*
	* @since   1.0
	* @change  1.0
	*
	* @param   array  $data  Bereits existente Links
	* @return  array  $data  Erweitertes Array mit Links
	*/

	public static function action_links($data)
	{
		/* Rechte? */
		if ( !current_user_can('manage_options') ) {
			return $data;
		}

		return array_merge(
			$data,
			array(
				sprintf(
					'<a href="%s">%s</a>',
					add_query_arg(
						array(
							'page' => 'cachify'
						),
						admin_url('options-general.php')
					),
					__('Settings')
				)
			)
		);
	}


	/**
	* Meta-Links des Plugins
	*
	* @since   0.5
	* @change  1.3
	*
	* @param   array   $data  Bereits vorhandene Links
	* @param   string  $page  Aktuelle Seite
	* @return  array   $data  Modifizierte Links
	*/

	public static function row_meta($data, $page)
	{
		/* Rechte */
		if ( $page != self::$base ) {
			return $data;
		}
		
		return array_merge(
			$data,
			array(
				'<a href="http://flattr.com/profile/sergej.mueller" target="_blank">Plugin flattern</a>',
				'<a href="https://plus.google.com/110569673423509816572" target="_blank">Auf Google+ folgen</a>'
			)
		);
	}
	
	
	/**
	* Hinzufügen eines Admin-Bar-Menüs
	*
	* @since   1.2
	* @change  1.2.1
	*
	* @param   object  Objekt mit Menü-Eigenschaften
	*/
	
	public static function add_menu($wp_admin_bar)
	{
		/* Aussteigen */
		if ( !function_exists('is_admin_bar_showing') or !is_admin_bar_showing() or !is_super_admin() ) {
			return;
		}
		
		/* Hinzufügen */
		$wp_admin_bar->add_menu(
			array(
				'id' 	 => 'cachify',
				'title'  => '<span class="ab-icon" title="Cache leeren"></span>',
				'href'   => add_query_arg('_cachify', 'flush'),
				'parent' => 'top-secondary'
			)
		);
	}


	/**
	* Verarbeitung der Plugin-Meta-Aktionen
	*
	* @since   0.5
	* @change  1.2
	*
	* @param   array  $data  Metadaten der Plugins
	*/

	public static function receive_flush($data)
	{
		/* Leer? */
		if ( empty($_GET['_cachify']) or $_GET['_cachify'] !== 'flush' ) {
			return;
		}
		
		/* Global */
		global $wpdb;

		/* Multisite & Network */
		if ( is_multisite() && is_plugin_active_for_network(self::$base) ) {
			/* Alter Blog */
			$old = $wpdb->blogid;

			/* Blog-IDs */
			$ids = self::_get_blog_ids();

			/* Loopen */
			foreach ($ids as $id) {
				switch_to_blog($id);
				self::flush_cache();
			}

			/* Wechsel zurück */
			switch_to_blog($old);
			
			/* Notiz */
			add_action(
				'network_admin_notices',
				array(
					__CLASS__,
					'flush_notice'
				)
			);
		} else {
			/* Leeren */
			self::flush_cache();
			
			/* Notiz */
			add_action(
				'admin_notices',
				array(
					__CLASS__,
					'flush_notice'
				)
			);
		}
	}
	
	
	/**
	* Hinweis nach erfolgreichem Cache-Leeren
	*
	* @since   1.2
	* @change  1.2
	*/
	
	public static function flush_notice()
	{
		/* Kein Admin */
		if ( !is_super_admin() ) {
			return false;
		}
		
		echo '<div id="message" class="updated"><p><strong>Cachify-Cache geleert.</strong></p></div>';
	}


	/**
	* Löschung des Cache beim Kommentar-Editieren
	*
	* @since   0.1
	* @change  0.4
	*
	* @param   integer  $id  ID des Kommentars
	*/

	public static function edit_comment($id)
	{
		self::_delete_cache(
			get_permalink(
				get_comment($id)->comment_post_ID
			)
		);
	}


	/**
	* Löschung des Cache beim neuen Kommentar
	*
	* @since   0.1
	* @change  0.1
	*
	* @param   array  $comment  Array mit Eigenschaften
	* @return  array  $comment  Array mit Eigenschaften
	*/

	public static function add_comment($comment)
	{
		self::_delete_cache(
			get_permalink($comment['comment_post_ID'])
		);

		return $comment;
	}


	/**
	* Löschung des Cache beim Editieren der Kommentare
	*
	* @since   0.1
	* @change  0.4
	*
	* @param   string  $new_status  Neuer Status
	* @param   string  $old_status  Alter Status
	* @param   object  $comment     Array mit Eigenschaften
	*/

	public static function touch_comment($new_status, $old_status, $comment)
	{
		if ( $new_status != $old_status ) {
			self::_delete_cache(
				get_permalink($comment->comment_post_ID)
			);
		}
	}


  	/**
	* Leerung des Cache bei neuen Beiträgen
	*
	* @since   0.1
	* @change  0.9.1
	*
	* @param   intval  $id  ID des Beitrags
	*/

	public static function publish_post($id)
	{
		/* Post */
		$post = get_post($id);

		/* Löschen */
		if ( in_array( $post->post_status, array('publish', 'future') ) ) {
			self::flush_cache();
		}
	}


  	/**
	* Leerung des Cache bei neuen Beiträgen
	*
	* @since   1.0
	* @change  1.0
	*
	* @param   intval  $id  ID des Beitrags
	*/

	public static function publish_page($id)
	{
		/* Page */
		$page = get_page($id);

		/* Löschen */
		if ( $page->post_status == 'publish' ) {
			self::flush_cache();
		}
	}


	/**
	* Rückgabe des Cache-Hash-Wertes
	*
	* @since   0.1
	* @change  1.3
	*
	* @param   string  $url  URL für den Hash-Wert [optional]
	* @return  string        Cachify-Hash-Wert
	*/

  	private static function _cache_hash($url = '')
	{
		return 'cachify_' .md5(
			empty($url) ? ( $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ) : ( parse_url($url, PHP_URL_HOST) . parse_url($url, PHP_URL_PATH) )
		);
	}


	/**
	* Rückgabe der Query-Anzahl
	*
	* @since   0.1
	* @change  1.0
	*
	* @return  intval  Query-Anzahl
	*/

	private static function _page_queries()
	{
		return $GLOBALS['wpdb']->num_queries;
	}


	/**
	* Rückgabe der Ausführungszeit
	*
	* @since   0.1
	* @change  1.0
	*
	* @return  intval  Anzahl der Sekunden
	*/

	private static function _page_timer()
	{
		return timer_stop(0, 2);
	}


	/**
	* Rückgabe des Speicherverbrauchs
	*
	* @since   0.7
	* @change  1.0
	*
	* @return  string  Konvertierter Größenwert
	*/

	private static function _memory_usage()
	{
		return ( function_exists('memory_get_usage') ? size_format(memory_get_usage(), 2) : 0 );
	}


	/**
	* Splittung nach Komma
	*
	* @since   0.9.1
	* @change  1.0
	*
	* @param   string  $input  Zu splittende Zeichenkette
	* @return  array           Konvertierter Array
	*/

	private static function _preg_split($input)
	{
		return (array)preg_split('/,/', $input, -1, PREG_SPLIT_NO_EMPTY);
	}


	/**
	* Prüfung auf Index
	*
	* @since   0.6
	* @change  1.0
	*
	* @return  boolean  TRUE bei Index
	*/

	private static function _is_index()
	{
		return basename($_SERVER['SCRIPT_NAME']) != 'index.php';
	}


	/**
	* Prüfung auf Mobile Devices
	*
	* @since   0.9.1
	* @change  1.0
	*
	* @return  boolean  TRUE bei Mobile
	*/

	private static function _is_mobile()
	{
		return ( strpos(TEMPLATEPATH, 'wptouch') or strpos(TEMPLATEPATH, 'carrington') );
	}


	/**
	* Definition der Ausnahmen für den Cache
	*
	* @since   0.2
	* @change  1.0
	*
	* @return  boolean  TRUE bei Ausnahmen
	*/

	private static function _skip_cache()
	{
		/* Optionen */
		$options = get_option('cachify');
		
		/* Filter */
		if ( self::_is_index() or is_feed() or is_trackback() or is_robots() or is_preview() or post_password_required() or ( $options['only_guests'] && is_user_logged_in() ) ) {
			return true;
		}
	
		/* WP Touch */
		if ( self::_is_mobile() ) {
			return true;
		}
	
		/* Post IDs */
		if ( $options['without_ids'] && is_singular() ) {
			if ( in_array( $GLOBALS['wp_query']->get_queried_object_id(), self::_preg_split($options['without_ids']) ) ) {
				return true;
			}
		}
	
		/* User Agents */
		if ( $options['without_agents'] && isset($_SERVER['HTTP_USER_AGENT']) ) {
			if ( array_filter( self::_preg_split($options['without_agents']), create_function('$a', 'return strpos($_SERVER["HTTP_USER_AGENT"], $a);') ) ) {
				return true;
			}
		}
	
		return false;
	}


	/**
	* Komprimierung des HTML-Codes
	*
	* @since   0.9.2
	* @change  1.2.1
	*
	* @param   string  $data  Zu komprimierende Datensatz
	* @return  string  $data  Komprimierter Datensatz
	*/

	private static function _sanitize_cache($data) {
		/* Optionen */
		$options = get_option('cachify');

		/* Komprimieren? */
		if ( !$options['compress_html'] ) {
			return($data);
		}
		
		/* Verkleinern */
		$cleaned = preg_replace(
			array(
				'/<!--[^\[><](.*?)-->/s',
				'#(?ix)(?>[^\S ]\s*|\s{2,})(?=(?:(?:[^<]++|<(?!/?(?:textarea|pre)\b))*+)(?:<(?>textarea|pre)\b|\z))#'
			),
			array(
				'',
				' '
			),
			(string) $data
		);
		
		/* Fehlerhaft? */
		if ( strlen($cleaned) <= 1 ) {
			return($data);
		}

		return($cleaned);
	}


	/**
	* Löschung des Cache für eine URL
	*
	* @since   0.1
	* @change  1.3
	*
	* @param  string  $url  URL für den Hash-Wert
	*/

	private static function _delete_cache($url)
	{
		/* Hash */
		$hash = self::_cache_hash($url);
		
		/* Löschen */
		if ( self::_apc_active() ) {
			apc_delete($hash);
		} else {
			delete_transient($hash);
		}
	}


	/**
	* Zurücksetzen des kompletten Cache
	*
	* @since   0.1
	* @change  1.3
	*/

	public static function flush_cache()
	{
		/* DB */
		$GLOBALS['wpdb']->query("DELETE FROM `" .$GLOBALS['wpdb']->options. "` WHERE `option_name` LIKE ('_transient%_cachify_%')");
		$GLOBALS['wpdb']->query("OPTIMIZE TABLE `" .$GLOBALS['wpdb']->options. "`");
		
		/* APC */
		if ( self::_apc_active() ) {
			apc_clear_cache('user');
		}
	}
	
	
	/**
	* Zuweisung des Cache
	*
	* @since   0.1
	* @change  1.0
	*
	* @param   string  $data  Inhalt der Seite
	* @return  string  $data  Inhalt der Seite
	*/

	public static function set_cache($data)
	{
		/* Leer */
		if ( empty($data) ) {
			return '';
		}
		
		/* Optionen */
		$options = get_option('cachify');
		
		/* Lifetime */
		$lifetime = 60 * 60 * (int)$options['cache_expires'];
		
		/* Hash */
		$hash = self::_cache_hash();
		
		/* APC */
		if ( self::_apc_active() ) {
			apc_store(
				$hash,
				gzencode( self::_sanitize_cache($data) . self::_apc_signatur(), 9 ),
				$lifetime
			);

			return $data;
		}
		
		/* Default (DB) */
		set_transient(
			$hash,
			array(
				'data'	  => self::_sanitize_cache($data),
				'queries' => self::_page_queries(),
				'timer'	  => self::_page_timer(),
				'memory'  => self::_memory_usage(),
				'time'	  => current_time('timestamp')
			),
			$lifetime
		);

		return $data;
	}


	/**
	* Verwaltung des Cache
	*
	* @since   0.1
	* @change  1.3
	*/

	public static function manage_cache()
	{
		/* Kein Cache? */
		if ( self::_skip_cache() ) {
			return;
		}
		
		/* Init */
		$hash = self::_cache_hash();
		
		/* APC */
		if ( self::_apc_active() ) {
			if ( !apc_exists($hash) ) {
				ob_start('Cachify::set_cache');
			}
			
			return;
		}
		
		/* DB-Cache */
		if ( $cache = get_transient($hash) ) {
			if ( !empty($cache['data']) ) {
				/* Content */
				echo $cache['data'];
	
				/* Signatur */
				echo sprintf(
					"\n\n<!--\n%s\n%s\n%s\n%s\n-->",
					'Cachify für WordPress | http://bit.ly/cachify',
					sprintf(
						'Ohne Cachify: %d DB-Anfragen, %s Sekunden, %s',
						$cache['queries'],
						$cache['timer'],
						$cache['memory']
					),
					sprintf(
						'Mit Cachify: %d DB-Anfragen, %s Sekunden, %s',
						self::_page_queries(),
						self::_page_timer(),
						self::_memory_usage()
					),
					sprintf(
						'Generiert: %s zuvor',
						human_time_diff($cache['time'], current_time('timestamp'))
					)
				);
	
				exit;
			}
		}
	
		/* Cachen */
		ob_start('Cachify::set_cache');
	}
	
	
	/**
	* Prüfung auf aktiviertes APC
	*
	* @since  1.3
	* @change 1.3
	*
	* @param  boolean  TRUE bei aktiviertem APC
	*/
	
	private static function _apc_active()
	{
		/* Optionen */
		$options = get_option('cachify');
		
		return ( !empty($options['use_apc']) && extension_loaded('apc') );
	}
	
	
	/**
	* Rückgabe der Signatur für APC
	*
	* @since  1.3
	* @change 1.3
	*
	* @param  string  Konvertierte Signatur
	*/
	
	private static function _apc_signatur()
	{
		return sprintf(
			"\n\n<!-- %s\n%s %s -->",
			'Cachify | http://bit.ly/cachify',
			'APC Cache @',
			date_i18n('d.m.Y H:i:s', (current_time('timestamp')))
		);
	}


	/**
	* Einbindung von CSS
	*
	* @since   1.0
	* @change  1.1
	*/

	public static function add_css()
	{
		/* Infos auslesen */
		$data = get_plugin_data(__FILE__);
		
		/* CSS registrieren */
		wp_register_style(
			'cachify_css',
			plugins_url('css/style.css', __FILE__),
			array(),
			$data['Version']
		);

		/* CSS einbinden */
		wp_enqueue_style('cachify_css');
	}


	/**
	* Einfügen der Optionsseite
	*
	* @since   1.0
	* @change  1.5
	*/

	public static function add_page()
	{
		add_options_page(
			'Cachify',
			'<span id="cachify_sidebar_icon"></span>Cachify',
			'manage_options',
			'cachify',
			array(
				__CLASS__,
				'options_page'
			)
		);
	}
	
	
	/**
	* Anzeige des Hilfe-Links
	*
	* @since  1.1
	* @change 1.3
	*
	* @param  string  $anchor  Anker in die Hilfe
	*/
	
	private static function _help_link($anchor)
	{
		echo sprintf(
			'<span>[<a href="http://playground.ebiene.de/cachify-wordpress-cache/#%s" target="_blank">?</a>]</span>',
			$anchor
		);
	}


	/**
	* Registrierung der Settings
	*
	* @since   1.0
	* @change  1.0
	*/

	public static function register_settings()
	{
		register_setting(
			'cachify',
			'cachify',
			array(
				__CLASS__,
				'validate_options'
			)
		);
	}


	/**
	* Valisierung der Optionsseite
	*
	* @since   1.0
	* @change  1.3
	*
	* @param   array  $data  Array mit Formularwerten
	* @return  array         Array mit geprüften Werten
	*/

	public static function validate_options($data)
	{
		/* Cache leeren */
		self::flush_cache();
		
		return array(
			'only_guests'	 => (int)(!empty($data['only_guests'])),
			'compress_html'	 => (int)(!empty($data['compress_html'])),
			'cache_expires'	 => (int)(@$data['cache_expires']),
			'without_ids'	 => (string)sanitize_text_field(@$data['without_ids']),
			'without_agents' => (string)sanitize_text_field(@$data['without_agents']),
			'use_apc'	 	 => (int)(!empty($data['use_apc']))
		);
	}


	/**
	* Darstellung der Optionsseite
	*
	* @since   1.0
	* @change  1.3
	*/

	public static function options_page()
	{ ?>
		<div class="wrap" id="cachify_main">
			<?php screen_icon('cachify') ?>

			<h2>
				Cachify
			</h2>

			<form method="post" action="options.php">
				<?php settings_fields('cachify') ?>

				<?php $options = get_option('cachify') ?>

				<table class="form-table">
					<tr>
						<th>
							Cache-Gültigkeit in Stunden <?php self::_help_link('cache_expires') ?>
						</th>
						<td>
							<input type="text" name="cachify[cache_expires]" value="<?php echo $options['cache_expires'] ?>" />
						</td>
					</tr>

					<tr>
						<th>
							Ausnahme für (Post/Pages) IDs <?php self::_help_link('without_ids') ?>
						</th>
						<td>
							<input type="text" name="cachify[without_ids]" value="<?php echo $options['without_ids'] ?>" />
						</td>
					</tr>

					<tr>
						<th>
							Ausnahme für User Agents <?php self::_help_link('without_agents') ?>
						</th>
						<td>
							<input type="text" name="cachify[without_agents]" value="<?php echo $options['without_agents'] ?>" />
						</td>
					</tr>
					
					<tr>
						<th>
							Komprimierung der Ausgabe <?php self::_help_link('compress_html') ?>
						</th>
						<td>
							<input type="checkbox" name="cachify[compress_html]" value="1" <?php checked('1', $options['compress_html']); ?> />
						</td>
					</tr>

					<tr>
						<th>
							Nur für nicht eingeloggte Nutzer <?php self::_help_link('only_guests') ?>
						</th>
						<td>
							<input type="checkbox" name="cachify[only_guests]" value="1" <?php checked('1', $options['only_guests']); ?> />
						</td>
					</tr>
					
					<?php if ( function_exists('apc_fetch') ) { ?>
						<tr>
							<th>
								APC (Alternative PHP Cache) nutzen <?php self::_help_link('use_apc') ?>
							</th>
							<td>
								<input type="checkbox" name="cachify[use_apc]" value="1" <?php checked('1', $options['use_apc']); ?> />
							</td>
						</tr>
					<?php } ?>
				</table>

				<p class="submit">
					<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
				</p>
			</form>
		</div><?php
	}
}


/* Fire */
add_action(
	'plugins_loaded',
	array(
		'Cachify',
		'init'
	),
	99
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

/* Updaten */
if ( function_exists('register_update_hook') ) {
	register_update_hook(
		__FILE__,
		array(
			'Cachify',
			'update'
		)
	);
}