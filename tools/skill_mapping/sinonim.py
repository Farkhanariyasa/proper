"""
Kamus istilah lowongan (Indonesia / singkatan / merek) -> judul skill ESCO (title_en).

Label alternatif ESCO hanya berbahasa Inggris, sehingga istilah sehari-hari
di lowongan berbahasa Indonesia perlu dipetakan manual. Kunci ditulis huruf
kecil; dicocokkan sebagai frasa utuh (bukan potongan kata).
Nilai = title_en skill ESCO (dicari otomatis id-nya saat script berjalan).
"""

SINONIM: dict[str, list[str]] = {
    # Perkantoran & komputer
    "excel": ["use spreadsheets software"],
    "ms excel": ["use spreadsheets software"],
    "microsoft excel": ["use spreadsheets software"],
    "spreadsheet": ["use spreadsheets software"],
    "ms office": ["use microsoft office"],
    "microsoft office": ["use microsoft office"],
    "ms word": ["use word processing software"],
    "microsoft word": ["use word processing software"],
    "powerpoint": ["use presentation software"],
    "mengetik": ["use word processing software"],
    "komputer": ["have computer literacy"],
    "mengoperasikan komputer": ["have computer literacy"],
    "administrasi": ["perform clerical duties"],
    "pengarsipan": ["keep task records"],
    "entry data": ["process data"],
    "input data": ["process data"],
    "data entry": ["process data"],
    "analisa data": ["perform data analysis"],
    "analisis data": ["perform data analysis"],
    # Komunikasi & sikap kerja
    "komunikasi": ["communication"],
    "berkomunikasi": ["communication"],
    "kerja sama tim": ["work in teams"],
    "kerjasama tim": ["work in teams"],
    "bekerja dalam tim": ["work in teams"],
    "teamwork": ["work in teams"],
    "kepemimpinan": ["leadership principles"],
    "leadership": ["leadership principles"],
    # "negosiasi" sengaja tidak dipetakan: ESCO hanya punya negosiasi spesifik
    # (harga, kontrak, dst.) sehingga diserahkan ke lapis semantik
    "presentasi": ["present reports"],
    "bahasa inggris": ["English"],
    "bahasa mandarin": ["Chinese"],
    "bahasa jepang": ["Japanese"],
    "manajemen waktu": ["manage time"],
    "problem solving": ["solve problems"],
    "pemecahan masalah": ["solve problems"],
    # Penjualan & layanan
    "penjualan": ["sell products"],
    "customer service": ["customer service"],
    "pelayanan pelanggan": ["customer service"],
    "layanan pelanggan": ["customer service"],
    "telemarketing": ["telemarketing"],
    "digital marketing": ["digital marketing techniques"],
    "media sosial": ["social media management"],
    "social media": ["social media management"],
    "kasir": ["operate cash register"],
    # Keuangan
    "akuntansi": ["accounting"],
    "laporan keuangan": ["financial statements"],
    "perpajakan": ["tax legislation"],
    "pajak": ["tax legislation"],
    "penagihan": ["debt collection techniques"],
    # Teknik, produksi, logistik
    "k3": ["health and safety in the workplace"],
    "keselamatan kerja": ["health and safety in the workplace"],
    "hse": ["health and safety in the workplace"],
    "autocad": ["use CAD software"],
    "gambar teknik": ["technical drawings"],
    "pengelasan": ["operate welding equipment"],
    "mengelas": ["operate welding equipment"],
    "welding": ["operate welding equipment"],
    "forklift": ["operate forklift"],
    "quality control": ["quality control systems"],
    "pengendalian mutu": ["quality control systems"],
    "inventaris": ["manage inventory"],
    "stok barang": ["manage inventory"],
    "gudang": ["warehouse operations"],
    "mengemudi": ["drive vehicles"],
    "sim a": ["drive vehicles"],
    "sim b1": ["drive vehicles"],
    "sim b2": ["drive vehicles"],
    "sim c": ["drive two-wheeled vehicles"],
    "listrik": ["electricity"],
    "plc": ["programmable logic controller"],
}

# Kata yang menandai skill "diutamakan" (bukan wajib) pada kalimat yang sama
PENANDA_DIUTAMAKAN = [
    "diutamakan", "lebih diutamakan", "nilai plus", "nilai tambah", "menjadi nilai",
    "preferred", "preferably", "is a plus", "a plus", "advantage", "nice to have",
]

# Label tunggal ESCO yang terlalu umum untuk dicocokkan secara leksikal
LABEL_TERLALU_UMUM = {
    "management", "service", "services", "sales", "data", "design", "planning",
    "support", "training", "control", "analysis", "research", "marketing",
    "operations", "production", "development", "office", "safety", "quality",
    "security", "maintenance", "communication", "teaching", "cleaning",
}
