<?php
// We use a variable so we can inject the dynamic URL later
$readme_text = <<<EOD
=====================================================
KWWD YAMTRACK WORDPRESS SYNC - SETUP GUIDE
=====================================================

Thank you for using the Yamtrack Sync tool! This script will 
automatically update your WordPress sidebar whenever you 
finish watching a Movie or TV Episode.

If you have any issued you can raise a support ticket:
https://github.com/KWWDCoding/kwwd-yamtrack-sync-for-wp/issues

STEP 1: UPLOAD THE SCRIPT
-------------------------
You need to move 'yam_to_wp.py' from your computer to your Yamtrack server.

This file pulls out the last watched Movie or TV episode from Yamtrack and sends
it to your Wordpress site.

METHOD A: Graphical FTP (FileZilla/WinSCP)
- Connect to your server IP.
- Drag and drop 'yam_to_wp.py' into your /home/ folder or Yamtrack folder.

METHOD B: Command Line FTP (For Headless Servers/VMs)
If you are using a terminal to move the file:
1. Open the terminal on the machine where you downloaded the ZIP.
2. Type: ftp [YOUR_SERVER_IP]
3. Enter your username and password (note that you cannot view your password when typing it).
4. Move to your desired folder e.g: cd Yamtrack
5. Upload the downloaded sync file: put yam_to_wp.py
6. Type 'bye' to exit.

METHOD C: Direct Download (wget)
If your server has internet access, you can download the script 
directly from the GitHub Source:

1. Download: wget https://raw.githubusercontent.com/KWWDCoding/kwwd-yamtrack-sync-for-wp/main/source/yam_to_wp.py
2. Open the file: nano yam_to_wp.py
3. Manually paste your WP_URL and SECRET_KEY (found in your WP Admin) into the variables at the top, and ensure your database path is correct based on your Docker setup

   WP_URL     = "{$api_url}"
   SECRET_KEY = "{$secret}"
   DB_PATH    = "/full/path/to/your/db.sqlite3"

4. Save and Exit (Ctrl+O, Enter, Ctrl+X).

STEP 2: FIND YOUR DATABASE PATH
-------------------------------
The script needs to know exactly where your 'db.sqlite3' file is.
1. Open your terminal.
2. Type: find / -name "db.sqlite3" 2>/dev/null
3. Copy that path.
4. Open 'yam_to_wp.py' in a text editor and update the 
   DB_PATH variable at the top.

STEP 3: TEST THE CONNECTION
---------------------------
Before automating, make sure it works! 
In your terminal, navigate to the folder where you uploaded the yam_to_wp.py and run it:
1. cd /Yamtrack
2. python3 yam_to_wp.py

If it says "Success!", check your WordPress site (don't forget to add the shortcode to your sidebar widget or a post/page!)

STEP 4: AUTOMATE WITH CRON
--------------------------
To make this run every 15 minutes in the background:
1. Type: crontab -e (if you're running this for the first time, select nano as that's the easiest editor)
2. Scroll to the bottom and paste this line (adjusting the path):

*/15 * * * * /usr/bin/python3 /path/to/your/folder/yam_to_wp.py >> /path/to/your/folder/sync.log 2>&1

3. CTRL+O, then Enter to save the file
4. CTRL+X to exit the nano editor

HOW IT WORKS (THE "SMART SKIP")
-------------------------------
To save server load, the script creates a small file called 'yam_last_id.txt'. 
It will ONLY send data to WordPress if it detects a NEW watch. 
If you run the script twice and nothing changes on your site, 
don't worry - it's just being efficient!

SUPPORT
-------
If you see a "404" or "403" error in your log file, ensure your 
WordPress site has 'Pretty Permalinks' enabled in Settings > Permalinks.
EOD;
?>