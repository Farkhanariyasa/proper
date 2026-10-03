'use client';

import React, { useState, useEffect, useCallback, useMemo, Suspense } from 'react';
import { useSearchParams } from 'next/navigation';
import {
  Target,
  Users,
  Briefcase,
  Search,
  CheckCircle2,
  AlertTriangle,
  Layers,
  Building2,
  MapPin,
  ChevronRight,
  Loader2,
  AlertCircle,
  Eye,
  Send,
  BarChart3,
  RefreshCw,
  SlidersHorizontal,
  ChevronLeft,
} from 'lucide-react';
import AuthGuard from '@/components/auth/AuthGuard';
import SearchableSelect from '@/components/ui/SearchableSelect';
import {
  PairwiseAnalysisResponse,
  RecommendationPairItem,
} from '@/types/matching';
import {
  getPairAnalysisApi,
  getUnifiedRecommendationsApi,
} from '@/services/matching';
import { getJobSeekers } from '@/services/job-seeker';
import { getLowonganListApi } from '@/services/lowongan';
import { JobSeeker } from '@/types/job-seeker';
import { LowonganItem } from '@/types/lowongan';
import SkillGapModal from '@/components/matching/SkillGapModal';

function RekomendasiContent() {
  const searchParams = useSearchParams();
  const initialLowonganId = searchParams.get('lowongan_id');

  // Master Lists for Dropdown Filters
  const [candidatesList, setCandidatesList] = useState<JobSeeker[]>([]);
  const [vacanciesList, setVacanciesList] = useState<LowonganItem[]>([]);
  const [loadingMasters, setLoadingMasters] = useState(true);

  // Filters State
  const [selectedSeekerId, setSelectedSeekerId] = useState<string>('');
  const [selectedLowonganId, setSelectedLowonganId] = useState<string>(initialLowonganId || '');
  const [selectedCategory, setSelectedCategory] = useState<string>('');
  const [searchQuery, setSearchQuery] = useState<string>('');

  // Unified Recommendation Results
  const [recommendations, setRecommendations] = useState<RecommendationPairItem[]>([]);
  const [loadingResults, setLoadingResults] = useState(false);
  const [error, setError] = useState<string | null>(null);

  // Pagination State
  const [currentPage, setCurrentPage] = useState<number>(1);
  const [itemsPerPage, setItemsPerPage] = useState<number>(10);

  // Modal Detail Gap Analysis
  const [gapModalOpen, setGapModalOpen] = useState(false);
  const [analysisData, setAnalysisData] = useState<PairwiseAnalysisResponse | null>(null);
  const [analyzingPair, setAnalyzingPair] = useState<string | null>(null);

  // 1. Load Master Lists for Filters
  useEffect(() => {
    async function loadMasters() {
      setLoadingMasters(true);
      try {
        const [seekersRes, vacanciesRes] = await Promise.all([
          getJobSeekers({ per_page: 100 }),
          getLowonganListApi({ per_page: 100, status_lowongan: 'Published' }),
        ]);
        setCandidatesList(seekersRes.data || []);
        setVacanciesList(vacanciesRes.data || []);
      } catch (err) {
        console.error('Gagal memuat master data pelamar/lowongan:', err);
      } finally {
        setLoadingMasters(false);
      }
    }
    loadMasters();
  }, []);

  // 2. Fetch Unified Recommendations
  const fetchUnifiedRecommendations = useCallback(async () => {
    setLoadingResults(true);
    setError(null);
    try {
      const res = await getUnifiedRecommendationsApi({
        job_seeker_id: selectedSeekerId ? Number(selectedSeekerId) : undefined,
        lowongan_id: selectedLowonganId || undefined,
        category: selectedCategory || undefined,
        search: searchQuery.trim() || undefined,
      });
      setRecommendations(res.data || []);
      setCurrentPage(1); // Reset to page 1 on filter change
    } catch (err: any) {
      setError(err.message || 'Gagal memuat rekomendasi terpadu.');
    } finally {
      setLoadingResults(false);
    }
  }, [selectedSeekerId, selectedLowonganId, selectedCategory, searchQuery]);

  useEffect(() => {
    fetchUnifiedRecommendations();
  }, [fetchUnifiedRecommendations]);

  // Options for Dropdowns
  const candidateOptions = useMemo(() => {
    return [
      { value: '', label: 'Semua Pencari Kerja' },
      ...candidatesList.map((c) => ({
        value: String(c.id),
        label: `${c.full_name} (${c.desired_occupation || 'Pencari Kerja'})`,
        code: c.nik,
      })),
    ];
  }, [candidatesList]);

  const vacancyOptions = useMemo(() => {
    return [
      { value: '', label: 'Semua Formasi Lowongan' },
      ...vacanciesList.map((v) => ({
        value: v.id,
        label: `${v.judul_lowongan} - ${v.nama_perusahaan}`,
        code: v.tipe_pekerjaan,
      })),
    ];
  }, [vacanciesList]);

  // KPI Statistics Summary
  const stats = useMemo(() => {
    const total = recommendations.length;
    const ready = recommendations.filter((r) => r.score >= 70).length;
    const gap = recommendations.filter((r) => r.score >= 40 && r.score < 70).length;
    const low = recommendations.filter((r) => r.score < 40).length;
    return { total, ready, gap, low };
  }, [recommendations]);

  // Pagination calculation
  const totalItems = recommendations.length;
  const totalPages = Math.max(1, Math.ceil(totalItems / itemsPerPage));
  const startIndex = (currentPage - 1) * itemsPerPage;
  const currentItems = useMemo(() => {
    return recommendations.slice(startIndex, startIndex + itemsPerPage);
  }, [recommendations, startIndex, itemsPerPage]);

  // Handler for Pairwise Analysis Modal
  const handleOpenAnalysisModal = async (seekerId: number, lowonganId: string) => {
    const pairKey = `${seekerId}_${lowonganId}`;
    setAnalyzingPair(pairKey);
    try {
      const data = await getPairAnalysisApi(seekerId, lowonganId);
      setAnalysisData(data);
      setGapModalOpen(true);
    } catch (err: any) {
      alert(err.message || 'Gagal menganalisis kesesuaian.');
    } finally {
      setAnalyzingPair(null);
    }
  };

  const handleRecommendCandidate = (candidateName: string, companyName: string) => {
    alert(`Sukses! Rekomendasi kandidat ${candidateName} telah didaftarkan untuk proses penempatan ke ${companyName}.`);
  };

  const handleResetFilters = () => {
    setSelectedSeekerId('');
    setSelectedLowonganId('');
    setSelectedCategory('');
    setSearchQuery('');
  };

  const formatCurrency = (val: number | null | undefined) => {
    if (val === null || val === undefined) return '-';
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      maximumFractionDigits: 0,
    }).format(val);
  };

  return (
    <AuthGuard>
      <div className="p-4 sm:p-6 lg:p-8 space-y-6 max-w-7xl mx-auto">
        {/* Header Title & Description */}
        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-slate-200">
          <div>
            <h1 className="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
              <Target className="w-6 h-6 text-[#0E385E]" />
              <span>Matriks Rekomendasi & Penjodohan Kerja</span>
            </h1>
            <p className="text-xs sm:text-sm text-slate-500 mt-1">
              Tabel terpadu penjodohan antara <strong>Pencari Kerja</strong> dan <strong>Formasi Lowongan Kerja</strong> berbasis komputasi taksonomi keahlian ESCO & KBJI.
            </p>
          </div>

          <button
            type="button"
            onClick={fetchUnifiedRecommendations}
            disabled={loadingResults}
            className="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 transition-colors shadow-2xs self-start sm:self-auto disabled:opacity-50"
          >
            <RefreshCw className={`w-3.5 h-3.5 ${loadingResults ? 'animate-spin text-[#0E385E]' : ''}`} />
            <span>Segarkan Perhitungan</span>
          </button>
        </div>

        {/* KPI Summary Cards */}
        <div className="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
          <div className="p-4 rounded-xl border border-slate-200 bg-white shadow-2xs">
            <span className="text-slate-500 text-xs font-medium block">Total Pasangan Dievaluasi</span>
            <div className="mt-1 flex items-baseline justify-between">
              <span className="text-2xl font-extrabold text-slate-900">{stats.total}</span>
              <span className="text-[11px] text-slate-400">Kombinasi</span>
            </div>
          </div>

          <div className="p-4 rounded-xl border border-emerald-200 bg-emerald-50/50 shadow-2xs">
            <span className="text-emerald-800 text-xs font-medium block">Siap Ditempatkan (≥70%)</span>
            <div className="mt-1 flex items-baseline justify-between">
              <span className="text-2xl font-extrabold text-emerald-700">{stats.ready}</span>
              <span className="text-[11px] font-semibold text-emerald-600">Prioritas Match</span>
            </div>
          </div>

          <div className="p-4 rounded-xl border border-amber-200 bg-amber-50/50 shadow-2xs">
            <span className="text-amber-800 text-xs font-medium block">Perlu Peningkatan (40-69%)</span>
            <div className="mt-1 flex items-baseline justify-between">
              <span className="text-2xl font-extrabold text-amber-700">{stats.gap}</span>
              <span className="text-[11px] font-semibold text-amber-600">Pelatihan Gap</span>
            </div>
          </div>

          <div className="p-4 rounded-xl border border-slate-200 bg-slate-50 shadow-2xs">
            <span className="text-slate-600 text-xs font-medium block">Belum Sesuai (&lt;40%)</span>
            <div className="mt-1 flex items-baseline justify-between">
              <span className="text-2xl font-extrabold text-slate-700">{stats.low}</span>
              <span className="text-[11px] text-slate-500">Bimbingan Karir</span>
            </div>
          </div>
        </div>

        {/* Filter Control Bar */}
        <div className="p-4 sm:p-5 rounded-xl border border-slate-200 bg-white shadow-2xs space-y-4">
          <div className="flex items-center justify-between gap-2 border-b border-slate-100 pb-3">
            <div className="flex items-center gap-2 text-xs sm:text-sm font-bold text-slate-800">
              <SlidersHorizontal className="w-4 h-4 text-[#0E385E]" />
              <span>Saring Berdasarkan Pelamar, Lowongan & Kategori</span>
            </div>
            {(selectedSeekerId || selectedLowonganId || selectedCategory || searchQuery) && (
              <button
                type="button"
                onClick={handleResetFilters}
                className="text-xs text-blue-600 hover:text-blue-800 font-medium underline underline-offset-2"
              >
                Reset Semua Filter
              </button>
            )}
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            {/* Search Input */}
            <div>
              <label className="block text-xs font-semibold text-slate-700 mb-1.5">
                Cari Kata Kunci
              </label>
              <div className="relative">
                <Search className="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                <input
                  type="text"
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  placeholder="Nama pelamar, posisi, kota..."
                  className="w-full pl-9 pr-3 py-2 text-xs rounded-lg border border-slate-300 bg-white focus:outline-hidden focus:ring-2 focus:ring-blue-600 text-slate-800"
                />
              </div>
            </div>

            {/* Filter Pelamar */}
            <div>
              <label className="block text-xs font-semibold text-slate-700 mb-1.5 flex items-center justify-between">
                <span>Pencari Kerja</span>
                {selectedSeekerId && (
                  <span className="text-[10px] text-blue-600 font-normal">Disaring</span>
                )}
              </label>
              <SearchableSelect
                options={candidateOptions}
                value={selectedSeekerId}
                onChange={setSelectedSeekerId}
                placeholder="Semua Pencari Kerja"
                disabled={loadingMasters}
              />
            </div>

            {/* Filter Lowongan */}
            <div>
              <label className="block text-xs font-semibold text-slate-700 mb-1.5 flex items-center justify-between">
                <span>Formasi Lowongan</span>
                {selectedLowonganId && (
                  <span className="text-[10px] text-blue-600 font-normal">Disaring</span>
                )}
              </label>
              <SearchableSelect
                options={vacancyOptions}
                value={selectedLowonganId}
                onChange={setSelectedLowonganId}
                placeholder="Semua Formasi Lowongan"
                disabled={loadingMasters}
              />
            </div>

            {/* Filter Kategori Skor */}
            <div>
              <label className="block text-xs font-semibold text-slate-700 mb-1.5">
                Status Kesiapan (Match)
              </label>
              <select
                value={selectedCategory}
                onChange={(e) => setSelectedCategory(e.target.value)}
                className="w-full py-2 px-3 text-xs rounded-lg border border-slate-300 bg-white focus:outline-hidden focus:ring-2 focus:ring-blue-600 text-slate-800"
              >
                <option value="">Semua Kategori Skor</option>
                <option value="perfect">100% Siap Kerja (Perfect Match)</option>
                <option value="high">≥ 70% Siap Ditempatkan (High Match)</option>
                <option value="gap">40% - 69% Perlu Peningkatan Skill</option>
                <option value="low">&lt; 40% Belum Sesuai</option>
              </select>
            </div>
          </div>
        </div>

        {/* Error Alert */}
        {error && (
          <div className="p-4 rounded-xl border border-red-200 bg-red-50 text-red-700 text-xs sm:text-sm flex items-start gap-2.5">
            <AlertCircle className="w-4 h-4 shrink-0 mt-0.5 text-red-600" />
            <span>{error}</span>
          </div>
        )}

        {/* Main Unified Recommendations Table */}
        <div className="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
          <div className="px-5 py-3.5 border-b border-slate-200 bg-slate-50/60 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
              <h2 className="text-sm font-bold text-slate-900">
                Daftar Pasangan Hasil Matching & Rekomendasi
              </h2>
              <p className="text-xs text-slate-500">
                Diurutkan berdasarkan skor kesesuaian keahlian tertinggi dari algoritma matching engine
              </p>
            </div>

            {/* Selector Items Per Page */}
            <div className="flex items-center gap-2 text-xs text-slate-600 self-end sm:self-auto">
              <span>Tampilkan:</span>
              <select
                value={itemsPerPage}
                onChange={(e) => {
                  setItemsPerPage(Number(e.target.value));
                  setCurrentPage(1);
                }}
                className="py-1 px-2 rounded border border-slate-300 bg-white font-medium text-slate-700 text-xs focus:ring-1 focus:ring-blue-600"
              >
                <option value={10}>10 data</option>
                <option value={25}>25 data</option>
                <option value={50}>50 data</option>
              </select>
            </div>
          </div>

          <div className="overflow-x-auto">
            <table className="w-full text-left border-collapse text-xs">
              <thead>
                <tr className="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold uppercase text-[11px] tracking-wider">
                  <th className="py-3 px-3.5 text-center w-12">#</th>
                  <th className="py-3 px-4 min-w-[220px]">Pencari Kerja (Kandidat)</th>
                  <th className="py-3 px-4 min-w-[250px]">Formasi Lowongan & Perusahaan</th>
                  <th className="py-3 px-4 min-w-[170px] text-center">Skor & Kesiapan</th>
                  <th className="py-3 px-4 min-w-[190px]">Kesesuaian Skill ESCO</th>
                  <th className="py-3 px-4 text-right min-w-[160px]">Aksi</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {loadingResults ? (
                  <tr>
                    <td colSpan={6} className="py-16 text-center text-slate-500">
                      <div className="flex flex-col items-center justify-center gap-2">
                        <Loader2 className="w-6 h-6 animate-spin text-[#0E385E]" />
                        <span className="text-xs font-medium">Sedang menghitung skor kecocokan kompetensi...</span>
                      </div>
                    </td>
                  </tr>
                ) : currentItems.length === 0 ? (
                  <tr>
                    <td colSpan={6} className="py-16 text-center text-slate-500">
                      <div className="max-w-sm mx-auto flex flex-col items-center gap-2">
                        <Target className="w-8 h-8 text-slate-300" />
                        <div className="text-sm font-bold text-slate-700">Tidak Ada Rekomendasi Ditemukan</div>
                        <p className="text-xs text-slate-400">
                          Tidak ditemukan pasangan yang memenuhi kriteria saringan Anda. Coba atur ulang filter di atas.
                        </p>
                        <button
                          type="button"
                          onClick={handleResetFilters}
                          className="mt-2 px-3 py-1.5 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 transition-colors"
                        >
                          Reset Filter
                        </button>
                      </div>
                    </td>
                  </tr>
                ) : (
                  currentItems.map((pair, index) => {
                    const rowNumber = startIndex + index + 1;
                    const pairKey = `${pair.candidate.id}_${pair.job.id}`;
                    const isAnalyzing = analyzingPair === pairKey;

                    return (
                      <tr
                        key={pair.id}
                        className="hover:bg-slate-50/70 transition-colors border-b border-slate-100"
                      >
                        {/* 1. Nomor Urut */}
                        <td className="py-3.5 px-3.5 text-center font-mono text-slate-400 text-xs">
                          {rowNumber}
                        </td>

                        {/* 2. Profil Pelamar */}
                        <td className="py-3.5 px-4">
                          <div className="space-y-1">
                            <div className="font-bold text-slate-900 hover:text-blue-700 transition-colors">
                              {pair.candidate.full_name}
                            </div>
                            <div className="text-[11px] text-slate-500 flex items-center gap-1.5">
                              <span className="font-mono bg-slate-100 px-1 py-0.2 rounded text-[10px] text-slate-600">
                                {pair.candidate.nik}
                              </span>
                              <span>•</span>
                              <span>{pair.candidate.desired_occupation || 'Pencari Kerja'}</span>
                            </div>
                            <div className="text-[11px] text-slate-400 flex items-center gap-1">
                              <MapPin className="w-3 h-3 shrink-0" />
                              <span className="truncate max-w-[200px]">{pair.candidate.location || '-'}</span>
                            </div>
                          </div>
                        </td>

                        {/* 3. Formasi Lowongan & Perusahaan */}
                        <td className="py-3.5 px-4">
                          <div className="space-y-1">
                            <div className="font-bold text-slate-900 line-clamp-1">
                              {pair.job.judul_lowongan}
                            </div>
                            <div className="text-[11px] text-slate-600 flex items-center gap-1">
                              <Building2 className="w-3 h-3 text-slate-400 shrink-0" />
                              <span className="font-medium">{pair.job.nama_perusahaan}</span>
                            </div>
                            <div className="text-[10px] text-slate-400 flex flex-wrap items-center gap-1.5">
                              <span className="px-1.5 py-0.2 rounded bg-slate-100 font-medium text-slate-600">
                                {pair.job.tipe_pekerjaan}
                              </span>
                              <span>•</span>
                              <span>{pair.job.sistem_kerja}</span>
                              {pair.job.gaji_tampilkan && pair.job.gaji_minimal && (
                                <>
                                  <span>•</span>
                                  <span className="font-semibold text-emerald-700">
                                    {formatCurrency(pair.job.gaji_minimal)}
                                  </span>
                                </>
                              )}
                            </div>
                          </div>
                        </td>

                        {/* 4. Skor & Status Kesiapan */}
                        <td className="py-3.5 px-4 text-center">
                          <div className="inline-flex flex-col items-center gap-1.5">
                            {/* Score number and bar */}
                            <div className="flex items-center gap-2">
                              <div className="w-20 bg-slate-100 rounded-full h-2 overflow-hidden border border-slate-200">
                                <div
                                  className={`h-full rounded-full transition-all duration-500 ${
                                    pair.score >= 70
                                      ? 'bg-emerald-500'
                                      : pair.score >= 40
                                      ? 'bg-amber-500'
                                      : 'bg-slate-400'
                                  }`}
                                  style={{ width: `${pair.score}%` }}
                                />
                              </div>
                              <span
                                className={`text-xs font-black ${
                                  pair.score >= 70
                                    ? 'text-emerald-700'
                                    : pair.score >= 40
                                    ? 'text-amber-700'
                                    : 'text-slate-600'
                                }`}
                              >
                                {pair.score}%
                              </span>
                            </div>

                            {/* Badge */}
                            <span
                              className={`px-2 py-0.5 rounded-full text-[10px] font-bold border inline-block whitespace-nowrap ${
                                pair.classification.badge_color === 'emerald'
                                  ? 'bg-emerald-50 text-emerald-800 border-emerald-300'
                                  : pair.classification.badge_color === 'blue'
                                  ? 'bg-blue-50 text-blue-800 border-blue-300'
                                  : pair.classification.badge_color === 'amber'
                                  ? 'bg-amber-50 text-amber-800 border-amber-300'
                                  : 'bg-slate-100 text-slate-700 border-slate-300'
                              }`}
                            >
                              {pair.classification.label}
                            </span>
                          </div>
                        </td>

                        {/* 5. Detail Skill Cocok & Gap */}
                        <td className="py-3.5 px-4">
                          <div className="space-y-1.5">
                            <div className="flex items-center gap-2 text-[11px]">
                              <span className="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-800 border border-emerald-200 font-semibold">
                                <CheckCircle2 className="w-3 h-3 text-emerald-600" />
                                <span>{pair.total_matched} Cocok</span>
                              </span>
                              {pair.total_gap > 0 ? (
                                <span className="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-amber-50 text-amber-800 border border-amber-200 font-semibold">
                                  <AlertTriangle className="w-3 h-3 text-amber-600" />
                                  <span>{pair.total_gap} Gap</span>
                                </span>
                              ) : (
                                <span className="text-[10px] text-emerald-600 font-medium">
                                  (Kompetensi Lengkap)
                                </span>
                              )}
                            </div>

                            {/* Skill names pill preview */}
                            <div className="flex flex-wrap gap-1 max-w-[260px]">
                              {pair.matched_skills.slice(0, 2).map((s) => (
                                <span
                                  key={s.id}
                                  className="text-[10px] px-1.5 py-0.2 rounded bg-slate-100 text-slate-700 truncate max-w-[120px]"
                                  title={s.title}
                                >
                                  {s.title}
                                </span>
                              ))}
                              {pair.matched_skills.length > 2 && (
                                <span className="text-[9px] text-slate-400 self-center">
                                  +{pair.matched_skills.length - 2} lainnya
                                </span>
                              )}
                            </div>
                          </div>
                        </td>

                        {/* 6. Aksi */}
                        <td className="py-3.5 px-4 text-right">
                          <div className="flex items-center justify-end gap-1.5">
                            {/* Tombol Lihat Detail Gap */}
                            <button
                              type="button"
                              onClick={() => handleOpenAnalysisModal(pair.candidate.id, pair.job.id)}
                              disabled={isAnalyzing}
                              className="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-md border border-slate-300 text-slate-700 bg-white hover:bg-slate-50 text-xs font-medium transition-colors shadow-2xs disabled:opacity-50"
                              title="Lihat Komparasi Skill & Gap Lengkap"
                            >
                              {isAnalyzing ? (
                                <Loader2 className="w-3 h-3 animate-spin text-[#0E385E]" />
                              ) : (
                                <Eye className="w-3 h-3 text-slate-500" />
                              )}
                              <span>Analisis</span>
                            </button>

                            {/* Tombol Rekomendasikan (Jika Score >= 70%) */}
                            {pair.score >= 70 && (
                              <button
                                type="button"
                                onClick={() => handleRecommendCandidate(pair.candidate.full_name, pair.job.nama_perusahaan)}
                                className="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-md bg-[#0E385E] text-white hover:bg-[#163A5F] text-xs font-semibold transition-colors shadow-2xs"
                                title="Rekomendasikan Pelamar ke Perusahaan Ini"
                              >
                                <Send className="w-3 h-3" />
                                <span className="hidden sm:inline">Kirim</span>
                              </button>
                            )}
                          </div>
                        </td>
                      </tr>
                    );
                  })
                )}
              </tbody>
            </table>
          </div>

          {/* Table Pagination Footer */}
          {totalItems > 0 && (
            <div className="px-5 py-3.5 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-600">
              <div>
                Menampilkan{' '}
                <span className="font-semibold text-slate-800">
                  {startIndex + 1} - {Math.min(startIndex + itemsPerPage, totalItems)}
                </span>{' '}
                dari <span className="font-semibold text-slate-800">{totalItems}</span> total pasangan rekomendasi
              </div>

              <div className="flex items-center gap-1">
                {/* Previous Button */}
                <button
                  type="button"
                  disabled={currentPage <= 1}
                  onClick={() => setCurrentPage((p) => Math.max(1, p - 1))}
                  className="inline-flex items-center gap-1 px-3 py-1.5 rounded-md border border-slate-300 bg-white hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors font-medium shadow-2xs"
                >
                  <ChevronLeft className="w-3.5 h-3.5" />
                  <span>Sebelumnya</span>
                </button>

                {/* Page Number Indicator */}
                <div className="px-3 py-1.5 font-semibold text-slate-800 bg-white rounded-md border border-slate-200">
                  {currentPage} / {totalPages}
                </div>

                {/* Next Button */}
                <button
                  type="button"
                  disabled={currentPage >= totalPages}
                  onClick={() => setCurrentPage((p) => Math.min(totalPages, p + 1))}
                  className="inline-flex items-center gap-1 px-3 py-1.5 rounded-md border border-slate-300 bg-white hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors font-medium shadow-2xs"
                >
                  <span>Selanjutnya</span>
                  <ChevronRight className="w-3.5 h-3.5" />
                </button>
              </div>
            </div>
          )}
        </div>

        {/* Modal Analisis Gap */}
        <SkillGapModal
          isOpen={gapModalOpen}
          onClose={() => setGapModalOpen(false)}
          data={analysisData}
          onRecommend={(dt) => handleRecommendCandidate(dt.candidate.full_name, dt.job.nama_perusahaan)}
        />
      </div>
    </AuthGuard>
  );
}

export default function RekomendasiPage() {
  return (
    <Suspense
      fallback={
        <div className="p-8 flex items-center justify-center min-h-[50vh]">
          <Loader2 className="w-8 h-8 animate-spin text-[#0E385E]" />
        </div>
      }
    >
      <RekomendasiContent />
    </Suspense>
  );
}
