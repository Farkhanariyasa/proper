'use client';

import React, { useCallback, useEffect, useState } from 'react';
import { AlertCircle, ChevronLeft, ChevronRight, Database, Loader2, Pencil, Search } from 'lucide-react';
import AuthGuard from '@/components/auth/AuthGuard';
import LokerSkillEditor from '@/components/pemetaan/LokerSkillEditor';
import { getLokerSkillsApi, GetLokerSkillsParams } from '@/services/lowongan-skill';
import { LokerSkillRow, LowonganSkillItem } from '@/types/lowongan-skill';

const MAX_CHIPS = 4;

export default function PemetaanEscoPage() {
  const [rows, setRows] = useState<LokerSkillRow[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [total, setTotal] = useState(0);

  const [searchInput, setSearchInput] = useState('');
  const [search, setSearch] = useState('');
  const [status, setStatus] = useState<NonNullable<GetLokerSkillsParams['status']>>('all');

  const [editingVacId, setEditingVacId] = useState<string | null>(null);

  // Debounce input pencarian
  useEffect(() => {
    const timer = setTimeout(() => {
      setSearch(searchInput.trim());
      setPage(1);
    }, 400);
    return () => clearTimeout(timer);
  }, [searchInput]);

  const fetchRows = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const res = await getLokerSkillsApi({ page, search, status });
      setRows(res.data);
      setLastPage(res.last_page);
      setTotal(res.total);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Gagal memuat data.');
    } finally {
      setLoading(false);
    }
  }, [page, search, status]);

  useEffect(() => {
    fetchRows();
  }, [fetchRows]);

  // Perbarui baris yang diedit tanpa memuat ulang seluruh halaman
  const handleSaved = (vacId: string, skills: LowonganSkillItem[]) => {
    setRows((prev) =>
      prev.map((r) => (r.vac_id === vacId ? { ...r, skills, jumlah_skill: skills.length } : r))
    );
  };

  return (
    <AuthGuard requiredPermission="mapping.kbji">
      <div className="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto space-y-6">
        <div>
          <div className="flex items-center gap-2">
            <h1 className="text-xl sm:text-2xl font-bold text-slate-900">Pemetaan Skill ESCO Lowongan</h1>
            <span className="px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
              {total.toLocaleString('id-ID')} Lowongan
            </span>
          </div>
          <p className="text-xs sm:text-sm text-slate-600 mt-1">
            Periksa dan sesuaikan skill ESCO setiap lowongan. Pemetaan awal dibuat otomatis; skill dapat ditambah, dihapus, atau dikosongkan.
          </p>
        </div>

        {/* Filter */}
        <div className="bg-white p-4 rounded-xl border border-slate-200/80 shadow-2xs flex flex-wrap gap-3 items-center">
          <div className="relative w-full sm:w-80">
            <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
            <input
              type="text"
              value={searchInput}
              onChange={(e) => setSearchInput(e.target.value)}
              placeholder="Cari judul, perusahaan, atau VAC-ID..."
              className="w-full rounded-lg border border-slate-300 py-2 pl-9 pr-3 text-sm focus:ring-[#0E385E]"
            />
          </div>
          <select
            value={status}
            onChange={(e) => {
              setStatus(e.target.value as typeof status);
              setPage(1);
            }}
            className="w-full sm:w-64 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:ring-[#0E385E]"
          >
            <option value="all">Semua Lowongan</option>
            <option value="terpetakan">Sudah Punya Skill</option>
            <option value="kosong">Belum Ada Skill</option>
          </select>
          <button
            type="button"
            onClick={fetchRows}
            className="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold rounded-lg transition cursor-pointer"
          >
            Refresh
          </button>
        </div>

        {/* Tabel */}
        <div className="bg-white rounded-xl border border-slate-200/80 shadow-2xs overflow-hidden">
          {loading ? (
            <div className="p-12 flex flex-col items-center text-slate-500">
              <Loader2 className="w-7 h-7 animate-spin text-[#0E385E] mb-3" />
              <p className="text-sm font-medium">Memuat data...</p>
            </div>
          ) : error ? (
            <div className="p-8 text-center">
              <AlertCircle className="w-8 h-8 text-red-500 mx-auto mb-2" />
              <p className="text-sm text-red-600">{error}</p>
            </div>
          ) : rows.length === 0 ? (
            <div className="p-12 text-center text-slate-500">
              <Database className="w-8 h-8 mx-auto mb-2 text-slate-400" />
              <p className="text-sm">Tidak ada lowongan ditemukan.</p>
            </div>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-left border-collapse text-sm">
                <thead>
                  <tr className="bg-slate-50 border-b border-slate-200 text-xs uppercase text-slate-500 font-bold">
                    <th className="py-3 px-4 w-1/3">Lowongan</th>
                    <th className="py-3 px-4">Skill ESCO</th>
                    <th className="py-3 px-4 text-center w-28">Aksi</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {rows.map((row) => (
                    <tr key={row.vac_id} className="hover:bg-slate-50 align-top">
                      <td className="py-3 px-4">
                        <div className="font-semibold text-slate-900">{row.judul_pekerjaan || '-'}</div>
                        <div className="text-xs text-slate-500 mt-0.5">{row.nama_perusahaan}</div>
                        <div className="text-[11px] text-slate-400 mt-0.5">
                          {row.vac_id} · {row.bidang_pekerjaan}
                        </div>
                      </td>
                      <td className="py-3 px-4">
                        {row.skills.length === 0 ? (
                          <span className="text-slate-400 italic text-xs">Belum ada skill</span>
                        ) : (
                          <div className="flex flex-wrap gap-1.5">
                            {row.skills.slice(0, MAX_CHIPS).map((s) => (
                              <span
                                key={s.esco_skill_id}
                                title={s.title_en ?? undefined}
                                className={`rounded-full border px-2 py-0.5 text-xs ${
                                  s.tipe_keahlian === 'diutamakan'
                                    ? 'border-amber-200 bg-amber-50 text-amber-800'
                                    : 'border-slate-200 bg-slate-50 text-slate-700'
                                }`}
                              >
                                {s.title}
                              </span>
                            ))}
                            {row.skills.length > MAX_CHIPS && (
                              <span className="px-1.5 py-0.5 text-xs font-semibold text-slate-500">
                                +{row.skills.length - MAX_CHIPS} lagi
                              </span>
                            )}
                          </div>
                        )}
                      </td>
                      <td className="py-3 px-4 text-center">
                        <button
                          type="button"
                          onClick={() => setEditingVacId(row.vac_id)}
                          className="inline-flex items-center gap-1 px-3 py-1.5 bg-[#0E385E] hover:bg-[#163A5F] text-white text-xs font-semibold rounded transition cursor-pointer"
                        >
                          <Pencil className="h-3.5 w-3.5" />
                          Edit
                        </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}

          {lastPage > 1 && (
            <div className="px-4 py-3 bg-slate-50 border-t flex justify-between items-center text-sm">
              <span className="text-slate-600">
                Hal {page} dari {lastPage}
              </span>
              <div className="flex gap-2">
                <button
                  type="button"
                  disabled={page <= 1}
                  onClick={() => setPage((p) => p - 1)}
                  className="p-1.5 rounded border border-slate-300 bg-white hover:bg-slate-50 disabled:opacity-50 cursor-pointer"
                  aria-label="Halaman sebelumnya"
                >
                  <ChevronLeft className="w-4 h-4" />
                </button>
                <button
                  type="button"
                  disabled={page >= lastPage}
                  onClick={() => setPage((p) => p + 1)}
                  className="p-1.5 rounded border border-slate-300 bg-white hover:bg-slate-50 disabled:opacity-50 cursor-pointer"
                  aria-label="Halaman berikutnya"
                >
                  <ChevronRight className="w-4 h-4" />
                </button>
              </div>
            </div>
          )}
        </div>
      </div>

      {editingVacId && (
        <LokerSkillEditor
          vacId={editingVacId}
          onClose={() => setEditingVacId(null)}
          onSaved={handleSaved}
        />
      )}
    </AuthGuard>
  );
}
