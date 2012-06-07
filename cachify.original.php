<?php
/*
Plugin Name: Cachify
Description: Smarter Cache für WordPress. Reduziert die Anzahl der Datenbankabfragen und dynamischer Anweisungen. Minimiert Ladezeiten der Blogseiten.
Author: Sergej M&uuml;ller
Author URI: http://www.wpSEO.de
Plugin URI: http://playground.ebiene.de/2652/cachify-wordpress-cache/
Version: 1.1
*/


/* Sicherheitsabfrage */
if ( !class_exists('WP') ) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit();
}


/**
* Cachify
*/

final class Cachify {


	/* Save me */
	private static $base;


	/**
	* Konstruktor der Klasse
	*
	* @since   1.0
	* @change  1.1
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
				'pre_current_active_plugins',
				array(
					__CLASS__,
					'receive_flush'
				)
			);
			
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
	* Installation des Plugins auch für MU-Blogs
	*
	* @since   1.0
	* @change  1.0
	*/

	public static function install()
	{
		/* Global */
		global $wpdb;

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
	* Installation des Plugins bei einem neuen MU-Blog
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
	* Eigentliche Installation der Option und der Tabelle
	*
	* @since   1.0
	* @change  1.0
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
				'without_agents' => ''
			),
			'',
			'no'
		);

		/* Flush */
		self::_flush_cache();
	}


	/**
	* Uninstallation des Plugins pro MU-Blog
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
	* Uninstallation des Plugins bei MU & Network-Plugin
	*
	* @since   1.0
	* @change  1.0
	*/

	public static function uninstall_later($id) {
		/* Global */
		global $wpdb;

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
		self::_flush_cache();
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
		self::_flush_cache();
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
	* Hinzufügen der Action-Links (Einstellungen links)
	*
	* @since   1.0
	* @change  1.0
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
	* Meta-Links zum Plugin
	*
	* @since   0.5
	* @change  1.1
	*
	* @param   array   $data  Bereits vorhandene Links
	* @param   string  $page  Aktuelle Seite
	* @return  array   $data  Modifizierte Links
	*/

	public static function row_meta($data, $page)
	{
		if ( $page == self::$base && current_user_can('manage_options') ) {
			$data = array_merge(
				$data,
				array(
					'<a href="http://flattr.com/thing/114377/Cachify-Handliches-Cache-Plugin-fur-WordPress" target="_blank">Plugin flattern</a>',
					'<a href="https://plus.google.com/110569673423509816572" target="_blank">Auf Google+ folgen</a>',
					sprintf(
						'<a href="%s">Cache leeren</a>',
						add_query_arg('_cachify', 'flush', 'plugins.php')
					)
				)
			);
		}

		return $data;
	}


	/**
	* Verarbeitung der Plugin-Meta-Aktionen
	*
	* @since   0.5
	* @change  1.1
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
				self::_flush_cache();
			}

			/* Wechsel zurück */
			switch_to_blog($old);
		} else {
			self::_flush_cache();
		}
	}


	/**
	* Löschung des Cache bei neuem Kommentar
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
	* Löschung des Cache bei neuem Kommentar
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
	* Löschung des Cache beim Kommentar-Editieren
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
	* Leerung des kompletten Cache
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
			self::_flush_cache();
		}
	}


  /**
	* Leerung des kompletten Cache
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
			self::_flush_cache();
		}
	}


	/**
	* Rückgabe des Cache-Hash-Wertes
	*
	* @since   0.1
	* @change  1.0
	*
	* @param   string  $url  URL für den Hash-Wert [optional]
	* @return  string        Cachify-Hash-Wert
	*/

  	private static function _cache_hash($url = '')
	{
		/* Leer? */
		if ( empty($url) ) {
			$url = esc_url_raw(
				sprintf(
					'%s://%s%s',
					(is_ssl() ? 'https' : 'http'),
					$_SERVER['HTTP_HOST'],
					$_SERVER['REQUEST_URI']
				)
			);
		}

		return 'cachify_' .md5($url);
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
	* @param   string   $type  Typ der Abfrage [optional]
	* @return  boolean         TRUE bei Ausnahmen
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
	* Komprimiert den HTML-Code
	*
	* @since   0.9.2
	* @change  1.0
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
				'/\<!--.+?--\>/s',
				'/\>(\s)+(\S)/s',
				'/\>[^\S ]+/s',
				'/[^\S ]+\</s',
				'/\>(\s)+/s',
				'/(\s)+\</s',
				'/\>\s+\</s'
			),
			array(
				'',
				'>\\1\\2',
				'>',
				'<',
				'>\\1',
				'\\1<',
				'><'
			),
			(string)$data
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
	* @change  1.0
	*
	* @param  string  $url  URL für den Hash-Wert
	*/

	private static function _delete_cache($url)
	{
		delete_transient(
			self::_cache_hash($url)
		);
	}


	/**
	* Zurücksetzen des kompletten Cache
	*
	* @since   0.1
	* @change  1.0
	*/

	private static function _flush_cache()
	{
		$GLOBALS['wpdb']->query("DELETE FROM `" .$GLOBALS['wpdb']->options. "` WHERE `option_name` LIKE ('_transient%_cachify_%')");
		$GLOBALS['wpdb']->query("OPTIMIZE TABLE `" .$GLOBALS['wpdb']->options. "`");
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
		/* Optionen */
		$options = get_option('cachify');

		/* Speichern */
		if ( !empty($data) ) {
			set_transient(
				self::_cache_hash(),
				array(
					'data'		=> self::_sanitize_cache($data),
					'queries' 	=> self::_page_queries(),
					'timer'		=> self::_page_timer(),
					'memory'	=> self::_memory_usage(),
					'time'		=> current_time('timestamp')
				),
				60 * 60 * (int)$options['cache_expires']
			);
		}

		return $data;
	}


	/**
	* Verwaltung des Cache
	*
	* @since   0.1
	* @change  0.9
	*/

	public static function manage_cache()
	{
		/* Kein Cache? */
		if ( self::_skip_cache() ) {
			return;
		}

		/* Im Cache? */
		if ( $cache = get_transient(self::_cache_hash()) ) {
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
	* Einbindung von CSS
	*
	* @since   1.0
	* @change  1.1
	*/

	function add_css()
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
	* Einfüger der Optionsseite
	*
	* @since   1.0
	* @change  1.0
	*/

	function add_page()
	{
		add_options_page(
			'Cachify',
			'<img src="' .plugins_url('cachify/img/icon.png'). '" alt="Cachify" />Cachify',
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
	* @change 1.1
	*
	* @param  string $anchor  Anker in der Hilfe
	*/
	
	function help_link($anchor) {
		echo sprintf(
			'<span>[<a href="http://playground.ebiene.de/2652/cachify-wordpress-cache/#%s" target="_blank">?</a>]</span>',
			$anchor
		);
	}


	/**
	* Registrierung der Settings
	*
	* @since   1.0
	* @change  1.0
	*/

	function register_settings()
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
	* @change  1.0
	*
	* @param   array  $data  Array mit Formularwerten
	* @return  array         Array mit geprüften Werten
	*/

	public static function validate_options($data)
	{
		/* Cache leeren */
		self::_flush_cache();
		
		return array(
			'only_guests'	 => (int)(!empty($data['only_guests'])),
			'compress_html'	 => (int)(!empty($data['compress_html'])),
			'cache_expires'	 => (int)(@$data['cache_expires']),
			'without_ids'	 => (string)sanitize_text_field(@$data['without_ids']),
			'without_agents' => (string)sanitize_text_field(@$data['without_agents'])
		);
	}


	/**
	* Darstellung der Optionsseite
	*
	* @since   1.0
	* @change  1.0
	*/

	public static function options_page()
	{ ?>
		<div class="wrap">
			<?php screen_icon('cachify') ?>

			<h2>
				Cachify
			</h2>

			<form method="post" action="options.php">
				<?php settings_fields('cachify') ?>

				<?php $options = get_option('cachify') ?>

				<table class="form-table cachify">
					<tr>
						<th>
							Cache-Gültigkeit in Stunden <?php self::help_link('cache_expires') ?>
						</th>
						<td>
							<input type="text" name="cachify[cache_expires]" value="<?php echo $options['cache_expires'] ?>" />
						</td>
					</tr>

					<tr>
						<th>
							Ausnahme für (Post/Pages) IDs <?php self::help_link('without_ids') ?>
						</th>
						<td>
							<input type="text" name="cachify[without_ids]" value="<?php echo $options['without_ids'] ?>" />
						</td>
					</tr>

					<tr>
						<th>
							Ausnahme für User Agents <?php self::help_link('without_agents') ?>
						</th>
						<td>
							<input type="text" name="cachify[without_agents]" value="<?php echo $options['without_agents'] ?>" />
						</td>
					</tr>
					
					<tr>
						<th>
							Komprimierung der Ausgabe <?php self::help_link('compress_html') ?>
						</th>
						<td>
							<input type="checkbox" name="cachify[compress_html]" value="1" <?php checked('1', $options['compress_html']); ?> />
						</td>
					</tr>

					<tr>
						<th>
							Nur für nicht eingeloggte Nutzer <?php self::help_link('only_guests') ?>
						</th>
						<td>
							<input type="checkbox" name="cachify[only_guests]" value="1" <?php checked('1', $options['only_guests']); ?> />
						</td>
					</tr>
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