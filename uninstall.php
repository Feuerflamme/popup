<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}

// Alle Popups und zugehörige Meta-Daten löschen
$popups = get_posts([
	'post_type'   => 'nbp_popup',
	'post_status' => 'any',
	'numberposts' => -1,
	'fields'      => 'ids',
]);

foreach ($popups as $popup_id) {
	wp_delete_post($popup_id, true);
}