<?php
if (
	extension_loaded('apc')
	&& ( !empty($_SERVER['PHP_SELF']) && strpos($_SERVER['PHP_SELF'], '/wp-admin/') === false )
	&& ( !empty($_SERVER['HTTP_ACCEPT_ENCODING']) && strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false )
	&& ( $cache = apc_fetch('cachify_' .md5($_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'])) )
) {
	header('Vary: Accept-Encoding');
	header('X-Powered-By: Cachify');
	header('Content-Encoding: gzip');
	header('Content-Length: '.strlen($cache)); 

    echo $cache;
    exit;
}