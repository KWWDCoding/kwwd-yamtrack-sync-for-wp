<?php
/********************************************************************
 * Plugin Name: Yamtrack Sync For WordPress
 * Plugin URI:  https://www.kwwd.co.uk/blog/Yamtrack-To-WP
 * Description: Syncs your last watched media from Yamtrack which you can display in posts, pages or widgets via shortcodes
 * Version: 1.6.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * License: GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Author: KWWDCoding
 * Author URI: https://www.kwwdcoding.com
 *******************************************************************/

// Stop anyone loading this file directly (outside WordPress)
if ( ! defined( 'ABSPATH' ) ) exit;

/* ==========================================================================
   UPDATE CHECKER (GITHUB Method)
   ========================================================================== */
require_once plugin_dir_path(__FILE__) . 'includes/plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/KWWDCoding/kwwd-yamtrack-sync-for-wp/',
    __FILE__,
    'kwwd-yamtrack-sync-for-wp'
);
// Use the ZIPs attached to GitHub "Releases"
$myUpdateChecker->getVcsApi()->enableReleaseAssets();

/** PLUGIN ICONS ***/
$myUpdateChecker->addResultFilter(function($info) {
    if ($info) {
        $githubAssets = 'https://raw.githubusercontent.com/KWWDCoding/kwwd-yamtrack-sync-for-wp/main/assets/img/';

        $info->icons = array(
            '1x'      => $githubAssets . 'icon-128x128.png',
            '2x'      => $githubAssets . 'icon-256x256.png',
            'default' => $githubAssets . 'icon-128x128.png',
        );
    }
    return $info;
});

/* ==========================================================================
   1. PLUGIN SETUP & API REGISTRATION
   ========================================================================== */

// Generate the secret key once, when the plugin is activated
register_activation_hook( __FILE__, function() {
    if ( ! get_option( 'kwwd_yamtrack_wp_secret' ) ) {
        update_option( 'kwwd_yamtrack_wp_secret', bin2hex( random_bytes( 8 ) ) );
    }
});

// Register the REST API route. The secret is part of the URL, so only
// someone who knows it can send data to this endpoint.
add_action('rest_api_init', function () {
    $secret = get_option('kwwd_yamtrack_wp_secret');
    register_rest_route('yamtrack/v1', '/update/' . $secret, array(
        'methods'  => 'POST',
        'callback' => 'kwwd_handle_yamtrack_ping',
        'permission_callback' => '__return_true',
    ));
});

/* ==========================================================================
   2. STYLE SETTINGS (single source of truth)
   ========================================================================== */

// The ONE place default values are defined. Everything else calls this.
function kwwd_yamtrack_default_styles() {
    return [
        'align'        => 'side',      // compact | side | stacked
        'label_size'   => '0.8em',
        'label_color'  => '#666666',
        'title_size'   => '1.1em',
        'title_color'  => '#000000',
        'meta_size'    => '0.85em',
        'meta_color'   => '#888888',
        'card_color'   => '#f6f6f6',
        'border_color' => '#108ba7',
    ];
}

// Returns the saved settings merged over the defaults, so any key
// missing from an older saved array is filled in with its default.
function kwwd_yamtrack_get_styles() {
    return wp_parse_args( get_option('kwwd_yamtrack_styles', []), kwwd_yamtrack_default_styles() );
}

/* ==========================================================================
   3. DATA RECEIVER (called by the Python script)
   ========================================================================== */

function kwwd_handle_yamtrack_ping(WP_REST_Request $request) {
    $data = $request->get_json_params();
    if (empty($data)) return new WP_REST_Response('No data', 400);

    // Download the cover image and store it in the uploads folder
    $local_image_url = '';
    if (!empty($data['image_url'])) {
        $image_content = @file_get_contents($data['image_url']);
        if ($image_content) {
            $upload_dir = wp_upload_dir();
            $filename   = 'kwwd_yamtrack_last_watched_cover.jpg';
            $file_path  = $upload_dir['basedir'] . '/' . $filename;

            // Delete the old cover first so the new one always replaces it
            if (file_exists($file_path)) {
                unlink($file_path);
            }

            file_put_contents($file_path, $image_content);
            $local_image_url = $upload_dir['baseurl'] . '/' . $filename;
        }
    }

    // Save the latest watched item
    update_option('kwwd_yamtrack_last_watched', [
        'title' => sanitize_text_field($data['title']),
        'type'  => sanitize_text_field($data['type']),
        'time'  => sanitize_text_field($data['watch_date']),
        'image' => $local_image_url
    ]);

    return new WP_REST_Response('Received!', 200);
}

/* ==========================================================================
   4. ADMIN MENU
   ========================================================================== */

add_action('admin_menu', function() {
    add_menu_page(
        'Yamtrack Sync',               // Page title
        'Yamtrack Sync',               // Menu title
        'manage_options',              // Capability needed to see it
        'kwwd-yamtrack-wp-sync',       // Menu slug
        'kwwd_yamtrack_admin_page',    // Function that draws the page
        'dashicons-update-alt'         // Icon
    );
});

/* ==========================================================================
   5. ADMIN INTERFACE
   ========================================================================== */

function kwwd_yamtrack_admin_page() {
    $api_url = get_rest_url(null, "yamtrack/v1/update");

    // --- Handle "Save": keep only known keys, sanitise each value ---
    if (isset($_POST['kwwd_yamtrack_save_settings'])) {
        $clean = [];
        foreach ( kwwd_yamtrack_default_styles() as $key => $default ) {
            $clean[$key] = isset($_POST['styles'][$key])
                ? sanitize_text_field($_POST['styles'][$key])
                : $default;
        }
        update_option('kwwd_yamtrack_styles', $clean);
        echo '<div class="updated"><p>Styling settings saved!</p></div>';
    }

    // --- Handle "Reset": overwrite with the defaults ---
    if (isset($_POST['kwwd_reset_settings'])) {
        update_option('kwwd_yamtrack_styles', kwwd_yamtrack_default_styles());
        echo '<div class="updated"><p>Styles reset to factory defaults.</p></div>';
    }

    // Load the current settings (saved values merged over defaults)
    $styles = kwwd_yamtrack_get_styles();
    ?>
    <div class="wrap">
        <h1>Yamtrack To WordPress Sync</h1>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
            <div class="card" style="margin:0; padding: 20px; max-width: none;">
                <h2>1. Download & Install</h2>
                <form method="post">
                    <input type="submit" name="kwwd_download_zip" class="button button-primary button-large" value="Download .zip Package">
                </form>
                <pre>The zip package contains the Python script you need to upload to your Yamtrack
server so the WordPress plugin can access your watch history. Full instructions
are within the included Readme.txt file which contains how to copy the script
to your Yamtrack server.</pre>
            </div>

            <div class="card" style="margin:0; padding: 20px; max-width: none;">
                <h2>2. Display Shortcode</h2>
                <input type="text" readonly value="[kwwd_show_yamtrack_last_watched]"
                       class="kwwd-copy-field"
                       style="width:100%; font-family:monospace; text-align:center; padding:10px; cursor: pointer; background: #f9f9f9;"
                       title="Click to copy"><br>
                       <pre>You can use attributes to override the settings so you can invidually 
style your "Now Watching" display. Example:</pre>
                      <input type="text" readonly value='[kwwd_show_yamtrack_last_watched title="Latest" align="stacked" card_color="#222222" title_color="#ffffff" border_color="#ff6600"]'
                       class="kwwd-copy-field"
                       style="width:100%; font-family:monospace; text-align:center; padding:10px; cursor: pointer; background: #f9f9f9;"
                       title="Click to copy">
                       
            </div>
        </div>

        <div class="card" style="margin-top:20px; max-width: 100%; padding:20px;">
            <h2>3. Shortcode Default Styles</h2>
            <form method="post">
                <pre>These styles will apply to all shortcode. You can style individual shortcode
using the attributes in the example above. Missing attributes will apply these defaults:</pre> 
                <table class="form-table">
                    <tr>
                        <th scope="row">Card Layout</th>
                        <td>
                            <select name="styles[align]">
                                <option value="compact" <?php selected($styles['align'], 'compact'); ?>>Compact - Small image left, text beside it</option>
                                <option value="side" <?php selected($styles['align'], 'side'); ?>>Side - Larger image left, text beside it</option>
                                <option value="stacked" <?php selected($styles['align'], 'stacked'); ?>>Stacked - Large image on top, centred text below</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">"Last Watched" Label</th>
                        <td>
                            Size: <input type="text" name="styles[label_size]" value="<?php echo esc_attr($styles['label_size']); ?>" style="width:80px;">
                            Color: <input type="color" name="styles[label_color]" value="<?php echo esc_attr($styles['label_color']); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Title (Show/Movie)</th>
                        <td>
                            Size: <input type="text" name="styles[title_size]" value="<?php echo esc_attr($styles['title_size']); ?>" style="width:80px;">
                            Color: <input type="color" name="styles[title_color]" value="<?php echo esc_attr($styles['title_color']); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Meta (Type & Date)</th>
                        <td>
                            Size: <input type="text" name="styles[meta_size]" value="<?php echo esc_attr($styles['meta_size']); ?>" style="width:80px;">
                            Color: <input type="color" name="styles[meta_color]" value="<?php echo esc_attr($styles['meta_color']); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Card Background</th>
                        <td>
                            Color: <input type="color" name="styles[card_color]" value="<?php echo esc_attr($styles['card_color']); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Left Border</th>
                        <td>
                            Color: <input type="color" name="styles[border_color]" value="<?php echo esc_attr($styles['border_color']); ?>">
                        </td>
                    </tr>
                </table>
                <div class="submit" style="display:flex; gap:10px; align-items:center;">
                    <input type="submit" name="kwwd_yamtrack_save_settings" class="button button-primary" value="Save Styling Changes">
                    <input type="submit" name="kwwd_reset_settings" class="button" value="Reset to Defaults" onclick="return confirm('Are you sure you want to reset all styles?');">
                </div>
            </form>
        </div>

        <div class="card" style="margin-top:20px; max-width: 100%; padding:20px;">
            <h2>Manual Configuration Values</h2>
            <table class="widefat fixed" cellspacing="0">
                <thead><tr><th style="width: 20%;">Variable</th><th>Value</th></tr></thead>
                <tbody>
                    <tr>
                        <td><strong>WP_URL</strong></td>
                        <td><code class="kwwd-copy-field" style="cursor:pointer; display:block; padding:8px; background:#f0f0f0; border:1px solid #ddd;"><?php echo esc_url($api_url); ?></code></td>
                    </tr>
                    <tr>
                        <td><strong>SECRET_KEY</strong></td>
                        <td>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <code id="kwwd-secret-display" class="kwwd-copy-field" style="cursor:pointer; flex-grow:1; padding:8px; background:#f0f0f0; border:1px solid #ddd;">********</code>
                                <button type="button" class="button" id="kwwd-toggle-secret">Show</button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}

/* ==========================================================================
   6. ZIP GENERATOR (setup package download)
   ========================================================================== */

add_action('admin_init', function() {
    if (isset($_POST['kwwd_download_zip'])) {
        // These two variables are used inside the included templates
        $secret  = get_option('kwwd_yamtrack_wp_secret');
        $api_url = untrailingslashit(get_rest_url(null, 'yamtrack/v1/update'));

        // The templates create $python_code and $readme_text
        include plugin_dir_path(__FILE__) . 'includes/python-template.php';
        include plugin_dir_path(__FILE__) . 'includes/readme-template.php';

        $zip = new ZipArchive();
        $zip_file = tempnam(sys_get_temp_dir(), 'zip');
        if ($zip->open($zip_file, ZipArchive::OVERWRITE) === TRUE) {
            $zip->addFromString("yam_to_wp.py", $python_code);
            $zip->addFromString("README.txt", $readme_text);
            $zip->close();

            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="kwwd-yamtrack-sync-setup.zip"');
            readfile($zip_file);
            unlink($zip_file);
            exit;
        }
    }
});

/* ==========================================================================
   7. FRONT-END STYLESHEET
   ========================================================================== */

add_action('wp_enqueue_scripts', function () {
    // Register (not enqueue) the stylesheet. It is only loaded on pages
    // that actually use the shortcode (see section 8).
    wp_register_style(
        'kwwd-yamtrack',
        plugin_dir_url(__FILE__) . 'assets/css/frontend.css',
        [],
        filemtime(plugin_dir_path(__FILE__) . 'assets/css/frontend.css')
    );

    $s        = kwwd_yamtrack_get_styles();
    $defaults = kwwd_yamtrack_default_styles();

    // Accept only values like 1.1em / 12px / 100%, otherwise use the default
    $size = function ($key) use ($s, $defaults) {
        return preg_match('/^\d*\.?\d+(em|rem|px|%)$/', $s[$key]) ? $s[$key] : $defaults[$key];
    };

    // Accept only valid hex colours, otherwise use the default
    $color = function ($key) use ($s, $defaults) {
        $c = sanitize_hex_color($s[$key]);
        return $c ? $c : $defaults[$key];
    };

    // Build the CSS variables from the saved settings. These override the
    // fallback values at the top of frontend.css (same selector, comes later).
    $css = '.kwwd-yamtrack-display{'
        . '--kwwd-label-size:'   . $size('label_size')    . ';'
        . '--kwwd-label-color:'  . $color('label_color')  . ';'
        . '--kwwd-title-size:'   . $size('title_size')    . ';'
        . '--kwwd-title-color:'  . $color('title_color')  . ';'
        . '--kwwd-meta-size:'    . $size('meta_size')     . ';'
        . '--kwwd-meta-color:'   . $color('meta_color')   . ';'
        . '--kwwd-card-color:'   . $color('card_color')   . ';'
        . '--kwwd-border-color:' . $color('border_color') . ';'
        . '}';

    wp_add_inline_style('kwwd-yamtrack', $css);
});

/* ==========================================================================
   8. SHORTCODE (the card itself)
   ========================================================================== */

function kwwd_yamtrack_shortcode($atts) {
    wp_enqueue_style('kwwd-yamtrack');

    // List every attribute the shortcode accepts. An empty default ('') means
    // "not supplied", so the saved admin setting is used instead.
    // Any attribute not in this list is ignored.
    $atts = shortcode_atts([
        'title'        => '',   // optional <h3> heading above the card
        'align'        => '',   // compact | side | stacked
        'label_size'   => '',
        'label_color'  => '',
        'title_size'   => '',
        'title_color'  => '',
        'meta_size'    => '',
        'meta_color'   => '',
        'card_color'   => '',
        'border_color' => '',
    ], $atts, 'kwwd_show_yamtrack_last_watched');

    $unique_id = 'kwwd_yt_' . wp_rand(100, 999);
    $styles    = kwwd_yamtrack_get_styles();

    // Layout: use the attribute if it's a valid option, else the admin setting
    $align = in_array($atts['align'], ['compact', 'side', 'stacked'], true)
        ? $atts['align']
        : $styles['align'];

    // Build per-card CSS variable overrides, only for attributes that were
    // supplied AND are valid. The names match the variables in frontend.css
    // (e.g. card_color becomes --kwwd-card-color).
    $overrides = '';

    foreach (['label_size', 'title_size', 'meta_size'] as $key) {
        if (preg_match('/^\d*\.?\d+(em|rem|px|%)$/', $atts[$key])) {
            $overrides .= '--kwwd-' . str_replace('_', '-', $key) . ':' . $atts[$key] . ';';
        }
    }

    foreach (['label_color', 'title_color', 'meta_color', 'card_color', 'border_color'] as $key) {
        $c = sanitize_hex_color($atts[$key]); // returns '' if invalid
        if ($c) {
            $overrides .= '--kwwd-' . str_replace('_', '-', $key) . ':' . $c . ';';
        }
    }

    // Only output a style attribute if there is something to override
    $style_attr = $overrides ? ' style="' . esc_attr($overrides) . '"' : '';

    // Only output a heading if a title was supplied
    $heading = $atts['title'] !== ''
        ? '<h3 class="kwwd-yt-heading">' . esc_html($atts['title']) . '</h3>'
        : '';

    ob_start(); ?>
    <?php echo $heading; ?>
    <div id="<?php echo esc_attr($unique_id); ?>" class="kwwd-yamtrack-display kwwd_yamtrack_<?php echo esc_attr($align); ?>"<?php echo $style_attr; ?>>
        <div class="kwwd-yt-text"><span class="kwwd-yt-loading">Fetching Viewing History...</span></div>
    </div>

    <script>
    (function() {
        const ajaxUrl = '<?php echo esc_url(admin_url('admin-ajax.php')); ?>';
        fetch(ajaxUrl + '?action=get_fresh_yamtrack&v=' + Date.now())
            .then(res => res.text())
            .then(html => {
                if (html.trim().length > 0) {
                    document.getElementById('<?php echo esc_js($unique_id); ?>').innerHTML = html;
                }
            })
            .catch(err => console.error('Yamtrack AJAX Error:', err));
    })();
    </script>
    <?php
    return ob_get_clean();
}

add_shortcode('kwwd_show_yamtrack_last_watched', 'kwwd_yamtrack_shortcode');

/* ==========================================================================
   9. AJAX HANDLER (returns the card's inner content only)
   ========================================================================== */

// Register for both logged-in users and visitors
add_action('wp_ajax_get_fresh_yamtrack', 'kwwd_ajax_fresh_data');
add_action('wp_ajax_nopriv_get_fresh_yamtrack', 'kwwd_ajax_fresh_data');

function kwwd_ajax_fresh_data() {
    // Clear any stray output so only our HTML is returned
    if (ob_get_length()) ob_clean();

    $last_watched = get_option('kwwd_yamtrack_last_watched');

    // Nothing synced yet: return nothing, so "Fetching..." stays on screen
    if (!$last_watched) {
        wp_die();
    }

    $title = esc_html($last_watched['title']);
    $type  = !empty($last_watched['type']) ? ucfirst(esc_html($last_watched['type'])) : 'Media';
    $time  = wp_date('M j, g:i a', strtotime($last_watched['time']));
    $img   = $last_watched['image'];

    // Output ONLY the inside of the card. No wrapper and no inline styles:
    // the shortcode div supplies the card, frontend.css supplies the look.
    if ($img) {
        // ?v=time() forces the browser to fetch the newest cover image
        echo '<img class="kwwd-yt-cover" src="' . esc_url($img) . '?v=' . time() . '" alt="">';
    }

    echo '<div class="kwwd-yt-text">';
    echo '<strong class="kwwd-yt-title">' . $title . '</strong>';
    echo '<span class="kwwd-yt-label">' . $type . ' Last Watched</span>';
    //echo '<span class="kwwd-yt-meta">' . $type . ' &bull; ' . $time . '</span>';
    echo '<span class="kwwd-yt-meta">' . $time . '</span>';
    echo '</div>';

    wp_die(); // Always end WordPress AJAX handlers with this
}

/* ==========================================================================
   10. ADMIN: CLICK TO COPY + SHOW/HIDE SECRET
   ========================================================================== */

add_action('admin_footer', function() {
    $secret = get_option('kwwd_yamtrack_wp_secret');
    ?>
    <script>
    jQuery(document).ready(function($) {
        // Click any .kwwd-copy-field to copy its text, with a green flash
        $('.kwwd-copy-field').on('click', function() {
            let text = $(this).is('input') ? $(this).val() : $(this).text();

            // The secret is hidden as ******** so copy the real value instead
            if ($(this).attr('id') === 'kwwd-secret-display' && $(this).text() === '********') {
                text = '<?php echo esc_js($secret); ?>';
            }

            navigator.clipboard.writeText(text).then(() => {
                const $el = $(this);
                const originalBg = $el.css('background-color');
                $el.css('background-color', '#d4edda');
                setTimeout(() => $el.css('background-color', originalBg), 600);
            });
        });

        // Show / Hide button for the secret key
        $('#kwwd-toggle-secret').on('click', function() {
            const display = $('#kwwd-secret-display');
            if (display.text() === '********') {
                display.text('<?php echo esc_js($secret); ?>');
                $(this).text('Hide');
            } else {
                display.text('********');
                $(this).text('Show');
            }
        });
    });
    </script>
    <?php
});

/* ==========================================================================
   11. SETTINGS LINK ON THE PLUGINS PAGE
   ========================================================================== */

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'kwwd_yamtrack_wp_settings_link');

function kwwd_yamtrack_wp_settings_link($links) {
    $settings_link = '<a href="admin.php?page=kwwd-yamtrack-wp-sync">' . __('Settings') . '</a>';
    array_push($links, $settings_link);
    return $links;
}