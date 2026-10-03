import React from 'react';
import { Briefcase, Building2, MapPin, Search, Calendar, ChevronRight } from 'lucide-react';
import AuthGuard from '@/components/auth/AuthGuard';

export default function LowonganPage() {
  const jobs = [
    {
      id: 'VAC-091',
      title: 'Senior Frontend Engineer (Next.js / TypeScript)',
      company: 'PT Telkom Digital Solusi',
      location: 'Jakarta Pusat (Hybrid)',
      type: 'Penuh Waktu',
      salary: 'Rp 14.000.000 - 20.000.000',
      posted: '2 hari lalu',
      matchedSkills: ['React 19', 'Next.js', 'Tailwind CSS', 'TypeScript'],
    },
    {
      id: 'VAC-092',
      title: 'Pranata Komputer / Analis Data Statistik',
      company: 'Badan Pusat Statistik (Mitra Proyek)',
      location: 'Jakarta Pusat (On-site)',
      type: 'Kontrak Pemerintah',
      salary: 'Standar Standar Biaya Masukan',
      posted: '3 hari lalu',
      matchedSkills: ['Python', 'SQL', 'Data Taxonomy', 'Power BI'],
    },
    {
      id: 'VAC-093',
      title: 'Quality Assurance & Automated Testing Specialist',
      company: 'PT Bank Mandiri (Persero) Tbk',
      location: 'Jakarta Selatan',
      type: 'Penuh Waktu',
      salary: 'Kompetitif',
      posted: '5 hari lalu',
      matchedSkills: ['Playwright', 'Jest', 'API Testing', 'CI/CD'],
    },
  ];

  return (
    <AuthGuard requiredPermission="lowongan.view">
      <div className="p-4 sm:p-6 lg:p-8 space-y-6 max-w-7xl mx-auto">
        <div>
          <h2 className="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
            Daftar Lowongan Pekerjaan
          </h2>
          <p className="text-xs sm:text-sm text-slate-500 mt-1">
            Lowongan pekerjaan yang telah dipetakan langsung dengan Skill Taxonomy Nasional.
          </p>
        </div>

        <div className="bg-white p-4 rounded-lg border border-slate-200 shadow-xs flex flex-col sm:flex-row gap-3">
          <div className="relative flex-1">
            <Search className="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
            <input
              type="text"
              placeholder="Cari posisi pekerjaan atau instansi/perusahaan..."
              className="w-full pl-9 pr-3 py-2 text-xs sm:text-sm rounded-md border border-slate-300 focus:outline-hidden focus:border-[#2563EB]"
            />
          </div>
          <select className="text-xs sm:text-sm py-2 px-3 rounded-md border border-slate-300 bg-white text-slate-700 focus:outline-hidden">
            <option>Semua Wilayah</option>
            <option>DKI Jakarta</option>
            <option>Jawa Barat</option>
            <option>Jawa Tengah</option>
          </select>
          <button className="px-5 py-2 rounded-md bg-[#0E385E] text-white text-xs sm:text-sm font-semibold hover:bg-[#163A5F]">
            Cari Lowongan
          </button>
        </div>

        <div className="space-y-3">
          {jobs.map((job) => (
            <div
              key={job.id}
              className="bg-white p-5 rounded-lg border border-slate-200 hover:border-blue-400 transition-colors shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4"
            >
              <div className="space-y-2">
                <div className="flex items-center gap-2">
                  <span className="text-xs font-mono font-bold text-blue-700 bg-blue-50 px-2 py-0.5 rounded">
                    {job.id}
                  </span>
                  <span className="text-xs px-2 py-0.5 rounded bg-slate-100 text-slate-600 font-medium">
                    {job.type}
                  </span>
                </div>
                <h3 className="text-base font-bold text-slate-900">{job.title}</h3>
                <div className="flex flex-wrap items-center gap-4 text-xs text-slate-600">
                  <span className="flex items-center gap-1 font-medium text-slate-800">
                    <Building2 className="w-3.5 h-3.5 text-slate-400" />
                    {job.company}
                  </span>
                  <span className="flex items-center gap-1">
                    <MapPin className="w-3.5 h-3.5 text-slate-400" />
                    {job.location}
                  </span>
                  <span className="flex items-center gap-1">
                    <Calendar className="w-3.5 h-3.5 text-slate-400" />
                    {job.posted}
                  </span>
                </div>
                <div className="flex flex-wrap items-center gap-1.5 pt-1">
                  <span className="text-[11px] text-slate-500 font-medium mr-1">Syarat Skill:</span>
                  {job.matchedSkills.map((sk) => (
                    <span
                      key={sk}
                      className="text-[10px] bg-slate-100 border border-slate-200 text-slate-700 px-2 py-0.5 rounded font-mono"
                    >
                      {sk}
                    </span>
                  ))}
                </div>
              </div>
              <div className="sm:text-right shrink-0">
                <div className="text-xs font-semibold text-emerald-700 mb-2">{job.salary}</div>
                <button
                  type="button"
                  className="inline-flex items-center gap-1 px-4 py-2 rounded-md bg-[#0E385E] text-white text-xs font-semibold hover:bg-[#163A5F] transition-colors"
                >
                  <span>Lihat Detail</span>
                  <ChevronRight className="w-3.5 h-3.5" />
                </button>
              </div>
            </div>
          ))}
        </div>
      </div>
    </AuthGuard>
  );
}
