from __future__ import annotations


def app_stylesheet() -> str:
    return """
    QWidget {
        font-family: "SF Pro Text", "Helvetica Neue", "Segoe UI", sans-serif;
        font-size: 13px;
        color: #e6e8ec;
        background-color: #111318;
    }

    QMainWindow::separator {
        background: #1c2128;
        width: 1px;
        height: 1px;
    }

    QTabWidget::pane {
        border: 1px solid #242a32;
        border-radius: 12px;
        padding: 12px;
        background: #151820;
    }

    QTabBar::tab {
        background: #1a1f27;
        color: #aab1bb;
        padding: 8px 16px;
        border-radius: 10px;
        margin-right: 8px;
    }

    QTabBar::tab:selected {
        background: #2b6ff8;
        color: #ffffff;
    }

    QPushButton {
        background: #2b6ff8;
        border: none;
        border-radius: 10px;
        padding: 8px 14px;
        font-weight: 600;
    }

    QPushButton:hover {
        background: #1f5fe0;
    }

    QPushButton:pressed {
        background: #184ec0;
    }

    QLineEdit, QComboBox, QTextEdit, QPlainTextEdit {
        background: #1a1f27;
        border: 1px solid #2a313b;
        border-radius: 8px;
        padding: 6px 8px;
        selection-background-color: #2b6ff8;
    }

    QComboBox::drop-down {
        border: none;
        width: 18px;
    }

    QTableWidget {
        background: #151820;
        border: 1px solid #242a32;
        border-radius: 10px;
        gridline-color: #232a32;
    }

    QHeaderView::section {
        background: #1a1f27;
        color: #9aa3af;
        padding: 6px 8px;
        border: none;
        border-bottom: 1px solid #242a32;
    }

    QTableWidget::item:selected {
        background: #24324b;
    }

    QLabel#sectionTitle {
        font-size: 18px;
        font-weight: 600;
        color: #f1f5f9;
    }

    QFrame#statCard {
        background: #1a1f27;
        border: 1px solid #2a313b;
        border-radius: 12px;
        padding: 12px;
    }

    QLabel#statValue {
        font-size: 20px;
        font-weight: 700;
        color: #ffffff;
    }

    QLabel#statHint {
        color: #94a3b8;
    }
    """
