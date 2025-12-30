from __future__ import annotations

import subprocess
import threading
import time
from app.config import settings

_lock = threading.Lock()
_processes: list[subprocess.Popen[str]] = []
_stop_event = threading.Event()


def run_001proxy_test() -> int:
    script = settings.PLAYWRIGHT_DIR / "001proxy-test.js"
    process = subprocess.Popen(
        ["node", str(script)],
        cwd=str(settings.PLAYWRIGHT_DIR.parent),
        stdout=subprocess.PIPE,
        stderr=subprocess.STDOUT,
        text=True,
    )
    with _lock:
        _processes.append(process)

    try:
        return process.wait()
    finally:
        with _lock:
            if process in _processes:
                _processes.remove(process)


def run_001proxy_loop() -> None:
    while not _stop_event.is_set():
        run_001proxy_test()
        time.sleep(1)


def stop_all() -> None:
    _stop_event.set()
    with _lock:
        active = list(_processes)

    for process in active:
        try:
            process.terminate()
        except Exception:
            continue

    for process in active:
        try:
            process.wait(timeout=3)
        except Exception:
            try:
                process.kill()
            except Exception:
                continue


def reset_stop() -> None:
    _stop_event.clear()
