"""
Pemetaan lowongan (req_pk_loker) -> skill ESCO (skill_nodes) ke tabel lowongan_skills.

Alur:
  1. Ambil data sekali saja (loker + skill ESCO) — semua pemrosesan di sisi script,
     bukan di database.
  2. Bersihkan HTML deskripsi, pecah menjadi poin/kalimat persyaratan.
  3. Lapis leksikal : frasa utuh cocok dengan judul/label alternatif ESCO atau kamus sinonim.
  4. Lapis semantik : kemiripan makna kalimat vs skill (model multibahasa, embedding).
  5. Gabungkan, ambil skill teratas per lowongan, tulis laporan (dan DB bila --write).

Contoh:
  python map_skills.py --limit 100                 # uji coba 100 loker, hanya laporan
  python map_skills.py --limit 0 --write --versi v1  # semua loker, tulis ke database
"""

from __future__ import annotations

import argparse
import csv
import html
import random
import re
import sys
import time
from collections import defaultdict
from dataclasses import dataclass
from pathlib import Path

import numpy as np
import psycopg

from sinonim import LABEL_TERLALU_UMUM, PENANDA_DIUTAMAKAN, SINONIM

ROOT = Path(__file__).resolve().parent
ENV_FILE = ROOT.parent.parent / "api" / ".env"
CACHE_DIR = ROOT / "cache"
OUTPUT_DIR = ROOT / "output"

SKOR_LEKSIKAL_JUDUL = 0.95
SKOR_LEKSIKAL_LABEL = 0.90
SKOR_SINONIM = 0.90
MAX_NGRAM = 6


# ---------------------------------------------------------------------------
# Koneksi database (kredensial dibaca dari api/.env)
# ---------------------------------------------------------------------------

def read_env(path: Path) -> dict[str, str]:
    env: dict[str, str] = {}
    for line in path.read_text(encoding="utf-8").splitlines():
        line = line.strip()
        if not line or line.startswith("#") or "=" not in line:
            continue
        key, value = line.split("=", 1)
        value = re.sub(r"\s+#.*$", "", value).strip().strip('"').strip("'")
        env[key.strip()] = value
    return env


def connect() -> psycopg.Connection:
    env = read_env(ENV_FILE)
    return psycopg.connect(
        host=env["DB_HOST"],
        port=env.get("DB_PORT", "5432"),
        dbname=env["DB_DATABASE"],
        user=env["DB_USERNAME"],
        password=env["DB_PASSWORD"],
        connect_timeout=30,
        # Batas aman agar query tidak membebani database bersama
        options="-c statement_timeout=60000",
    )


# ---------------------------------------------------------------------------
# Data
# ---------------------------------------------------------------------------

@dataclass
class Skill:
    id: int
    title: str
    title_en: str
    alt_labels_en: list[str]


@dataclass
class Loker:
    vac_id: str
    judul: str
    bidang: str
    deskripsi: str


@dataclass
class Hasil:
    skill_id: int
    skor: float
    metode: str
    bukti: str
    tipe: str


def load_data(conn: psycopg.Connection) -> tuple[list[Skill], list[Loker]]:
    with conn.cursor() as cur:
        cur.execute("""
            SELECT id, title, COALESCE(title_en, ''), COALESCE(alt_labels_en, '{}')
            FROM skill_nodes WHERE type = 'skill'
        """)
        skills = [Skill(r[0], r[1] or "", r[2], list(r[3])) for r in cur.fetchall()]

        cur.execute("""
            SELECT vac_id, COALESCE(judul_pekerjaan, ''), COALESCE(bidang_pekerjaan, ''),
                   COALESCE(deskripsi_pekerjaan, '')
            FROM req_pk_loker ORDER BY id
        """)
        lokers = [Loker(*r) for r in cur.fetchall()]
    return skills, lokers


# ---------------------------------------------------------------------------
# Pembersihan & segmentasi teks
# ---------------------------------------------------------------------------

def clean_html(text: str) -> str:
    text = html.unescape(html.unescape(text))  # sebagian data ter-escape dua kali
    text = re.sub(r"(?i)<\s*(br|/p|/li|li|/div|/h\d|/tr|/ul|/ol)[^>]*>", "\n", text)
    text = re.sub(r"<[^>]+>", " ", text)
    return text.replace("\xa0", " ")


BULLET = re.compile(r"(?:^|\s)(?:\d{1,2}[.)]|[-•*·▪●►✓])\s+")
KALIMAT = re.compile(r"(?<=[.!?;])\s+(?=[A-Z0-9])")
KONTAK = re.compile(r"@|https?://|www\.|wa\.me|\b0?8\d{7,}\b", re.I)


def segment(text: str) -> list[str]:
    segments: list[str] = []
    for line in clean_html(text).split("\n"):
        for part in BULLET.split(line):
            for kalimat in KALIMAT.split(part):
                kalimat = re.sub(r"\s+", " ", kalimat).strip(" -:;,.")
                if len(kalimat) < 3 or KONTAK.search(kalimat):
                    continue
                # Kalimat sangat panjang dipotong per ~300 karakter
                while len(kalimat) > 400:
                    cut = kalimat.rfind(" ", 0, 300)
                    cut = cut if cut > 0 else 300
                    segments.append(kalimat[:cut])
                    kalimat = kalimat[cut:].strip()
                segments.append(kalimat)
    return segments


def normalize(text: str) -> str:
    return re.sub(r"\s+", " ", re.sub(r"[^\w+#]+", " ", text.lower())).strip()


def tipe_keahlian(segmen: str) -> str:
    lower = segmen.lower()
    return "diutamakan" if any(p in lower for p in PENANDA_DIUTAMAKAN) else "wajib"


# ---------------------------------------------------------------------------
# Lapis leksikal
# ---------------------------------------------------------------------------

def build_lexical_index(skills: list[Skill]) -> dict[str, list[tuple[int, float]]]:
    index: dict[str, list[tuple[int, float]]] = defaultdict(list)
    by_title_en = {normalize(s.title_en): s.id for s in skills if s.title_en}

    def add(label: str, skill_id: int, skor: float, check_generic: bool = True) -> None:
        key = normalize(label)
        words = key.split()
        if not words or len(words) > MAX_NGRAM:
            return
        # Label ESCO satu kata ("plan", "migration") terlalu umum -> hanya lewat kamus sinonim
        if check_generic and (len(words) < 2 or key in LABEL_TERLALU_UMUM):
            return
        if all(sid != skill_id for sid, _ in index[key]):
            index[key].append((skill_id, skor))

    for s in skills:
        add(s.title_en, s.id, SKOR_LEKSIKAL_JUDUL)
        add(s.title, s.id, SKOR_LEKSIKAL_JUDUL)
        for label in s.alt_labels_en:
            add(label, s.id, SKOR_LEKSIKAL_LABEL)

    missing = []
    for istilah, targets in SINONIM.items():
        for target in targets:
            skill_id = by_title_en.get(normalize(target))
            if skill_id is None:
                missing.append(f"{istilah} -> {target}")
                continue
            add(istilah, skill_id, SKOR_SINONIM, check_generic=False)
    if missing:
        print(f"  ! {len(missing)} sinonim tidak menemukan skill ESCO (dilewati):")
        for m in missing:
            print(f"      {m}")
    return index


def lexical_matches(segmen: str, index: dict[str, list[tuple[int, float]]]) -> list[tuple[int, float]]:
    tokens = normalize(segmen).split()
    found: list[tuple[int, float]] = []
    for n in range(1, MAX_NGRAM + 1):
        for i in range(len(tokens) - n + 1):
            found.extend(index.get(" ".join(tokens[i:i + n]), ()))
    return found


# ---------------------------------------------------------------------------
# Lapis semantik
# ---------------------------------------------------------------------------

def load_model(name: str):
    from sentence_transformers import SentenceTransformer
    return SentenceTransformer(name)


def skill_embeddings(model, model_name: str, skills: list[Skill]) -> np.ndarray:
    CACHE_DIR.mkdir(exist_ok=True)
    safe = re.sub(r"[^\w.-]+", "_", model_name)
    path = CACHE_DIR / f"skills_{safe}_{len(skills)}_{skills[-1].id}.npy"
    if path.exists():
        return np.load(path)
    print(f"  Membuat embedding {len(skills):,} skill ESCO (sekali saja, lalu disimpan ke cache)...")
    texts = [f"passage: {s.title} / {s.title_en}" for s in skills]
    emb = model.encode(texts, batch_size=64, normalize_embeddings=True, show_progress_bar=True)
    np.save(path, emb)
    return emb


# ---------------------------------------------------------------------------
# Pemetaan
# ---------------------------------------------------------------------------

def map_lokers(lokers, skills, index, model, skill_emb, args) -> dict[str, list[Hasil]]:
    results: dict[str, list[Hasil]] = {}
    batch_size = 50

    for start in range(0, len(lokers), batch_size):
        batch = lokers[start:start + batch_size]
        seg_lists = [[l.judul] + segment(l.deskripsi) for l in batch]

        # Semantik hanya untuk kalimat yang cukup informatif; judul (indeks 0) dan
        # kalimat pendek ("Jobdesk", "Caregiver") hanya dicocokkan secara leksikal
        semantic_idx = [
            [j for j, s in enumerate(segs) if j > 0 and len(s.split()) >= args.min_words]
            for segs in seg_lists
        ]
        sims_by_loker: list[dict[int, np.ndarray]] = [{} for _ in batch]
        if model is not None:
            flat, owners = [], []
            for i, (segs, idxs) in enumerate(zip(seg_lists, semantic_idx)):
                for j in idxs:
                    flat.append(f"query: {segs[j]}")
                    owners.append((i, j))
            if flat:
                seg_emb = model.encode(flat, batch_size=64, normalize_embeddings=True)
                sims = seg_emb @ skill_emb.T
                for row, (i, j) in zip(sims, owners):
                    sims_by_loker[i][j] = row

        for i, (loker, segs) in enumerate(zip(batch, seg_lists)):
            best: dict[int, Hasil] = {}

            def keep(h: Hasil) -> None:
                if h.skill_id not in best or h.skor > best[h.skill_id].skor:
                    best[h.skill_id] = h

            for j, seg in enumerate(segs):
                tipe = tipe_keahlian(seg)
                for skill_id, skor in lexical_matches(seg, index):
                    keep(Hasil(skill_id, skor, "leksikal", seg[:300], tipe))
                row = sims_by_loker[i].get(j)
                if row is not None:
                    for k in np.argpartition(-row, args.top_per_segment)[:args.top_per_segment]:
                        if row[k] >= args.threshold:
                            keep(Hasil(skills[k].id, float(row[k]), "semantik", seg[:300], tipe))

            ranked = sorted(best.values(), key=lambda h: h.skor, reverse=True)
            results[loker.vac_id] = ranked[:args.max_per_loker]

        print(f"  {min(start + batch_size, len(lokers)):,} / {len(lokers):,} loker diproses")
    return results


# ---------------------------------------------------------------------------
# Output
# ---------------------------------------------------------------------------

def write_report(lokers, results, skills_by_id, args) -> Path:
    OUTPUT_DIR.mkdir(exist_ok=True)
    stamp = time.strftime("%Y%m%d-%H%M%S")

    csv_path = OUTPUT_DIR / f"pemetaan_{args.versi}_{stamp}.csv"
    with csv_path.open("w", newline="", encoding="utf-8-sig") as f:
        w = csv.writer(f)
        w.writerow(["vac_id", "judul", "esco_skill_id", "skill", "skill_en", "skor", "metode", "tipe", "bukti"])
        for l in lokers:
            for h in results.get(l.vac_id, []):
                s = skills_by_id[h.skill_id]
                w.writerow([l.vac_id, l.judul, h.skill_id, s.title, s.title_en, f"{h.skor:.4f}", h.metode, h.tipe, h.bukti])

    html_path = OUTPUT_DIR / f"pemetaan_{args.versi}_{stamp}.html"
    esc = html.escape
    parts = [
        "<!doctype html><meta charset='utf-8'><title>Pemetaan Lowongan ke Skill ESCO</title>",
        "<style>body{font-family:system-ui,sans-serif;margin:24px;color:#0f172a;max-width:1200px}"
        "h2{font-size:15px;margin:28px 0 4px}table{border-collapse:collapse;width:100%;font-size:13px}"
        "td,th{border-bottom:1px solid #e2e8f0;padding:4px 6px;text-align:left;vertical-align:top}"
        "th{background:#f8fafc}.lek{color:#047857}.sem{color:#1d4ed8}.muted{color:#64748b;font-size:12px}"
        "details{margin:4px 0 8px;font-size:12px;color:#334155}</style>",
        f"<h1>Pemetaan Lowongan → Skill ESCO</h1><p class='muted'>versi {esc(args.versi)} · "
        f"{len(lokers)} loker · ambang semantik {args.threshold} · maks {args.max_per_loker} skill/loker</p>",
    ]
    for l in lokers:
        rows = results.get(l.vac_id, [])
        teks = re.sub(r"\s+", " ", clean_html(l.deskripsi))[:3000]
        parts.append(f"<h2>{esc(l.judul)} <span class='muted'>{esc(l.vac_id)} · {esc(l.bidang)}</span></h2>")
        parts.append(f"<details><summary>Teks lowongan</summary>{esc(teks)}</details>")
        if not rows:
            parts.append("<p class='muted'>Tidak ada skill terpetakan.</p>")
            continue
        parts.append("<table><tr><th>Skill ESCO</th><th>Skor</th><th>Metode</th><th>Tipe</th><th>Bukti</th></tr>")
        for h in rows:
            s = skills_by_id[h.skill_id]
            cls = "lek" if h.metode == "leksikal" else "sem"
            parts.append(
                f"<tr><td>{esc(s.title)}<br><span class='muted'>{esc(s.title_en)} (#{s.id})</span></td>"
                f"<td>{h.skor:.3f}</td><td class='{cls}'>{h.metode}</td><td>{h.tipe}</td>"
                f"<td class='muted'>{esc(h.bukti)}</td></tr>"
            )
        parts.append("</table>")
    html_path.write_text("\n".join(parts), encoding="utf-8")
    print(f"  CSV    : {csv_path}")
    return html_path


def write_db(conn: psycopg.Connection, results: dict[str, list[Hasil]], versi: str) -> int:
    rows = [
        (vac_id, h.skill_id, h.tipe, round(h.skor, 4), h.metode, h.bukti, versi)
        for vac_id, hasil in results.items()
        for h in hasil
    ]
    with conn.transaction():
        with conn.cursor() as cur:
            # Hasil versi yang sama untuk loker yang diproses diganti (tidak dobel)
            cur.execute(
                "DELETE FROM lowongan_skills WHERE versi = %s AND vac_id = ANY(%s)",
                (versi, list(results.keys())),
            )
            with cur.copy(
                "COPY lowongan_skills (vac_id, esco_skill_id, tipe_keahlian, skor, metode, teks_bukti, versi) FROM STDIN"
            ) as copy:
                for row in rows:
                    copy.write_row(row)
    return len(rows)


def print_summary(results: dict[str, list[Hasil]]) -> None:
    all_hasil = [h for hs in results.values() for h in hs]
    counts = [len(hs) for hs in results.values()]
    sem = np.array([h.skor for h in all_hasil if h.metode == "semantik"])
    print("\nRingkasan:")
    print(f"  loker diproses          : {len(results):,}")
    print(f"  loker tanpa skill       : {sum(c == 0 for c in counts):,}")
    print(f"  rata-rata skill / loker : {np.mean(counts):.1f}")
    print(f"  pemetaan leksikal       : {sum(h.metode == 'leksikal' for h in all_hasil):,}")
    print(f"  pemetaan semantik       : {len(sem):,}")
    if len(sem):
        p = np.percentile(sem, [10, 50, 90])
        print(f"  skor semantik p10/p50/p90: {p[0]:.3f} / {p[1]:.3f} / {p[2]:.3f}")


# ---------------------------------------------------------------------------

def main() -> None:
    ap = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    ap.add_argument("--limit", type=int, default=100, help="jumlah loker acak (0 = semua)")
    ap.add_argument("--seed", type=int, default=42, help="seed sampel acak")
    ap.add_argument("--threshold", type=float, default=0.87, help="ambang skor semantik")
    ap.add_argument("--top-per-segment", type=int, default=2, help="kandidat semantik per kalimat")
    ap.add_argument("--min-words", type=int, default=4, help="min. kata agar kalimat dicocokkan semantik")
    ap.add_argument("--max-per-loker", type=int, default=15, help="maks skill per loker")
    ap.add_argument("--model", default="intfloat/multilingual-e5-small")
    ap.add_argument("--no-semantic", action="store_true", help="hanya lapis leksikal")
    ap.add_argument("--versi", default="pilot", help="label versi di kolom versi")
    ap.add_argument("--write", action="store_true", help="tulis hasil ke tabel lowongan_skills")
    args = ap.parse_args()

    t0 = time.time()
    print("1. Mengambil data dari database...")
    with connect() as conn:
        skills, lokers = load_data(conn)
    print(f"  {len(skills):,} skill ESCO, {len(lokers):,} loker")

    if args.limit:
        lokers = random.Random(args.seed).sample(lokers, min(args.limit, len(lokers)))

    print("2. Menyiapkan lapis leksikal...")
    index = build_lexical_index(skills)
    print(f"  {len(index):,} frasa terindeks")

    model = skill_emb = None
    if not args.no_semantic:
        print(f"3. Memuat model semantik {args.model}...")
        model = load_model(args.model)
        skill_emb = skill_embeddings(model, args.model, skills)

    print("4. Memetakan loker...")
    results = map_lokers(lokers, skills, index, model, skill_emb, args)
    print_summary(results)

    print("\n5. Menulis hasil...")
    report = write_report(lokers, results, {s.id: s for s in skills}, args)
    print(f"  Laporan: {report}")

    if args.write:
        with connect() as conn:
            n = write_db(conn, results, args.versi)
        print(f"  Database: {n:,} baris ditulis ke lowongan_skills (versi '{args.versi}')")
    else:
        print("  Database: tidak ditulis (jalankan dengan --write untuk menyimpan)")

    print(f"\nSelesai dalam {time.time() - t0:.0f} detik.")


if __name__ == "__main__":
    sys.exit(main())
