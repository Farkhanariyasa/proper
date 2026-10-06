<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Kurasi pemetaan lowongan (req_pk_loker) -> skill ESCO (tabel lowongan_skills).
 *
 * Pemetaan awal dibuat otomatis oleh tools/skill_mapping; di sini petugas dapat
 * menambah, menghapus, atau mengosongkan skill per lowongan. Skill yang ditambah
 * manual disimpan dengan metode 'manual', skor 1, versi 'manual'.
 */
class LowonganSkillController extends Controller
{
    /**
     * GET /api/admin/lowongan-skills?search=&status=all|terpetakan|kosong&page=
     */
    public function index(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search', ''));
        $status = $request->query('status', 'all');

        $query = DB::table('req_pk_loker as l')
            ->select('l.vac_id', 'l.judul_pekerjaan', 'l.nama_perusahaan', 'l.bidang_pekerjaan', 'l.status_loker')
            ->selectRaw('(SELECT COUNT(*) FROM lowongan_skills ls WHERE ls.vac_id = l.vac_id) AS jumlah_skill');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('l.judul_pekerjaan', 'ILIKE', "%{$search}%")
                    ->orWhere('l.nama_perusahaan', 'ILIKE', "%{$search}%")
                    ->orWhere('l.vac_id', 'ILIKE', "%{$search}%");
            });
        }

        if ($status === 'terpetakan') {
            $query->whereExists(fn ($q) => $q->from('lowongan_skills as ls')->whereColumn('ls.vac_id', 'l.vac_id'));
        } elseif ($status === 'kosong') {
            $query->whereNotExists(fn ($q) => $q->from('lowongan_skills as ls')->whereColumn('ls.vac_id', 'l.vac_id'));
        }

        $page = $query->orderBy('l.judul_pekerjaan')->paginate(20);

        // Skill untuk lowongan di halaman ini saja
        $skills = $this->skillsFor(collect($page->items())->pluck('vac_id')->all());
        $items = collect($page->items())->map(function ($row) use ($skills) {
            $row->jumlah_skill = (int) $row->jumlah_skill;
            $row->skills = $skills[$row->vac_id] ?? [];
            return $row;
        });

        return response()->json([
            'data' => $items,
            'current_page' => $page->currentPage(),
            'last_page' => $page->lastPage(),
            'total' => $page->total(),
        ]);
    }

    /**
     * GET /api/admin/lowongan-skills/{vacId}
     * Detail lowongan (termasuk deskripsi teks polos) beserta skill-nya
     */
    public function show(string $vacId): JsonResponse
    {
        $loker = DB::table('req_pk_loker')
            ->where('vac_id', $vacId)
            ->first(['vac_id', 'judul_pekerjaan', 'nama_perusahaan', 'bidang_pekerjaan', 'industri', 'status_loker', 'deskripsi_pekerjaan']);

        if (!$loker) {
            return response()->json(['message' => 'Lowongan tidak ditemukan.'], 404);
        }

        // Deskripsi sumber berformat HTML -> dikirim sebagai teks polos (aman ditampilkan)
        $loker->deskripsi_teks = $this->toPlainText($loker->deskripsi_pekerjaan);
        unset($loker->deskripsi_pekerjaan);
        $loker->skills = $this->skillsFor([$vacId])[$vacId] ?? [];

        return response()->json(['data' => $loker]);
    }

    /**
     * PUT /api/admin/lowongan-skills/{vacId}
     * Body: { skills: [{ esco_skill_id, tipe_keahlian }] } — array kosong = kosongkan semua skill
     */
    public function update(Request $request, string $vacId): JsonResponse
    {
        $validated = $request->validate([
            'skills' => 'present|array',
            'skills.*.esco_skill_id' => 'required|integer|distinct|exists:skill_nodes,id',
            'skills.*.tipe_keahlian' => 'nullable|in:wajib,diutamakan',
        ]);

        if (!DB::table('req_pk_loker')->where('vac_id', $vacId)->exists()) {
            return response()->json(['message' => 'Lowongan tidak ditemukan.'], 404);
        }

        $wanted = collect($validated['skills'])->keyBy('esco_skill_id');

        DB::transaction(function () use ($vacId, $wanted) {
            // Hapus skill yang tidak dipilih lagi
            DB::table('lowongan_skills')
                ->where('vac_id', $vacId)
                ->when($wanted->isNotEmpty(), fn ($q) => $q->whereNotIn('esco_skill_id', $wanted->keys()))
                ->delete();

            $existing = DB::table('lowongan_skills')->where('vac_id', $vacId)->pluck('esco_skill_id')->all();

            foreach ($wanted as $skillId => $skill) {
                $tipe = $skill['tipe_keahlian'] ?? 'wajib';
                if (in_array($skillId, $existing)) {
                    // Skill hasil otomatis dipertahankan beserta bukti & skornya; hanya tipe yang diperbarui
                    DB::table('lowongan_skills')
                        ->where('vac_id', $vacId)
                        ->where('esco_skill_id', $skillId)
                        ->update(['tipe_keahlian' => $tipe]);
                } else {
                    DB::table('lowongan_skills')->insert([
                        'vac_id' => $vacId,
                        'esco_skill_id' => $skillId,
                        'tipe_keahlian' => $tipe,
                        'skor' => 1,
                        'metode' => 'manual',
                        'teks_bukti' => null,
                        'versi' => 'manual',
                    ]);
                }
            }
        });

        return response()->json([
            'message' => 'Pemetaan skill berhasil disimpan.',
            'data' => $this->skillsFor([$vacId])[$vacId] ?? [],
        ]);
    }

    // ------------------------------------------------------------------

    /**
     * Skill per vac_id, urut dari skor tertinggi
     */
    private function skillsFor(array $vacIds): array
    {
        if (empty($vacIds)) {
            return [];
        }

        $rows = DB::table('lowongan_skills as ls')
            ->join('skill_nodes as s', 's.id', '=', 'ls.esco_skill_id')
            ->whereIn('ls.vac_id', $vacIds)
            ->orderByDesc('ls.skor')
            ->get([
                'ls.vac_id', 'ls.esco_skill_id', 's.title', 's.title_en',
                'ls.tipe_keahlian', 'ls.skor', 'ls.metode', 'ls.teks_bukti', 'ls.versi',
            ]);

        $grouped = [];
        foreach ($rows as $row) {
            $vacId = $row->vac_id;
            unset($row->vac_id);
            $row->skor = $row->skor !== null ? (float) $row->skor : null;
            $grouped[$vacId][] = $row;
        }
        return $grouped;
    }

    private function toPlainText(?string $html): string
    {
        $text = html_entity_decode(html_entity_decode((string) $html, ENT_QUOTES | ENT_HTML5), ENT_QUOTES | ENT_HTML5);
        $text = preg_replace('/<\s*(br|\/p|\/li|li|\/div|\/h\d|\/tr)[^>]*>/i', "\n", $text);
        $text = strip_tags($text);
        $text = preg_replace("/[ \t\x{00A0}]+/u", ' ', $text);
        return trim(preg_replace("/\n\s*\n+/", "\n", $text));
    }
}
