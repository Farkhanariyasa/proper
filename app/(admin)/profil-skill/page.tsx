'use client';

import React, { useState, useEffect, useCallback, useMemo } from 'react';
import SearchableSelect from '@/components/ui/SearchableSelect';
import {
  Plus,
  Search,
  CheckCircle2,
  AlertCircle,
  Loader2,
  RefreshCw,
  Users,
  Briefcase,
  GraduationCap,
  MapPin,
} from 'lucide-react';
import { JobSeeker, Province } from '@/types/job-seeker';
import { getJobSeekers, getJobSeekerOptions } from '@/services/job-seeker';
import { getProvinces } from '@/services/wilayah';
import JobSeekerFormModal from '@/components/job-seeker/JobSeekerFormModal';
import JobSeekerDetailModal from '@/components/job-seeker/JobSeekerDetailModal';
import AuthGuard from '@/components/auth/AuthGuard';
import { useAuth } from '@/hooks/useAuth';

export default function ProfilSkillPage() {
  const { hasPermission } = useAuth();
  const [jobSeekers, setJobSeekers] = useState<JobSeeker[]>([]);
  const [provinces, setProvinces] = useState<Province[]>([]);
  const [studyFieldGroups, setStudyFieldGroups] = useState<string[]>([]);
  const [meta, setMeta] = useState({
    current_page: 1,
    last_page: 1,
    total: 0,
    per_page: 10,
  });

  // Filter States
  const [searchQuery, setSearchQuery] = useState('');
  const [selectedProvince, setSelectedProvince] = useState('');
  const [selectedFieldGroup, setSelectedFieldGroup] = useState('');

  // UI States
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [isFormOpen, setIsFormOpen] = useState(false);
  const [detailSeekerId, setDetailSeekerId] = useState<number | null>(null);

  // Opsi provinsi untuk SearchableSelect
  const provinceOptions = useMemo(
    () => [
      { value: '', label: 'Semua Provinsi' },
      ...provinces.map((p) => ({
        value: p.id,
        label: p.name,
        code: p.id,
      })),
    ],
    [provinces]
  );

  // Load Filter Options
  useEffect(() => {
    Promise.all([getProvinces(), getJobSeekerOptions()])
      .then(([provData, optData]) => {
        setProvinces(provData);
        setStudyFieldGroups(optData.study_field_groups);
      })
      .catch((err) => {
        console.error('Gagal memuat filter options:', err);
      });
  }, []);

  // Fetch Job Seekers Data
  const loadJobSeekers = useCallback(
    async (page = 1) => {
      setIsLoading(true);
      setError(null);
      try {
        const response = await getJobSeekers({
          page,
          q: searchQuery.trim() || undefined,
          province_id: selectedProvince || undefined,
          study_field_group: selectedFieldGroup || undefined,
          per_page: 10,
        });
        setJobSeekers(response.data);
        setMeta(response.meta);
      } catch (err: any) {
        setError(err.message || 'Gagal memuat daftar profil pencari kerja');
      } finally {
        setIsLoading(false);
      }
    },
    [searchQuery, selectedProvince, selectedFieldGroup]
  );

  useEffect(() => {
    loadJobSeekers(1);
  }, [loadJobSeekers]);

  return (
    <AuthGuard requiredPermission="job_seekers.view">
      <div className="p-4 sm:p-6 lg:p-8 space-y-6 max-w-7xl mx-auto">
      {/* Page Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h2 className="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
            <Users className="w-6 h-6 text-[#0E385E]" />
            <span>Profil Skill Pencari Kerja</span>
          </h2>
        </div>
        <div className="flex items-center gap-2">
          <button
            type="button"
            onClick={() => loadJobSeekers(meta.current_page)}
            disabled={isLoading}
            className="p-2 rounded-md border border-slate-300 text-slate-600 hover:bg-slate-50 transition-colors"
            title="Refresh Data"
          >
            <RefreshCw className={`w-4 h-4 ${isLoading ? 'animate-spin' : ''}`} />
          </button>
          {hasPermission('job_seekers.create') && (
            <button
              type="button"
              onClick={() => setIsFormOpen(true)}
              className="inline-flex items-center gap-2 px-4 py-2 rounded-md bg-[#0E385E] text-white text-xs sm:text-sm font-semibold hover:bg-[#163A5F] transition-colors shadow-xs"
            >
              <Plus className="w-4 h-4" />
              <span>Tambah Profil / Kandidat Baru</span>
            </button>
          )}
        </div>
      </div>

      {/* Filter Toolbar */}
      <div className="bg-white p-4 rounded-lg border border-slate-200 shadow-xs flex flex-col md:flex-row gap-3 items-center justify-between">
        <div className="relative w-full md:w-80">
          <Search className="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
          <input
            type="text"
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            onKeyDown={(e) => {
              if (e.key === 'Enter') loadJobSeekers(1);
            }}
            placeholder="Cari NIK atau Nama kandidat..."
            className="w-full pl-9 pr-3 py-2 text-xs sm:text-sm rounded-md border border-slate-300 focus:outline-hidden focus:border-[#2563EB] focus:ring-1 focus:ring-[#2563EB]"
          />
        </div>

        <div className="flex flex-wrap items-center gap-2 w-full md:w-auto">
          {/* Filter Provinsi Searchable */}
          <div className="w-full sm:w-56">
            <SearchableSelect
              options={provinceOptions}
              value={selectedProvince}
              onChange={(val) => setSelectedProvince(val)}
              placeholder="Semua Provinsi"
              searchPlaceholder="Cari provinsi (nama/kode)..."
            />
          </div>

          {/* Filter Rumpun Pendidikan */}
          <select
            value={selectedFieldGroup}
            onChange={(e) => setSelectedFieldGroup(e.target.value)}
            className="text-xs sm:text-sm py-2 px-3 rounded-md border border-slate-300 bg-white text-slate-700 focus:outline-hidden max-w-[220px]"
          >
            <option value="">Semua Rumpun Bidang</option>
            {studyFieldGroups.map((group) => (
              <option key={group} value={group}>
                {group}
              </option>
            ))}
          </select>

          <button
            type="button"
            onClick={() => loadJobSeekers(1)}
            className="px-4 py-2 rounded-md bg-[#0E385E] text-white text-xs font-semibold hover:bg-[#163A5F] transition-colors"
          >
            Filter
          </button>
        </div>
      </div>

      {/* Error Message */}
      {error && (
        <div className="p-4 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm flex items-center gap-2">
          <AlertCircle className="w-4 h-4 shrink-0 text-red-600" />
          <span>{error}</span>
        </div>
      )}

      {/* Table Data */}
      <div className="bg-white rounded-lg border border-slate-200 shadow-xs overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs sm:text-sm">
            <thead className="bg-[#F8FAFC] border-b border-[#DEE2E6] text-slate-700 font-semibold">
              <tr>
                <th className="py-3 px-4">NIK</th>
                <th className="py-3 px-4">Nama Lengkap</th>
                <th className="py-3 px-4">Domisili (Kab/Kota)</th>
                <th className="py-3 px-4">Pendidikan & Rumpun</th>
                <th className="py-3 px-4">Target Pekerjaan</th>
                <th className="py-3 px-4 text-center">Skill ESCO</th>
                <th className="py-3 px-4 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100 text-slate-700">
              {isLoading ? (
                <tr>
                  <td colSpan={7} className="py-12 text-center text-slate-400">
                    <div className="flex flex-col items-center justify-center gap-2">
                      <Loader2 className="w-6 h-6 animate-spin text-[#0E385E]" />
                      <span className="text-xs">Memuat data pencari kerja...</span>
                    </div>
                  </td>
                </tr>
              ) : jobSeekers.length === 0 ? (
                <tr>
                  <td colSpan={7} className="py-12 text-center text-slate-400">
                    <p className="text-sm font-medium text-slate-500">
                      Belum ada profil pencari kerja yang cocok.
                    </p>
                    <p className="text-xs text-slate-400 mt-1">
                      Klik tombol &quot;Tambah Profil / Kandidat Baru&quot; untuk mendaftarkan data.
                    </p>
                  </td>
                </tr>
              ) : (
                jobSeekers.map((item) => (
                  <tr key={item.id} className="hover:bg-slate-50 transition-colors">
                    <td className="py-3.5 px-4 font-mono text-xs text-blue-700 font-medium">
                      {item.nik}
                    </td>
                    <td className="py-3.5 px-4 font-medium text-slate-900">
                      <div>{item.full_name}</div>
                      <div className="text-[11px] text-slate-400">
                        {item.gender === 'L' ? 'Laki-laki' : 'Perempuan'} • {item.phone}
                      </div>
                    </td>
                    <td className="py-3.5 px-4 text-slate-600">
                      <div className="font-medium text-slate-800">
                        {item.regency?.name || item.regency_id}
                      </div>
                      <div className="text-[11px] text-slate-400">
                        {item.regency?.province?.name || ''}
                      </div>
                    </td>
                    <td className="py-3.5 px-4 text-slate-600">
                      <div className="font-medium text-slate-800">
                        {item.education_level?.name || '-'}
                      </div>
                      <div className="text-[11px] text-slate-500">
                        {item.study_field_group}
                        {item.study_field_detail ? ` (${item.study_field_detail})` : ''}
                      </div>
                    </td>
                    <td className="py-3.5 px-4 text-slate-600">
                      <div className="font-medium text-slate-800">
                        {item.desired_occupation || '-'}
                      </div>
                      <div className="text-[11px] text-slate-400">
                        Pengalaman:{' '}
                        {item.experience_range === 'fresh_graduate'
                          ? 'Fresh Graduate'
                          : `${item.experience_range} Thn`}
                      </div>
                    </td>
                    <td className="py-3.5 px-4 text-center">
                      <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-sky-50 text-[#0E385E] border border-sky-200">
                        <CheckCircle2 className="w-3 h-3 text-emerald-600" />
                        <span>{item.skills_count ?? 0} Skill</span>
                      </span>
                    </td>
                    <td className="py-3.5 px-4 text-right">
                      <button
                        type="button"
                        onClick={() => setDetailSeekerId(item.id)}
                        className="px-3 py-1 rounded bg-[#0E385E] text-white text-xs font-medium hover:bg-[#163A5F] transition-colors shadow-2xs"
                      >
                        Detail
                      </button>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>

        {/* Pagination Footer */}
        {meta.total > 0 && (
          <div className="px-4 py-3 bg-[#F8FAFC] border-t border-[#DEE2E6] flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-600">
            <div>
              Menampilkan {jobSeekers.length} dari {meta.total} total pencari kerja
            </div>
            <div className="flex items-center gap-2">
              <button
                type="button"
                disabled={meta.current_page <= 1 || isLoading}
                onClick={() => loadJobSeekers(meta.current_page - 1)}
                className="px-2.5 py-1 rounded border border-slate-300 bg-white hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Sebelumnya
              </button>
              <span className="px-2 font-medium">
                Halaman {meta.current_page} dari {meta.last_page}
              </span>
              <button
                type="button"
                disabled={meta.current_page >= meta.last_page || isLoading}
                onClick={() => loadJobSeekers(meta.current_page + 1)}
                className="px-2.5 py-1 rounded border border-slate-300 bg-white hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Selanjutnya
              </button>
            </div>
          </div>
        )}
      </div>

      {/* Form Modal */}
      <JobSeekerFormModal
        isOpen={isFormOpen}
        onClose={() => setIsFormOpen(false)}
        onSuccess={() => {
          loadJobSeekers(1);
        }}
      />

      {/* Detail Modal */}
      <JobSeekerDetailModal
        jobSeekerId={detailSeekerId}
        onClose={() => setDetailSeekerId(null)}
      />
    </div>
  </AuthGuard>
);
}
