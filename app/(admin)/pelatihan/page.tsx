import React from 'react';
import { GraduationCap, Clock, Award, BookOpen, Search, CheckCircle } from 'lucide-react';
import AuthGuard from '@/components/auth/AuthGuard';

export default function PelatihanPage() {
  const courses = [
    {
      id: 'TRN-101',
      title: 'Pelatihan Kompetensi Analisis Data & Visualisasi (Python & PowerBI)',
      provider: 'Balai Besar Pelatihan Vokasi dan Produktivitas (BBPVP)',
      duration: '80 Jam Pelajaran (JP)',
      level: 'Menengah',
      certification: 'Sertifikasi BNSP / SKKNI',
      slots: 'Tersedia 18 Kursi',
    },
    {
      id: 'TRN-102',
      title: 'Web Application Modern Engineering with Next.js & React 19',
      provider: 'Pusdiklat Ketenagakerjaan Kemnaker RI',
      duration: '60 Jam Pelajaran (JP)',
      level: 'Lanjutan',
      certification: 'Sertifikat Kompetensi Industri',
      slots: 'Tersedia 10 Kursi',
    },
    {
      id: 'TRN-103',
      title: 'Dasar Standar Pengantar Kerja & Penempatan Tenaga Kerja',
      provider: 'Direktorat Bina Pengantar Kerja',
      duration: '40 Jam Pelajaran (JP)',
      level: 'Dasar',
      certification: 'SK Penetapan Fungsional',
      slots: 'Tersedia 25 Kursi',
    },
  ];

  return (
    <AuthGuard requiredPermission="pelatihan.view">
      <div className="p-4 sm:p-6 lg:p-8 space-y-6 max-w-7xl mx-auto">
        <div>
          <h2 className="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
            Rekomendasi Pelatihan & Vokasi
          </h2>
          <p className="text-xs sm:text-sm text-slate-500 mt-1">
            Program pelatihan berbasis gap kompetensi untuk memenuhi kualifikasi lowongan kerja impian.
          </p>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          {courses.map((c) => (
            <div
              key={c.id}
              className="bg-white rounded-lg border border-slate-200 p-5 flex flex-col justify-between hover:shadow-sm transition-shadow"
            >
              <div className="space-y-3">
                <div className="flex items-center justify-between text-xs">
                  <span className="font-mono text-blue-700 font-semibold">{c.id}</span>
                  <span className="bg-emerald-50 text-emerald-700 border border-emerald-200 px-2 py-0.5 rounded text-[11px] font-semibold">
                    {c.slots}
                  </span>
                </div>
                <h3 className="font-bold text-slate-900 text-sm leading-snug">{c.title}</h3>
                <p className="text-xs text-slate-500 font-medium">{c.provider}</p>

                <div className="space-y-1.5 text-xs text-slate-600 border-t border-slate-100 pt-3">
                  <div className="flex items-center gap-2">
                    <Clock className="w-3.5 h-3.5 text-slate-400" />
                    <span>Durasi: {c.duration}</span>
                  </div>
                  <div className="flex items-center gap-2">
                    <Award className="w-3.5 h-3.5 text-slate-400" />
                    <span>{c.certification}</span>
                  </div>
                </div>
              </div>

              <div className="pt-4 mt-3 border-t border-slate-100">
                <button
                  type="button"
                  className="w-full py-2 rounded-md bg-[#0E385E] text-white text-xs font-semibold hover:bg-[#163A5F] transition-colors"
                >
                  Daftar Pelatihan
                </button>
              </div>
            </div>
          ))}
        </div>
      </div>
    </AuthGuard>
  );
}
