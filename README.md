![Steam Market Multi Watcher](docs/banner.png)

# Steam Market Multi Watcher (PHP)

Ein leichtgewichtiges Tool, um **mehrere CS2-Items (z. B. Messer)** am **Steam Community Market** zu beobachten.  
Es bietet ein **Web-Dashboard** mit Auto-Refresh und kann **Discord-Benachrichtigungen** senden, sobald der Zielpreis erreicht oder unterschritten wird.

---

## ✨ Features
- Multi-Item Tracking über `items.json`
- Web-Dashboard (`index.php`) mit Auto-Refresh
- Discord-Alerts direkt aus dem Browser oder via CLI-Watcher
- Datei-Cache pro Item; robustes Preis-Parsing

---

## 🚀 Installation & Nutzung

### Voraussetzungen
- PHP 7.4+ (empfohlen 8.x) mit `ext-curl` und `ext-json`

### 1) Repository klonen
```bash
git clone https://github.com/<dein-user>/<dein-repo>.git
cd <dein-repo>
```

### 2) Items definieren
Bearbeite `items.json` und trage die exakten Market-Namen und Zielpreise ein:
```json
[
  { "name": "★ M9 Bayonet | Doppler (Factory New)", "target": 300.0 },
  { "name": "★ Karambit | Marble Fade (Factory New)", "target": 450.0 }
]
```

### 3) Web-Dashboard starten
```bash
php -S 0.0.0.0:8080
```
Dann im Browser: `http://localhost:8080/index.php`

### 4) Discord Alerts aktivieren
- Trage deine Discord Webhook URL im Dashboard ein
- Aktiviere Alerts und teste mit "Test senden"

---

## 🔔 CLI-Watcher (optional)
```bash
export DISCORD_WEBHOOK_URL="https://discord.com/api/webhooks/..."
php watcher_cli.php
```

---

## ⚠️ Hinweise
- Nutzt undokumentierte Steam-Endpunkte (`priceoverview`), die rate-limited sind.
- Bitte moderates Polling und Cache verwenden.

---

## 🧩 Erweiterungen
- Discord-Embeds
- Cool-down pro Item
- Pro Item eigener Webhook
- Docker/Compose für 24/7-Betrieb

---

## 📝 Lizenz
MIT License
