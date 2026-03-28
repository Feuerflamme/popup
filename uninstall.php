<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}

$sliders = get_posts([
	'post_type' => 'tp_slider',
	'post_status' => 'any',
	'numberposts' => -1,
	'fields' => 'ids'
]);

foreach ($sliders as $slider_id) {
	delete_post_meta($slider_id, '_tp_slider_images');
	delete_post_meta($slider_id, '_tp_slider_bullets');
}