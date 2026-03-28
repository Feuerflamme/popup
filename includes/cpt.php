<?php
if (!defined('ABSPATH')) exit;

add_action('init', 'nbp_register_post_type');

function nbp_register_post_type() {
    $labels = [
        'name'               => 'Popups',
        'singular_name'      => 'Popup',
        'add_new'            => 'Neues Popup',
        'add_new_item'       => 'Neues Popup erstellen',
        'edit_item'          => 'Popup bearbeiten',
        'new_item'           => 'Neues Popup',
        'view_item'          => 'Popup ansehen',
        'search_items'       => 'Popups suchen',
        'not_found'          => 'Keine Popups gefunden',
        'not_found_in_trash' => 'Keine Popups im Papierkorb',
        'menu_name'          => 'No Bloat Popups',
    ];

    register_post_type('nbp_popup', [
        'labels'       => $labels,
        'public'       => false,
        'show_ui'      => true,
        'show_in_menu' => true,
        'menu_icon'    => 'dashicons-format-chat',
        'supports'     => ['title'],
        'has_archive'  => false,
        'rewrite'      => false,
    ]);
}
