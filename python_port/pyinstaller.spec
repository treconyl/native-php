# -*- mode: python ; coding: utf-8 -*-
from pathlib import Path

block_cipher = None

project_dir = Path(__file__).resolve().parent
repo_root = project_dir.parent

app = project_dir / "app" / "main.py"
playwright_script = repo_root / "playwright" / "garena-runner.js"

analysis = Analysis(
    [str(app)],
    pathex=[str(project_dir)],
    binaries=[],
    datas=[(str(playwright_script), "playwright")],
    hiddenimports=[],
    hookspath=[],
    runtime_hooks=[],
    excludes=[],
    noarchive=False,
)
pyz = PYZ(analysis.pure, analysis.zipped_data, cipher=block_cipher)

exe = EXE(
    pyz,
    analysis.scripts,
    analysis.binaries,
    analysis.datas,
    [],
    name="garena-tool",
    debug=False,
    bootloader_ignore_signals=False,
    strip=False,
    upx=True,
    console=False,
)
