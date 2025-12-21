from __future__ import annotations

from PySide6 import QtWidgets

from app.services import accounts_service, proxies_service


class DashboardView(QtWidgets.QWidget):
    def __init__(self) -> None:
        super().__init__()
        layout = QtWidgets.QVBoxLayout(self)

        title = QtWidgets.QLabel("System Overview")
        title.setStyleSheet("font-size: 18px; font-weight: 600;")
        layout.addWidget(title)

        self._stats_label = QtWidgets.QLabel("")
        layout.addWidget(self._stats_label)

        refresh = QtWidgets.QPushButton("Refresh")
        refresh.clicked.connect(self.refresh)  # type: ignore[attr-defined]
        layout.addWidget(refresh)

        layout.addStretch(1)

        self.refresh()

    def refresh(self) -> None:
        account_stats = accounts_service.stats()
        proxy_stats = proxies_service.stats()
        self._stats_label.setText(
            "Accounts: {total} (pending {pending}, success {success}, failed {failed}) | "
            "Proxies: {running}/{total} running".format(
                **account_stats, **proxy_stats
            )
        )
