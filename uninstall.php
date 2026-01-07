<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package kwwd-yamtrack-sync-for-wp
 */

// If uninstall not called from WordPress, die.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// 1. Define the option keys you want to delete
$options = array(
    'kwwd_yamtrack_wp_secret',     // Secret Key
    'kwwd_yamtrack_last_watched',    // Last Watched (Title, Date/Time etc)
    'kwwd_yamtrack_styles' // Styles for widget
);

// 2. Delete the options from the database
foreach ( $options as $option ) {
    delete_option( $option );
    // If you saved data as "Site Options" (Network wide), use:
    // delete_site_option( $option );
}

// 3. Delete the downloaded cover image from the uploads folder
$upload_dir = wp_upload_dir();
$filename   = 'kwwd_yamtrack_last_watched_cover.jpg';
$file_path  = $upload_dir['basedir'] . '/' . $filename;

if ( file_exists( $file_path ) ) {
    wp_delete_file( $file_path );
}

// 4. Clear any scheduled tasks (Crons) if you added them
wp_clear_scheduled_hook( 'kwwd_yamtrack_sync_event' );

?>