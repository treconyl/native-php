from __future__ import annotations

from PySide6 import QtCore, QtWidgets

from app.services import accounts_service, proxies_service
from app.services.queue_service import QueueService
from app.workers.process_pending_worker import run_proxy_loop


class AccountsView(QtWidgets.QWidget):
    def __init__(self) -> None:
        super().__init__()
        layout = QtWidgets.QVBoxLayout(self)

        header = QtWidgets.QLabel("Accounts")
        header.setObjectName("sectionTitle")
        layout.addWidget(header)

        filter_row = QtWidgets.QHBoxLayout()
        self._search = QtWidgets.QLineEdit()
        self._search.setPlaceholderText("Search login...")
        self._status_filter = QtWidgets.QComboBox()
        self._status_filter.addItems(["", "pending", "processing", "success", "failed"])
        filter_btn = QtWidgets.QPushButton("Filter")
        filter_btn.clicked.connect(self.load_accounts)  # type: ignore[attr-defined]
        filter_row.addWidget(self._search)
        filter_row.addWidget(self._status_filter)
        filter_row.addWidget(filter_btn)
        filter_row.addStretch(1)
        layout.addLayout(filter_row)

        actions = QtWidgets.QHBoxLayout()
        add_btn = QtWidgets.QPushButton("Add")
        add_btn.clicked.connect(self.add_account)  # type: ignore[attr-defined]
        import_btn = QtWidgets.QPushButton("Import")
        import_btn.clicked.connect(self.import_accounts)  # type: ignore[attr-defined]
        export_btn = QtWidgets.QPushButton("Export")
        export_btn.clicked.connect(self.export_accounts)  # type: ignore[attr-defined]
        multi_btn = QtWidgets.QPushButton("Run All Active Proxies")
        multi_btn.clicked.connect(self.run_all_active_proxies)  # type: ignore[attr-defined]
        refresh_btn = QtWidgets.QPushButton("Refresh")
        refresh_btn.clicked.connect(self.load_accounts)  # type: ignore[attr-defined]
        actions.addWidget(add_btn)
        actions.addWidget(import_btn)
        actions.addWidget(export_btn)
        actions.addWidget(multi_btn)
        actions.addWidget(refresh_btn)
        actions.addStretch(1)
        layout.addLayout(actions)

        self._table = QtWidgets.QTableWidget(0, 6)
        self._table.setHorizontalHeaderLabels(
            ["Login", "Current Password", "Next Password", "Status", "Last Attempt", "Last Error"]
        )
        self._table.horizontalHeader().setStretchLastSection(True)
        self._table.itemDoubleClicked.connect(self.edit_account)  # type: ignore[attr-defined]
        layout.addWidget(self._table)

        self._queue = QueueService(max_workers=5)
        self.load_accounts()

    def load_accounts(self) -> None:
        self._table.setRowCount(0)
        search = self._search.text().strip() or None
        status = self._status_filter.currentText().strip() or None
        for row in accounts_service.list_accounts(search=search, status=status):
            row_idx = self._table.rowCount()
            self._table.insertRow(row_idx)
            login_item = QtWidgets.QTableWidgetItem(row["login"])
            login_item.setData(QtCore.Qt.ItemDataRole.UserRole, row["id"])
            self._table.setItem(row_idx, 0, login_item)
            self._table.setItem(row_idx, 1, QtWidgets.QTableWidgetItem(row.get("current_password") or ""))
            self._table.setItem(row_idx, 2, QtWidgets.QTableWidgetItem(row.get("next_password") or ""))
            self._table.setItem(row_idx, 3, QtWidgets.QTableWidgetItem(row.get("status") or ""))
            self._table.setItem(row_idx, 4, QtWidgets.QTableWidgetItem(row.get("last_attempted_at") or ""))
            self._table.setItem(row_idx, 5, QtWidgets.QTableWidgetItem(row.get("last_error") or ""))

    def add_account(self) -> None:
        dialog = AccountDialog(self)
        if dialog.exec() == QtWidgets.QDialog.Accepted:
            accounts_service.create_account(dialog.payload())
            self.load_accounts()

    def edit_account(self, item: QtWidgets.QTableWidgetItem) -> None:
        account_id = item.data(QtCore.Qt.ItemDataRole.UserRole)
        if account_id is None:
            return
        row_idx = item.row()
        dialog = AccountDialog(self, self._row_payload(row_idx))
        if dialog.exec() == QtWidgets.QDialog.Accepted:
            accounts_service.update_account(account_id, dialog.payload())
            self.load_accounts()

    def import_accounts(self) -> None:
        file_path, _ = QtWidgets.QFileDialog.getOpenFileName(
            self, "Import Accounts", "", "CSV/TXT (*.csv *.txt)"
        )
        if not file_path:
            return
        result = accounts_service.import_accounts(file_path)
        QtWidgets.QMessageBox.information(
            self,
            "Import",
            "Inserted: {inserted}, Updated: {updated}, Skipped: {skipped}".format(**result),
        )
        self.load_accounts()

    def export_accounts(self) -> None:
        file_path, _ = QtWidgets.QFileDialog.getSaveFileName(
            self, "Export Accounts", "accounts.csv", "CSV (*.csv)"
        )
        if not file_path:
            return
        accounts_service.export_accounts(file_path)
        QtWidgets.QMessageBox.information(self, "Export", "Export done")

    def run_all_active_proxies(self) -> None:
        proxies = proxies_service.list_proxies()
        active = [
            proxy
            for proxy in proxies
            if proxy.get("is_active") and proxy.get("status") == "running"
        ]
        if not active:
            QtWidgets.QMessageBox.warning(self, "Run", "No active proxies")
            return
        for proxy in active:
            self._queue.submit(run_proxy_loop, int(proxy["id"]))

    def _row_payload(self, row_idx: int) -> dict[str, str]:
        return {
            "login": self._table.item(row_idx, 0).text(),
            "current_password": self._table.item(row_idx, 1).text(),
            "next_password": self._table.item(row_idx, 2).text(),
            "status": self._table.item(row_idx, 3).text() or "pending",
            "last_error": self._table.item(row_idx, 5).text(),
        }


class AccountDialog(QtWidgets.QDialog):
    def __init__(self, parent: QtWidgets.QWidget | None = None, payload: dict[str, str] | None = None) -> None:
        super().__init__(parent)
        self.setWindowTitle("Account")
        self.setModal(True)

        layout = QtWidgets.QVBoxLayout(self)
        form = QtWidgets.QFormLayout()

        self._login = QtWidgets.QLineEdit(payload.get("login", "") if payload else "")
        self._current_password = QtWidgets.QLineEdit(payload.get("current_password", "") if payload else "")
        self._next_password = QtWidgets.QLineEdit(payload.get("next_password", "") if payload else "")
        self._status = QtWidgets.QComboBox()
        self._status.addItems(["pending", "processing", "success", "failed"])
        if payload and payload.get("status") in ["pending", "processing", "success", "failed"]:
            self._status.setCurrentText(payload["status"])
        self._last_error = QtWidgets.QLineEdit(payload.get("last_error", "") if payload else "")

        form.addRow("Login", self._login)
        form.addRow("Current Password", self._current_password)
        form.addRow("Next Password", self._next_password)
        form.addRow("Status", self._status)
        form.addRow("Last Error", self._last_error)
        layout.addLayout(form)

        buttons = QtWidgets.QDialogButtonBox(QtWidgets.QDialogButtonBox.Save | QtWidgets.QDialogButtonBox.Cancel)
        buttons.accepted.connect(self.accept)  # type: ignore[attr-defined]
        buttons.rejected.connect(self.reject)  # type: ignore[attr-defined]
        layout.addWidget(buttons)

    def payload(self) -> dict[str, str]:
        return {
            "login": self._login.text().strip(),
            "current_password": self._current_password.text().strip(),
            "next_password": self._next_password.text().strip(),
            "status": self._status.currentText(),
            "last_error": self._last_error.text().strip(),
        }
