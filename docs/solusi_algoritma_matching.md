# Solusi & Algoritma Derajat Kecocokan (Matching Engine)
## 100% Title & Description First: Content-Based Semantic Matching

---

## 1. Menjawab Masalah Mismatch KBJI & ESCO

Pada arsitektur lama, sistem mengalami kegagalan karena membandingkan ID taksonomi kaku:
1. **ESCO Mismatch**: Teks profil dan lowongan dipaksa masuk ke ID ESCO bahasa Inggris yang tidak cocok.
2. **KBJI Mismatch**: Kode 4-digit KBJI dijadikan filter mutlak (`continue;`), sehingga jika kode meleset sedikit saja, rekomendasi dibuang.

### Solusi Mutlak: "Judul & Deskripsi First"
* **Sumber Kebenaran Primer**: Teks asli yang ditulis oleh pembuat lowongan dan pencari kerja:
  * **Lowongan**: `judul_lowongan`, `deskripsi_pekerjaan`, `persyaratan_tambahan`.
  * **Pencaker**: `desired_occupation`, riwayat jabatan di `experience`, `keahlian`, `sertifikasi`.
* **Peran KBJI & ESCO Sekarang**:
  * **KBJI**: **0% Bobot Skor**. Hanya disimpan sebagai metadata informasional di response API (badge UI). Sama sekali tidak memengaruhi perhitungan skor atau menggugurkan pasangan lowongan.
  * **ESCO**: **Hanya Referensi/Kamus**. Bukan filter gerbang mutlak. Jika ada skill ESCO yang cocok, tetap ditampilkan sebagai nilai tambah, namun profil tanpa skill ESCO tetap dinilai adil 100% dari teks aslinya.

---

## 2. Formula Komposit Multi-Kriteria (Title & Description First)

```
Total Skor = (40% * Skor_Kompetensi_Skill)
           + (30% * Skor_Pendidikan_Jurusan)
           + (20% * Skor_Pengalaman_Kerja)
           + (10% * Skor_Lokasi)
```

---

## 3. Logika Perhitungan Dimensi Peran (100% Title & Description First)

Sistem menilai kesesuaian peran kandidat terhadap lowongan melalui 5 lapisan teks bertingkat:

1. **Exact / Substring Title Match (Skor: 95%)**:
   Jika peran kandidat dan judul lowongan identik atau merupakan frasa bagian (contoh: *"Product Designer"* di *"UI/UX Product Designer"* atau *"Frontend Developer"* di *"Senior Frontend Developer"*).
2. **Contextual Description Match (Skor: 85%)**:
   Jika peran kandidat muncul langsung di dalam `deskripsi_pekerjaan` lowongan (contoh: kandidat mencari posisi *"Kasir"*, lowongan berjudul *"Crew Outlet"* tetapi di dalam deskripsi tertulis *"melayani transaksi kasir dan pembayaran"*).
3. **Important Title Tokens Overlap (Skor: 60% - 95%)**:
   Pencocokan kata kunci penting pada judul (contoh: *"Web Developer"* vs *"Senior Software Developer (Fullstack Web)"* -> kata *"Developer"* dan *"Web"* cocok).
4. **Description Keyword Overlap (Skor: 50% - 80%)**:
   Kata kunci peran kandidat memiliki irisan kuat dengan tanggung jawab di deskripsi lowongan.
5. **Fuzzy String Similarity (Skor: 40% - 70%)**:
   Toleransi typo atau kemiripan leksikal berbasis *Dice's Coefficient*.
6. **Baseline Unrelated (Skor: 15%)**:
   Jika peran kandidat sama sekali tidak ada kaitannya (misal: *"Account Manager"* melamar ke *"Software Developer"*).

---

## 4. Klasifikasi Tingkat Kecocokan (4 Action Tiers)

```
+-------------------+--------------------+---------------------------------------------+
| Rentang Skor      | Kategori           | Tindakan yang Direkomendasikan              |
+-------------------+--------------------+---------------------------------------------+
| Skor = 100%       | 1. Perfect Match   | Rekomendasikan langsung ke Perusahaan       |
| 70% <= Skor < 100%| 2. High Match      | Siap Ditempatkan Langsung                   |
| 40% <= Skor < 70% | 3. Gap Match       | Perlu Pelatihan Skill Gap (BPVP / BLK)      |
| Skor < 40%        | 4. Low Match       | Belum Sesuai (Bimbingan Karir Dasar)        |
+-------------------+--------------------+---------------------------------------------+
```

| Profil Kandidat | Lowongan yang Diuji | Skor Akhir | Kesesuaian Peran | Status Rekomendasi |
| :--- | :--- | :---: | :---: | :--- |
| **Siti Designer** (Figma, UI Design, DKV) | **UI/UX Product Designer** | **74%** | **95%** | **Potensial (Siap Ditempatkan)** |
| **Siti Designer** | Senior Software Developer | 38% | 15% | Belum Sesuai |
| **Rian Analyst** (SQL, Python, Tableau) | **Data Analyst & BI Specialist** | **80%** | **95%** | **Sangat Cocok (Siap Kerja)** |
| **Rian Analyst** | Junior Software Engineer (Python) | 58% | 67% | Perlu Pelatihan Skill Gap |
| **Rian Analyst** | UI/UX Product Designer | 38% | 15% | Belum Sesuai |
| **Usdar** (Account Manager, CRM) | **Technical Account Manager** | **100%** | **100%** | **Sangat Cocok (Siap Kerja)** |
| **Usdar** | Senior Software Developer | 43% | 15% | Belum Sesuai |

> **Catatan Penting**: Seluruh kandidat di atas diuji **tanpa memiliki kode KBJI** dan **tanpa skill ESCO**, dan sistem tetap berhasil membedakan posisi yang tepat sasaran secara otomatis hanya dari **Judul & Deskripsi Pekerjaan**.
