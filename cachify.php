<?php
/*
Plugin Name: Cachify
Description: Smarter Cache für WordPress. Reduziert die Anzahl der Datenbankabfragen und dynamischer Anweisungen. Minimiert Ladezeiten der Blogseiten.
Author: Sergej M&uuml;ller
Author URI: http://wpseo.de
Plugin URI: http://playground.ebiene.de/2652/cachify-wordpress-cache/
Version: 1.2.1
*/


if ( !class_exists('WP') ) {
header('Status: 403 Forbidden');
header('HTTP/1.1 403 Forbidden');
exit();
}
final class Cachify {
private static $base;
public static function init()
{
if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
return;
}
self::$base = plugin_basename(__FILE__);
add_action(
'publish_post',
array(
__CLASS__,
'publish_post'
)
);
add_action(
'publish_page',
array(
__CLASS__,
'publish_page'
)
);
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
public static function install()
{
global $wpdb;
if ( is_multisite() && !empty($_GET['networkwide']) ) {
$ids = self::_get_blog_ids();
foreach ($ids as $id) {
switch_to_blog( (int)$id );
self::_install_backend();
}
restore_current_blog();
} else {
self::_install_backend();
}
}
public static function install_later($id) {
if ( !is_plugin_active_for_network(self::$base) ) {
return;
}
switch_to_blog( (int)$id );
self::_install_backend();
restore_current_blog();
}
private static function _install_backend()
{
add_option(
'cachify',
array(
'only_guests'=> 1,
'compress_html'=> 0,
'cache_expires'=> 12,
'without_ids'=> '',
'without_agents' => ''
),
'',
'no'
);
self::flush_cache();
}
public static function uninstall()
{
global $wpdb;
if ( is_multisite() && !empty($_GET['networkwide']) ) {
$old = $wpdb->blogid;
$ids = self::_get_blog_ids();
foreach ($ids as $id) {
switch_to_blog($id);
self::_uninstall_backend();
}
switch_to_blog($old);
} else {
self::_uninstall_backend();
}
}
public static function uninstall_later($id) {
global $wpdb;
if ( !is_plugin_active_for_network(self::$base) ) {
return;
}
switch_to_blog( (int)$id );
self::_uninstall_backend();
restore_current_blog();
}
private static function _uninstall_backend()
{
delete_option('cachify');
self::flush_cache();
}
public static function update()
{
self::_update_backend();
}
private static function _update_backend()
{
self::flush_cache();
}
private static function _get_blog_ids()
{
global $wpdb;
return $wpdb->get_col(
$wpdb->prepare("SELECT blog_id FROM `$wpdb->blogs`")
);
}
public static function action_links($data)
{
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
public static function add_menu( $wp_admin_bar ) {
if ( !function_exists('is_admin_bar_showing') or !is_admin_bar_showing() or !is_super_admin() ) {
return;
}
$wp_admin_bar->add_menu(
array(
'id'=> 'cachify',
'title' => '<span class="ab-icon"></span><span class="ab-label">Cache leeren</span>',
'href'=> add_query_arg('_cachify', 'flush')
)
);
}
public static function receive_flush($data)
{
if ( empty($_GET['_cachify']) or $_GET['_cachify'] !== 'flush' ) {
return;
}
global $wpdb;
if ( is_multisite() && is_plugin_active_for_network(self::$base) ) {
$old = $wpdb->blogid;
$ids = self::_get_blog_ids();
foreach ($ids as $id) {
switch_to_blog($id);
self::flush_cache();
}
switch_to_blog($old);
add_action(
'network_admin_notices',
array(
__CLASS__,
'flush_notice'
)
);
} else {
self::flush_cache();
add_action(
'admin_notices',
array(
__CLASS__,
'flush_notice'
)
);
}
}
public static function flush_notice() {
if ( !is_super_admin() ) {
return false;
}
echo '<div id="message" class="updated"><p><strong>Cachify-Cache geleert.</strong></p></div>';
}
public static function edit_comment($id)
{
self::_delete_cache(
get_permalink(
get_comment($id)->comment_post_ID
)
);
}
public static function add_comment($comment)
{
self::_delete_cache(
get_permalink($comment['comment_post_ID'])
);
return $comment;
}
public static function touch_comment($new_status, $old_status, $comment)
{
if ( $new_status != $old_status ) {
self::_delete_cache(
get_permalink($comment->comment_post_ID)
);
}
}
public static function publish_post($id)
{
$post = get_post($id);
if ( in_array( $post->post_status, array('publish', 'future') ) ) {
self::flush_cache();
}
}
public static function publish_page($id)
{
$page = get_page($id);
if ( $page->post_status == 'publish' ) {
self::flush_cache();
}
}
private static function _cache_hash($url = '')
{
if ( empty($url) ) {
$url = esc_url_raw(
sprintf(
'%s://%s%s',
( is_ssl() ? 'https' : 'http' ),
$_SERVER['HTTP_HOST'],
$_SERVER['REQUEST_URI']
)
);
}
return 'cachify_' .md5($url);
}
private static function _page_queries()
{
return $GLOBALS['wpdb']->num_queries;
}
private static function _page_timer()
{
return timer_stop(0, 2);
}
private static function _memory_usage()
{
return ( function_exists('memory_get_usage') ? size_format(memory_get_usage(), 2) : 0 );
}
private static function _preg_split($input)
{
return (array)preg_split('/,/', $input, -1, PREG_SPLIT_NO_EMPTY);
}
private static function _is_index()
{
return basename($_SERVER['SCRIPT_NAME']) != 'index.php';
}
private static function _is_mobile()
{
return ( strpos(TEMPLATEPATH, 'wptouch') or strpos(TEMPLATEPATH, 'carrington') );
}
private static function _skip_cache()
{
$options = get_option('cachify');
if ( self::_is_index() or is_feed() or is_trackback() or is_robots() or is_preview() or post_password_required() or ( $options['only_guests'] && is_user_logged_in() ) ) {
return true;
}
if ( self::_is_mobile() ) {
return true;
}
if ( $options['without_ids'] && is_singular() ) {
if ( in_array( $GLOBALS['wp_query']->get_queried_object_id(), self::_preg_split($options['without_ids']) ) ) {
return true;
}
}
if ( $options['without_agents'] && isset($_SERVER['HTTP_USER_AGENT']) ) {
if ( array_filter( self::_preg_split($options['without_agents']), create_function('$a', 'return strpos($_SERVER["HTTP_USER_AGENT"], $a);') ) ) {
return true;
}
}
return false;
}
private static function _sanitize_cache($data) {
$options = get_option('cachify');
if ( !$options['compress_html'] ) {
return($data);
}
$cleaned = preg_replace(
array(
'/<!--[^\[](.|\s)*?-->/s',
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
'> <'
),
(string)$data
);
if ( strlen($cleaned) <= 1 ) {
return($data);
}
return($cleaned);
}
private static function _delete_cache($url)
{
delete_transient(
self::_cache_hash($url)
);
}
public static function flush_cache()
{
$GLOBALS['wpdb']->query("DELETE FROM `" .$GLOBALS['wpdb']->options. "` WHERE `option_name` LIKE ('_transient%_cachify_%')");
$GLOBALS['wpdb']->query("OPTIMIZE TABLE `" .$GLOBALS['wpdb']->options. "`");
}
public static function set_cache($data)
{
$options = get_option('cachify');
if ( !empty($data) ) {
set_transient(
self::_cache_hash(),
array(
'data'=> self::_sanitize_cache($data),
'queries'=> self::_page_queries(),
'timer'=> self::_page_timer(),
'memory'=> self::_memory_usage(),
'time'=> current_time('timestamp')
),
60 * 60 * (int)$options['cache_expires']
);
}
return $data;
}
public static function manage_cache()
{
if ( self::_skip_cache() ) {
return;
}
if ( $cache = get_transient(self::_cache_hash()) ) {
if ( !empty($cache['data']) ) {
echo $cache['data'];
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
ob_start('Cachify::set_cache');
}
function add_css()
{
$data = get_plugin_data(__FILE__);
wp_register_style(
'cachify_css',
plugins_url('css/style.css', __FILE__),
array(),
$data['Version']
);
wp_enqueue_style('cachify_css');
}
function add_page()
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
function help_link($anchor) {
echo sprintf(
'<span>[<a href="http://playground.ebiene.de/2652/cachify-wordpress-cache/#%s" target="_blank">?</a>]</span>',
$anchor
);
}
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
public static function validate_options($data)
{
self::flush_cache();
return array(
'only_guests'=> (int)(!empty($data['only_guests'])),
'compress_html'=> (int)(!empty($data['compress_html'])),
'cache_expires'=> (int)(@$data['cache_expires']),
'without_ids'=> (string)sanitize_text_field(@$data['without_ids']),
'without_agents' => (string)sanitize_text_field(@$data['without_agents'])
);
}
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
add_action(
'plugins_loaded',
array(
'Cachify',
'init'
),
99
);
register_activation_hook(
__FILE__,
array(
'Cachify',
'install'
)
);
register_uninstall_hook(
__FILE__,
array(
'Cachify',
'uninstall'
)
);
if ( function_exists('register_update_hook') ) {
register_update_hook(
__FILE__,
array(
'Cachify',
'update'
)
);
}