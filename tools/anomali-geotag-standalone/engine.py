"""
SE2026 Geotag Anomaly Detection Standalone Engine
Mengolah data CSV titik koordinat dan GeoJSON SLS tanpa dependensi database.
Universal untuk seluruh BPS Kabupaten/Kota se-Indonesia dengan fitur setara versi Web.
"""

import os
import io
import csv
import math
import json
import re
import datetime
from collections import defaultdict
from typing import Dict, List, Any, Optional, Tuple

try:
    from shapely.geometry import shape, Point
    from shapely.prepared import prep
    HAS_SHAPELY = True
except ImportError:
    HAS_SHAPELY = False

# Master 14 Kecamatan Map (Demak BPS Codes as default reference)
KEC_NAME_MAP = {
    '3321010': 'Mranggen',
    '3321020': 'Karangawen',
    '3321030': 'Guntur',
    '3321040': 'Sayung',
    '3321050': 'Karangtengah',
    '3321060': 'Bonang',
    '3321070': 'Demak',
    '3321080': 'Wonosalam',
    '3321090': 'Dempet',
    '3321091': 'Kebonagung',
    '3321100': 'Gajah',
    '3321110': 'Karanganyar',
    '3321120': 'Mijen',
    '3321130': 'Wedung',
}


class GeotagAnomalyEngine:
    def __init__(self):
        self.raw_points_count = 0
        self.clusters: Dict[str, Dict[str, Any]] = {}
        self.petugas_ranking: List[Dict[str, Any]] = []
        self.petugas_with_clusters: List[Dict[str, Any]] = []
        self.kecamatan_summary: List[Dict[str, Any]] = []
        self.sls_geojson: Dict[str, Any] = {"type": "FeatureCollection", "features": []}
        self.sls_indexed: List[Dict[str, Any]] = []
        self.stats = {
            "total_points": 0,
            "total_clusters": 0,
            "total_petugas": 0,
            "total_ekstrem": 0,
            "total_berat": 0,
            "total_sedang": 0,
            "total_ringan": 0,
            "total_fraud_btt": 0,
            "total_fraud_points": 0,
            "total_fraud_clusters": 0,
            "total_fraud_sls": 0,
            "total_wajar_points": 0,
            "total_wajar_clusters": 0,
            "total_campuran_points": 0,
            "total_campuran_clusters": 0,
            "csv_filename": "",
            "geojson_filename": "",
            "generated_at": datetime.datetime.now().strftime("%d %b %Y | %H:%M WIB"),
        }

    @staticmethod
    def format_email_to_name(email: str) -> str:
        """Mengubah email menjadi format nama rapi (contoh: budi.santoso@bps.go.id -> Budi Santoso)"""
        if not email:
            return "Petugas Tidak Diketahui"
        clean = re.sub(r'@.*$', '', email)
        clean = re.sub(r'[._\d]+', ' ', clean).strip()
        parts = [p.capitalize() for p in clean.split() if len(p) > 1]
        return " ".join(parts) if parts else email

    def load_csv(self, file_path_or_buffer, filename: str = "data.csv") -> Dict[str, Any]:
        """Parse file CSV SQL Lab SE2026 dan bangun dataset klaster anomali."""
        self.clusters.clear()
        self.petugas_ranking.clear()
        self.petugas_with_clusters.clear()
        self.kecamatan_summary.clear()
        self.raw_points_count = 0
        self.stats["csv_filename"] = filename
        self.stats["generated_at"] = datetime.datetime.now().strftime("%d %b %Y | %H:%M WIB")

        if isinstance(file_path_or_buffer, str):
            f = open(file_path_or_buffer, mode='r', encoding='utf-8-sig', errors='replace')
            should_close = True
        else:
            f = io.StringIO(file_path_or_buffer.read().decode('utf-8-sig', errors='replace'))
            should_close = False

        reader = csv.reader(f)
        try:
            header = next(reader)
        except StopIteration:
            if should_close: f.close()
            return {"status": "error", "message": "File CSV kosong."}

        header_lower = [col.strip().lower() for col in header]

        # Auto-detect column indexes
        def find_col(candidates):
            for c in candidates:
                if c in header_lower:
                    return header_lower.index(c)
            return None

        idx_email = find_col(['pencacah_email', 'email', 'email_pencacah', 'petugas_email', 'enumerator_email'])
        idx_size = find_col(['cluster_size', 'jml_titik', 'size', 'count', 'banyak_titik'])
        idx_non_bku = find_col(['cluster_non_bku_size', 'non_bku_size', 'non_bku'])
        idx_label = find_col(['kode_bang_label', 'jenis_bangunan', 'label_bangunan', 'bangunan_label'])
        idx_nama_assign = find_col(['nama_assignment', 'nama_usaha', 'nama_responden', 'nama'])
        idx_no_bang = find_col(['no_bang', 'nomor_bangunan', 'no_bangunan'])
        idx_c_lat = find_col(['cluster_center_lat', 'center_lat', 'cluster_lat', 'lat_pusat'])
        idx_c_lon = find_col(['cluster_center_lon', 'center_lon', 'cluster_lon', 'lon_pusat'])
        idx_min_lat = find_col(['cluster_min_lat', 'min_lat'])
        idx_max_lat = find_col(['cluster_max_lat', 'max_lat'])
        idx_min_lon = find_col(['cluster_min_lon', 'min_lon'])
        idx_max_lon = find_col(['cluster_max_lon', 'max_lon'])
        idx_avg_acc = find_col(['cluster_avg_accuracy', 'avg_accuracy', 'accuracy'])
        idx_assign = find_col(['assignment_id', 'id_assignment', 'id'])
        idx_p_lat = find_col(['point_lat', 'latitude', 'lat'])
        idx_p_lon = find_col(['point_lon', 'longitude', 'lon'])
        idx_p_acc = find_col(['point_accuracy', 'p_acc', 'akurasi', 'accuracy_point'])
        idx_sub_sls = find_col(['id_sub_sls', 'sub_sls', 'kode_sub_sls', 'kodesubsls', 'id_subsls'])
        idx_pml = find_col(['pml_nama', 'nama_pml', 'pengawas', 'pml_email', 'email_pengawas'])
        idx_nmkec = find_col(['namakec', 'nama_kec', 'kecamatan', 'nmkec'])
        idx_kdkec = find_col(['kodekec', 'kode_kec', 'kdkec', 'kd_kec_bps'])
        idx_nmdesa = find_col(['namadesa', 'nama_desa', 'desa', 'kelurahan', 'nmdesa'])
        idx_kddesa = find_col(['kodedesa', 'kode_desa', 'kddesa'])
        idx_nmsls = find_col(['namasls', 'nama_sls', 'sls', 'nmsls'])
        idx_kdsls = find_col(['kodesls', 'kode_sls', 'kdsls', 'idsls'])

        if idx_email is None or idx_c_lat is None or idx_c_lon is None:
            if should_close: f.close()
            return {
                "status": "error",
                "message": f"Kolom wajib tidak ditemukan! Pastikan CSV memuat pencacah_email, cluster_center_lat, dan cluster_center_lon."
            }

        for row in reader:
            if not row or len(row) <= max(idx_email, idx_c_lat, idx_c_lon):
                continue

            self.raw_points_count += 1
            email = row[idx_email].strip().lower()
            c_lat_str = row[idx_c_lat].strip()
            c_lon_str = row[idx_c_lon].strip()

            if not email or not c_lat_str or not c_lon_str:
                continue

            try:
                c_lat = float(c_lat_str)
                c_lon = float(c_lon_str)
            except ValueError:
                continue

            cluster_key = f"{email}|{c_lat:.6f}|{c_lon:.6f}"

            raw_sub_sls = row[idx_sub_sls].strip() if idx_sub_sls is not None and len(row) > idx_sub_sls else ""
            pml_name = row[idx_pml].strip() if idx_pml is not None and len(row) > idx_pml else "-"
            csv_kec = row[idx_nmkec].strip().title() if idx_nmkec is not None and len(row) > idx_nmkec else ""
            csv_kdkec = row[idx_kdkec].strip() if idx_kdkec is not None and len(row) > idx_kdkec else ""
            csv_desa = row[idx_nmdesa].strip().title() if idx_nmdesa is not None and len(row) > idx_nmdesa else ""
            csv_kddesa = row[idx_kddesa].strip() if idx_kddesa is not None and len(row) > idx_kddesa else ""
            csv_sls = row[idx_nmsls].strip() if idx_nmsls is not None and len(row) > idx_nmsls else ""
            csv_kdsls = row[idx_kdsls].strip() if idx_kdsls is not None and len(row) > idx_kdsls else ""

            if raw_sub_sls and len(raw_sub_sls) >= 7:
                sub_kd = raw_sub_sls[:7]
                if sub_kd in KEC_NAME_MAP and not csv_kec:
                    csv_kdkec = sub_kd
                    csv_kec = KEC_NAME_MAP[sub_kd]

            if cluster_key not in self.clusters:
                size = int(row[idx_size]) if idx_size is not None and row[idx_size].isdigit() else 1
                min_lat = float(row[idx_min_lat]) if idx_min_lat is not None and row[idx_min_lat] else c_lat
                max_lat = float(row[idx_max_lat]) if idx_max_lat is not None and row[idx_max_lat] else c_lat
                min_lon = float(row[idx_min_lon]) if idx_min_lon is not None and row[idx_min_lon] else c_lon
                max_lon = float(row[idx_max_lon]) if idx_max_lon is not None and row[idx_max_lon] else c_lon
                avg_acc = float(row[idx_avg_acc]) if idx_avg_acc is not None and row[idx_avg_acc] else 0.0

                # Radius sebaran (meter)
                lat_dist_m = abs(max_lat - min_lat) * 111320
                lon_dist_m = abs(max_lon - min_lon) * 111320 * math.cos(math.radians(c_lat))
                approx_radius_m = round(math.sqrt(lat_dist_m**2 + lon_dist_m**2), 1)

                # Severity
                if size > 100:
                    severity = 'ekstrem'
                    severity_label = '🚨 Ekstrem (>100)'
                    badge_class = 'bg-danger text-white'
                    marker_color = '#dc2626'
                elif size > 50:
                    severity = 'berat'
                    severity_label = '⚠️ Berat (51-100)'
                    badge_class = 'bg-orange text-white'
                    marker_color = '#ea580c'
                elif size > 20:
                    severity = 'sedang'
                    severity_label = '🟡 Sedang (21-50)'
                    badge_class = 'bg-warning text-dark'
                    marker_color = '#ca8a04'
                else:
                    severity = 'ringan'
                    severity_label = '🔵 Ringan (10-20)'
                    badge_class = 'bg-info text-white'
                    marker_color = '#0284c7'

                nama_petugas = self.format_email_to_name(email)
                cluster_id = f"cls_{abs(hash(cluster_key)) % 10000000:07d}"

                self.clusters[cluster_key] = {
                    'id': cluster_id,
                    'key': cluster_key,
                    'email': email,
                    'nama_petugas': nama_petugas,
                    'kodekec': csv_kdkec,
                    'namakec': csv_kec or 'Wilayah Terdeteksi',
                    'kodedesa': csv_kddesa,
                    'namadesa': csv_desa or '-',
                    'kodesls': csv_kdsls,
                    'namasls': csv_sls or '-',
                    'id_sub_sls': raw_sub_sls,
                    'sub_sls_short': raw_sub_sls[-4:] if len(raw_sub_sls) >= 4 else raw_sub_sls,
                    'pml_nama': pml_name or '-',
                    'cluster_size': size,
                    'bku_count': 0,
                    'btt_count': 0,
                    'campuran_count': 0,
                    'lainnya_count': 0,
                    'pasar_kw_count': 0,
                    'center_lat': c_lat,
                    'center_lon': c_lon,
                    'approx_radius_m': approx_radius_m,
                    'avg_accuracy': round(avg_acc, 2),
                    'severity': severity,
                    'severity_label': severity_label,
                    'badge_class': badge_class,
                    'marker_color': marker_color,
                    'google_maps_url': f"https://www.google.com/maps?q={c_lat},{c_lon}&z=19&t=k",
                    'sample_names': [],
                    'points': [],
                }

            cls_obj = self.clusters[cluster_key]
            if raw_sub_sls and not cls_obj.get('id_sub_sls'):
                cls_obj['id_sub_sls'] = raw_sub_sls
                cls_obj['sub_sls_short'] = raw_sub_sls[-4:] if len(raw_sub_sls) >= 4 else raw_sub_sls

            label = row[idx_label].strip() if idx_label is not None and len(row) > idx_label else ""
            nama_assign = row[idx_nama_assign].strip() if idx_nama_assign is not None and len(row) > idx_nama_assign else ""
            p_lat = float(row[idx_p_lat]) if idx_p_lat is not None and len(row) > idx_p_lat and row[idx_p_lat] else c_lat
            p_lon = float(row[idx_p_lon]) if idx_p_lon is not None and len(row) > idx_p_lon and row[idx_p_lon] else c_lon
            p_acc = float(row[idx_p_acc]) if idx_p_acc is not None and len(row) > idx_p_acc and row[idx_p_acc] else cls_obj['avg_accuracy']
            assign_id = row[idx_assign].strip() if idx_assign is not None and len(row) > idx_assign else ""
            no_bang = row[idx_no_bang].strip() if idx_no_bang is not None and len(row) > idx_no_bang else ""

            if nama_assign and len(cls_obj['sample_names']) < 4 and nama_assign not in cls_obj['sample_names']:
                cls_obj['sample_names'].append(nama_assign)

            # Categorize building type & detect pasar keywords
            lbl_lower = label.lower()
            is_bku = ('1. bangunan khusus usaha' in lbl_lower) or ('khusus usaha' in lbl_lower)
            is_campuran = ('2. bangunan campuran' in lbl_lower) or ('campuran' in lbl_lower)
            is_btt = any(x in lbl_lower for x in ['3. bangunan tempat tinggal', '4. bangunan tempat tinggal', '5. bangunan lainnya yang tercakup', 'tempat tinggal'])
            is_pasar_kw = bool(nama_assign and re.search(r'\b(pasar|los|kios|lapak|toko|warung|ruko|pedagang|ikan|sayur|buah)\b', nama_assign, re.I))

            if is_pasar_kw:
                cls_obj['pasar_kw_count'] += 1

            if is_bku or is_pasar_kw:
                cls_obj['bku_count'] += 1
                b_type = 'bku'
                point_color = '#10b981'  # Green for BKU/Pasar
            elif is_btt:
                cls_obj['btt_count'] += 1
                b_type = 'btt'
                point_color = '#ef4444'  # Red for BTT (Fraud)
            elif is_campuran:
                cls_obj['campuran_count'] += 1
                b_type = 'campuran'
                point_color = '#f59e0b'  # Amber for Campuran
            else:
                cls_obj['lainnya_count'] += 1
                b_type = 'lainnya'
                point_color = '#8b5cf6'  # Purple for Others

            # Points structure matches the Web Blade structure
            cls_obj['points'].append([
                round(p_lat, 7),
                round(p_lon, 7),
                assign_id[:8] if assign_id else f"#{len(cls_obj['points']) + 1}",
                b_type,
                label or 'Tipe Bangunan Belum Terdata',
                point_color,
                nama_assign,
                no_bang,
                assign_id,
                raw_sub_sls,
                cls_obj['namadesa'],
                cls_obj['namasls'],
                round(p_acc, 1)
            ])

        if should_close:
            f.close()

        # Fraud classification & summary compilation
        self._finalize_clusters()

        # Re-run spatial matching if GeoJSON is already loaded
        if self.sls_indexed:
            self.match_clusters_with_sls()

        return {
            "status": "success",
            "message": f"Berhasil memproses {self.raw_points_count:,} titik ke dalam {len(self.clusters):,} klaster unik.",
            "total_points": self.raw_points_count,
            "total_clusters": len(self.clusters)
        }

    def _finalize_clusters(self):
        """Menyelesaikan klasifikasi fraud (BTT vs BKU), judul klaster, ordinal, dan ranking petugas."""
        petugas_map = defaultdict(lambda: {
            'email': '',
            'nama': '',
            'namakec': '',
            'pml_nama': '-',
            'total_clusters': 0,
            'total_anomali_points': 0,
            'total_btt_points': 0,
            'total_bku_points': 0,
            'max_cluster_size': 0,
            'clusters': [],
            'top_cluster_lat': 0.0,
            'top_cluster_lon': 0.0,
            'top_cluster_id': '',
            'severity_counts': defaultdict(int)
        })

        stat_ekstrem = 0
        stat_berat = 0
        stat_sedang = 0
        stat_ringan = 0
        stat_fraud_btt = 0
        stat_fraud_points = 0
        stat_wajar_clusters = 0
        stat_wajar_points = 0
        stat_campuran_clusters = 0
        stat_campuran_points = 0

        # Sort clusters by size desc
        sorted_clusters = sorted(self.clusters.values(), key=lambda x: x['cluster_size'], reverse=True)
        officer_cluster_counts = defaultdict(int)

        for c in sorted_clusters:
            total = len(c['points']) if c['points'] else c['cluster_size']
            c['cluster_size'] = total
            btt = c['btt_count']
            bku = c['bku_count']
            campuran = c['campuran_count']
            lainnya = c['lainnya_count']
            pasar_kw = c.get('pasar_kw_count', 0)

            pct_bku = round((bku / max(1, total)) * 100)
            pct_btt = round((btt / max(1, total)) * 100)
            pct_campuran = round((campuran / max(1, total)) * 100)
            pct_lainnya = round((lainnya / max(1, total)) * 100)

            c['pct_bku'] = pct_bku
            c['pct_btt'] = pct_btt
            c['pct_campuran'] = pct_campuran
            c['pct_lainnya'] = pct_lainnya

            # Fraud classification matching Se2026ClusterAnomalyService
            if bku >= (total * 0.40) or pasar_kw >= (total * 0.40):
                c['fraud_category'] = 'wajar_bku'
                c['fraud_label'] = '🟢 Potensi Wajar (Pasar / Ruko BKU)'
                c['fraud_badge'] = 'bg-success text-white'
                c['fraud_summary'] = f"{pct_bku}% BKU (Pasar/Usaha)" if bku >= pasar_kw else "Sentra Pasar/Kios"
                stat_wajar_clusters += 1
                stat_wajar_points += total
            elif btt >= (total * 0.35) and pasar_kw < (total * 0.20):
                c['fraud_category'] = 'fraud_btt'
                c['fraud_label'] = '🚨 Indikasi Kuat Fraud (BTT/Tempat Tinggal)'
                c['fraud_badge'] = 'bg-danger text-white'
                c['fraud_summary'] = f"{pct_btt}% BTT (Tempat Tinggal)"
                stat_fraud_btt += 1
                stat_fraud_points += total
            else:
                c['fraud_category'] = 'campuran'
                c['fraud_label'] = '🟡 Campuran (BTT & BKU)'
                c['fraud_badge'] = 'bg-warning text-dark'
                c['fraud_summary'] = f"Campuran ({pct_bku}% BKU, {pct_btt}% BTT)"
                stat_campuran_clusters += 1
                stat_campuran_points += total

            # Officer ordinal and title
            email = c['email']
            officer_cluster_counts[email] += 1
            c['officer_cluster_num'] = officer_cluster_counts[email]
            c['cluster_title'] = f"Klaster #{c['officer_cluster_num']} ({c['cluster_size']} Titik)"
            c['cluster_ordinal_text'] = f"Klaster #{c['officer_cluster_num']}"
            c['landmark'] = c['sample_names'][0] if c.get('sample_names') else ''

            sev = c['severity']
            if sev == 'ekstrem': stat_ekstrem += 1
            elif sev == 'berat': stat_berat += 1
            elif sev == 'sedang': stat_sedang += 1
            elif sev == 'ringan': stat_ringan += 1

            # Aggregate per Petugas
            p = petugas_map[c['email']]
            p['email'] = c['email']
            p['nama'] = c['nama_petugas']
            p['namakec'] = c['namakec']
            if c.get('pml_nama') and c['pml_nama'] != '-':
                p['pml_nama'] = c['pml_nama']
            p['total_clusters'] += 1
            p['total_anomali_points'] += c['cluster_size']
            p['total_btt_points'] += btt
            p['total_bku_points'] += bku
            p['severity_counts'][sev] += 1
            p['clusters'].append(c['id'])

            if c['cluster_size'] > p['max_cluster_size']:
                p['max_cluster_size'] = c['cluster_size']
                p['top_cluster_lat'] = c['center_lat']
                p['top_cluster_lon'] = c['center_lon']
                p['top_cluster_id'] = c['id']

        # Sort and rank petugas
        ranked = sorted(petugas_map.values(), key=lambda x: x['total_anomali_points'], reverse=True)
        for i, p in enumerate(ranked, 1):
            p['rank'] = i
            max_size = p['max_cluster_size']
            if max_size > 100:
                p['severity_label'] = '🚨 Kritis'
                p['severity_badge'] = 'bg-danger text-white'
            elif max_size > 50:
                p['severity_label'] = '⚠️ Tinggi'
                p['severity_badge'] = 'bg-orange text-white'
            elif max_size > 20:
                p['severity_label'] = '🟡 Sedang'
                p['severity_badge'] = 'bg-warning text-dark'
            else:
                p['severity_label'] = '🔵 Rendah'
                p['severity_badge'] = 'bg-info text-white'

        self.petugas_ranking = ranked

        # Build hierarchical officer accordion (petugas_with_clusters)
        petugas_grouped = {}
        for c in sorted_clusters:
            email = c['email']
            if email not in petugas_grouped:
                petugas_grouped[email] = {
                    'email': email,
                    'nama': c['nama_petugas'],
                    'namakec': c['namakec'],
                    'pml_nama': c.get('pml_nama', '-'),
                    'total_clusters': 0,
                    'total_points': 0,
                    'total_btt': 0,
                    'total_bku': 0,
                    'max_cluster_size': 0,
                    'clusters': [],
                }
            pg = petugas_grouped[email]
            pg['total_clusters'] += 1
            pg['total_points'] += c['cluster_size']
            pg['total_btt'] += c['btt_count']
            pg['total_bku'] += c['bku_count']
            if c['cluster_size'] > pg['max_cluster_size']:
                pg['max_cluster_size'] = c['cluster_size']
            pg['clusters'].append(c)

        self.petugas_with_clusters = sorted(petugas_grouped.values(), key=lambda x: x['total_points'], reverse=True)

        # Build kecamatan_summary
        kec_map = defaultdict(lambda: {
            'code': '',
            'name': '',
            'total_clusters': 0,
            'petugas_emails': set(),
            'total_points': 0,
            'total_bku_points': 0,
            'total_btt_points': 0,
            'total_fraud_clusters': 0,
            'total_wajar_clusters': 0,
            'max_cluster_size': 0
        })

        for c in self.clusters.values():
            kname = c['namakec'] or 'Lainnya / Tidak Terpetakan'
            kd = c.get('kodekec') or 'other'
            kitem = kec_map[kname]
            kitem['name'] = kname
            kitem['code'] = kd
            kitem['total_clusters'] += 1
            kitem['petugas_emails'].add(c['email'])
            kitem['total_points'] += c['cluster_size']
            kitem['total_bku_points'] += c['bku_count']
            kitem['total_btt_points'] += c['btt_count']
            if c['fraud_category'] == 'fraud_btt':
                kitem['total_fraud_clusters'] += 1
            elif c['fraud_category'] == 'wajar_bku':
                kitem['total_wajar_clusters'] += 1
            if c['cluster_size'] > kitem['max_cluster_size']:
                kitem['max_cluster_size'] = c['cluster_size']

        summary_list = []
        for kname, kitem in kec_map.items():
            summary_list.append({
                'code': kitem['code'],
                'name': kitem['name'],
                'total_clusters': kitem['total_clusters'],
                'total_petugas': len(kitem['petugas_emails']),
                'total_points': kitem['total_points'],
                'total_bku_points': kitem['total_bku_points'],
                'total_btt_points': kitem['total_btt_points'],
                'total_fraud_clusters': kitem['total_fraud_clusters'],
                'total_wajar_clusters': kitem['total_wajar_clusters'],
                'max_cluster_size': kitem['max_cluster_size'],
            })
        self.kecamatan_summary = sorted(summary_list, key=lambda x: x['total_points'], reverse=True)

        # Update stats
        self.stats.update({
            "total_points": self.raw_points_count,
            "total_clusters": len(self.clusters),
            "total_petugas": len(self.petugas_ranking),
            "total_ekstrem": stat_ekstrem,
            "total_berat": stat_berat,
            "total_sedang": stat_sedang,
            "total_ringan": stat_ringan,
            "total_fraud_btt": stat_fraud_btt,
            "total_fraud_points": stat_fraud_points,
            "total_fraud_clusters": stat_fraud_btt,
            "total_wajar_points": stat_wajar_points,
            "total_wajar_clusters": stat_wajar_clusters,
            "total_campuran_points": stat_campuran_points,
            "total_campuran_clusters": stat_campuran_clusters,
        })

    def load_geojson(self, file_path_or_buffer, filename: str = "peta_sls.geojson") -> Dict[str, Any]:
        """Parse file GeoJSON poligon SLS batas wilayah kabupaten."""
        self.sls_indexed.clear()
        self.stats["geojson_filename"] = filename

        if isinstance(file_path_or_buffer, str):
            with open(file_path_or_buffer, 'r', encoding='utf-8', errors='replace') as f:
                data = json.load(f)
        else:
            data = json.loads(file_path_or_buffer.read().decode('utf-8', errors='replace'))

        features = data.get('features', [])
        if not features:
            return {"status": "error", "message": "GeoJSON tidak memiliki features poligon."}

        for f in features:
            geom = f.get('geometry')
            props = f.get('properties', {})
            if not geom or geom.get('type') not in ['Polygon', 'MultiPolygon']:
                continue

            if HAS_SHAPELY:
                try:
                    s_geom = shape(geom)
                    min_x, min_y, max_x, max_y = s_geom.bounds
                    item = {
                        'min_x': min_x, 'min_y': min_y,
                        'max_x': max_x, 'max_y': max_y,
                        'geometry': geom,
                        'properties': props,
                        'shapely_geom': s_geom,
                        'prepared': prep(s_geom),
                    }
                    self.sls_indexed.append(item)
                    continue
                except Exception:
                    pass

            # Fallback manual bbox calculation
            coords = geom.get('coordinates', [])
            min_x, min_y, max_x, max_y = 180.0, 90.0, -180.0, -90.0

            def scan_coords(c_list):
                nonlocal min_x, min_y, max_x, max_y
                if not isinstance(c_list, (list, tuple)): return
                if len(c_list) >= 2 and isinstance(c_list[0], (int, float)) and isinstance(c_list[1], (int, float)):
                    lon, lat = float(c_list[0]), float(c_list[1])
                    if lon < min_x: min_x = lon
                    if lon > max_x: max_x = lon
                    if lat < min_y: min_y = lat
                    if lat > max_y: max_y = lat
                else:
                    for sub in c_list:
                        scan_coords(sub)

            scan_coords(coords)

            item = {
                'min_x': min_x, 'min_y': min_y,
                'max_x': max_x, 'max_y': max_y,
                'geometry': geom,
                'properties': props,
                'shapely_geom': None,
                'prepared': None,
            }
            self.sls_indexed.append(item)

        # Match clusters
        if self.clusters:
            self.match_clusters_with_sls()

        return {
            "status": "success",
            "message": f"Berhasil memuat {len(self.sls_indexed):,} poligon SLS dari {filename}.",
            "total_sls": len(self.sls_indexed)
        }

    def match_clusters_with_sls(self):
        """Mencocokkan titik klaster anomali dengan poligon SLS kabupaten secara instan."""
        if not self.clusters or not self.sls_indexed:
            return

        matched_features = []
        sls_matched_ids = set()

        for c in self.clusters.values():
            lat = c['center_lat']
            lon = c['center_lon']

            for sls in self.sls_indexed:
                # Fast BBox reject
                if not (sls['min_x'] <= lon <= sls['max_x'] and sls['min_y'] <= lat <= sls['max_y']):
                    continue

                is_inside = False
                if sls['prepared']:
                    is_inside = sls['prepared'].contains(Point(lon, lat))
                else:
                    is_inside = True

                if is_inside:
                    props = sls['properties']
                    idsls = props.get('idsls') or props.get('id_sls') or str(props.get('OBJECTID', ''))
                    nmsls = props.get('nmsls') or props.get('nama_sls') or ''
                    nmdesa = props.get('nmdesa') or props.get('nama_desa') or ''
                    nmkec = props.get('nmkec') or props.get('nama_kec') or ''
                    kdkec = props.get('kd_kec_bps') or props.get('kdkec') or ''

                    c['sls_id'] = idsls
                    c['kodesls'] = idsls
                    c['namasls'] = nmsls
                    c['namadesa'] = nmdesa
                    c['sls_nama'] = f"{nmsls} - {nmdesa}".strip(' -')
                    if nmkec:
                        c['namakec'] = nmkec.title()
                    if kdkec:
                        c['kodekec'] = kdkec

                    # Update point references if desa/sls empty
                    for pt in c['points']:
                        if not pt[10] or pt[10] == '-': pt[10] = nmdesa
                        if not pt[11] or pt[11] == '-': pt[11] = nmsls

                    if idsls and idsls not in sls_matched_ids:
                        sls_matched_ids.add(idsls)
                        matched_features.append({
                            "type": "Feature",
                            "properties": {
                                "idsls": idsls,
                                "nmsls": nmsls,
                                "nmdesa": nmdesa,
                                "nmkec": nmkec,
                                "kd_kec_bps": kdkec,
                                "fraud_clusters_count": props.get('fraud_clusters_count', 1),
                                "fraud_points_count": props.get('fraud_points_count', c['cluster_size']),
                                "petugas_list": props.get('petugas_list', [c['nama_petugas']]),
                            },
                            "geometry": sls['geometry']
                        })
                    break

        self.sls_geojson = {
            "type": "FeatureCollection",
            "features": matched_features
        }
        self.stats["total_sls_terdampak"] = len(sls_matched_ids)
        self.stats["total_fraud_sls"] = len(sls_matched_ids)

        # Re-finalize clusters with newly matched SLS & kecamatan
        self._finalize_clusters()

    def get_data(self, kecamatan: Optional[str] = None, severity: Optional[str] = None,
                 fraud_category: Optional[str] = None, search: Optional[str] = None) -> Dict[str, Any]:
        """Mengambil data klaster terfilter dan daftar opsi filter."""
        filtered_clusters = list(self.clusters.values())
        filtered_petugas = list(self.petugas_ranking)

        if kecamatan:
            kec_clean = kecamatan.strip().lower()
            filtered_clusters = [c for c in filtered_clusters if kec_clean in c['namakec'].lower() or kec_clean == str(c.get('kodekec', '')).lower()]
            filtered_petugas = [p for p in filtered_petugas if kec_clean in p['namakec'].lower()]

        if severity:
            sev_clean = severity.strip().lower()
            filtered_clusters = [c for c in filtered_clusters if c['severity'].lower() == sev_clean]
            filtered_petugas = [p for p in filtered_petugas if p['severity_counts'][sev_clean] > 0]

        if fraud_category:
            fraud_clean = fraud_category.strip().lower()
            filtered_clusters = [c for c in filtered_clusters if c.get('fraud_category', '').lower() == fraud_clean]

        if search:
            q = search.strip().lower()
            filtered_clusters = [
                c for c in filtered_clusters
                if q in c['nama_petugas'].lower()
                or q in c['email'].lower()
                or q in c['id'].lower()
                or q in c.get('sls_nama', '').lower()
                or q in c.get('namadesa', '').lower()
                or q in c.get('namasls', '').lower()
                or q in c.get('landmark', '').lower()
                or q in c.get('pml_nama', '').lower()
            ]
            filtered_petugas = [
                p for p in filtered_petugas
                if q in p['nama'].lower()
                or q in p['email'].lower()
                or q in p.get('pml_nama', '').lower()
                or q in p.get('namakec', '').lower()
            ]

        # Re-group filtered clusters per officer for hierarchical accordion view
        petugas_grouped = {}
        for c in filtered_clusters:
            email = c['email']
            if email not in petugas_grouped:
                petugas_grouped[email] = {
                    'email': email,
                    'nama': c['nama_petugas'],
                    'namakec': c['namakec'],
                    'pml_nama': c.get('pml_nama', '-'),
                    'total_clusters': 0,
                    'total_points': 0,
                    'total_btt': 0,
                    'total_bku': 0,
                    'max_cluster_size': 0,
                    'clusters': [],
                }
            pg = petugas_grouped[email]
            pg['total_clusters'] += 1
            pg['total_points'] += c['cluster_size']
            pg['total_btt'] += c['btt_count']
            pg['total_bku'] += c['bku_count']
            if c['cluster_size'] > pg['max_cluster_size']:
                pg['max_cluster_size'] = c['cluster_size']
            pg['clusters'].append(c)

        filtered_petugas_with_clusters = sorted(petugas_grouped.values(), key=lambda x: x['total_points'], reverse=True)

        kecamatans = sorted(list(set(c['namakec'] for c in self.clusters.values() if c['namakec'] and c['namakec'] != 'Wilayah Terdeteksi')))

        # Dynamic KPI based on current filtered clusters
        fraud_c = [c for c in filtered_clusters if c.get('fraud_category') == 'fraud_btt']
        wajar_c = [c for c in filtered_clusters if c.get('fraud_category') == 'wajar_bku']
        camp_c = [c for c in filtered_clusters if c.get('fraud_category') == 'campuran']

        kpi = {
            'total_points': sum(c['cluster_size'] for c in filtered_clusters),
            'total_clusters': len(filtered_clusters),
            'total_petugas': len(set(c['email'] for c in filtered_clusters)),
            'total_fraud_clusters': len(fraud_c),
            'total_fraud_points': sum(c['cluster_size'] for c in fraud_c),
            'total_fraud_sls': self.stats.get('total_sls_terdampak', 0),
            'total_wajar_clusters': len(wajar_c),
            'total_wajar_points': sum(c['cluster_size'] for c in wajar_c),
            'total_campuran_clusters': len(camp_c),
            'total_campuran_points': sum(c['cluster_size'] for c in camp_c),
        }

        return {
            "stats": self.stats,
            "kpi": kpi,
            "clusters": filtered_clusters,
            "petugas_ranking": filtered_petugas,
            "petugas_with_clusters": filtered_petugas_with_clusters,
            "kecamatan_summary": self.kecamatan_summary,
            "kecamatan_options": kecamatans,
            "sls_geojson": self.sls_geojson,
            "generated_at": self.stats["generated_at"],
        }

    def export_csv_stream(self, export_type: str = 'clusters', cluster_id: Optional[str] = None) -> io.StringIO:
        """Menghasilkan CSV string dengan UTF-8 BOM untuk dibuka di Excel."""
        output = io.StringIO()
        output.write('\ufeff')
        writer = csv.writer(output)

        if export_type == 'petugas':
            writer.writerow([
                'Rank', 'Nama Petugas', 'Email', 'Kecamatan', 'PML (Pengawas)',
                'Tingkat Risiko', 'Total Klaster', 'Titik BTT (Rumah/Fraud)', 'Titik BKU (Pasar/Wajar)',
                'Titik Terbanyak 1 Spot', 'Total Titik Anomali', 'Koordinat Klaster Terbesar'
            ])
            for p in self.petugas_ranking:
                writer.writerow([
                    p.get('rank', ''),
                    p.get('nama', ''),
                    p.get('email', ''),
                    p.get('namakec', ''),
                    p.get('pml_nama', '-'),
                    p.get('severity_label', ''),
                    p.get('total_clusters', 0),
                    p.get('total_btt_points', 0),
                    p.get('total_bku_points', 0),
                    p.get('max_cluster_size', 0),
                    p.get('total_anomali_points', 0),
                    f"{p.get('top_cluster_lat', '')}, {p.get('top_cluster_lon', '')}"
                ])
        elif export_type in ['titik', 'detail_bangunan']:
            writer.writerow([
                'No', 'ID Klaster', 'Label Klaster', 'Nama Petugas', 'Email Petugas', 'Kecamatan',
                'Kode Desa', 'Nama Desa', 'Kode SLS', 'Nama SLS', 'Kode Sub-SLS',
                'ID Assignment', 'No Bangunan', 'Nama Usaha / Responden',
                'Jenis Bangunan', 'Tipe Anomali', 'Latitude Titik', 'Longitude Titik', 'Akurasi GPS (meter)', 'Google Maps Link Titik'
            ])
            point_no = 1
            for c in self.clusters.values():
                if cluster_id and c['id'] != cluster_id:
                    continue

                cluster_label = c.get('cluster_title') or f"Klaster #{c.get('officer_cluster_num', 1)}"

                for pt in c['points']:
                    p_lat = pt[0] if len(pt) > 0 else c['center_lat']
                    p_lon = pt[1] if len(pt) > 1 else c['center_lon']
                    b_type = pt[3] if len(pt) > 3 else 'lainnya'
                    b_label = pt[4] if len(pt) > 4 else '-'
                    nama_assign = pt[6] if len(pt) > 6 else ''
                    no_bang = pt[7] if len(pt) > 7 else ''
                    full_assign_id = pt[8] if len(pt) > 8 else (pt[2] if len(pt) > 2 else '')
                    sub_sls = pt[9] if len(pt) > 9 else (c.get('id_sub_sls', ''))
                    pt_desa = pt[10] if len(pt) > 10 and pt[10] != '-' else c.get('namadesa', '')
                    pt_sls = pt[11] if len(pt) > 11 and pt[11] != '-' else c.get('namasls', '')
                    p_acc = pt[12] if len(pt) > 12 else c.get('avg_accuracy', '')

                    if b_type == 'bku':
                        b_type_name = 'BKU (Khusus Usaha)'
                    elif b_type == 'btt':
                        b_type_name = 'BTT (Tempat Tinggal)'
                    elif b_type == 'campuran':
                        b_type_name = 'Campuran (Usaha & Hunian)'
                    else:
                        b_type_name = 'Lainnya / Bangunan Rusak'

                    writer.writerow([
                        point_no,
                        c['id'],
                        cluster_label,
                        c['nama_petugas'],
                        c['email'],
                        c['namakec'],
                        c.get('kodedesa', ''),
                        pt_desa,
                        c.get('kodesls', ''),
                        pt_sls,
                        sub_sls,
                        full_assign_id,
                        no_bang,
                        nama_assign,
                        b_type_name,
                        b_label,
                        p_lat,
                        p_lon,
                        p_acc,
                        f"https://www.google.com/maps?q={p_lat},{p_lon}&z=20&t=k",
                    ])
                    point_no += 1
        else:
            # clusters export
            writer.writerow([
                'ID Klaster', 'Label Klaster', 'Nama Petugas', 'Email', 'Kecamatan',
                'Kode Desa', 'Nama Desa', 'Kode SLS', 'Nama SLS', 'Kode Sub-SLS', 'Landmark / Usaha Utama',
                'PML (Pengawas)', 'Klasifikasi Fraud', 'Komposisi', 'Titik BTT (Rumah)', 'Titik BKU (Pasar)',
                'Tingkat Keparahan', 'Jumlah Titik Bertumpuk', 'Lat Pusat', 'Lon Pusat',
                'Radius Sebaran (meter)', 'Akurasi GPS (meter)', 'Google Maps Link'
            ])
            for c in self.clusters.values():
                writer.writerow([
                    c.get('id', ''),
                    c.get('cluster_title', f"Klaster #{c.get('officer_cluster_num', 1)}"),
                    c.get('nama_petugas', ''),
                    c.get('email', ''),
                    c.get('namakec', ''),
                    c.get('kodedesa', ''),
                    c.get('namadesa', ''),
                    c.get('kodesls', ''),
                    c.get('namasls', ''),
                    c.get('id_sub_sls', ''),
                    c.get('landmark', ''),
                    c.get('pml_nama', '-'),
                    c.get('fraud_label', '-'),
                    c.get('fraud_summary', '-'),
                    c.get('btt_count', 0),
                    c.get('bku_count', 0),
                    c.get('severity_label', ''),
                    c.get('cluster_size', 0),
                    c.get('center_lat', ''),
                    c.get('center_lon', ''),
                    c.get('approx_radius_m', ''),
                    c.get('avg_accuracy', ''),
                    c.get('google_maps_url', '')
                ])

        output.seek(0)
        return output
