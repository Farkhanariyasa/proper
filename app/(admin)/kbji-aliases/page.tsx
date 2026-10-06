'use client';

import React, { useState, useEffect, useCallback } from 'react';
import AuthGuard from '@/components/auth/AuthGuard';
import { useAuth } from '@/hooks/useAuth';
import { getKbjiAliasesApi, updateKbjiAliasApi } from '@/services/admin';
import { searchKbji } from '@/services/kbji';
import { 
  CheckCircle2, 
  Search, 
  Loader2, 
  AlertCircle, 
  ChevronLeft, 
  ChevronRight,
  Database,
  Check
} from 'lucide-react';
import { KbjiNode } from '@/types/kbji';

export default function KbjiAliasCurationPage() {
  const { hasPermission } = useAuth();
  
  const [aliases, setAliases] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [totalItems, setTotalItems] = useState(0);

  const [statusFilter, setStatusFilter] = useState('pending');
  
  // States for KBJI selection modal/dropdown
  const [searchingId, setSearchingId] = useState<number | null>(null);
  const [searchQuery, setSearchQuery] = useState('');
  const [searchResults, setSearchResults] = useState<KbjiNode[]>([]);
  const [searchLoading, setSearchLoading] = useState(false);

  const fetchAliases = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const res = await getKbjiAliasesApi({
        page: currentPage,
        status: statusFilter === 'all' ? undefined : statusFilter,
      });
      setAliases(res.data);
      setCurrentPage(res.current_page);
      setTotalPages(res.last_page);
      setTotalItems(res.total);
    } catch (err: any) {
      setError(err.message || 'Gagal memuat daftar pemetaan.');
    } finally {
      setLoading(false);
    }
  }, [currentPage, statusFilter]);

  useEffect(() => {
    fetchAliases();
  }, [fetchAliases]);

  // Reset page when filter changes
  useEffect(() => {
    setCurrentPage(1);
  }, [statusFilter]);

  const handleSearchKbji = async (q: string) => {
    setSearchQuery(q);
    if (!q || q.length < 3) {
      setSearchResults([]);
      return;
    }
    setSearchLoading(true);
    try {
      const res = await searchKbji(q, 1, 10);
      setSearchResults(res.data);
    } catch (err) {
      console.error(err);
    } finally {
      setSearchLoading(false);
    }
  };

  const handleVerify = async (aliasId: number, kbjiId: number) => {
    try {
      await updateKbjiAliasApi(aliasId, kbjiId);
      // Update local state to reflect changes instantly
      setAliases(prev => prev.map(a => 
        a.id === aliasId ? { ...a, status: 'verified', kbji_id: kbjiId } : a
      ));
      setSearchingId(null);
      setSearchQuery('');
    } catch (err: any) {
      alert(err.message || 'Gagal menyimpan.');
    }
  };

  return (
    <AuthGuard requiredPermission="mapping.kbji">
      <div className="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto space-y-6">
        {/* Header Title */}
        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <div className="flex items-center gap-2">
              <h1 className="text-xl sm:text-2xl font-bold text-slate-900">
                Kurasi Pemetaan KBJI (Manual)
              </h1>
              <span className="px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                {totalItems} Data
              </span>
            </div>
            <p className="text-xs sm:text-sm text-slate-600 mt-1">
              Pasangkan judul pekerjaan dari lowongan dengan kode KBJI 2026 yang paling relevan.
            </p>
          </div>
        </div>

        {/* Filters */}
        <div className="bg-white p-4 rounded-xl border border-slate-200/80 shadow-2xs flex flex-wrap gap-4 items-center">
          <div className="w-full sm:w-64">
            <select
              value={statusFilter}
              onChange={(e) => setStatusFilter(e.target.value)}
              className="block w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-[#0E385E]"
            >
              <option value="all">Semua Status</option>
              <option value="pending">Butuh Direview (Pending)</option>
              <option value="verified">Sudah Disetujui (Verified)</option>
            </select>
          </div>
          <button 
            onClick={fetchAliases}
            className="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold rounded-lg transition"
          >
            Refresh
          </button>
        </div>

        {/* Data List */}
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
          ) : aliases.length === 0 ? (
            <div className="p-12 text-center text-slate-500">
              <Database className="w-8 h-8 mx-auto mb-2 text-slate-400" />
              <p className="text-sm">Tidak ada data ditemukan.</p>
            </div>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-left border-collapse text-sm">
                <thead>
                  <tr className="bg-slate-50 border-b border-slate-200 text-xs uppercase text-slate-500 font-bold">
                    <th className="py-3 px-4 w-1/3">Judul Mentah (Raw Term)</th>
                    <th className="py-3 px-4">Frekuensi</th>
                    <th className="py-3 px-4 w-1/2">KBJI Dipilih</th>
                    <th className="py-3 px-4 text-center">Aksi</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {aliases.map(alias => (
                    <tr key={alias.id} className="hover:bg-slate-50">
                      <td className="py-3 px-4">
                        <div className="font-semibold text-slate-900">{alias.raw_term}</div>
                        <div className="text-xs text-slate-500 flex gap-2 mt-1">
                          <span className={`px-1.5 py-0.5 rounded text-[10px] uppercase font-bold ${
                            alias.status === 'verified' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'
                          }`}>
                            {alias.status}
                          </span>
                          <span className="text-slate-400">Metode: {alias.method}</span>
                        </div>
                      </td>
                      <td className="py-3 px-4 text-slate-600 font-medium">
                        {alias.frequency}x
                      </td>
                      <td className="py-3 px-4">
                        {searchingId === alias.id ? (
                          <div className="relative">
                            <div className="flex items-center gap-2">
                              <input 
                                type="text"
                                autoFocus
                                placeholder="Ketik judul/kode KBJI..."
                                value={searchQuery}
                                onChange={(e) => handleSearchKbji(e.target.value)}
                                className="w-full px-3 py-1.5 text-sm border border-slate-300 rounded focus:ring-blue-500"
                              />
                              <button onClick={() => setSearchingId(null)} className="text-xs text-slate-500 hover:text-slate-800">
                                Batal
                              </button>
                            </div>
                            
                            {searchQuery.length >= 3 && (
                              <div className="absolute z-10 w-full mt-1 bg-white border border-slate-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                                {searchLoading ? (
                                  <div className="p-3 text-center text-xs text-slate-500">Mencari...</div>
                                ) : searchResults.length > 0 ? (
                                  searchResults.map(kbji => (
                                    <button
                                      key={kbji.id}
                                      onClick={() => handleVerify(alias.id, kbji.id)}
                                      className="w-full text-left px-3 py-2 text-sm hover:bg-blue-50 border-b border-slate-100 last:border-0"
                                    >
                                      <div className="font-semibold text-slate-800">{kbji.title}</div>
                                      <div className="text-xs text-slate-500">{kbji.code}</div>
                                    </button>
                                  ))
                                ) : (
                                  <div className="p-3 text-center text-xs text-slate-500">Tidak ada hasil.</div>
                                )}
                              </div>
                            )}
                          </div>
                        ) : (
                          <div className="text-sm">
                            {alias.kbji ? (
                              <div>
                                <span className="font-semibold text-slate-900">{alias.kbji.title}</span>
                                <span className="ml-2 text-xs text-slate-500">({alias.kbji.code})</span>
                              </div>
                            ) : (
                              <span className="text-slate-400 italic">Belum ada KBJI</span>
                            )}
                          </div>
                        )}
                      </td>
                      <td className="py-3 px-4 text-center">
                        {searchingId !== alias.id && (
                          <button
                            onClick={() => {
                              setSearchingId(alias.id);
                              setSearchQuery(alias.kbji?.title || alias.raw_term);
                              handleSearchKbji(alias.kbji?.title || alias.raw_term);
                            }}
                            className="px-3 py-1.5 bg-[#0E385E] hover:bg-[#163A5F] text-white text-xs font-semibold rounded transition"
                          >
                            Pilih KBJI
                          </button>
                        )}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}

          {/* Pagination */}
          {totalPages > 1 && (
            <div className="px-4 py-3 bg-slate-50 border-t flex justify-between items-center text-sm">
              <span className="text-slate-600">Hal {currentPage} dari {totalPages}</span>
              <div className="flex gap-2">
                <button
                  disabled={currentPage <= 1}
                  onClick={() => setCurrentPage(p => p - 1)}
                  className="p-1.5 rounded border border-slate-300 bg-white hover:bg-slate-50 disabled:opacity-50"
                >
                  <ChevronLeft className="w-4 h-4" />
                </button>
                <button
                  disabled={currentPage >= totalPages}
                  onClick={() => setCurrentPage(p => p + 1)}
                  className="p-1.5 rounded border border-slate-300 bg-white hover:bg-slate-50 disabled:opacity-50"
                >
                  <ChevronRight className="w-4 h-4" />
                </button>
              </div>
            </div>
          )}
        </div>
      </div>
    </AuthGuard>
  );
}
