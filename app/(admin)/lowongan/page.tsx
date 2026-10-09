'use client';

import React, { useState, useEffect, useCallback, useMemo } from 'react';
import SearchableSelect from '@/components/ui/SearchableSelect';
import {
  Briefcase,
  Building2,
  MapPin,
  Search,
  Calendar,
  Plus,
  Edit2,
  Trash2,
  Eye,
  Loader2,
  AlertCircle,
  Users,
  CheckCircle2,
  Clock,
  ExternalLink,
} from 'lucide-react';
import AuthGuard from '@/components/auth/AuthGuard';
import { useAuth } from '@/hooks/useAuth';
import { LowonganItem, LowonganStatus } from '@/types/lowongan';
import {
  deleteLowonganApi,
  getLowonganListApi,
  toggleLowonganStatusApi,
} from '@/services/lowongan';
import { getProvinces } from '@/services/wilayah';
import { Province } from '@/types/job-seeker';
import LowonganFormModal from '@/components/lowongan/LowonganFormModal';
import LowonganDetailModal from '@/components/lowongan/LowonganDetailModal';

const TIPE_PEKERJAAN_OPTIONS = [
  { value: 'Full time', label: 'Full time' },
  { value: 'Part time', label: 'Part time' },
  { value: 'Contract', label: 'Kontrak' },
  { value: 'Internship', label: 'Magang' },
];

const STATUS_OPTIONS: LowonganStatus[] = [
  'Published',
  'Draft',
  'Closed',
  'Expired',
  'Suspended',
  'Blocked',
  'Archived',
];

const statusBadgeClass = (status: string) => {
  if (status === 'Published') return 'bg-emerald-50 text-emerald-700 border-emerald-200';
  if (status === 'Closed' || status === 'Blocked' || status === 'Suspended')
    return 'bg-red-50 text-red-700 border-red-200';
  if (status === 'Expired' || status === 'Archived')
    return 'bg-slate-100 text-slate-600 border-slate-200';
  return 'bg-amber-50 text-amber-700 border-amber-200';
};

export default function LowonganPage() {
  const { hasPermission } = useAuth();

  // Data & Pagination State
  const [vacancies, setVacancies] = useState<LowonganItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [totalItems, setTotalItems] = useState(0);

  // Filter State
  const [search, setSearch] = useState('');
  const [selectedProvince, setSelectedProvince] = useState('');
  const [selectedType, setSelectedType] = useState('');
  const [selectedStatus, setSelectedStatus] = useState('');
  const [provinces, setProvinces] = useState<Province[]>([]);

  // Modals State
  const [isFormModalOpen, setIsFormModalOpen] = useState(false);
  const [editingItem, setEditingItem] = useState<LowonganItem | null>(null);

  const [isDetailModalOpen, setIsDetailModalOpen] = useState(false);
  const [detailItem, setDetailItem] = useState<LowonganItem | null>(null);

  const [isDeleting, setIsDeleting] = useState<string | null>(null);

  // Opsi provinsi untuk SearchableSelect diurutkan berdasarkan ID
  const provinceFilterOptions = useMemo(
    () => [
      { value: '', label: 'Semua Wilayah' },
      ...(Array.isArray(provinces) ? provinces : [])
        .slice()
        .sort((a, b) => a.id.localeCompare(b.id, undefined, { numeric: true }))
        .map((p) => ({
          value: p.id,
          label: p.name,
          code: p.id,
        })),
    ],
    [provinces]
  );

  // Load provinces for filter
  useEffect(() => {
    getProvinces()
      .then(setProvinces)
      .catch((err) => console.error('Gagal memuat provinsi filter:', err));
  }, []);

  // Fetch Lowongan list
  const fetchVacancies = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const res = await getLowonganListApi({
        search: search.trim() || undefined,
        provinsi_id: selectedProvince || undefined,
        tipe_pekerjaan: selectedType || undefined,
        status_lowongan: selectedStatus || undefined,
        page: currentPage,
        per_page: 10,
      });

      setVacancies(res.data);
      setCurrentPage(res.meta.current_page);
      setTotalPages(res.meta.last_page);
      setTotalItems(res.meta.total);
    } catch (err: unknown) {
      if (err instanceof Error) {
        setError(err.message);
      } else {
        setError('Gagal memuat daftar lowongan pekerjaan.');
      }
    } finally {
      setLoading(false);
    }
  }, [search, selectedProvince, selectedType, selectedStatus, currentPage]);

  useEffect(() => {
    const timer = setTimeout(() => {
      fetchVacancies();
    }, 300);
    return () => clearTimeout(timer);
  }, [fetchVacancies]);

  // Action Handlers
  const handleOpenCreate = () => {
    setEditingItem(null);
    setIsFormModalOpen(true);
  };

  const handleOpenEdit = (item: LowonganItem) => {
    setEditingItem(item);
    setIsFormModalOpen(true);
  };

  const handleOpenDetail = (item: LowonganItem) => {
    setDetailItem(item);
    setIsDetailModalOpen(true);
  };

  const handleDelete = async (id: string, title: string) => {
    if (!window.confirm(`Apakah Anda yakin ingin menghapus lowongan "${title}"?`)) {
      return;
    }

    setIsDeleting(id);
    try {
      await deleteLowonganApi(id);
      fetchVacancies();
    } catch (err: unknown) {
      alert(err instanceof Error ? err.message : 'Gagal menghapus lowongan.');
    } finally {
      setIsDeleting(null);
    }
  };

  const handleStatusChange = async (
    id: string,
    newStatus: LowonganStatus
  ) => {
    try {
      await toggleLowonganStatusApi(id, newStatus);
      fetchVacancies();
    } catch (err: unknown) {
      alert(err instanceof Error ? err.message : 'Gagal mengubah status.');
    }
  };

  const formatCurrency = (val: number | null | undefined) => {
    if (!val) return null;
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      maximumFractionDigits: 0,
    }).format(val);
  };

  const formatDate = (isoString?: string | null) => {
    if (!isoString) return '-';
    try {
      return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
      }).format(new Date(isoString));
    } catch {
      return isoString;
    }
  };

  const isExpired = (isoString: string) => {
    return new Date(isoString).getTime() < new Date().getTime();
  };

  return (
    <AuthGuard requiredPermission="lowongan.view">
      <div className="p-4 sm:p-6 lg:p-8 space-y-6 max-w-7xl mx-auto">
        {/* Header Title & CTA */}
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div>
            <h2 className="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
              Daftar Lowongan Pekerjaan
            </h2>
            <p className="text-xs sm:text-sm text-slate-500 mt-1">
              Tabel data lowongan pekerjaan industri terintegrasi kualifikasi dan kebutuhan formasi.
            </p>
          </div>

          {hasPermission('lowongan.create') && (
            <button
              type="button"
              onClick={handleOpenCreate}
              className="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-[#0E385E] text-white text-xs sm:text-sm font-semibold hover:bg-[#163A5F] transition-colors shadow-xs shrink-0 self-start sm:self-auto"
            >
              <Plus className="w-4 h-4" />
              <span>Tambah Lowongan</span>
            </button>
          )}
        </div>

        {/* Filter & Search Bar */}
        <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex flex-col md:flex-row gap-3 items-stretch md:items-center">
          <div className="relative flex-1">
            <Search className="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
            <input
              type="text"
              placeholder="Cari judul posisi pekerjaan atau nama perusahaan..."
              value={search}
              onChange={(e) => {
                setSearch(e.target.value);
                setCurrentPage(1);
              }}
              className="w-full pl-9 pr-3 py-2 text-xs sm:text-sm rounded-md border border-slate-300 focus:outline-hidden focus:border-[#2563EB]"
            />
          </div>

          <div className="flex flex-wrap items-center gap-2">
            <div className="w-full sm:w-56">
              <SearchableSelect
                options={provinceFilterOptions}
                value={selectedProvince}
                onChange={(val) => {
                  setSelectedProvince(val);
                  setCurrentPage(1);
                }}
                placeholder="Semua Wilayah"
                searchPlaceholder="Cari provinsi (nama/kode)..."
              />
            </div>

            <select
              value={selectedType}
              onChange={(e) => {
                setSelectedType(e.target.value);
                setCurrentPage(1);
              }}
              className="text-xs sm:text-sm py-2 px-3 rounded-md border border-slate-300 bg-white text-slate-700 focus:outline-hidden"
            >
              <option value="">Semua Tipe</option>
              {TIPE_PEKERJAAN_OPTIONS.map((opt) => (
                <option key={opt.value} value={opt.value}>
                  {opt.label}
                </option>
              ))}
            </select>

            <select
              value={selectedStatus}
              onChange={(e) => {
                setSelectedStatus(e.target.value);
                setCurrentPage(1);
              }}
              className="text-xs sm:text-sm py-2 px-3 rounded-md border border-slate-300 bg-white text-slate-700 focus:outline-hidden"
            >
              <option value="">Semua Status</option>
              {STATUS_OPTIONS.map((st) => (
                <option key={st} value={st}>
                  {st}
                </option>
              ))}
            </select>
          </div>
        </div>

        {/* Error Alert */}
        {error && (
          <div className="p-4 rounded-lg bg-red-50 border border-red-200 text-red-700 text-xs flex items-center gap-2">
            <AlertCircle className="w-4 h-4 shrink-0" />
            <span>{error}</span>
          </div>
        )}

        {/* Tabel Lowongan Kerja */}
        <div className="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full text-left border-collapse text-xs sm:text-sm">
              <thead>
                <tr className="bg-slate-50/80 border-b border-slate-200 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                  <th className="py-3.5 px-4">Posisi & Perusahaan</th>
                  <th className="py-3.5 px-4">Wilayah & Kuota</th>
                  <th className="py-3.5 px-4">Kualifikasi</th>
                  <th className="py-3.5 px-4">Kompensasi Gaji</th>
                  <th className="py-3.5 px-4">Batas Akhir</th>
                  <th className="py-3.5 px-4 text-center">Status</th>
                  <th className="py-3.5 px-4 text-right">Aksi</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100 text-slate-800">
                {loading ? (
                  <tr>
                    <td colSpan={7} className="py-16 text-center text-slate-500">
                      <Loader2 className="w-6 h-6 animate-spin mx-auto text-blue-600 mb-2" />
                      <p className="text-xs sm:text-sm">Memuat daftar lowongan pekerjaan...</p>
                    </td>
                  </tr>
                ) : vacancies.length === 0 ? (
                  <tr>
                    <td colSpan={7} className="py-16 text-center text-slate-500">
                      <Briefcase className="w-8 h-8 text-slate-300 mx-auto mb-2" />
                      <p className="text-sm font-semibold text-slate-700">Tidak ada lowongan ditemukan</p>
                      <p className="text-xs text-slate-400 mt-1">
                        Coba sesuaikan kata kunci pencarian atau ubah filter di atas.
                      </p>
                    </td>
                  </tr>
                ) : (
                  vacancies.map((job) => {
                    const minSalary = formatCurrency(job.gaji_minimal);
                    const maxSalary = formatCurrency(job.gaji_maksimal);
                    let salaryDisplay = 'Kompetitif';
                    if (job.gaji_tampilkan) {
                      if (minSalary && maxSalary) {
                        salaryDisplay = `${minSalary} - ${maxSalary}`;
                      } else if (minSalary) {
                        salaryDisplay = `Mulai ${minSalary}`;
                      }
                    } else {
                      salaryDisplay = 'Tidak Disebutkan';
                    }

                    const expired = isExpired(job.tanggal_tutup);

                    return (
                      <tr key={job.id} className="hover:bg-slate-50/70 transition-colors">
                        {/* Posisi & Perusahaan */}
                        <td className="py-3.5 px-4 max-w-xs">
                          <div>
                            <span
                              onClick={() => handleOpenDetail(job)}
                              className="font-bold text-slate-900 hover:text-blue-600 cursor-pointer block truncate text-xs sm:text-sm"
                              title={job.judul_lowongan}
                            >
                              {job.judul_lowongan}
                            </span>
                            <div className="flex items-center gap-1.5 text-xs text-slate-600 mt-0.5">
                              <Building2 className="w-3.5 h-3.5 text-slate-400 shrink-0" />
                              <span className="truncate">{job.nama_perusahaan}</span>
                            </div>
                            <div className="flex flex-wrap items-center gap-1 mt-1.5">
                              <span className="text-[10px] px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 font-medium">
                                {job.tipe_pekerjaan}
                              </span>
                              <span className="text-[10px] px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 font-medium">
                                {job.sistem_kerja}
                              </span>
                              {job.is_disabilitas && (
                                <span className="text-[10px] px-1.5 py-0.5 rounded bg-purple-50 text-purple-700 border border-purple-200 font-semibold">
                                  Inklusif
                                </span>
                              )}
                            </div>
                          </div>
                        </td>

                        {/* Wilayah & Kuota */}
                        <td className="py-3.5 px-4 whitespace-nowrap text-xs text-slate-600">
                          <div className="flex items-center gap-1 font-medium text-slate-800">
                            <MapPin className="w-3.5 h-3.5 text-slate-400 shrink-0" />
                            <span>{job.regency?.name || '-'}</span>
                          </div>
                          <div className="text-[11px] text-slate-400 pl-4.5">
                            {job.province?.name || '-'}
                          </div>
                          <div className="text-[11px] text-slate-500 pl-4.5 mt-0.5 flex items-center gap-1">
                            <Users className="w-3 h-3 text-slate-400" />
                            <span>Kuota: {job.jumlah_kebutuhan} orang</span>
                          </div>
                        </td>

                        {/* Kualifikasi & Keahlian */}
                        <td className="py-3.5 px-4 max-w-[220px]">
                          <div className="text-xs font-semibold text-slate-800 mb-1">
                            {job.education_level?.name || '-'}
                            {job.pengalaman_minimal_tahun > 0 && (
                              <span className="text-slate-500 font-normal ml-1">
                                • {job.pengalaman_minimal_tahun} thn
                              </span>
                            )}
                          </div>
                          {job.skills && job.skills.length > 0 ? (
                            <div className="flex flex-wrap gap-1">
                              {job.skills.slice(0, 2).map((sk) => (
                                <span
                                  key={sk.id}
                                  className={`text-[10px] px-1.5 py-0.5 rounded font-mono truncate max-w-[150px] ${
                                    sk.tipe_keahlian === 'wajib'
                                      ? 'bg-blue-50 text-blue-700 border border-blue-200 font-semibold'
                                      : 'bg-slate-100 text-slate-600 border border-slate-200'
                                  }`}
                                  title={`${sk.title} (${sk.tipe_keahlian})`}
                                >
                                  {sk.title}
                                </span>
                              ))}
                              {job.skills.length > 2 && (
                                <span className="text-[10px] bg-slate-100 text-slate-500 px-1 py-0.5 rounded font-mono">
                                  +{job.skills.length - 2}
                                </span>
                              )}
                            </div>
                          ) : (
                            <span className="text-[11px] text-slate-400 italic">Tanpa syarat skill</span>
                          )}
                        </td>

                        {/* Kompensasi Gaji */}
                        <td className="py-3.5 px-4 whitespace-nowrap">
                          <span
                            className={`text-xs font-semibold ${
                              job.gaji_tampilkan ? 'text-emerald-700' : 'text-slate-400 italic'
                            }`}
                          >
                            {salaryDisplay}
                          </span>
                        </td>

                        {/* Batas Tutup */}
                        <td className="py-3.5 px-4 whitespace-nowrap text-xs">
                          <div className="flex items-center gap-1.5">
                            <Calendar className="w-3.5 h-3.5 text-slate-400" />
                            <span className={expired ? 'text-red-600 font-semibold' : 'text-slate-700'}>
                              {formatDate(job.tanggal_tutup)}
                            </span>
                          </div>
                          {expired && (
                            <span className="text-[10px] text-red-500 font-medium pl-5 block">
                              (Sudah Berakhir)
                            </span>
                          )}
                        </td>

                        {/* Status */}
                        <td className="py-3.5 px-4 text-center whitespace-nowrap">
                          {hasPermission('lowongan.edit') ? (
                            <select
                              value={job.status_lowongan}
                              onChange={(e) =>
                                handleStatusChange(job.id, e.target.value as LowonganStatus)
                              }
                              className={`text-[11px] font-bold px-2 py-1 rounded-md border cursor-pointer focus:outline-hidden ${statusBadgeClass(
                                job.status_lowongan
                              )}`}
                            >
                              {STATUS_OPTIONS.map((st) => (
                                <option key={st} value={st}>
                                  {st}
                                </option>
                              ))}
                            </select>
                          ) : (
                            <span
                              className={`text-[11px] font-bold px-2.5 py-1 rounded-md border inline-block ${statusBadgeClass(
                                job.status_lowongan
                              )}`}
                            >
                              {job.status_lowongan}
                            </span>
                          )}
                        </td>

                        {/* Aksi */}
                        <td className="py-3.5 px-4 text-right whitespace-nowrap">
                          <div className="flex items-center justify-end gap-1">
                            <button
                              type="button"
                              onClick={() => handleOpenDetail(job)}
                              className="p-1.5 rounded-md text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                              title="Lihat Detail Lowongan"
                            >
                              <Eye className="w-4 h-4" />
                            </button>

                            {hasPermission('lowongan.edit') && (
                              <button
                                type="button"
                                onClick={() => handleOpenEdit(job)}
                                className="p-1.5 rounded-md text-slate-600 hover:text-amber-600 hover:bg-amber-50 transition-colors"
                                title="Edit Lowongan"
                              >
                                <Edit2 className="w-4 h-4" />
                              </button>
                            )}

                            {hasPermission('lowongan.delete') && (
                              <button
                                type="button"
                                disabled={isDeleting === job.id}
                                onClick={() => handleDelete(job.id, job.judul_lowongan)}
                                className="p-1.5 rounded-md text-slate-600 hover:text-red-600 hover:bg-red-50 transition-colors disabled:opacity-50"
                                title="Hapus Lowongan"
                              >
                                {isDeleting === job.id ? (
                                  <Loader2 className="w-4 h-4 animate-spin" />
                                ) : (
                                  <Trash2 className="w-4 h-4" />
                                )}
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

          {/* Pagination Footer */}
          {totalItems > 0 && (
            <div className="px-4 py-3 bg-slate-50 border-t border-slate-200 flex items-center justify-between text-xs text-slate-600">
              <span>
                Menampilkan {vacancies.length} dari {totalItems} total lowongan
              </span>
              <div className="flex items-center gap-1">
                <button
                  type="button"
                  disabled={currentPage <= 1}
                  onClick={() => setCurrentPage((p) => Math.max(1, p - 1))}
                  className="px-3 py-1.5 rounded border border-slate-300 bg-white hover:bg-slate-50 disabled:opacity-50 transition-colors font-medium"
                >
                  Sebelumnya
                </button>
                <span className="px-3 py-1.5 font-semibold text-slate-800">
                  {currentPage} / {totalPages}
                </span>
                <button
                  type="button"
                  disabled={currentPage >= totalPages}
                  onClick={() => setCurrentPage((p) => Math.min(totalPages, p + 1))}
                  className="px-3 py-1.5 rounded border border-slate-300 bg-white hover:bg-slate-50 disabled:opacity-50 transition-colors font-medium"
                >
                  Selanjutnya
                </button>
              </div>
            </div>
          )}
        </div>

        {/* Modal Form Tambah / Edit */}
        <LowonganFormModal
          isOpen={isFormModalOpen}
          onClose={() => setIsFormModalOpen(false)}
          onSuccess={fetchVacancies}
          initialData={editingItem}
        />

        {/* Modal Detail Lowongan */}
        <LowonganDetailModal
          isOpen={isDetailModalOpen}
          onClose={() => setIsDetailModalOpen(false)}
          lowongan={detailItem}
          onEdit={(item) => {
            setIsDetailModalOpen(false);
            setEditingItem(item);
            setIsFormModalOpen(true);
          }}
        />
      </div>
    </AuthGuard>
  );
}
