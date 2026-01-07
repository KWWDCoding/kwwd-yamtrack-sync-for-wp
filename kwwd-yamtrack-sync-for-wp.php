<?php
/********************************************************************
 * Plugin Name: Yamtrack Sync For WordPress
 * Plugin URI:  https://www.kwwd.co.uk/blog/Yamtrack-To-WP
 * Description: Syncs your latest watched media from Yamtrack which you can display in posts, pages or widgets via a shortcode
 * Version: 1.52
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * License: GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Author: KWWDCoding
 * Author URI: https://www.kwwdcoding.com
 *******************************************************************/

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
// Since you're using GitHub's "Releases" feature to host the ZIPs:
$myUpdateChecker->getVcsApi()->enableReleaseAssets();

/** PLUGIN ICONS ***/
$myUpdateChecker->addResultFilter(function($info) {
    if ($info) {
        // Use the RAW content URL from GitHub
        $githubAssets = 'https://raw.githubusercontent.com/KWWDCoding/kwwd-yamtrack-sync-for-wp/main/assets/';
        
        $info->icons = array(
            '1x'      => $githubAssets . 'icon-128x128.png',
            '2x'      => $githubAssets . 'icon-256x256.png', // Optional
            'default' => $githubAssets . 'icon-128x128.png',
        );
    }
    return $info;
});

/* ==========================================================================
   1. PLUGIN SETUP & API REGISTRATION
   ========================================================================== */

// Generate Secret Key on Activation
register_activation_hook( __FILE__, function() {
    if ( ! get_option( 'kwwd_yamtrack_wp_secret' ) ) {
        update_option( 'kwwd_yamtrack_wp_secret', bin2hex( random_bytes( 8 ) ) );
    }
});

// Register the REST API Route
add_action('rest_api_init', function () {
    $secret = get_option('kwwd_yamtrack_wp_secret');
    register_rest_route('yamtrack/v1', '/update/' . $secret, array(
        'methods'  => 'POST',
        'callback' => 'kwwd_handle_yamtrack_ping',
        'permission_callback' => '__return_true',
    ));
});

/********************************************************************
   2. DATA RECEIVER
 *******************************************************************/

function kwwd_handle_yamtrack_ping(WP_REST_Request $request) {
    $data = $request->get_json_params();
    if (empty($data)) return new WP_REST_Response('No data', 400);

    $local_image_url = '';
    if (!empty($data['image_url'])) {
        $image_content = @file_get_contents($data['image_url']);
        if ($image_content) {
            $upload_dir = wp_upload_dir();
            $filename = 'kwwd_yamtrack_last_watched_cover.jpg';
            $file_path = $upload_dir['basedir'] . '/' . $filename;
            
            // --- NEW: FORCE REFRESH ---
            if (file_exists($file_path)) {
                unlink($file_path); // Delete the old file first
            }
            // ---------------------------

            file_put_contents($file_path, $image_content);
            $local_image_url = $upload_dir['baseurl'] . '/' . $filename;
        }
    }

    update_option('kwwd_yamtrack_last_watched', [
        'title' => sanitize_text_field($data['title']),
        'type'  => sanitize_text_field($data['type']),
        'time'  => sanitize_text_field($data['watch_date']),
        'image' => $local_image_url 
    ]);

    return new WP_REST_Response('Received!', 200);
}

/********************************************************************
   3. ADMIN MENU
  *******************************************************************/

add_action('admin_menu', function() {
    add_menu_page(
        'Yamtrack Sync', // Page Title
        'Yamtrack Sync', // Menu Title
        'manage_options', // What's the page do
        'kwwd-yamtrack-wp-sync', // Menu Slug
        'kwwd_yamtrack_admin_page', // Function that shows page
        'dashicons-update-alt'); // Icon
});

/********************************************************************
   4. ADMIN INTERFACE
  *******************************************************************/

function kwwd_yamtrack_admin_page() {
    $secret = get_option('kwwd_yamtrack_wp_secret');
    $api_url = get_rest_url(null, "yamtrack/v1/update");

    // Default Style Array
    $default_styles = [
        'align' => 'left',
        'label_size' => '0.8em', 'label_color' => '#666666',
        'title_size' => '1.1em', 'title_color' => '#000000',
        'meta_size'  => '0.85em', 'meta_color'  => '#888888',
        'round'      => '4px'
    ];
    
    // Handle Save
    if (isset($_POST['kwwd_yamtrack_save_settings'])) {
        update_option('kwwd_yamtrack_styles', $_POST['styles']);
        echo '<div class="updated"><p>Styling settings saved!</p></div>';
    }

    // Handle Reset
    if (isset($_POST['kwwd_reset_settings'])) {
        update_option('kwwd_yamtrack_styles', $default_styles);
        echo '<div class="updated"><p>Styles reset to factory defaults.</p></div>';
    }

    $styles = get_option('kwwd_yamtrack_styles', $default_styles);
    ?>
    <div class="wrap">
        <h1>Yamtrack To WordPress Sync</h1>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
            <div class="card" style="margin:0; padding: 20px; max-width: none;">
                <h2>1. Download & Install</h2>
                <form method="post">
                    <input type="submit" name="kwwd_download_zip" class="button button-primary button-large" value="Download .zip Package">
                </form>
            </div>

            <div class="card" style="margin:0; padding: 20px; max-width: none;">
                <h2>2. Display Widget</h2>
                <input type="text" readonly value="[kwwd_show_yamtrack_last_watched]" 
                       class="kwwd-copy-field" 
                       style="width:100%; font-family:monospace; text-align:center; padding:10px; cursor: pointer; background: #f9f9f9;"
                       title="Click to copy">
            </div>
        </div>

        <div class="card" style="margin-top:20px; max-width: 100%; padding:20px;">
            <h2>3. Widget Styling</h2>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th scope="row">Global Layout</th>
                        <td>
                            <label>Alignment: </label>
                            <select name="styles[align]">
                                <option value="left" <?php selected($styles['align'], 'left'); ?>>Left</option>
                                <option value="center" <?php selected($styles['align'], 'center'); ?>>Center</option>
                                <option value="right" <?php selected($styles['align'], 'right'); ?>>Right</option>
                            </select>
                            <label style="margin-left:20px;">Image Rounding: </label>
                            <input type="text" name="styles[round]" value="<?php echo esc_attr($styles['round']); ?>" style="width:60px;">
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

/*******************************************************************
   5. ZIP GENERATOR
 *******************************************************************/

add_action('admin_init', function() {
    if (isset($_POST['kwwd_download_zip'])) {
        $secret = get_option('kwwd_yamtrack_wp_secret');
        $api_url = untrailingslashit(get_rest_url(null, 'yamtrack/v1/update'));

        // Load our Templates
        include plugin_dir_path(__FILE__) . 'includes/python-template.php';
        include plugin_dir_path(__FILE__) . 'includes/readme-template.php';

        $zip = new ZipArchive();
        $zip_file = tempnam(sys_get_temp_dir(), 'zip');
        if ($zip->open($zip_file, ZipArchive::OVERWRITE) === TRUE) {
            $zip->addFromString("yam_to_wp.py", $python_code); // From include
            $zip->addFromString("README.txt", $readme_text);   // From include
            $zip->close();
            
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="kwwd-yamtrack-sync-setup.zip"');
            readfile($zip_file);
            unlink($zip_file);
            exit;
        }
    }
});

/********************************************************************
   6. SHORTCODE DISPLAY
  *******************************************************************/

add_shortcode('kwwd_show_yamtrack_last_watched', function() {
    $last_watched = get_option('kwwd_yamtrack_last_watched');
    $styles = get_option('kwwd_yamtrack_styles', [
        'align' => 'left',
        'label_size' => '0.8em', 'label_color' => '#666666',
        'title_size' => '1.1em', 'title_color' => '#000000',
        'meta_size'  => '0.85em', 'meta_color'  => '#888888',
        'round'      => '4px'
    ]);

    if (!$last_watched) return '';

    $title = esc_html($last_watched['title']);
    $type  = !empty($last_watched['type']) ? ucfirst(esc_html($last_watched['type'])) : 'Media';
    $time  = date('M j, g:i a', strtotime($last_watched['time']));
    $img   = $last_watched['image'];
    $align = esc_attr($styles['align']);
    $round = esc_attr($styles['round']);

    $img_margin = ($align === 'center') ? '0 auto 10px' : ($align === 'right' ? '0 0 10px auto' : '0 0 10px 0');

    $output = '<div class="kwwd-yamtrack-widget" style="line-height:1.4; text-align:' . $align . ';">';
    
    if ($img) {
        // Cache-buster ?v= ensures the image updates even if filename is the same
        $output .= '<img src="' . esc_url($img) . '?v=' . time() . '" style="max-width:100%; height:auto; display:block; margin:' . $img_margin . '; border-radius:' . $round . ';">';
    }

    $output .= '<span style="display:block; text-transform:uppercase; font-size:' . esc_attr($styles['label_size']) . '; color:' . esc_attr($styles['label_color']) . ';">Last Watched</span>';
    $output .= '<strong style="display:block; font-size:' . esc_attr($styles['title_size']) . '; color:' . esc_attr($styles['title_color']) . ';">' . $title . '</strong>';
    $output .= '<span style="display:block; margin-top:5px; font-size:' . esc_attr($styles['meta_size']) . '; color:' . esc_attr($styles['meta_color']) . ';">' . $type . ' &bull; ' . $time . '</span>';
    $output .= '</div>';

    return $output;
});
add_filter('widget_text', 'do_shortcode');


/*******************************************************************
   7. CLICK TO COPY ELEMENTS JAVASCRIPT
  *******************************************************************/


add_action('admin_footer', function() {
    $secret = get_option('kwwd_yamtrack_wp_secret');
    ?>
    <script>
    jQuery(document).ready(function($) {
        // Copy to Clipboard with Visual Feedback
        $('.kwwd-copy-field').on('click', function() {
            let text = $(this).is('input') ? $(this).val() : $(this).text();
            
            // Logic for the obfuscated secret key
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

        // Toggle Secret Key Visibility
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

/*******************************************************************
   8. SETTINGS LINK ON PLUGINS PAGE
 *******************************************************************/

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'kwwd_yamtrack_wp_settings_link');

function kwwd_yamtrack_wp_settings_link($links) {
    $settings_link = '<a href="admin.php?page=kwwd-yamtrack-wp-sync">' . __('Settings') . '</a>';
    array_push($links, $settings_link);
    return $links;
}

?>
