'use client';

import React, { useEffect, useState } from 'react';
import {
  X,
  User,
  MapPin,
  Briefcase,
  GraduationCap,
  Calendar,
  Phone,
  BookOpen,
  Award,
  Loader2,
  CheckCircle2,
  Target,
} from 'lucide-react';
import Link from 'next/link';
import { JobSeeker } from '@/types/job-seeker';
import { getJobSeekerDetail } from '@/services/job-seeker';

interface JobSeekerDetailModalProps {
  jobSeekerId: number | null;
  onClose: () => void;
}

export default function JobSeekerDetailModal({
  jobSeekerId,
  onClose,
}: JobSeekerDetailModalProps) {
  const [data, setData] = useState<JobSeeker | null>(null);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!jobSeekerId) return;

    let isMounted = true;
    setIsLoading(true);
    setError(null);

    getJobSeekerDetail(jobSeekerId)
      .then((res) => {
        if (isMounted) setData(res);
      })
      .catch((err) => {
        if (isMounted) setError(err.message || 'Gagal memuat detail pencari kerja');
      })
      .finally(() => {
        if (isMounted) setIsLoading(false);
      });

    return () => {
      isMounted = false;
    };
  }, [jobSeekerId]);

  if (!jobSeekerId) return null;

  return (
    <div className="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
      <div className="bg-white rounded-xl shadow-2xl border border-slate-200 w-full max-w-3xl max-h-[90vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-200">
        {/* Header */}
        <div className="px-6 py-4 bg-gradient-to-r from-[#0E385E] to-[#1E5282] text-white flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center">
              <User className="w-5 h-5 text-sky-200" />
            </div>
            <div>
              <h3 className="text-base sm:text-lg font-bold">
                {data ? data.name || data.full_name || 'Tanpa Nama' : 'Memuat Profil...'}
              </h3>
              <p className="text-xs text-sky-200 font-mono">
                {data ? `ID: ${data.profile_id || data.nik || data.id}` : ''}
              </p>
            </div>
          </div>
          <button
            type="button"
            onClick={onClose}
            className="p-1.5 rounded-lg text-white/80 hover:text-white hover:bg-white/10 transition-colors"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Content */}
        <div className="flex-1 overflow-y-auto p-6 space-y-6">
          {isLoading ? (
            <div className="py-16 flex flex-col items-center justify-center text-slate-500 gap-3">
              <Loader2 className="w-7 h-7 animate-spin text-[#0E385E]" />
              <p className="text-sm">Memuat data profil pencari kerja...</p>
            </div>
          ) : error ? (
            <div className="p-4 rounded-lg bg-red-50 text-red-700 text-sm">
              {error}
            </div>
          ) : data ? (
            <>
              {/* Summary Cards */}
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div className="p-3.5 rounded-lg bg-slate-50 border border-slate-200 space-y-2 text-xs sm:text-sm">
                  <div className="flex items-center gap-2 text-slate-500 font-semibold text-xs">
                    <User className="w-3.5 h-3.5 text-[#0E385E]" />
                    <span>Informasi Pribadi</span>
                  </div>
                  <div className="grid grid-cols-2 gap-1 text-slate-700">
                    <span className="text-slate-500">Jenis Kelamin:</span>
                    <span className="font-medium">
                      {data.jenis_kelamin || '-'}
                    </span>
                    <span className="text-slate-500">Umur:</span>
                    <span className="font-medium">{data.umur ? `${data.umur} Tahun` : '-'}</span>
                    <span className="text-slate-500">Status Menikah:</span>
                    <span className="font-medium">{data.marital || '-'}</span>
                    <span className="text-slate-500">Kondisi Fisik:</span>
                    <span className="font-medium">
                      {data.kondisi_fisik || '-'} 
                      {data.jenis_disabilitas && data.jenis_disabilitas !== 'Tidak Disabilitas' ? ` (${data.jenis_disabilitas})` : ''}
                    </span>
                  </div>
                </div>

                <div className="p-3.5 rounded-lg bg-slate-50 border border-slate-200 space-y-2 text-xs sm:text-sm">
                  <div className="flex items-center gap-2 text-slate-500 font-semibold text-xs">
                    <MapPin className="w-3.5 h-3.5 text-[#0E385E]" />
                    <span>Domisili Wilayah</span>
                  </div>
                  <div className="space-y-1 text-slate-700">
                    <div className="font-semibold text-slate-900">
                      {data.regency?.name || data.kab_kota || '-'}
                    </div>
                    <div className="text-xs text-slate-500">
                      Provinsi: {data.regency?.province?.name || data.provinsi || '-'}
                    </div>
                    <div className="text-[11px] font-mono text-slate-400 mt-2">
                      Target Luar Negeri: {data.rencana_kerja_luar_negeri === 'Ya' ? (data.country_wish || 'Ya') : 'Tidak'}
                    </div>
                  </div>
                </div>
              </div>

              {/* Pendidikan & Karir */}
              <div className="p-4 rounded-lg bg-white border border-slate-200 space-y-3">
                <div className="flex items-center gap-2 text-slate-800 font-semibold text-xs sm:text-sm pb-2 border-b border-slate-100">
                  <GraduationCap className="w-4 h-4 text-[#0E385E]" />
                  <span>Pendidikan & Pekerjaan</span>
                </div>
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs sm:text-sm">
                  <div>
                    <span className="text-slate-500 block text-xs">Jenjang Pendidikan:</span>
                    <span className="font-semibold text-slate-800">
                      {data.education_level?.name || data.pendidikan || '-'}
                    </span>
                  </div>
                  <div>
                    <span className="text-slate-500 block text-xs">Jurusan / Keahlian:</span>
                    <span className="font-semibold text-slate-800">
                      {data.jurusan || '-'}
                    </span>
                  </div>
                  <div className="sm:col-span-2">
                    <span className="text-slate-500 block text-xs">Asal Sekolah / Institusi:</span>
                    <span className="font-medium text-slate-700">
                      {data.nama_sekolah || '-'}
                    </span>
                  </div>
                  <div>
                    <span className="text-slate-500 block text-xs">Status Pekerjaan:</span>
                    <span className="font-medium text-slate-700">
                      {data.status_bekerja || '-'} {data.status_sekarang ? `(${data.status_sekarang})` : ''}
                    </span>
                  </div>
                  <div>
                    <span className="text-slate-500 block text-xs">Pengalaman Kerja:</span>
                    <span className="font-medium text-slate-700">
                      {data.experience || '-'}
                    </span>
                  </div>
                </div>
              </div>

              {/* Skills ESCO */}
              <div className="p-4 rounded-lg bg-white border border-slate-200 space-y-3">
                <div className="flex items-center justify-between pb-2 border-b border-slate-100">
                  <div className="flex items-center gap-2 text-slate-800 font-semibold text-xs sm:text-sm">
                    <Briefcase className="w-4 h-4 text-[#0E385E]" />
                    <span>Keahlian & Kompetensi ESCO</span>
                  </div>
                  <span className="text-xs text-slate-500">
                    {data.skills?.length || 0} Keahlian Terverifikasi
                  </span>
                </div>
                <div className="flex flex-wrap gap-2">
                  {data.skills && data.skills.length > 0 ? (
                    data.skills.map((skill: any) => {
                      const src = skill.source || skill.pivot?.source;
                      return (
                        <span
                          key={skill.id}
                          className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-sky-50 text-[#0E385E] border border-sky-200 shadow-2xs"
                        >
                          <CheckCircle2 className="w-3.5 h-3.5 text-emerald-600" />
                          <span>{skill.title}</span>
                          {src && (
                            <span className="text-[10px] px-1.5 py-0.2 rounded bg-white text-slate-600 border border-slate-200 font-normal">
                              {src}
                            </span>
                          )}
                        </span>
                      );
                    })
                  ) : (
                    <span className="text-xs text-slate-400 italic">
                      Tidak ada skill terverifikasi yang terdaftar.
                    </span>
                  )}
                </div>
              </div>

              {/* Trainings & Certifications (Raw Data) */}
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div className="p-3.5 rounded-lg bg-slate-50 border border-slate-200 space-y-2">
                  <div className="flex items-center gap-2 text-slate-700 font-semibold text-xs">
                    <BookOpen className="w-3.5 h-3.5 text-[#0E385E]" />
                    <span>Riwayat Keahlian & Pelatihan</span>
                  </div>
                  <div className="space-y-3 text-xs text-slate-700">
                    <div>
                      <span className="block text-slate-500 mb-0.5">Keahlian (Skill Set):</span>
                      <div className="font-medium">{data.keahlian || '-'}</div>
                    </div>
                    <div>
                      <span className="block text-slate-500 mb-0.5">Lembaga & Program Pelatihan:</span>
                      <div className="font-medium">
                        {data.lembaga_pelatihan || data.progpel ? (
                          <>
                            {data.lembaga_pelatihan} {data.progpel ? `— ${data.progpel}` : ''}
                          </>
                        ) : '-'}
                      </div>
                    </div>
                  </div>
                </div>

                <div className="p-3.5 rounded-lg bg-slate-50 border border-slate-200 space-y-2">
                  <div className="flex items-center gap-2 text-slate-700 font-semibold text-xs">
                    <Award className="w-3.5 h-3.5 text-[#0E385E]" />
                    <span>Sertifikasi & Riwayat Lain</span>
                  </div>
                  <div className="space-y-3 text-xs text-slate-700">
                    <div>
                      <span className="block text-slate-500 mb-0.5">Sertifikasi Dimiliki:</span>
                      <div className="font-medium">{data.sertifikasi || '-'}</div>
                    </div>
                    <div>
                      <span className="block text-slate-500 mb-0.5">Penguasaan Bahasa:</span>
                      <div className="font-medium">{data.bahasa || '-'}</div>
                    </div>
                  </div>
                </div>
              </div>
            </>
          ) : null}
        </div>

        {/* Footer */}
        <div className="px-6 py-3 bg-slate-50 border-t border-slate-200 flex justify-end gap-2">
          <button
            type="button"
            onClick={onClose}
            className="px-4 py-2 rounded-md border border-slate-300 bg-white text-slate-700 text-xs sm:text-sm font-semibold hover:bg-slate-100"
          >
            Tutup
          </button>
          <Link
            href={`/rekomendasi?pencaker_id=${jobSeekerId}`}
            id="btn-match-lowongan"
            className="inline-flex items-center gap-2 px-4 py-2 rounded-md bg-[#0E385E] text-white text-xs sm:text-sm font-semibold hover:bg-[#163A5F]"
          >
            <Target className="w-4 h-4" />
            Match Lowongan
          </Link>
        </div>
      </div>
    </div>
  );
}
