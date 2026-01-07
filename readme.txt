=== kwwd-yamtrack-sync-for-wp ===
Contributors: KWWDCoding
Requires at least: 6.0
Tested up to: 6.49
Stable tag: 
License: GPLv2 or later

== Description ==
This plugin provides seamless synchronization between Yamtrack and your WordPress site. It automatically generates a python script that you can upload to your Yamtrack server that will then sync with the Yamtrack Sync For Wordpress plugin and will display your latest watched TV Show or Movie on your site via a shortcode.

**Features:**
* Automatic data syncing.
* Secure API authentication.
* Style the output to fit your site's branding.

== Installation ==
1. Upload the plugin files to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Access the plugin settings page and download your customised Python Sync Script then follow the instructions in the supplied readme file.
4. Style the display of the Latest watched show/movie as required through the plugin settings.
5. Grab a cup of tea, put your feet and and watch something! When your Yamtrack instance receives a "watched" updated (either through webhook scrobble or by manually adding an entry) your widget will automatically update when the next sync runs.

== Changelog ==`r`n=  =`r`n* 
= 1.52 =
* Added custom branding and improved update logic.
* Fixed file permission issues during unpacking.

= 1.51 =
* Initial stable release with corrected versioning.
