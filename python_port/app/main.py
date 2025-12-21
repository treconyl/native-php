from __future__ import annotations

import sys

from PySide6 import QtWidgets

from app.services import db
from app.ui.accounts import AccountsView
from app.ui.dashboard import DashboardView
from app.ui.garena_test import GarenaTestView
from app.ui.proxies import ProxiesView


class MainWindow(QtWidgets.QMainWindow):
    def __init__(self) -> None:
        super().__init__()
        self.setWindowTitle("Garena Tool")
        self.resize(1280, 720)

        tabs = QtWidgets.QTabWidget()
        tabs.addTab(DashboardView(), "Dashboard")
        tabs.addTab(ProxiesView(), "Proxy Keys")
        tabs.addTab(AccountsView(), "Accounts")
        tabs.addTab(GarenaTestView(), "Account Test")

        self.setCentralWidget(tabs)


def main() -> int:
    db.ensure_paths()
    db.migrate()

    app = QtWidgets.QApplication(sys.argv)
    window = MainWindow()
    window.show()

    return app.exec()


if __name__ == "__main__":
    raise SystemExit(main())
