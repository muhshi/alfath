"""
Tarik Data GC PLN dari FASIH Dashboard API (Superset)
=====================================================
Sumber: https://fasih-dashboard.bps.go.id/api/v1/chart/data
Filter: Kab. Demak (UNITUP mengandung 'DEMAK')
Output: MySQL tabel GC_PLN

Cara pakai:
1. Update cookies.txt dengan cookies terbaru dari browser fasih-dashboard.bps.go.id
2. Jalankan: python fasih_dashboard.py
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

# Database
DB_CONFIG = {
    "host": "10.133.21.24",
    "user": "root",
    "password": "demak3321",
    "database": "fasih",
}

COOKIES_FILE = Path(__file__).parent / "cookies.txt"

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
# COOKIES
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


# ============================================================
# REQUEST PAYLOAD
# ============================================================

def build_payload():
    """Bangun request payload untuk Superset Chart API."""
    return {
        "datasource": {"id": 5978, "type": "table"},
        "force": False,
        "queries": [
            {
                "filters": [
                    {"col": "date_modified", "op": "TEMPORAL_RANGE", "val": "No filter"}
                ],
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
                "metrics": [
                    {
                        "aggregate": "SUM",
                        "column": {
                            "column_name": "OPEN",
                            "type": "INT",
                            "type_generic": 0,
                            "id": 251970,
                            "filterable": True,
                            "groupby": True,
                        },
                        "expressionType": "SIMPLE",
                        "hasCustomLabel": True,
                        "label": "OPEN",
                    },
                    {
                        "expressionType": "SQL",
                        "hasCustomLabel": True,
                        "label": "SUBMITTED",
                        "sqlExpression": "SUM(SUBMITTED) + SUM(COMPLETED) + SUM(EDITED)",
                    },
                    {
                        "expressionType": "SQL",
                        "hasCustomLabel": True,
                        "label": "REJECTED",
                        "sqlExpression": "SUM(REJECTED)+SUM(REVOKED)",
                    },
                ],
                "orderby": [
                    [
                        {
                            "aggregate": "MAX",
                            "column": {
                                "column_name": "level_4_full_code",
                                "type": "STRING",
                                "type_generic": 1,
                                "id": 253319,
                            },
                            "expressionType": "SIMPLE",
                            "hasCustomLabel": False,
                            "label": "MAX(level_4_full_code)",
                        },
                        False,
                    ]
                ],
                "row_limit": 50000,
                "series_limit": 0,
                "order_desc": True,
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
            "dashboards": [DASHBOARD_ID],
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


def fetch_data(cookies: dict) -> list:
    """Fetch data dari FASIH Dashboard API."""
    log.info("Mengirim request ke FASIH Dashboard API...")

    headers = {
        "Accept": "application/json",
        "Content-Type": "application/json",
        "Origin": "https://fasih-dashboard.bps.go.id",
        "Referer": f"https://fasih-dashboard.bps.go.id/superset/dashboard/{DASHBOARD_ID}/",
        "User-Agent": "Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) "
                      "AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36",
        "Cache-Control": "no-cache",
        "Pragma": "no-cache",
    }

    # Ambil CSRF token dari cookies jika ada
    csrf_token = cookies.get("csrf_access_token", "")
    if csrf_token:
        headers["X-CSRFToken"] = csrf_token

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

    # Ambil data dari query pertama
    first_query = queries[0]
    data = first_query.get("data", [])
    log.info(f"Total records dari API: {len(data)}")

    return data


# ============================================================
# FILTER KAB. DEMAK
# ============================================================


def filter_demak(data: list) -> list:
    """Filter data khusus Kab. Demak berdasarkan kolom UNITUP."""
    filtered = []
    for row in data:
        unitup = str(row.get("UNITUP", "")).upper()
        if "DEMAK" in unitup:
            filtered.append(row)

    log.info(f"Data Kab. Demak: {len(filtered)} dari {len(data)} total records")
    return filtered


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
    log.info(f"Filter   : Kab. Demak")
    log.info("=" * 60)

    # Load cookies
    cookies = load_cookies()

    # Fetch data dari API
    all_data = fetch_data(cookies)
    if not all_data:
        log.error("Tidak ada data yang diterima dari API. Cek cookies atau koneksi VPN.")
        return

    # Filter Demak
    demak_data = filter_demak(all_data)
    if not demak_data:
        log.warning("Tidak ada data Kab. Demak ditemukan dalam response.")
        return

    # Hitung ringkasan
    total_open = sum(int(r.get("OPEN", 0) or 0) for r in demak_data)
    total_submitted = sum(int(r.get("SUBMITTED", 0) or 0) for r in demak_data)
    total_rejected = sum(int(r.get("REJECTED", 0) or 0) for r in demak_data)

    log.info(f"\nRINGKASAN DATA KAB. DEMAK:")
    log.info(f"  Jumlah Biller : {len(demak_data)}")
    log.info(f"  OPEN          : {total_open:,}")
    log.info(f"  SUBMITTED     : {total_submitted:,}")
    log.info(f"  REJECTED      : {total_rejected:,}")

    # Simpan ke database
    save_to_db(demak_data)

    elapsed = time.time() - start_time
    log.info(f"\n[COMPLETED] Selesai dalam {elapsed:.1f} detik")


if __name__ == "__main__":
    main()
