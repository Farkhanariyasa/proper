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
} from 'lucide-react';
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
                {data ? data.full_name : 'Memuat Profil...'}
              </h3>
              <p className="text-xs text-sky-200 font-mono">
                {data ? `NIK: ${data.nik}` : ''}
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
                      {data.gender === 'L' ? 'Laki-laki' : 'Perempuan'}
                    </span>
                    <span className="text-slate-500">Tanggal Lahir:</span>
                    <span className="font-medium">{String(data.birth_date).slice(0, 10)}</span>
                    <span className="text-slate-500">No. Kontak:</span>
                    <span className="font-medium font-mono">{data.phone}</span>
                  </div>
                </div>

                <div className="p-3.5 rounded-lg bg-slate-50 border border-slate-200 space-y-2 text-xs sm:text-sm">
                  <div className="flex items-center gap-2 text-slate-500 font-semibold text-xs">
                    <MapPin className="w-3.5 h-3.5 text-[#0E385E]" />
                    <span>Domisili Wilayah</span>
                  </div>
                  <div className="space-y-1 text-slate-700">
                    <div className="font-semibold text-slate-900">
                      {data.regency?.name || '-'}
                    </div>
                    <div className="text-xs text-slate-500">
                      Provinsi: {data.regency?.province?.name || '-'}
                    </div>
                    <div className="text-[11px] font-mono text-slate-400">
                      Kode Kemendagri: {data.regency_id}
                    </div>
                  </div>
                </div>
              </div>

              {/* Pendidikan & Karir */}
              <div className="p-4 rounded-lg bg-white border border-slate-200 space-y-3">
                <div className="flex items-center gap-2 text-slate-800 font-semibold text-xs sm:text-sm pb-2 border-b border-slate-100">
                  <GraduationCap className="w-4 h-4 text-[#0E385E]" />
                  <span>Pendidikan & Target Karir</span>
                </div>
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs sm:text-sm">
                  <div>
                    <span className="text-slate-500 block text-xs">Jenjang Pendidikan:</span>
                    <span className="font-semibold text-slate-800">
                      {data.education_level?.name || '-'}
                    </span>
                  </div>
                  <div>
                    <span className="text-slate-500 block text-xs">Rumpun Bidang:</span>
                    <span className="font-semibold text-slate-800">
                      {data.study_field_group}
                    </span>
                  </div>
                  {data.study_field_detail && (
                    <div>
                      <span className="text-slate-500 block text-xs">Program Studi:</span>
                      <span className="font-medium text-slate-700">
                        {data.study_field_detail}
                      </span>
                    </div>
                  )}
                  <div>
                    <span className="text-slate-500 block text-xs">Pengalaman Kerja:</span>
                    <span className="font-medium text-slate-700">
                      {data.experience_range === 'fresh_graduate'
                        ? 'Fresh Graduate'
                        : `${data.experience_range} Tahun`}
                    </span>
                  </div>
                  {data.desired_occupation && (
                    <div className="sm:col-span-2">
                      <span className="text-slate-500 block text-xs">Target Jabatan / Minat:</span>
                      <span className="font-semibold text-blue-700">
                        {data.desired_occupation}
                      </span>
                    </div>
                  )}
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
                    {data.skills?.length || 0} Keahlian
                  </span>
                </div>
                <div className="flex flex-wrap gap-2">
                  {data.skills && data.skills.length > 0 ? (
                    data.skills.map((skill) => (
                      <span
                        key={skill.id}
                        className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-sky-50 text-[#0E385E] border border-sky-200 shadow-2xs"
                      >
                        <CheckCircle2 className="w-3.5 h-3.5 text-emerald-600" />
                        <span>{skill.title}</span>
                      </span>
                    ))
                  ) : (
                    <span className="text-xs text-slate-400 italic">
                      Tidak ada skill yang terdaftar.
                    </span>
                  )}
                </div>
              </div>

              {/* Trainings & Certifications */}
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div className="p-3.5 rounded-lg bg-slate-50 border border-slate-200 space-y-2">
                  <div className="flex items-center gap-2 text-slate-700 font-semibold text-xs">
                    <BookOpen className="w-3.5 h-3.5 text-[#0E385E]" />
                    <span>Riwayat Pelatihan</span>
                  </div>
                  {data.trainings && data.trainings.length > 0 ? (
                    <ul className="space-y-1.5 text-xs">
                      {data.trainings.map((t, idx) => (
                        <li key={idx} className="p-2 rounded bg-white border border-slate-200">
                          <div className="font-semibold text-slate-800">{t.name}</div>
                          <div className="text-[11px] text-slate-500">
                            {t.organizer} {t.year ? `(${t.year})` : ''}
                          </div>
                        </li>
                      ))}
                    </ul>
                  ) : (
                    <p className="text-xs text-slate-400 italic">Belum ada riwayat pelatihan.</p>
                  )}
                </div>

                <div className="p-3.5 rounded-lg bg-slate-50 border border-slate-200 space-y-2">
                  <div className="flex items-center gap-2 text-slate-700 font-semibold text-xs">
                    <Award className="w-3.5 h-3.5 text-[#0E385E]" />
                    <span>Sertifikasi Kompetensi</span>
                  </div>
                  {data.certifications && data.certifications.length > 0 ? (
                    <ul className="space-y-1.5 text-xs">
                      {data.certifications.map((c, idx) => (
                        <li key={idx} className="p-2 rounded bg-white border border-slate-200">
                          <div className="font-semibold text-slate-800">{c.name}</div>
                          <div className="text-[11px] text-slate-500">
                            {c.type} {c.year ? `(${c.year})` : ''}
                          </div>
                        </li>
                      ))}
                    </ul>
                  ) : (
                    <p className="text-xs text-slate-400 italic">Belum ada sertifikasi.</p>
                  )}
                </div>
              </div>
            </>
          ) : null}
        </div>

        {/* Footer */}
        <div className="px-6 py-3 bg-slate-50 border-t border-slate-200 flex justify-end">
          <button
            type="button"
            onClick={onClose}
            className="px-4 py-2 rounded-md bg-[#0E385E] text-white text-xs sm:text-sm font-semibold hover:bg-[#163A5F]"
          >
            Tutup
          </button>
        </div>
      </div>
    </div>
  );
}
