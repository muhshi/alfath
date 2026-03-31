"""
Tarik Data GC PLN dari FASIH Dashboard API (Superset)
=====================================================
Sumber: https://fasih-dashboard.bps.go.id/api/v1/chart/data
Filter: Kab. Demak (UNITUP = [52551] DEMAK, via API filter)
Output: MySQL tabel GC_PLN

Cara pakai:
1. Update cookies.txt dengan cookies terbaru dari browser fasih-dashboard.bps.go.id
2. Update csrf_token.txt dengan X-CSRFToken terbaru
3. Jalankan: python fasih_dashboard.py
"""

import requests
import json
import logging
import sys
import time
from datetime import datetime
from pathlib import Path

import mysql.connector
from mysql.connector import Error as MySQLError

# ============================================================
# KONFIGURASI
# ============================================================

API_URL = "https://fasih-dashboard.bps.go.id/api/v1/chart/data"
DASHBOARD_ID = 514
SLICE_ID = 11365

# Filter values
FILTER_UNITUPI = "[52] JAWA TENGAH DAN DIY"
FILTER_UNITAP = "[52550] GROBOGAN"
FILTER_UNITUP = "[52551] DEMAK"

# Database
DB_CONFIG = {
    "host": "10.133.21.24",
    "user": "root",
    "password": "demak3321",
    "database": "fasih",
}

COOKIES_FILE = Path(__file__).parent / "cookies.txt"
CSRF_TOKEN_FILE = Path(__file__).parent / "csrf_token.txt"

# ============================================================
# LOGGING
# ============================================================

logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(message)s",
    datefmt="%Y-%m-%d %H:%M:%S",
    handlers=[logging.StreamHandler(sys.stdout)],
)
log = logging.getLogger(__name__)

# ============================================================
# COOKIES & CSRF TOKEN
# ============================================================


def load_cookies() -> dict:
    """Load cookies dari cookies.txt dan parse jadi dict."""
    if not COOKIES_FILE.exists():
        log.error(f"File cookies.txt tidak ditemukan: {COOKIES_FILE}")
        sys.exit(1)

    raw = COOKIES_FILE.read_text(encoding="utf-8").strip()
    cookies = {}
    for pair in raw.split(";"):
        pair = pair.strip()
        if "=" in pair:
            key, value = pair.split("=", 1)
            cookies[key.strip()] = value.strip()

    log.info(f"Loaded {len(cookies)} cookies dari cookies.txt")
    return cookies


def load_csrf_token() -> str:
    """Load CSRF token dari csrf_token.txt."""
    if not CSRF_TOKEN_FILE.exists():
        log.warning(f"File csrf_token.txt tidak ditemukan: {CSRF_TOKEN_FILE}")
        return ""

    token = CSRF_TOKEN_FILE.read_text(encoding="utf-8").strip()
    if token:
        log.info(f"Loaded CSRF token ({len(token)} chars)")
    return token


# ============================================================
# REQUEST PAYLOAD
# ============================================================

def build_payload():
    """Bangun request payload untuk Superset Chart API (dengan filter Demak)."""

    # Shared filter definitions
    filters = [
        {"col": "UNITUPI", "op": "IN", "val": [FILTER_UNITUPI]},
        {"col": "UNITAP", "op": "IN", "val": [FILTER_UNITAP]},
        {"col": "UNITUP", "op": "IN", "val": [FILTER_UNITUP]},
        {"col": "date_modified", "op": "TEMPORAL_RANGE", "val": "No filter"},
    ]

    # Shared metrics
    metrics = [
        {
            "aggregate": "SUM",
            "column": {
                "advanced_data_type": None,
                "certification_details": None,
                "certified_by": None,
                "column_name": "OPEN",
                "description": None,
                "expression": None,
                "filterable": True,
                "groupby": True,
                "id": 251970,
                "is_certified": False,
                "is_dttm": False,
                "python_date_format": None,
                "type": "INT",
                "type_generic": 0,
                "verbose_name": None,
                "warning_markdown": None,
            },
            "datasourceWarning": False,
            "expressionType": "SIMPLE",
            "hasCustomLabel": True,
            "label": "OPEN",
            "optionName": "metric_d3fu6qxbkrn_xnrl2z8pxcr",
            "sqlExpression": None,
        },
        {
            "aggregate": None,
            "column": None,
            "datasourceWarning": False,
            "expressionType": "SQL",
            "hasCustomLabel": True,
            "label": "SUBMITTED",
            "optionName": "metric_1pbrcid8yi1_lboi8kuhqg",
            "sqlExpression": "SUM(SUBMITTED) + SUM(COMPLETED) + SUM(EDITED)",
        },
        {
            "aggregate": None,
            "column": None,
            "datasourceWarning": False,
            "expressionType": "SQL",
            "hasCustomLabel": True,
            "label": "REJECTED",
            "optionName": "metric_fdnkf9tijw8_k2budi485e9",
            "sqlExpression": "SUM(REJECTED)+SUM(REVOKED)",
        },
    ]

    # Series limit metric (shared)
    series_limit_metric = {
        "aggregate": "MAX",
        "column": {
            "advanced_data_type": None,
            "changed_on": "2026-03-09T02:08:56.891372",
            "column_name": "level_4_full_code",
            "created_on": "2026-03-09T02:08:56.891358",
            "description": None,
            "expression": None,
            "extra": "{}",
            "filterable": True,
            "groupby": True,
            "id": 253319,
            "is_active": True,
            "is_dttm": False,
            "python_date_format": None,
            "type": "STRING",
            "type_generic": 1,
            "uuid": "9e70a272-6f55-409e-8b8a-19ced1b00dd8",
            "verbose_name": None,
        },
        "datasourceWarning": False,
        "expressionType": "SIMPLE",
        "hasCustomLabel": False,
        "label": "MAX(level_4_full_code)",
        "optionName": "metric_16x0ninyb5d_elwclx66o8",
        "sqlExpression": None,
    }

    return {
        "datasource": {"id": 5978, "type": "table"},
        "force": False,
        "queries": [
            # Query 1: Detail per biller (with columns/groupby)
            {
                "filters": filters,
                "extras": {"time_grain_sqla": "P1D", "having": "", "where": ""},
                "applied_time_extras": {},
                "columns": [
                    "UNITUPI",
                    "UNITAP",
                    "UNITUP",
                    {
                        "expressionType": "SQL",
                        "label": "Email Biller",
                        "sqlExpression": "email_pencacah",
                    },
                ],
                "metrics": metrics,
                "orderby": [
                    [series_limit_metric, False]
                ],
                "annotation_layers": [],
                "row_limit": 50000,
                "series_limit": 0,
                "series_limit_metric": series_limit_metric,
                "order_desc": True,
                "url_params": {},
                "custom_params": {},
                "custom_form_data": {},
                "post_processing": [],
            },
            # Query 2: Summary totals (no columns/groupby)
            {
                "filters": filters,
                "extras": {"time_grain_sqla": "P1D", "having": "", "where": ""},
                "applied_time_extras": {},
                "columns": [],
                "metrics": metrics,
                "annotation_layers": [],
                "row_limit": 0,
                "row_offset": 0,
                "series_limit": 0,
                "series_limit_metric": series_limit_metric,
                "url_params": {},
                "custom_params": {},
                "custom_form_data": {},
                "post_processing": [],
            },
        ],
        "form_data": {
            "datasource": "5978__table",
            "viz_type": "table",
            "slice_id": SLICE_ID,
            "url_params": {},
            "query_mode": "aggregate",
            "groupby": [
                "UNITUPI",
                "UNITAP",
                "UNITUP",
                {
                    "expressionType": "SQL",
                    "label": "Email Biller",
                    "sqlExpression": "email_pencacah",
                },
            ],
            "time_grain_sqla": "P1D",
            "temporal_columns_lookup": {},
            "metrics": metrics,
            "all_columns": [],
            "percent_metrics": [],
            "adhoc_filters": [
                {
                    "clause": "WHERE",
                    "comparator": "No filter",
                    "datasourceWarning": False,
                    "expressionType": "SIMPLE",
                    "filterOptionName": "filter_mm7f81j9nx_yqe6y7b28m7",
                    "isExtra": False,
                    "isNew": False,
                    "operator": "TEMPORAL_RANGE",
                    "sqlExpression": None,
                    "subject": "date_modified",
                },
            ],
            "timeseries_limit_metric": series_limit_metric,
            "order_by_cols": [],
            "row_limit": 50000,
            "server_page_length": 10,
            "order_desc": True,
            "show_totals": True,
            "table_timestamp_format": "smart_date",
            "include_search": True,
            "show_cell_bars": False,
            "color_pn": True,
            "column_config": {
                "OPEN": {"d3NumberFormat": ",d", "d3SmallNumberFormat": "SMART_NUMBER"},
                "SUBMITTED": {"d3NumberFormat": ",d"},
            },
            "conditional_formatting": [],
            "dashboards": [DASHBOARD_ID],
            "extra_form_data": {
                "filters": [
                    {"col": "UNITUPI", "op": "IN", "val": [FILTER_UNITUPI]},
                    {"col": "UNITAP", "op": "IN", "val": [FILTER_UNITAP]},
                    {"col": "UNITUP", "op": "IN", "val": [FILTER_UNITUP]},
                ],
            },
            "label_colors": {},
            "shared_label_colors": {},
            "color_scheme": "echarts5Colors",
            "extra_filters": [],
            "dashboardId": DASHBOARD_ID,
            "force": None,
            "result_format": "json",
            "result_type": "full",
        },
        "result_format": "json",
        "result_type": "full",
    }


# ============================================================
# API CALL
# ============================================================


def fetch_data(cookies: dict, csrf_token: str) -> list:
    """Fetch data dari FASIH Dashboard API (sudah difilter Demak dari API)."""
    log.info("Mengirim request ke FASIH Dashboard API...")
    log.info(f"Filter: UNITUPI={FILTER_UNITUPI}, UNITAP={FILTER_UNITAP}, UNITUP={FILTER_UNITUP}")

    headers = {
        "Accept": "application/json",
        "Accept-Language": "en-US,en;q=0.9",
        "Content-Type": "application/json",
        "Origin": "https://fasih-dashboard.bps.go.id",
        "Referer": f"https://fasih-dashboard.bps.go.id/superset/dashboard/{DASHBOARD_ID}/",
        "User-Agent": "Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) "
                      "AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36",
        "Cache-Control": "no-cache",
        "Pragma": "no-cache",
        "Connection": "keep-alive",
        "Sec-Fetch-Dest": "empty",
        "Sec-Fetch-Mode": "same-origin",
        "Sec-Fetch-Site": "same-origin",
        "sec-ch-ua": '"Chromium";v="146", "Not-A.Brand";v="24", "Google Chrome";v="146"',
        "sec-ch-ua-mobile": "?1",
        "sec-ch-ua-platform": '"Android"',
    }

    # Set CSRF token dari file terpisah atau dari cookies
    if csrf_token:
        headers["X-CSRFToken"] = csrf_token
    else:
        # Fallback: coba ambil dari cookies
        csrf_from_cookie = cookies.get("csrf_access_token", "")
        if csrf_from_cookie:
            headers["X-CSRFToken"] = csrf_from_cookie
            log.info("Menggunakan csrf_access_token dari cookies sebagai X-CSRFToken")

    payload = build_payload()

    # Kirim request ke API Superset
    url = f"{API_URL}?form_data=%7B%22slice_id%22%3A{SLICE_ID}%7D&dashboard_id={DASHBOARD_ID}&force"

    try:
        resp = requests.post(url, headers=headers, cookies=cookies, json=payload, timeout=120)
    except requests.exceptions.RequestException as e:
        log.error(f"Request gagal: {e}")
        return []

    if resp.status_code != 200:
        log.error(f"HTTP {resp.status_code}: {resp.text[:500]}")
        return []

    try:
        result = resp.json()
    except json.JSONDecodeError:
        log.error("Response bukan JSON valid")
        return []

    # Parse Superset response structure
    queries = result.get("result", [])
    if not queries:
        log.error("Response tidak mengandung 'result'")
        return []

    # Ambil data dari query pertama (detail per biller)
    first_query = queries[0]
    data = first_query.get("data", [])
    log.info(f"Total records detail dari API: {len(data)}")

    # Log summary dari query kedua jika ada
    if len(queries) > 1:
        summary = queries[1].get("data", [])
        if summary:
            log.info(f"Summary dari API: {summary}")

    return data


# ============================================================
# DATABASE
# ============================================================

CREATE_TABLE_SQL = """
CREATE TABLE IF NOT EXISTS GC_PLN (
    id INT AUTO_INCREMENT PRIMARY KEY,
    unitupi VARCHAR(200),
    unitap VARCHAR(200),
    unitup VARCHAR(200),
    email_biller VARCHAR(200),
    open_count INT DEFAULT 0,
    submitted_count INT DEFAULT 0,
    rejected_count INT DEFAULT 0,
    fetch_date DATE NOT NULL,
    fetched_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_email_unitup_date (email_biller, unitup, fetch_date),
    INDEX idx_unitup (unitup),
    INDEX idx_email (email_biller),
    INDEX idx_fetch_date (fetch_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
"""

UPSERT_SQL = """
INSERT INTO GC_PLN (unitupi, unitap, unitup, email_biller, open_count, submitted_count, rejected_count, fetch_date, fetched_at)
VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s)
ON DUPLICATE KEY UPDATE
    unitupi = VALUES(unitupi),
    unitap = VALUES(unitap),
    open_count = VALUES(open_count),
    submitted_count = VALUES(submitted_count),
    rejected_count = VALUES(rejected_count),
    fetched_at = VALUES(fetched_at);
"""


def save_to_db(data: list):
    """Simpan data ke MySQL. Upsert jika tanggal sama, insert baru jika beda hari."""
    log.info("Menyimpan data ke database...")

    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        cursor = conn.cursor()
        cursor.execute(CREATE_TABLE_SQL)
        conn.commit()
    except MySQLError as e:
        log.error(f"Database connection error: {e}")
        return 0

    now = datetime.now()
    today = now.date()
    success = 0

    for row in data:
        try:
            cursor.execute(UPSERT_SQL, (
                row.get("UNITUPI", ""),
                row.get("UNITAP", ""),
                row.get("UNITUP", ""),
                row.get("Email Biller", ""),
                int(row.get("OPEN", 0) or 0),
                int(row.get("SUBMITTED", 0) or 0),
                int(row.get("REJECTED", 0) or 0),
                today,
                now,
            ))
            success += 1
        except MySQLError as e:
            log.warning(f"Error insert: {e}")
        except (ValueError, TypeError) as e:
            log.warning(f"Error data: {e}")

    conn.commit()
    cursor.close()
    conn.close()

    log.info(f"Berhasil simpan/update {success}/{len(data)} records ke tabel GC_PLN (tanggal: {today})")
    return success


# ============================================================
# MAIN
# ============================================================


def main():
    start_time = time.time()

    log.info("=" * 60)
    log.info("TARIK DATA FASIH DASHBOARD - SUPERSET API")
    log.info(f"Tanggal  : {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
    log.info(f"Filter   : UNITUP={FILTER_UNITUP}")
    log.info("=" * 60)

    # Load cookies
    cookies = load_cookies()

    # Load CSRF token
    csrf_token = load_csrf_token()

    # Fetch data dari API (sudah difilter Demak dari sisi API)
    data = fetch_data(cookies, csrf_token)
    if not data:
        log.error("Tidak ada data yang diterima dari API. Cek cookies, CSRF token, atau koneksi VPN.")
        return

    # Hitung ringkasan
    total_open = sum(int(r.get("OPEN", 0) or 0) for r in data)
    total_submitted = sum(int(r.get("SUBMITTED", 0) or 0) for r in data)
    total_rejected = sum(int(r.get("REJECTED", 0) or 0) for r in data)

    log.info(f"\nRINGKASAN DATA KAB. DEMAK:")
    log.info(f"  Jumlah Biller : {len(data)}")
    log.info(f"  OPEN          : {total_open:,}")
    log.info(f"  SUBMITTED     : {total_submitted:,}")
    log.info(f"  REJECTED      : {total_rejected:,}")

    # Simpan ke database
    save_to_db(data)

    elapsed = time.time() - start_time
    log.info(f"\n[COMPLETED] Selesai dalam {elapsed:.1f} detik")


if __name__ == "__main__":
    main()
