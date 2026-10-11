<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\KbjiAlias;
use Illuminate\Http\Request;

class KbjiAliasController extends Controller
{
    /**
     * Menampilkan daftar pemetaan yang belum terverifikasi / pending
     */
    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');
        $method = $request->query('method');
        $search = $request->query('search');
        
        $query = KbjiAlias::query()->with('kbji');
        
        if ($status !== 'all') {
            $query->where('status', $status);
        }
        
        if ($method) {
            $query->where('method', $method);
        }

        if ($search) {
            $query->where('raw_term', 'like', "%{$search}%");
        }

        // Tampilkan dari frekuensi muncul paling banyak
        $aliases = $query->orderBy('frequency', 'desc')->paginate(20);
        
        return response()->json($aliases);
    }

    /**
     * Memverifikasi atau mengupdate KBJI ID untuk sebuah judul pekerjaan
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'kbji_id' => 'required|exists:kbji_classifications_2026,id'
        ]);

        $alias = KbjiAlias::findOrFail($id);
        
        $alias->update([
            'kbji_id' => $request->kbji_id,
            'status' => 'verified',
            'method' => 'manual',
            'confidence' => 1.0,
        ]);

        // Setelah diverifikasi, perbarui data lowongan terkait secara luas
        \App\Models\LowonganKerja::where('judul_pekerjaan', 'ilike', '%' . trim($alias->raw_term) . '%')
            ->update([
                'kbji_2026_id' => $request->kbji_id,
                'is_mapped' => true
            ]);

        return response()->json([
            'message' => 'Berhasil dipetakan dan diverifikasi.', 
            'data' => $alias->load('kbji')
        ]);
    }
}
