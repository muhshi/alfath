"""
SE2026 Geotag Anomaly Detection Standalone Engine
Mengolah data CSV titik koordinat dan GeoJSON SLS tanpa dependensi database.
Universal untuk seluruh BPS Kabupaten/Kota se-Indonesia.
"""

import os
import io
import csv
import math
import json
import re
from collections import defaultdict
from typing import Dict, List, Any, Optional, Tuple

try:
    from shapely.geometry import shape, Point
    from shapely.prepared import prep
    HAS_SHAPELY = True
except ImportError:
    HAS_SHAPELY = False


class GeotagAnomalyEngine:
    def __init__(self):
        self.raw_points_count = 0
        self.clusters: Dict[str, Dict[str, Any]] = {}
        self.petugas_ranking: List[Dict[str, Any]] = []
        self.kecamatan_stats: Dict[str, Dict[str, Any]] = {}
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
            "total_sls_terdampak": 0,
            "csv_filename": "",
            "geojson_filename": "",
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
        self.kecamatan_stats.clear()
        self.raw_points_count = 0
        self.stats["csv_filename"] = filename

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
                    'kodekec': '',
                    'namakec': 'Wilayah Terdeteksi',
                    'sls_id': '',
                    'sls_nama': '',
                    'cluster_size': size,
                    'bku_count': 0,
                    'btt_count': 0,
                    'campuran_count': 0,
                    'lainnya_count': 0,
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

            # Update point info inside cluster
            cls_obj = self.clusters[cluster_key]
            label = row[idx_label].strip() if idx_label is not None else ""
            nama_assign = row[idx_nama_assign].strip() if idx_nama_assign is not None else ""
            p_lat = float(row[idx_p_lat]) if idx_p_lat is not None and row[idx_p_lat] else c_lat
            p_lon = float(row[idx_p_lon]) if idx_p_lon is not None and row[idx_p_lon] else c_lon

            if nama_assign and len(cls_obj['sample_names']) < 3 and nama_assign not in cls_obj['sample_names']:
                cls_obj['sample_names'].append(nama_assign)

            # Categorize building type
            lbl_lower = label.lower()
            if '1. bangunan khusus usaha' in lbl_lower or 'khusus usaha' in lbl_lower:
                cls_obj['bku_count'] += 1
                b_type = 'BKU'
            elif '2. bangunan campuran' in lbl_lower or 'campuran' in lbl_lower:
                cls_obj['campuran_count'] += 1
                b_type = 'Campuran'
            elif any(x in lbl_lower for x in ['3. bangunan tempat tinggal', '4. bangunan tempat tinggal', '5. bangunan lainnya yang tercakup', 'tempat tinggal']):
                cls_obj['btt_count'] += 1
                b_type = 'BTT'
            else:
                cls_obj['lainnya_count'] += 1
                b_type = 'Lainnya'

            if len(cls_obj['points']) < 50:
                cls_obj['points'].append({
                    'lat': p_lat,
                    'lon': p_lon,
                    'name': nama_assign,
                    'type': b_type,
                    'label': label,
                })

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
        """Menyelesaikan klasifikasi fraud (BTT vs BKU) dan ranking petugas."""
        petugas_map = defaultdict(lambda: {
            'email': '',
            'nama': '',
            'namakec': '',
            'total_clusters': 0,
            'total_anomali_points': 0,
            'total_btt_points': 0,
            'total_bku_points': 0,
            'max_cluster_size': 0,
            'clusters': [],
            'top_cluster_lat': 0.0,
            'top_cluster_lon': 0.0,
            'severity_counts': defaultdict(int)
        })

        stat_ekstrem = 0
        stat_berat = 0
        stat_sedang = 0
        stat_ringan = 0
        stat_fraud_btt = 0

        for c in self.clusters.values():
            btt = c['btt_count']
            bku = c['bku_count']
            cmp = c['campuran_count']
            oth = c['lainnya_count']

            # Fraud categorization
            if btt > 0 and bku == 0:
                c['fraud_category'] = 'fraud_btt'
                c['fraud_label'] = '🚨 Rekayasa Geotag BTT'
                c['fraud_badge'] = 'bg-danger text-white'
                c['fraud_summary'] = f'100% Non-BKU ({btt} BTT Rumah)'
                stat_fraud_btt += 1
            elif btt > 0 and bku > 0:
                c['fraud_category'] = 'campuran'
                c['fraud_label'] = '⚠️ Campuran (BTT & BKU)'
                c['fraud_badge'] = 'bg-warning text-dark'
                c['fraud_summary'] = f'{btt} BTT Rumah + {bku} BKU Usaha'
            elif bku > 0 and btt == 0:
                c['fraud_category'] = 'wajar_bku'
                c['fraud_label'] = '✅ Klaster Wajar BKU'
                c['fraud_badge'] = 'bg-success text-white'
                c['fraud_summary'] = f'{bku} BKU Usaha (Pasar/Sentra)'
            else:
                c['fraud_category'] = 'lainnya'
                c['fraud_label'] = 'ℹ️ Non-Usaha/Lainnya'
                c['fraud_badge'] = 'bg-secondary text-white'
                c['fraud_summary'] = f'{oth} Bangunan Lainnya'

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

        # Sort and rank petugas
        ranked = sorted(petugas_map.values(), key=lambda x: x['total_anomali_points'], reverse=True)
        for i, p in enumerate(ranked, 1):
            p['rank'] = i
            if p['severity_counts']['ekstrem'] > 0:
                p['severity_label'] = '🚨 Kritis'
                p['severity_badge'] = 'bg-danger text-white'
            elif p['severity_counts']['berat'] > 0:
                p['severity_label'] = '⚠️ Tinggi'
                p['severity_badge'] = 'bg-orange text-white'
            elif p['severity_counts']['sedang'] > 0:
                p['severity_label'] = '🟡 Sedang'
                p['severity_badge'] = 'bg-warning text-dark'
            else:
                p['severity_label'] = '🔵 Rendah'
                p['severity_badge'] = 'bg-info text-white'

        self.petugas_ranking = ranked

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

                    c['sls_id'] = idsls
                    c['sls_nama'] = f"{nmsls} - {nmdesa}".strip(' -')
                    if nmkec:
                        c['namakec'] = nmkec.title()

                    if idsls and idsls not in sls_matched_ids:
                        sls_matched_ids.add(idsls)
                        matched_features.append({
                            "type": "Feature",
                            "properties": {
                                "idsls": idsls,
                                "nmsls": nmsls,
                                "nmdesa": nmdesa,
                                "nmkec": nmkec,
                                "fraud_count": 1,
                            },
                            "geometry": sls['geometry']
                        })
                    break

        self.sls_geojson = {
            "type": "FeatureCollection",
            "features": matched_features
        }
        self.stats["total_sls_terdampak"] = len(sls_matched_ids)

        for p in self.petugas_ranking:
            kec_counts = defaultdict(int)
            for cid in p['clusters']:
                for c in self.clusters.values():
                    if c['id'] == cid and c.get('namakec'):
                        kec_counts[c['namakec']] += 1
            if kec_counts:
                top_kec = max(kec_counts.items(), key=lambda x: x[1])[0]
                p['namakec'] = top_kec

    def get_data(self, kecamatan: Optional[str] = None, severity: Optional[str] = None,
                 fraud_category: Optional[str] = None, search: Optional[str] = None) -> Dict[str, Any]:
        """Mengambil data klaster terfilter dan daftar opsi filter."""
        filtered = list(self.clusters.values())

        if kecamatan:
            kec_clean = kecamatan.strip().lower()
            filtered = [c for c in filtered if kec_clean in c['namakec'].lower()]

        if severity:
            sev_clean = severity.strip().lower()
            filtered = [c for c in filtered if c['severity'].lower() == sev_clean]

        if fraud_category:
            fraud_clean = fraud_category.strip().lower()
            filtered = [c for c in filtered if c.get('fraud_category', '').lower() == fraud_clean]

        if search:
            q = search.strip().lower()
            filtered = [c for c in filtered if q in c['nama_petugas'].lower() or q in c['email'].lower() or q in c['id'].lower() or q in c.get('sls_nama', '').lower()]

        kecamatans = sorted(list(set(c['namakec'] for c in self.clusters.values() if c['namakec'])))

        return {
            "stats": self.stats,
            "clusters": filtered,
            "petugas_ranking": self.petugas_ranking,
            "kecamatan_options": kecamatans,
            "sls_geojson": self.sls_geojson,
        }

    def export_csv_stream(self, export_type: str = 'clusters') -> io.StringIO:
        """Menghasilkan CSV string dengan UTF-8 BOM untuk dibuka di Excel."""
        output = io.StringIO()
        output.write('\ufeff')
        writer = csv.writer(output)

        if export_type == 'petugas':
            writer.writerow([
                'Rank', 'Nama Petugas', 'Email', 'Kecamatan',
                'Tingkat Risiko', 'Total Klaster', 'Titik BTT (Rumah/Fraud)', 'Titik BKU (Pasar/Wajar)',
                'Titik Terbanyak 1 Spot', 'Total Titik Anomali', 'Koordinat Klaster Terbesar'
            ])
            for p in self.petugas_ranking:
                writer.writerow([
                    p.get('rank', ''),
                    p.get('nama', ''),
                    p.get('email', ''),
                    p.get('namakec', ''),
                    p.get('severity_label', ''),
                    p.get('total_clusters', 0),
                    p.get('total_btt_points', 0),
                    p.get('total_bku_points', 0),
                    p.get('max_cluster_size', 0),
                    p.get('total_anomali_points', 0),
                    f"{p.get('top_cluster_lat', '')}, {p.get('top_cluster_lon', '')}"
                ])
        else:
            writer.writerow([
                'ID Klaster', 'Nama Petugas', 'Email', 'Kecamatan', 'SLS Terdampak',
                'Klasifikasi Fraud', 'Komposisi Bangunan', 'Titik BTT (Rumah)', 'Titik BKU (Pasar)',
                'Tingkat Keparahan', 'Jumlah Titik Bertumpuk', 'Lat Pusat', 'Lon Pusat',
                'Radius Sebaran (meter)', 'Akurasi GPS (meter)', 'Google Maps Link'
            ])
            for c in self.clusters.values():
                writer.writerow([
                    c.get('id', ''),
                    c.get('nama_petugas', ''),
                    c.get('email', ''),
                    c.get('namakec', ''),
                    c.get('sls_nama', ''),
                    c.get('fraud_label', ''),
                    c.get('fraud_summary', ''),
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
