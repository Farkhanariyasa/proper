<?php

namespace App\Services;

use App\Models\KbjiAlias;
use App\Models\KbjiClassification2026;
use Illuminate\Support\Str;

class KbjiResolver
{
    /**
     * Resolve a raw job title to a KBJI 2026 ID.
     */
    public function resolve(string $rawTitle): ?int
    {
        // 1. Bersihkan string
        $cleanTitle = $this->normalizeTitle($rawTitle);
        
        if (empty($cleanTitle)) {
            return null;
        }

        // 2. Cek di tabel kamus (alias)
        $alias = KbjiAlias::where('raw_term', $cleanTitle)->first();
        if ($alias) {
            // Update frekuensi pemanggilan (tanpa menyentuh timestamps default Laravel jika tidak perlu)
            $alias->increment('frequency');
            
            return $alias->kbji_id; // Baik statusnya verified atau pending, kita kembalikan ID-nya jika ada.
        }

        // 3. Pencarian Exact Match
        $kbji = KbjiClassification2026::where('title', 'like', $cleanTitle)->first();
        if ($kbji) {
            KbjiAlias::create([
                'raw_term' => $cleanTitle,
                'kbji_id' => $kbji->id,
                'method' => 'exact',
                'confidence' => 1.0,
                'status' => 'verified',
                'frequency' => 1,
            ]);
            return $kbji->id;
        }

        // 4. Pencarian Fuzzy Agresif (Full Text Search / Keyword Match)
        $keywords = explode(' ', $cleanTitle);
        // Buang kata-kata pendek yang kurang bermakna (stop words sederhana)
        $keywords = array_filter($keywords, fn($w) => strlen($w) > 3); 

        if (!empty($keywords)) {
            $query = KbjiClassification2026::query();
            
            // Wajib mengandung semua keyword di title atau deskripsi
            foreach ($keywords as $word) {
                $query->where(function($q) use ($word) {
                    $q->where('title', 'ilike', '%' . $word . '%')
                      ->orWhere('description', 'ilike', '%' . $word . '%');
                });
            }
            
            $fuzzyKbji = $query->first();

            // Jika masih tidak ketemu, coba pencarian sangat longgar (OR) yang mencari kecocokan parsial
            if (!$fuzzyKbji) {
                $looseQuery = KbjiClassification2026::query();
                foreach ($keywords as $word) {
                    $looseQuery->orWhere('title', 'ilike', '%' . $word . '%');
                }
                $fuzzyKbji = $looseQuery->first();
            }

            if ($fuzzyKbji) {
                KbjiAlias::create([
                    'raw_term' => $cleanTitle,
                    'kbji_id' => $fuzzyKbji->id,
                    'method' => 'fuzzy',
                    'confidence' => 0.6,
                    'status' => 'pending', // Butuh review admin karena hasil pencocokan agresif
                    'frequency' => 1,
                ]);
                return null; // JANGAN return id, biarkan admin verifikasi via UI
            }
        }

        // 5. Tidak ketemu sama sekali
        KbjiAlias::create([
            'raw_term' => $cleanTitle,
            'kbji_id' => null,
            'method' => 'none',
            'confidence' => 0.0,
            'status' => 'pending',
            'frequency' => 1,
        ]);
        
        return null;
    }

    /**
     * Membersihkan teks judul pekerjaan agar standar.
     */
    private function normalizeTitle(string $title): string
    {
        $title = Str::lower(trim($title));
        
        // Hapus tanda baca berlebih dan karakter selain huruf, angka, spasi, dan dash
        $title = preg_replace('/[^a-z0-9\s\-]/', '', $title);
        
        // Hapus spasi berlebih
        $title = preg_replace('/\s+/', ' ', $title);
        
        return trim($title);
    }
}
