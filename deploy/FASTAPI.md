# PaperTrail FastAPI deployment

The FastAPI process is deliberately bound to `127.0.0.1:8001`. Do not expose
port 8001 through Nginx, UFW, or the OVH network firewall.

## Install

```bash
sudo apt update
sudo apt install -y python3-venv
cd /var/www/papertrail/ai
python3 -m venv .venv
.venv/bin/pip install --upgrade pip
.venv/bin/pip install -r requirements.txt
```

Create `/etc/papertrail-ai.env` with a new Gemini API key:

```dotenv
GEMINI_API_KEY=replace-with-a-new-restricted-key
GEMINI_MODEL=gemini-3.6-flash
GEMINI_TIMEOUT_MS=30000
```

Protect the environment file and install the service:

```bash
sudo chown root:www-data /etc/papertrail-ai.env
sudo chmod 640 /etc/papertrail-ai.env
sudo cp /var/www/papertrail/deploy/papertrail-ai.service /etc/systemd/system/papertrail-ai.service
sudo systemctl daemon-reload
sudo systemctl enable --now papertrail-ai
```

Verify the local service:

```bash
curl --fail http://127.0.0.1:8001/health
sudo systemctl status papertrail-ai --no-pager
```

## Laravel configuration

Add these values to `/var/www/papertrail/.env`:

```dotenv
PAPERTRAIL_AI_ENABLED=true
PAPERTRAIL_AI_URL=http://127.0.0.1:8001
PAPERTRAIL_AI_TIMEOUT=35
```

The Gemini key belongs only in `/etc/papertrail-ai.env`; remove it from the
Laravel `.env` after the FastAPI service is configured.

Reload Laravel and the worker:

```bash
cd /var/www/papertrail
php artisan optimize:clear
php artisan config:cache
php artisan queue:restart
sudo supervisorctl restart papertrail-worker:*
```

## Troubleshooting

```bash
sudo journalctl -u papertrail-ai --no-pager -n 100
curl --fail http://127.0.0.1:8001/health
```

Laravel automatically falls back to its local deterministic title matcher when
FastAPI or Gemini is unavailable.
