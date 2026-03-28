<?php
if (!defined('ABSPATH')) exit;

add_action('wp_enqueue_scripts', 'nbp_enqueue_assets');
add_action('wp_footer', 'nbp_render_popups');

function nbp_enqueue_assets() {
    $popups = nbp_get_published_popups();
    if (empty($popups)) return;

    wp_enqueue_style('nbp-style', NBP_PLUGIN_URL . 'assets/pop-style.css', [], NBP_VERSION);
    wp_enqueue_script('nbp-script', NBP_PLUGIN_URL . 'assets/pop.js', [], NBP_VERSION, true);

    // Pass popup configs to JS (dates, triggers, page rules handled client-side for cache safety)
    $configs = [];
    foreach ($popups as $popup) {
        $id = $popup->ID;
        $configs[] = [
            'id'              => $id,
            'active'          => get_post_meta($id, '_nbp_active', true) === '1',
            'activateDate'    => get_post_meta($id, '_nbp_activate_date', true),
            'deactivateDate'  => get_post_meta($id, '_nbp_deactivate_date', true),
            'cookieDays'      => intval(get_post_meta($id, '_nbp_cookie_days', true) ?: 5),
            'pageRule'        => get_post_meta($id, '_nbp_page_rule', true) ?: 'all',
            'pages'           => get_post_meta($id, '_nbp_pages', true),
            'triggerType'     => get_post_meta($id, '_nbp_trigger_type', true) ?: 'timer',
            'triggerDelay'    => intval(get_post_meta($id, '_nbp_trigger_delay', true)),
            'triggerSelector' => get_post_meta($id, '_nbp_trigger_selector', true),
            'showAlways'      => get_post_meta($id, '_nbp_show_always', true) === '1',
            'debug'           => get_post_meta($id, '_nbp_debug', true) === '1',
        ];
    }

    wp_localize_script('nbp-script', 'nbpConfig', [
        'popups' => $configs,
    ]);
}

function nbp_get_published_popups() {
    return get_posts([
        'post_type'   => 'nbp_popup',
        'post_status' => 'publish',
        'numberposts' => -1,
        'orderby'     => 'date',
        'order'       => 'ASC',
    ]);
}

function nbp_render_popups() {
    $popups = nbp_get_published_popups();
    if (empty($popups)) return;

    foreach ($popups as $popup) {
        $id            = $popup->ID;
        $image         = get_post_meta($id, '_nbp_image', true);
        $headline      = get_post_meta($id, '_nbp_headline', true);
        $subheadline   = get_post_meta($id, '_nbp_subheadline', true);
        $text          = get_post_meta($id, '_nbp_text', true);
        $button_text   = get_post_meta($id, '_nbp_button_text', true);
        $button_url    = get_post_meta($id, '_nbp_button_url', true);
        $button_target = get_post_meta($id, '_nbp_button_target', true) ?: '_self';
        $custom_html   = get_post_meta($id, '_nbp_custom_html', true);

        // Per-element CSS
        $defaults = nbp_default_css($id);
        $css_overlay     = get_post_meta($id, '_nbp_css_overlay', true)     ?: $defaults['overlay'];
        $css_container   = get_post_meta($id, '_nbp_css_container', true)   ?: $defaults['container'];
        $css_close       = get_post_meta($id, '_nbp_css_close', true)       ?: $defaults['close'];
        $css_image       = get_post_meta($id, '_nbp_css_image', true)       ?: $defaults['image'];
        $css_headline    = get_post_meta($id, '_nbp_css_headline', true)    ?: $defaults['headline'];
        $css_subheadline = get_post_meta($id, '_nbp_css_subheadline', true) ?: $defaults['subheadline'];
        $css_text        = get_post_meta($id, '_nbp_css_text', true)        ?: $defaults['text'];
        $css_button      = get_post_meta($id, '_nbp_css_button', true)      ?: $defaults['button'];

        $s = '.nbp-popup-' . intval($id);
        $inline_css  = $s . ' { ' . wp_strip_all_tags($css_overlay) . ' } ';
        $inline_css .= $s . ' .nbp-popup-container { ' . wp_strip_all_tags($css_container) . ' } ';
        $inline_css .= $s . ' .nbp-close { ' . wp_strip_all_tags($css_close) . ' } ';
        $inline_css .= $s . ' .nbp-image img { ' . wp_strip_all_tags($css_image) . ' } ';
        $inline_css .= $s . ' .nbp-headline { ' . wp_strip_all_tags($css_headline) . ' } ';
        $inline_css .= $s . ' .nbp-subheadline { ' . wp_strip_all_tags($css_subheadline) . ' } ';
        $inline_css .= $s . ' .nbp-text { ' . wp_strip_all_tags($css_text) . ' } ';
        $inline_css .= $s . ' .btn { ' . wp_strip_all_tags($css_button) . ' } ';

        echo '<style>' . $inline_css . '</style>';
        ?>
        <div class="nbp-popup nbp-popup-<?php echo intval($id); ?>"
             data-popup-id="<?php echo intval($id); ?>"
             style="display:none;"
             role="dialog"
             aria-modal="true"
             aria-label="<?php echo esc_attr($headline ?: $popup->post_title); ?>">
            <div class="nbp-popup-container">
                <button type="button" class="nbp-close" aria-label="Fenster schließen">
                    <span class="nbp-close__icon" aria-hidden="true">&times;</span>
                    <span class="nbp-close__text">Close</span>
                </button>
                <div class="nbp-content">
                    <?php if ($image): ?>
                        <div class="nbp-image">
                            <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($headline ?: ''); ?>">
                        </div>
                    <?php endif; ?>

                    <?php if ($headline): ?>
                        <h2 class="nbp-headline"><?php echo esc_html($headline); ?></h2>
                    <?php endif; ?>

                    <?php if ($subheadline): ?>
                        <h3 class="nbp-subheadline"><?php echo esc_html($subheadline); ?></h3>
                    <?php endif; ?>

                    <?php if ($text): ?>
                        <div class="nbp-text"><?php echo wp_kses_post($text); ?></div>
                    <?php endif; ?>

                    <?php if ($button_text && $button_url): ?>
                        <div class="nbp-button-wrap">
                            <a href="<?php echo esc_url($button_url); ?>"
                               class="btn"
                               target="<?php echo esc_attr($button_target); ?>"
                               <?php echo $button_target === '_blank' ? 'rel="noopener noreferrer"' : ''; ?>>
                                <?php echo esc_html($button_text); ?>
                            </a>
                        </div>
                    <?php endif; ?>

                    <?php if ($custom_html): ?>
                        <div class="nbp-custom"><?php echo wp_kses_post($custom_html); ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }
}
