import React from 'react';
import { UserCheck, Plus, Search, FileText, CheckCircle2, AlertCircle } from 'lucide-react';

export default function ProfilSkillPage() {
  const dummyProfiles = [
    {
      id: 'SKP-2026-001',
      name: 'Budi Santoso',
      jobTarget: 'Data Analyst / Statistician',
      matchedSkills: 12,
      gapCount: 2,
      status: 'Terverifikasi',
      updatedAt: '28 Sep 2026',
    },
    {
      id: 'SKP-2026-002',
      name: 'Siti Rahmawati',
      jobTarget: 'Fullstack Web Developer',
      matchedSkills: 15,
      gapCount: 1,
      status: 'Menunggu Verifikasi',
      updatedAt: '27 Sep 2026',
    },
    {
      id: 'SKP-2026-003',
      name: 'Ahmad Fauzi',
      jobTarget: 'Pengantar Kerja Ahli Pertama',
      matchedSkills: 9,
      gapCount: 3,
      status: 'Terverifikasi',
      updatedAt: '26 Sep 2026',
    },
  ];

  return (
    <div className="p-4 sm:p-6 lg:p-8 space-y-6 max-w-7xl mx-auto">
      {/* Page Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h2 className="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
            Profil Skill Pencari Kerja
          </h2>
          <p className="text-xs sm:text-sm text-slate-500 mt-1">
            Kelola data kompetensi, ekstraksi CV berbasis taksonomi, dan verifikasi profil kandidat.
          </p>
        </div>
        <button
          type="button"
          className="inline-flex items-center gap-2 px-4 py-2 rounded-md bg-[#0E385E] text-white text-xs sm:text-sm font-semibold hover:bg-[#163A5F] transition-colors shadow-xs"
        >
          <Plus className="w-4 h-4" />
          <span>Tambah Profil / Upload CV</span>
        </button>
      </div>

      {/* Filter Toolbar (design.md) */}
      <div className="bg-white p-4 rounded-lg border border-slate-200 shadow-xs flex flex-col sm:flex-row gap-3 items-center justify-between">
        <div className="relative w-full sm:w-80">
          <Search className="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
          <input
            type="text"
            placeholder="Cari nama atau NIK kandidat..."
            className="w-full pl-9 pr-3 py-2 text-xs sm:text-sm rounded-md border border-slate-300 focus:outline-hidden focus:border-[#2563EB] focus:ring-1 focus:ring-[#2563EB]"
          />
        </div>
        <div className="flex items-center gap-2 w-full sm:w-auto">
          <select className="text-xs sm:text-sm py-2 px-3 rounded-md border border-slate-300 bg-white text-slate-700 focus:outline-hidden">
            <option>Semua Status</option>
            <option>Terverifikasi</option>
            <option>Menunggu Verifikasi</option>
          </select>
          <button className="px-4 py-2 rounded-md bg-[#0E385E] text-white text-xs font-semibold hover:bg-[#163A5F]">
            Cari
          </button>
        </div>
      </div>

      {/* Table Data (design.md DataGrid) */}
      <div className="bg-white rounded-lg border border-slate-200 shadow-xs overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs sm:text-sm">
            <thead className="bg-[#F8FAFC] border-b border-[#DEE2E6] text-slate-700 font-semibold">
              <tr>
                <th className="py-3 px-4">No. Registrasi</th>
                <th className="py-3 px-4">Nama Pencari Kerja</th>
                <th className="py-3 px-4">Target Pekerjaan</th>
                <th className="py-3 px-4 text-center">Skill Cocok</th>
                <th className="py-3 px-4 text-center">Gap</th>
                <th className="py-3 px-4">Status</th>
                <th className="py-3 px-4 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100 text-slate-700">
              {dummyProfiles.map((item) => (
                <tr key={item.id} className="hover:bg-slate-50 transition-colors">
                  <td className="py-3.5 px-4 font-mono text-xs text-blue-700">{item.id}</td>
                  <td className="py-3.5 px-4 font-medium text-slate-900">{item.name}</td>
                  <td className="py-3.5 px-4 text-slate-600">{item.jobTarget}</td>
                  <td className="py-3.5 px-4 text-center font-semibold text-emerald-600">
                    {item.matchedSkills}
                  </td>
                  <td className="py-3.5 px-4 text-center font-semibold text-amber-600">
                    {item.gapCount}
                  </td>
                  <td className="py-3.5 px-4">
                    <span
                      className={`inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold ${
                        item.status === 'Terverifikasi'
                          ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                          : 'bg-amber-50 text-amber-700 border border-amber-200'
                      }`}
                    >
                      {item.status === 'Terverifikasi' ? (
                        <CheckCircle2 className="w-3 h-3" />
                      ) : (
                        <AlertCircle className="w-3 h-3" />
                      )}
                      {item.status}
                    </span>
                  </td>
                  <td className="py-3.5 px-4 text-right">
                    <button
                      type="button"
                      className="px-2.5 py-1 rounded bg-[#28A745] text-white text-xs font-medium hover:bg-[#1E8E3E] transition-colors"
                    >
                      Detail
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}
