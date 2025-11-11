![Steam Market Multi Watcher](docs/banner.png)

# Steam Market Multi Watcher (PHP)

A lightweight tool to **monitor multiple CS2 items (e.g., knives)** on the **Steam Community Market**.  
It provides a **web dashboard** with auto-refresh and can send **Discord notifications** when the target price is reached or undercut.

---

## ✨ Features
- Multi-item tracking via `items.json`
- Web dashboard (`index.php`) with auto-refresh
- Discord alerts directly from the browser or via CLI watcher
- File cache per item; robust price parsing

---

## 🚀 Installation & Usage

### Requirements
- PHP 7.4+ (recommended 8.x) with `ext-curl` and `ext-json`

### 1) Clone the repository
```bash
git clone https://github.com/<your-user>/<your-repo>.git
cd <your-repo>
```

### 2) Define items
Edit `items.json` and enter the exact market names and target prices:
```json
[
  { "name": "★ M9 Bayonet | Doppler (Factory New)", "target": 300.0 },
  { "name": "★ Karambit | Marble Fade (Factory New)", "target": 450.0 }
]
```

### 3) Start the web dashboard
```bash
php -S 0.0.0.0:8080
```
Then open in browser: `http://localhost:8080/index.php`

### 4) Enable Discord alerts
- Enter your Discord webhook URL in the dashboard
- Activate alerts and test with **Send Test**

---

## 🔔 CLI Watcher (optional)
```bash
export DISCORD_WEBHOOK_URL="https://discord.com/api/webhooks/..."
php watcher_cli.php
```

---

## ⚠️ Notes
- Uses undocumented Steam endpoints (`priceoverview`), which are rate-limited.
- Please use moderate polling and caching.

---

## 🧩 Extensions
- Discord embeds
- Cool-down per item
- Per-item webhook
- Docker/Compose for 24/7 operation

---

## 📝 License
MIT License
