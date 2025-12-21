from __future__ import annotations

from pathlib import Path

BASE_DIR = Path(__file__).resolve().parents[2]
DATA_DIR = BASE_DIR / "data"
LOG_DIR = BASE_DIR / "logs"
DB_PATH = DATA_DIR / "app.sqlite3"
LOG_FILE = LOG_DIR / "garena-test.log"

DEFAULT_NEW_PASSWORD = "Password#2025"

DB_BUSY_TIMEOUT_MS = 5000
DB_RETRY_COUNT = 7
DB_RETRY_MIN_DELAY = 0.05
DB_RETRY_MAX_DELAY = 0.2

PROXY_API_URL = "https://proxyxoay.shop/api/get.php"
