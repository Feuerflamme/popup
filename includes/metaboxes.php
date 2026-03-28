<?php
if (!defined('ABSPATH')) exit;

add_action('add_meta_boxes', 'nbp_add_meta_boxes');
add_action('save_post_nbp_popup', 'nbp_save_meta', 10, 2);
add_action('admin_enqueue_scripts', 'nbp_admin_assets');

function nbp_admin_assets($hook) {
    global $post_type;
    if ($post_type !== 'nbp_popup') return;

    wp_enqueue_media();
    wp_enqueue_style('nbp-admin', NBP_PLUGIN_URL . 'assets/admin-style.css', [], NBP_VERSION);
}

function nbp_add_meta_boxes() {
    add_meta_box('nbp_settings', 'Popup Einstellungen', 'nbp_settings_callback', 'nbp_popup', 'normal', 'high');
    add_meta_box('nbp_content', 'Popup Inhalt', 'nbp_content_callback', 'nbp_popup', 'normal', 'high');
    add_meta_box('nbp_display', 'Anzeige-Regeln', 'nbp_display_callback', 'nbp_popup', 'normal', 'default');
    add_meta_box('nbp_trigger', 'Trigger / Auslöser', 'nbp_trigger_callback', 'nbp_popup', 'normal', 'default');
    add_meta_box('nbp_advanced', 'Erweitert', 'nbp_advanced_callback', 'nbp_popup', 'normal', 'default');
}

function nbp_get_meta($post_id, $key, $default = '') {
    $val = get_post_meta($post_id, '_nbp_' . $key, true);
    return $val !== '' ? $val : $default;
}

/* ── Default CSS Values ── */
function nbp_default_css($popup_id = 0) {
    $s = '.nbp-popup-' . intval($popup_id);
    return [
        'overlay' =>
"background-color: rgba(0, 0, 0, 0.55);
padding: 20px;",

        'container' =>
"background-color: #fff;
border-radius: 12px;
max-width: 560px;
box-shadow: 0 24px 48px rgba(0, 0, 0, 0.2);",

        'close' =>
"color: #555;
background: rgba(0, 0, 0, 0.05);",

        'image' =>
"width: 100%;
height: auto;
object-fit: contain;",

        'headline' =>
"color: #111;
font-size: 22px;
line-height: 1.3;
font-weight: 700;
padding: 28px 28px 0 28px;
margin: 0 0 8px 0;",

        'subheadline' =>
"color: #666;
font-size: 15px;
line-height: 1.5;
font-weight: 400;
padding: 0 28px;
margin: 0 0 16px 0;",

        'text' =>
"font-size: 15px;
line-height: 1.65;
color: #444;
padding: 0 28px;",

        'button' =>
"display: block;
width: 100%;
text-align: center;",
    ];
}

/* ── Settings Meta Box ── */
function nbp_settings_callback($post) {
    wp_nonce_field('nbp_save', 'nbp_nonce');

    $active          = nbp_get_meta($post->ID, 'active', '0');
    $activate_date   = nbp_get_meta($post->ID, 'activate_date');
    $deactivate_date = nbp_get_meta($post->ID, 'deactivate_date');
    $cookie_days     = nbp_get_meta($post->ID, 'cookie_days', '5');
    $show_always     = nbp_get_meta($post->ID, 'show_always', '0');
    ?>
    <table class="form-table nbp-form-table">
        <tr>
            <th>Popup ID</th>
            <td><code style="font-size:14px; padding:4px 10px; background:#f0f0f1; border-radius:3px;"><?php echo intval($post->ID); ?></code> &nbsp; CSS-Selektor: <code>.nbp-popup-<?php echo intval($post->ID); ?></code></td>
        </tr>
        <tr>
            <th><label for="nbp_active">Popup aktiv</label></th>
            <td>
                <label class="nbp-toggle">
                    <input type="checkbox" id="nbp_active" name="nbp_active" value="1" <?php checked($active, '1'); ?>>
                    <span>Popup ist aktiviert</span>
                </label>
                <p class="description">Aktiv bedeutet nicht automatisch sichtbar &ndash; das Popup benötigt zusätzlich einen Trigger (siehe Trigger-Einstellungen).</p>
            </td>
        </tr>
        <tr>
            <th><label for="nbp_activate_date">Automatisch aktivieren am</label></th>
            <td>
                <input type="datetime-local" id="nbp_activate_date" name="nbp_activate_date" value="<?php echo esc_attr($activate_date); ?>">
                <p class="description">Popup wird ab diesem Zeitpunkt automatisch aktiv (optional).</p>
            </td>
        </tr>
        <tr>
            <th><label for="nbp_deactivate_date">Automatisch deaktivieren am</label></th>
            <td>
                <input type="datetime-local" id="nbp_deactivate_date" name="nbp_deactivate_date" value="<?php echo esc_attr($deactivate_date); ?>">
                <p class="description">Popup wird ab diesem Zeitpunkt automatisch deaktiviert (optional).</p>
            </td>
        </tr>
        <tr>
            <th><label for="nbp_show_always">Bei jedem Besuch anzeigen</label></th>
            <td>
                <label>
                    <input type="checkbox" id="nbp_show_always" name="nbp_show_always" value="1" <?php checked($show_always, '1'); ?>>
                    Popup bei jedem Seitenaufruf erneut anzeigen (kein Cookie)
                </label>
                <p class="description">Wenn aktiv, wird kein Cookie gesetzt und das Popup erscheint bei jedem Reload.</p>
            </td>
        </tr>
        <tr>
            <th><label for="nbp_cookie_days">Cookie-Dauer (Tage)</label></th>
            <td>
                <input type="number" id="nbp_cookie_days" name="nbp_cookie_days" value="<?php echo esc_attr($cookie_days); ?>" min="0" step="1">
                <p class="description">Wie lange das Cookie gespeichert wird (0 = Session-Cookie, wird beim Schließen des Browsers gelöscht). Gilt nur für Timer-Trigger wenn &quot;Bei jedem Besuch anzeigen&quot; deaktiviert ist.</p>
            </td>
        </tr>
    </table>
    <?php
}

/* ── Content Meta Box ── */
function nbp_content_callback($post) {
    $image         = nbp_get_meta($post->ID, 'image');
    $headline      = nbp_get_meta($post->ID, 'headline');
    $subheadline   = nbp_get_meta($post->ID, 'subheadline');
    $text          = nbp_get_meta($post->ID, 'text');
    $button_text   = nbp_get_meta($post->ID, 'button_text');
    $button_url    = nbp_get_meta($post->ID, 'button_url');
    $button_target = nbp_get_meta($post->ID, 'button_target', '_self');
    $custom_html   = nbp_get_meta($post->ID, 'custom_html');

    $pid = intval($post->ID);

    // Default CSS per element
    $defaults = nbp_default_css($pid);

    $css_overlay   = nbp_get_meta($post->ID, 'css_overlay',   $defaults['overlay']);
    $css_container = nbp_get_meta($post->ID, 'css_container', $defaults['container']);
    $css_image     = nbp_get_meta($post->ID, 'css_image',     $defaults['image']);
    $css_headline  = nbp_get_meta($post->ID, 'css_headline',  $defaults['headline']);
    $css_subheadline = nbp_get_meta($post->ID, 'css_subheadline', $defaults['subheadline']);
    $css_text      = nbp_get_meta($post->ID, 'css_text',      $defaults['text']);
    $css_button    = nbp_get_meta($post->ID, 'css_button',    $defaults['button']);
    $css_close     = nbp_get_meta($post->ID, 'css_close',     $defaults['close']);
    ?>
    <table class="form-table nbp-form-table">
        <tr>
            <th><label for="nbp_css_overlay">Overlay CSS</label></th>
            <td>
                <textarea id="nbp_css_overlay" name="nbp_css_overlay" rows="4" class="large-text code"><?php echo esc_textarea($css_overlay); ?></textarea>
                <p class="description">CSS für den Overlay-Hintergrund <code>.nbp-popup-<?php echo $pid; ?></code></p>
            </td>
        </tr>
        <tr>
            <th><label for="nbp_css_container">Container CSS</label></th>
            <td>
                <textarea id="nbp_css_container" name="nbp_css_container" rows="4" class="large-text code"><?php echo esc_textarea($css_container); ?></textarea>
                <p class="description">CSS für die Popup-Karte <code>.nbp-popup-<?php echo $pid; ?> .nbp-popup-container</code></p>
            </td>
        </tr>
        <tr>
            <th><label for="nbp_css_close">Close-Button CSS</label></th>
            <td>
                <textarea id="nbp_css_close" name="nbp_css_close" rows="3" class="large-text code"><?php echo esc_textarea($css_close); ?></textarea>
                <p class="description">CSS für den Schließen-Button <code>.nbp-popup-<?php echo $pid; ?> .nbp-close</code></p>
            </td>
        </tr>
        <tr>
            <th><label>Bild</label></th>
            <td>
                <div class="nbp-image-upload">
                    <input type="hidden" id="nbp_image" name="nbp_image" value="<?php echo esc_attr($image); ?>">
                    <div id="nbp_image_preview">
                        <?php if ($image): ?>
                            <img src="<?php echo esc_url($image); ?>" style="max-width: 300px; height: auto;">
                        <?php endif; ?>
                    </div>
                    <div>
                        <button type="button" class="button" id="nbp_image_upload_btn">Bild auswählen</button>
                        <button type="button" class="button" id="nbp_image_remove_btn" <?php echo $image ? '' : 'style="display:none"'; ?>>Bild entfernen</button>
                    </div>
                </div>
            </td>
        </tr>
        <tr>
            <th><label for="nbp_css_image">Bild CSS</label></th>
            <td>
                <textarea id="nbp_css_image" name="nbp_css_image" rows="3" class="large-text code"><?php echo esc_textarea($css_image); ?></textarea>
                <p class="description">CSS für <code>.nbp-popup-<?php echo $pid; ?> .nbp-image img</code></p>
            </td>
        </tr>
        <tr>
            <th><label for="nbp_headline">Headline</label></th>
            <td><input type="text" id="nbp_headline" name="nbp_headline" value="<?php echo esc_attr($headline); ?>" class="large-text"></td>
        </tr>
        <tr>
            <th><label for="nbp_css_headline">Headline CSS</label></th>
            <td>
                <textarea id="nbp_css_headline" name="nbp_css_headline" rows="3" class="large-text code"><?php echo esc_textarea($css_headline); ?></textarea>
                <p class="description">CSS für <code>.nbp-popup-<?php echo $pid; ?> .nbp-headline</code></p>
            </td>
        </tr>
        <tr>
            <th><label for="nbp_subheadline">Subheadline</label></th>
            <td><input type="text" id="nbp_subheadline" name="nbp_subheadline" value="<?php echo esc_attr($subheadline); ?>" class="large-text"></td>
        </tr>
        <tr>
            <th><label for="nbp_css_subheadline">Subheadline CSS</label></th>
            <td>
                <textarea id="nbp_css_subheadline" name="nbp_css_subheadline" rows="3" class="large-text code"><?php echo esc_textarea($css_subheadline); ?></textarea>
                <p class="description">CSS für <code>.nbp-popup-<?php echo $pid; ?> .nbp-subheadline</code></p>
            </td>
        </tr>
        <tr>
            <th><label for="nbp_text">Text</label></th>
            <td>
                <?php wp_editor($text, 'nbp_text', [
                    'textarea_name' => 'nbp_text',
                    'textarea_rows' => 6,
                    'media_buttons' => false,
                    'teeny'         => true,
                ]); ?>
            </td>
        </tr>
        <tr>
            <th><label for="nbp_css_text">Text CSS</label></th>
            <td>
                <textarea id="nbp_css_text" name="nbp_css_text" rows="3" class="large-text code"><?php echo esc_textarea($css_text); ?></textarea>
                <p class="description">CSS für <code>.nbp-popup-<?php echo $pid; ?> .nbp-text</code></p>
            </td>
        </tr>
        <tr>
            <th><label for="nbp_button_text">Button Text</label></th>
            <td><input type="text" id="nbp_button_text" name="nbp_button_text" value="<?php echo esc_attr($button_text); ?>" class="regular-text"></td>
        </tr>
        <tr>
            <th><label for="nbp_button_url">Button URL</label></th>
            <td><input type="url" id="nbp_button_url" name="nbp_button_url" value="<?php echo esc_url($button_url); ?>" class="regular-text"></td>
        </tr>
        <tr>
            <th><label for="nbp_button_target">Button Ziel</label></th>
            <td>
                <select id="nbp_button_target" name="nbp_button_target">
                    <option value="_self" <?php selected($button_target, '_self'); ?>>Gleiches Fenster</option>
                    <option value="_blank" <?php selected($button_target, '_blank'); ?>>Neues Fenster</option>
                </select>
            </td>
        </tr>
        <tr>
            <th><label for="nbp_css_button">Button CSS</label></th>
            <td>
                <textarea id="nbp_css_button" name="nbp_css_button" rows="3" class="large-text code"><?php echo esc_textarea($css_button); ?></textarea>
                <p class="description">CSS für <code>.nbp-popup-<?php echo $pid; ?> .btn</code></p>
            </td>
        </tr>
        <tr>
            <th><label for="nbp_custom_html">Custom HTML</label></th>
            <td>
                <textarea id="nbp_custom_html" name="nbp_custom_html" rows="6" class="large-text code"><?php echo esc_textarea($custom_html); ?></textarea>
                <p class="description">Eigenes HTML wird unterhalb des Buttons eingefügt.</p>
            </td>
        </tr>
    </table>

    <script>
    jQuery(document).ready(function($) {
        $('#nbp_image_upload_btn').on('click', function(e) {
            e.preventDefault();
            var frame = wp.media({
                title: 'Bild auswählen',
                button: { text: 'Bild verwenden' },
                multiple: false
            });
            frame.on('select', function() {
                var attachment = frame.state().get('selection').first().toJSON();
                $('#nbp_image').val(attachment.url);
                $('#nbp_image_preview').html('<img src="' + attachment.url + '" style="max-width:300px;height:auto;">');
                $('#nbp_image_remove_btn').show();
            });
            frame.open();
        });

        $('#nbp_image_remove_btn').on('click', function(e) {
            e.preventDefault();
            $('#nbp_image').val('');
            $('#nbp_image_preview').html('');
            $(this).hide();
        });
    });
    </script>
    <?php
}

/* ── Display Rules Meta Box ── */
function nbp_display_callback($post) {
    $page_rule = nbp_get_meta($post->ID, 'page_rule', 'all');
    $pages     = nbp_get_meta($post->ID, 'pages');
    ?>
    <table class="form-table nbp-form-table">
        <tr>
            <th><label>Seitenregel</label></th>
            <td>
                <fieldset>
                    <label><input type="radio" name="nbp_page_rule" value="all" <?php checked($page_rule, 'all'); ?>> Auf allen Seiten anzeigen</label><br>
                    <label><input type="radio" name="nbp_page_rule" value="include" <?php checked($page_rule, 'include'); ?>> Nur auf bestimmten Seiten anzeigen</label><br>
                    <label><input type="radio" name="nbp_page_rule" value="exclude" <?php checked($page_rule, 'exclude'); ?>> Nicht auf bestimmten Seiten anzeigen</label>
                </fieldset>
            </td>
        </tr>
        <tr class="nbp-pages-row" <?php echo $page_rule === 'all' ? 'style="display:none"' : ''; ?>>
            <th><label for="nbp_pages">Seiten</label></th>
            <td>
                <textarea id="nbp_pages" name="nbp_pages" rows="4" class="large-text"><?php echo esc_textarea($pages); ?></textarea>
                <p class="description">Eine URL oder ein URL-Pfad pro Zeile (z.B. <code>/kontakt/</code> oder <code>/produkte/*</code>). Wildcards (<code>*</code>) möglich.</p>
            </td>
        </tr>
    </table>
    <script>
    jQuery(document).ready(function($) {
        $('input[name="nbp_page_rule"]').on('change', function() {
            if ($(this).val() === 'all') {
                $('.nbp-pages-row').hide();
            } else {
                $('.nbp-pages-row').show();
            }
        });
    });
    </script>
    <?php
}

/* ── Trigger Meta Box ── */
function nbp_trigger_callback($post) {
    $trigger_type     = nbp_get_meta($post->ID, 'trigger_type', 'timer');
    $trigger_delay    = nbp_get_meta($post->ID, 'trigger_delay', '0');
    $trigger_selector = nbp_get_meta($post->ID, 'trigger_selector');
    ?>
    <table class="form-table nbp-form-table">
        <tr>
            <th><label>Trigger-Typ</label></th>
            <td>
                <fieldset>
                    <label><input type="radio" name="nbp_trigger_type" value="timer" <?php checked($trigger_type, 'timer'); ?>> Nach X Sekunden anzeigen</label><br>
                    <label><input type="radio" name="nbp_trigger_type" value="click" <?php checked($trigger_type, 'click'); ?>> Nach Klick auf Element</label>
                </fieldset>
            </td>
        </tr>
        <tr class="nbp-trigger-timer" <?php echo $trigger_type !== 'timer' ? 'style="display:none"' : ''; ?>>
            <th><label for="nbp_trigger_delay">Verzögerung (Sekunden)</label></th>
            <td>
                <input type="number" id="nbp_trigger_delay" name="nbp_trigger_delay" value="<?php echo esc_attr($trigger_delay); ?>" min="0" step="1">
                <p class="description">0 = sofort anzeigen.</p>
            </td>
        </tr>
        <tr class="nbp-trigger-click" <?php echo $trigger_type !== 'click' ? 'style="display:none"' : ''; ?>>
            <th><label for="nbp_trigger_selector">CSS-Selektor</label></th>
            <td>
                <input type="text" id="nbp_trigger_selector" name="nbp_trigger_selector" value="<?php echo esc_attr($trigger_selector); ?>" class="regular-text" placeholder=".my-button, #trigger-element">
                <p class="description">CSS-Selektor des Elements, auf das geklickt werden muss (z.B. <code>.popup-trigger</code>).</p>
            </td>
        </tr>
    </table>
    <script>
    jQuery(document).ready(function($) {
        $('input[name="nbp_trigger_type"]').on('change', function() {
            if ($(this).val() === 'timer') {
                $('.nbp-trigger-timer').show();
                $('.nbp-trigger-click').hide();
            } else {
                $('.nbp-trigger-timer').hide();
                $('.nbp-trigger-click').show();
            }
        });
    });
    </script>
    <?php
}

/* ── Advanced Meta Box ── */
function nbp_advanced_callback($post) {
    $debug = nbp_get_meta($post->ID, 'debug', '0');
    ?>
    <table class="form-table nbp-form-table">
        <tr>
            <th><label for="nbp_debug">Debug-Modus</label></th>
            <td>
                <label>
                    <input type="checkbox" id="nbp_debug" name="nbp_debug" value="1" <?php checked($debug, '1'); ?>>
                    Debug-Informationen in der Browser-Konsole ausgeben
                </label>
            </td>
        </tr>
    </table>
    <?php
}

/* ── Save Meta Data ── */
function nbp_save_meta($post_id, $post) {
    if (!isset($_POST['nbp_nonce']) || !wp_verify_nonce($_POST['nbp_nonce'], 'nbp_save')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $fields = [
        'activate_date'    => 'sanitize_text_field',
        'deactivate_date'  => 'sanitize_text_field',
        'cookie_days'      => 'intval',
        'image'            => 'esc_url_raw',
        'headline'         => 'sanitize_text_field',
        'subheadline'      => 'sanitize_text_field',
        'text'             => 'wp_kses_post',
        'button_text'      => 'sanitize_text_field',
        'button_url'       => 'esc_url_raw',
        'button_target'    => 'sanitize_text_field',
        'custom_html'      => 'wp_kses_post',
        'page_rule'        => 'sanitize_text_field',
        'pages'            => 'sanitize_textarea_field',
        'trigger_type'     => 'sanitize_text_field',
        'trigger_delay'    => 'intval',
        'trigger_selector' => 'sanitize_text_field',
        'css_overlay'      => 'wp_strip_all_tags',
        'css_container'    => 'wp_strip_all_tags',
        'css_close'        => 'wp_strip_all_tags',
        'css_image'        => 'wp_strip_all_tags',
        'css_headline'     => 'wp_strip_all_tags',
        'css_subheadline'  => 'wp_strip_all_tags',
        'css_text'         => 'wp_strip_all_tags',
        'css_button'       => 'wp_strip_all_tags',
    ];

    // Checkboxes (missing = unchecked = 0)
    update_post_meta($post_id, '_nbp_active', isset($_POST['nbp_active']) ? '1' : '0');
    update_post_meta($post_id, '_nbp_debug', isset($_POST['nbp_debug']) ? '1' : '0');
    update_post_meta($post_id, '_nbp_show_always', isset($_POST['nbp_show_always']) ? '1' : '0');

    foreach ($fields as $key => $sanitize) {
        $value = isset($_POST['nbp_' . $key]) ? $_POST['nbp_' . $key] : '';
        update_post_meta($post_id, '_nbp_' . $key, $sanitize($value));
    }
}
