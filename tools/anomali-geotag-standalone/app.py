"""
Aplikasi Web Server Standalone Anomali Geotag SE2026
Dapat dijalankan langsung dengan 'python app.py' atau dikompilasi menjadi .exe tunggal.
"""

import os
import sys
import glob
import socket
import webbrowser
import threading
import time
from flask import Flask, render_template, request, jsonify, Response, send_from_directory
from engine import GeotagAnomalyEngine

# Tentukan direktori dasar (mendukung PyInstaller bundle)
if getattr(sys, 'frozen', False):
    BASE_DIR = sys._MEIPASS
    APP_DIR = os.path.dirname(sys.executable)
else:
    BASE_DIR = os.path.dirname(os.path.abspath(__file__))
    APP_DIR = BASE_DIR

app = Flask(
    __name__,
    template_folder=os.path.join(BASE_DIR, 'templates'),
    static_folder=os.path.join(BASE_DIR, 'static')
)
app.config['MAX_CONTENT_LENGTH'] = 100 * 1024 * 1024  # Maksimal 100MB upload

engine = GeotagAnomalyEngine()

def find_free_port(start_port=5055):
    """Mencari port jaringan lokal yang tersedia."""
    port = start_port
    while port < 6000:
        try:
            with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as s:
                s.bind(('127.0.0.1', port))
                return port
        except OSError:
            port += 1
    return 5055

def auto_load_data_folder():
    """Mendeteksi otomatis file CSV dan GeoJSON di folder 'data/' saat aplikasi dibuka."""
    data_dir = os.path.join(APP_DIR, 'data')
    if not os.path.exists(data_dir):
        os.makedirs(data_dir, exist_ok=True)
        return

    # Cari file CSV
    csv_files = glob.glob(os.path.join(data_dir, '*.csv'))
    if csv_files:
        # Prioritaskan input_anomali.csv jika ada
        target_csv = next((f for f in csv_files if 'input_anomali' in os.path.basename(f).lower()), csv_files[0])
        engine.load_csv(target_csv, filename=os.path.basename(target_csv))

    # Cari file GeoJSON
    geojson_files = glob.glob(os.path.join(data_dir, '*.geojson'))
    if geojson_files:
        target_geojson = next((f for f in geojson_files if 'peta_sls' in os.path.basename(f).lower()), geojson_files[0])
        engine.load_geojson(target_geojson, filename=os.path.basename(target_geojson))

@app.route('/')
def index():
    return render_template('index.html')

@app.route('/api/status')
def api_status():
    return jsonify({
        "status": "ok",
        "has_csv": len(engine.clusters) > 0,
        "has_geojson": len(engine.sls_indexed) > 0,
        "stats": engine.stats
    })

@app.route('/api/data')
def api_data():
    kecamatan = request.args.get('kecamatan')
    severity = request.args.get('severity')
    fraud_category = request.args.get('fraud_category')
    search = request.args.get('search')

    data = engine.get_data(
        kecamatan=kecamatan,
        severity=severity,
        fraud_category=fraud_category,
        search=search
    )
    return jsonify(data)

@app.route('/api/upload', methods=['POST'])
def api_upload():
    uploaded_csv = request.files.get('csv_file')
    uploaded_geojson = request.files.get('geojson_file')

    results = []

    if uploaded_csv and uploaded_csv.filename:
        # Simpan juga ke folder data/ agar bertahan saat restart
        data_dir = os.path.join(APP_DIR, 'data')
        os.makedirs(data_dir, exist_ok=True)
        save_path = os.path.join(data_dir, uploaded_csv.filename)
        uploaded_csv.save(save_path)
        res_csv = engine.load_csv(save_path, filename=uploaded_csv.filename)
        results.append(f"CSV: {res_csv.get('message', 'Sukses')}")

    if uploaded_geojson and uploaded_geojson.filename:
        data_dir = os.path.join(APP_DIR, 'data')
        os.makedirs(data_dir, exist_ok=True)
        save_path = os.path.join(data_dir, uploaded_geojson.filename)
        uploaded_geojson.save(save_path)
        res_geo = engine.load_geojson(save_path, filename=uploaded_geojson.filename)
        results.append(f"GeoJSON: {res_geo.get('message', 'Sukses')}")

    if not results:
        return jsonify({"status": "error", "message": "Tidak ada file yang dipilih untuk di-upload."}), 400

    return jsonify({
        "status": "success",
        "message": " | ".join(results),
        "stats": engine.stats
    })

@app.route('/api/export')
def api_export():
    export_type = request.args.get('type', 'clusters')
    csv_buffer = engine.export_csv_stream(export_type)
    filename = f"anomali_geotag_se2026_{export_type}_{int(time.time())}.csv"

    return Response(
        csv_buffer.getvalue(),
        mimetype="text/csv; charset=utf-8",
        headers={"Content-Disposition": f"attachment; filename={filename}"}
    )

@app.route('/api/shutdown', methods=['POST'])
def api_shutdown():
    func = request.environ.get('werkzeug.server.shutdown')
    if func is None:
        # Alternatif exit
        threading.Thread(target=lambda: (time.sleep(0.5), os._exit(0))).start()
        return jsonify({"status": "ok", "message": "Aplikasi ditutup."})
    func()
    return jsonify({"status": "ok", "message": "Aplikasi ditutup."})

def open_browser(port):
    """Membuka browser otomatis saat aplikasi dijalankan."""
    time.sleep(1.2)
    url = f"http://127.0.0.1:{port}"
    print(f"Membuka antarmuka aplikasi di {url} ...")
    webbrowser.open_new_tab(url)

if __name__ == '__main__':
    auto_load_data_folder()
    port = find_free_port(5055)

    # Jalankan thread untuk auto-open browser
    threading.Thread(target=open_browser, args=(port,), daemon=True).start()

    print(f"=" * 60)
    print(f" APLIKASI DETEKSI ANOMALI GEOTAG SE2026 (STANDALONE)")
    print(f"=" * 60)
    print(f" Server berjalan di: http://127.0.0.1:{port}")
    print(f" Tekan Ctrl+C di terminal ini atau tutup jendela untuk keluar.")
    print(f"=" * 60)

    app.run(host='127.0.0.1', port=port, debug=False)
