from __future__ import annotations

from pathlib import Path
import sys

if getattr(sys, "frozen", False):
    BASE_DIR = Path(sys._MEIPASS)  # type: ignore[attr-defined]
    REPO_ROOT = BASE_DIR
else:
    BASE_DIR = Path(__file__).resolve().parents[2]
    REPO_ROOT = BASE_DIR.parent
DATA_DIR = BASE_DIR / "data"
LOG_DIR = BASE_DIR / "logs"
ASSETS_DIR = BASE_DIR / "assets"
DB_PATH = DATA_DIR / "app.sqlite3"
LOG_FILE = LOG_DIR / "garena-test.log"
PLAYWRIGHT_DIR = REPO_ROOT / "playwright"

DEFAULT_NEW_PASSWORD = "Password#2025"

DB_BUSY_TIMEOUT_MS = 5000
DB_RETRY_COUNT = 7
DB_RETRY_MIN_DELAY = 0.05
DB_RETRY_MAX_DELAY = 0.2

PROXY_API_URL = "https://proxyxoay.shop/api/get.php"
