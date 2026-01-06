# YamTrack Sync for WordPress 🎬

[![WordPress Version](https://img.shields.io/badge/wordpress-%3E%3D6.0-blue.svg)](https://wordpress.org)
[![License](https://img.shields.io/badge/license-GPLv2-green.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D7.4-8892bf.svg)](https://php.net)

**YamTrack Sync for WordPress** is a simple-to-use bridge that connects your YamTrack watch history to your WordPress site.

This plugin will display your most recently watched movie or TV show with a beautiful, customizable dashboard widget.

<p style="font-size:0.75em"><strong>Please Note</strong>: I'm actively developing this plugin and more layout options will be coming soon so make sure to add this repo to your watch list to keep up with them!</p>

---

## 👀 Demo

You can see the plugin in action on my review site <a href="https://www.whatkatyreviewednext.com" target="_blank">What Katy Reviewed Next</a> — check the right sidebar for and that "What Katy's watching next" section, or check out the example below that shows how a TV episode is currently displayed:

<p align="center"><img src="https://www.kwwdcoding.com/external/github/kwwd-yamtrack-to-wp-example.png" style="width: 60%;" alt="Screenshot of a website showing the Yamtrack To WordPress display on a sidebar"></p>

## ✨ Key Features
* **Automated Python Sync:** Lightweight script fetches data from your local YamTrack SQLite database.
* **Secure REST API:** Communication is protected by a unique, auto-generated secret key.
* **Zero-Config UI:** Download a pre-configured Python setup package directly from your WordPress admin.
* **Highly Customizable:** Full control over alignment, colors, typography, and image rounding through the settings panel.
* **Smart Caching:** Avoids redundant uploads by fingerprinting watch dates.

## 🚀 How It Works
1. **Download the Plugin:** Download the [latest release](https://github.com/KWWDCoding/kwwd-yamtrack-sync-for-wp/releases) to your PC
2. **Install the Plugin:** Upload the `.zip` to your WordPress site and activate it.
3. **Generate Your Sync File:** Go to the "YamTrack Sync" admin page and click **Download .zip Package**.
4. **Deploy Python:** Extract the downloaded `kwwd-yamtrack-sync-setup.zip` and then upload the `yam_to_wp.py` file to your YamTrack server (e.g., inside your Docker container or server environment), or FTP your files to your YamTrack server. (*Note: You'll need to ensure that your YamTrack Database path is set correctly in the `yam_to_wp.py` File - check the included readme.txt file for details on how to find its location if you didn't install Yamtrack with default options*).
5. **Automate:** Set a Cron job or Task Scheduler on your Yamtrack server to run the Python script—your WordPress site updates. 15 minutes is recommended but you can set this however you want.

## 🛠️ Requirements
* **WordPress:** 6.0 or higher.
* **Python:** 3.x with `requests` and `sqlite3` libraries — this should be pre-installed on your Yamtrack Docker instance by default
* **YamTrack:** Access to the `db.sqlite3` file.

## 🤝 Contributing
Pull requests are more than welcome! Whether that's to add some functionality, fix a bug or correct a spelling mistake — this helps make this plugin, and by extension, Yamtrack, better for everyone.

## 💡 Feature Suggestions
If you'd like to see some functionality that's not included or on the road map please use [Github issues](https://github.com/KWWDCoding/kwwd-yamtrack-sync-for-wp/issues) to add a feature request.

## 🪲 Bug Reports
While I try to release bug-free code, you might have found something I missed. Please use the [Github issues tracker](https://github.com/KWWDCoding/kwwd-yamtrack-sync-for-wp/issues) and include exactly what you were doing when the bug occurred, any error messages that were displayed, what version on WordPress you're running and your PHP version (if you know it). The more information you provide will help me to track down the cause of the issue much faster.

## 🤗 Support Katy

☕ Building these tools does take time so if you'd like to financially support me you can do so on Ko-Fi

<a href='https://ko-fi.com/W7W61F06CS' target='_blank'><img height='36' style='border:0px;height:36px;' src='https://storage.ko-fi.com/cdn/kofi4.png?v=6' border='0' alt='Buy Me a Coffee at ko-fi.com' /></a>

👍 Can't support me financially? That's fine, I know times are hard. But if you'd consider sharing this plugin on your socials that would be fantastic!

⭐ You can also star the project which will help increase visibility and show appreciation of my work!

---
*Developed with ❤️ by [KWWDCoding](https://www.kwwdcoding.com)*
