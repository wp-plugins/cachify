<?php
/*
Plugin Name: Cachify
Description: Smarter Cache für WordPress. Reduziert die Anzahl der Datenbankabfragen und dynamischer Anweisungen. Minimiert die Ladezeit der Blogseiten.
Author: Sergej M&uuml;ller
Author URI: http://www.wpSEO.de
Plugin URI: http://playground.ebiene.de/2652/cachify-wordpress-cache/
Version: 0.9.1
*/


/* Secure */
if (!function_exists ('is_admin')) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit();
}


/**
* Cachify
*
* @since  0.1
*/

final class Cachify {


	/**
	* Cache-Gültigkeit in Stunden
	*
	* @since  0.5
	*/

	const CACHE_EXPIRES = 1;


	/**
	* Cache auch für eingeloggte Nutzer (0 = JA)
	*
	* @since  0.6
	*/

	const ONLY_GUESTS = 1;
	
	
	/**
	* Ausnahme für (Post)IDs (bspw. '1, 2, 3')
	*
	* @since  0.8
	*/
	
	const WITHOUT_IDS = '';
	
	
	/**
	* Ausnahme für UserAgents (bspw. 'MSIE 6, Safari')
	*
	* @since  0.8
	*/
	
	const WITHOUT_USER_AGENTS = '';


	/**
	* Konstruktor der Klasse
	*
	* @since   0.1
	* @change  0.9.1
	*/

  public function __construct()
  {
  	/* Autosave? */
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
			return;
		}
		
		/* Publish post */
		add_action(
			'publish_post',
			'Cachify::publish_post'
		);
		
  	/* Backend */
  	if ( is_admin() ) {
			add_action(
				'transition_comment_status',
				'Cachify::touch_comment',
				10,
				3
			);
			add_action(
				'edit_comment',
				'Cachify::edit_comment'
			);

			add_action(
				'pre_current_active_plugins',
				'Cachify::plugin_action'
			);
			add_filter(
				'plugin_row_meta',
				'Cachify::plugin_meta',
				10,
				2
			);

  	/* Frontend */
  	} else {
  		add_action(
				'preprocess_comment',
				'Cachify::add_comment',
				1
			);
			
			if ( self::WITHOUT_IDS ) {
	  		add_action(
					'template_redirect',
					'Cachify::manage_cache'
				);
			} else {
				add_action(
					'init',
					'Cachify::manage_cache',
					999
				);
			}
  	}
	}


	/**
	* Meta-Links zum Plugin
	*
	* @since   0.5
	* @change  0.5
	*
	* @param   array   $data  Bereits vorhandene Links
	* @param   string  $page  Aktuelle Seite
	* @return  array   $data  Modifizierte Links
	*/

	public static function plugin_meta($data, $page)
  {
		if ( $page == plugin_basename(__FILE__) ) {
			$data = array_merge(
				$data,
				array(
					sprintf(
						'<a href="%s">%s</a>',
						add_query_arg('_cachify', 'reset', admin_url('plugins.php')),
						esc_html__('Cache leeren', 'cachify')
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
	* @change  0.5
	*
	* @param   array  $data  Metadaten der Plugins
	*/

	public static function plugin_action($data)
  {
  	if ( !empty($_GET['_cachify']) && $_GET['_cachify'] == 'reset' ) {
  		self::flush_cache();
  	}
  }


	/**
	* Löschung des Cache bei neuem Kommentar
	*
	* @since   0.1
	* @change  0.4
	*
	* @param	 integer  $id  ID des Kommentars
	*/

	public static function edit_comment($id)
  {
		self::delete_cache(
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
		self::delete_cache(
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
			self::delete_cache(
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
			self::flush_cache();
  	}
  }


	/**
	* Rückgabe des Cache-Hash-Wertes
	*
	* @since   0.1
	* @change  0.1
	*
	* @param   string  $url  URL für den Hash-Wert [optional]
	* @return	 string        Cachify-Hash-Wert
	*/

  private static function cache_hash($url = '')
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
	* @change  0.1
	*
	* @return	 intval  Query-Anzahl
	*/

	private static function page_queries()
	{
		return $GLOBALS['wpdb']->num_queries;
	}


	/**
	* Rückgabe der Ausführungszeit
	*
	* @since   0.1
	* @change  0.1
	*
	* @return	 intval  Anzahl der Sekunden
	*/

	private static function page_timer()
	{
		return timer_stop(0, 2);
	}
	
	
	/**
	* Rückgabe des Speicherverbrauchs
	*
	* @since   0.7
	* @change  0.7
	*
	* @return  string  Konvertierter Größenwert
	*/

	private static function memory_usage()
	{
		return ( function_exists('memory_get_usage') ? size_format(memory_get_usage(), 2) : 0 );
	}
	
	
	/**
	* Splittung nach Komma
	*
	* @since   0.9.1
	* @change  0.9.1
	*
	* @param   string  Zu splittende Zeichenkette
	* @return  array   Konvertierter Array
	*/
	
	private static function preg_split($input)
	{
		return (array)preg_split('/,/', $input, -1, PREG_SPLIT_NO_EMPTY);
	}


	/**
	* Prüfung auf Feed
	*
	* @since   0.6
	* @change  0.6
	*
	* @return	 intval  Anzahl der Übereinstimmungen
	*/

	private static function is_feed()
	{
		return preg_match('#(?:/feed/?$|[?&]feed=)#', $_SERVER['REQUEST_URI']);
	}


	/**
	* Prüfung auf Preview
	*
	* @since   0.6
	* @change  0.6
	*
	* @return	 boolean  TRUE bei Preview
	*/

	private static function is_preview()
	{
		return ( is_user_logged_in() && !empty($_GET['preview']) && $_GET['preview'] == 'true' );
	}


	/**
	* Prüfung auf Index
	*
	* @since   0.6
	* @change  0.6
	*
	* @return	 boolean  TRUE bei Index
	*/

	private static function is_index()
	{
		return basename($_SERVER['SCRIPT_NAME']) != 'index.php';
	}
	
	
	/**
	* Prüfung auf Mobile Devices
	*
	* @since   0.9.1
	* @change  0.9.1
	*
	* @return	 boolean  TRUE bei Mobile
	*/

	private static function is_mobile()
	{
		return ( strpos(TEMPLATEPATH, 'wptouch') or strpos(TEMPLATEPATH, 'carrington') );
	}


	/**
	* Definition der Ausnahmen für den Cache
	*
	* @since   0.2
	* @change  0.8
	*
	* @param   string   $type  Typ der Abfrage [optional]
	* @return	 boolean         TRUE bei Ausnahmen
	*/

	private static function skip_cache()
	{
		/* Filter */
  	if ( self::is_index() or self::is_feed() or self::is_preview() or ( self::ONLY_GUESTS && is_user_logged_in() ) ) {
  		return true;
  	}
  	
  	/* WP Touch */
  	if ( self::is_mobile() ) {
  		return true;
  	}
  	
  	/* Post IDs */
 		if ( self::WITHOUT_IDS && is_singular() ) {
 			if ( in_array( $GLOBALS['wp_query']->get_queried_object_id(), self::preg_split(self::WITHOUT_IDS) ) ) {
 				return true;
 			}
  	}
  	
  	/* User Agents */
  	if ( self::WITHOUT_USER_AGENTS && isset($_SERVER['HTTP_USER_AGENT']) ) {
  		if ( array_filter( self::preg_split(self::WITHOUT_USER_AGENTS), create_function('$a', 'return strpos($_SERVER["HTTP_USER_AGENT"], $a);') ) ) {
 				return true;
  		}
  	}

  	return false;
	}


	/**
	* Löschung des Cache für eine URL
	*
	* @since   0.1
	* @change  0.1
	*
	* @param   string  $url  URL für den Hash-Wert
	*/

	public static function delete_cache($url)
	{
		delete_transient(
			self::cache_hash($url)
		);
	}


	/**
	* Zurücksetzen des kompletten Cache
	*
	* @since   0.1
	* @change  0.1
	*/

	public static function flush_cache()
	{
		$GLOBALS['wpdb']->query("DELETE FROM `" .$GLOBALS['wpdb']->options. "` WHERE `option_name` LIKE ('_transient%_cachify_%')");
		$GLOBALS['wpdb']->query("OPTIMIZE TABLE `" .$GLOBALS['wpdb']->options. "`");
	}


	/**
	* Zuweisung des Cache
	*
	* @since   0.1
	* @change  0.9
	*
	* @param   string  $data  Inhalt der Seite
	* @return	 string  $data  Inhalt der Seite
	*/

	public static function set_cache($data)
	{
		if ( !empty($data) ) {
			set_transient(
				self::cache_hash(),
				array(
					'data' 		=> $data,
					'queries' => self::page_queries(),
					'timer' 	=> self::page_timer(),
					'memory'	=> self::memory_usage(),
					'time'		=> current_time('timestamp')
				),
				60 * 60 * self::CACHE_EXPIRES
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
		if ( self::skip_cache() ) {
			return;
		}

  	/* Im Cache? */
  	if ( $cache = get_transient(self::cache_hash()) ) {
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
	 					'Mit Cachify:   %d DB-Anfragen, %s Sekunden, %s',
	 					self::page_queries(),
	  				self::page_timer(),
	  				self::memory_usage()
	 				),
	 				sprintf(
	 					'Generiert:     %s zuvor',
	 					human_time_diff($cache['time'], current_time('timestamp'))
	 				)
	  		);
	
	  		exit;
  		}
  	}

  	/* Cachen */
  	ob_start('Cachify::set_cache');
	}
}

new Cachify();