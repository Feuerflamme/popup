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
        $stoerer_text  = get_post_meta($id, '_nbp_stoerer_text', true);

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
        $css_stoerer     = get_post_meta($id, '_nbp_css_stoerer', true)     ?: $defaults['stoerer'];

        $s = '.nbp-popup-' . intval($id);
        $inline_css  = $s . ' { ' . wp_strip_all_tags($css_overlay) . ' } ';
        $inline_css .= $s . ' .container { ' . wp_strip_all_tags($css_container) . ' } ';
        $inline_css .= $s . ' .close { ' . wp_strip_all_tags($css_close) . ' } ';
        $inline_css .= $s . ' .nbp-image img { ' . wp_strip_all_tags($css_image) . ' } ';
        $inline_css .= $s . ' .headline-2 { ' . wp_strip_all_tags($css_headline) . ' } ';
        $inline_css .= $s . ' .nbp-subheadline { ' . wp_strip_all_tags($css_subheadline) . ' } ';
        $inline_css .= $s . ' .text { ' . wp_strip_all_tags($css_text) . ' } ';
        $inline_css .= $s . ' .button.btn { ' . wp_strip_all_tags($css_button) . ' } ';
        $inline_css .= $s . ' .stoerer { ' . wp_strip_all_tags($css_stoerer) . ' } ';

        echo '<style>' . $inline_css . '</style>';
        ?>
        <div class="nbp-popup nbp-popup-<?php echo intval($id); ?>"
             data-popup-id="<?php echo intval($id); ?>"
             style="display:none;"
             role="dialog"
             aria-modal="true"
             aria-label="<?php echo esc_attr($headline ?: $popup->post_title); ?>">
            <div class="container">
                <button type="button" class="close" aria-label="Fenster schließen" tabindex="0">X</button>

                <?php if ($image): ?>
                    <div class="nbp-image">
                        <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($headline ?: ''); ?>">
                    </div>
                <?php endif; ?>

                <?php if ($headline): ?>
                    <h2 class="headline-2"><?php echo wp_kses_post($headline); ?></h2>
                <?php endif; ?>

                <?php if ($subheadline): ?>
                    <h3 class="nbp-subheadline"><?php echo wp_kses_post($subheadline); ?></h3>
                <?php endif; ?>

                <?php if ($text): ?>
                    <div class="text"><?php echo wp_kses_post($text); ?></div>
                <?php endif; ?>

                <?php if ($stoerer_text): ?>
                    <div class="stoerer" aria-label="Störer"><?php echo wp_kses_post($stoerer_text); ?></div>
                <?php endif; ?>

                <?php if ($custom_html): ?>
                    <div class="nbp-custom"><?php echo wp_kses_post($custom_html); ?></div>
                <?php endif; ?>

                <?php if ($button_text && $button_url): ?>
                    <a href="<?php echo esc_url($button_url); ?>"
                       class="button btn"
                       target="<?php echo esc_attr($button_target); ?>"
                       <?php echo $button_target === '_blank' ? 'rel="noopener noreferrer"' : ''; ?>
                       tabindex="0">
                        <?php echo esc_html($button_text); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}
