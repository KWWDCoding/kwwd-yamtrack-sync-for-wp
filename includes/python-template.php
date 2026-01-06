<?php
// Note: We use double quotes for the string so PHP can inject the variables
$python_code = <<<EOD
import sqlite3, requests, os

# --- YOUR DATABASE PATH ---
DB_PATH = '/home/docker/Yamtrack' 

# --- CONFIGURATION (Auto-generated) ---
WP_URL = '{$api_url}'
SECRET_KEY = '{$secret}'
YAMDB_LOCATION = os.path.join(DB_PATH, 'db', 'db.sqlite3')
CACHE_FILE = os.path.join(DB_PATH, 'yam_last_id.txt')

def get_latest_watch():
    try:
        # Use uri=True to handle potential locking issues
        conn = sqlite3.connect(YAMDB_LOCATION, uri=True)
        cur = conn.cursor()
        query = """
        SELECT 
            i.id, 
            CASE 
                WHEN i.media_type = 'episode' THEN i.title || ' (S' || i.season_number || 'E' || i.episode_number || ')'
                ELSE i.title 
            END as display_title,
            i.image, 
            m.end_date, 
            i.media_type 
        FROM app_movie m 
        JOIN app_item i ON m.item_id = i.id 
        WHERE m.end_date IS NOT NULL
        UNION ALL
        SELECT 
            i.id, 
            i.title || ' (S' || i.season_number || 'E' || i.episode_number || ')',
            i.image, 
            e.end_date, 
            i.media_type 
        FROM app_episode e 
        JOIN app_item i ON e.item_id = i.id 
        WHERE e.end_date IS NOT NULL
        ORDER BY end_date DESC LIMIT 1
        """
        cur.execute(query)
        row = cur.fetchone()
        conn.close()
        if row:
            return {"id": str(row[0]), "title": row[1], "image_url": row[2], "watch_date": row[3], "type": row[4]}
        return None
    except Exception as e:
        print(f"DB Error: {e}"); return None

def send_to_wordpress(data):
    # Ensure the URL is built correctly to match the WP REST route
    target_url = f"{WP_URL.rstrip('/')}/{SECRET_KEY}"
    try:
        response = requests.post(target_url, json=data, timeout=15)
        if response.status_code == 200:
            print(f"✅ Success: {data['title']}")
        else:
            print(f"❌ Error {response.status_code}: {response.text}")
    except Exception as e:
        print(f"❌ Failed: {e}")

if __name__ == "__main__":
    latest = get_latest_watch()
    
    if latest:
        last_sent_date = ""
        if os.path.exists(CACHE_FILE):
            with open(CACHE_FILE, "r") as f:
                last_sent_date = f.read().strip()
        
        if latest["watch_date"] == last_sent_date:
            print(f"Skipping: {latest['title']} has already been uploaded.")
        else:
            print(f"New activity found! Sending {latest['title']} to WordPress...")
            send_to_wordpress(latest)
            
            with open(CACHE_FILE, "w") as f:
                f.write(latest["watch_date"])
EOD;
?>