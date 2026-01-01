from __future__ import annotations

import os

from app.config import settings


def build_node_env() -> dict[str, str]:
    env = os.environ.copy()
    path = env.get("PATH", "")
    extra_paths = ["/opt/homebrew/bin", "/usr/local/bin"]
    for extra in extra_paths:
        if extra not in path:
            path = f"{extra}{os.pathsep}{path}"
    env["PATH"] = path

    node_modules = settings.REPO_ROOT / "node_modules"
    if node_modules.exists():
        env["NODE_PATH"] = str(node_modules)

    return env
