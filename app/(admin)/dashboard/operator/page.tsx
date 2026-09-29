import React from 'react';
import { ShieldCheck, CheckCircle2, Clock, AlertCircle, FileSearch, ArrowRight } from 'lucide-react';

export default function DashboardOperatorPage() {
  const tasks = [
    {
      id: 'VER-4401',
      candidate: 'Dwi Prasetyo',
      type: 'Validasi Klaim Skill Baru (ESCO: Python/Data)',
      status: 'Menunggu Review',
      priority: 'Tinggi',
      time: '15 menit lalu',
    },
    {
      id: 'VER-4402',
      candidate: 'Anita Kusuma',
      type: 'Asesmen Kesenjangan Skill Lowongan QA Engineer',
      status: 'Proses Analisis',
      priority: 'Sedang',
      time: '45 menit lalu',
    },
    {
      id: 'VER-4403',
      candidate: 'Rian Firmansyah',
      type: 'Verifikasi Berkas Penempatan Kerja Final',
      status: 'Menunggu Verifikasi',
      priority: 'Tinggi',
      time: '2 jam lalu',
    },
  ];

  return (
    <div className="p-4 sm:p-6 lg:p-8 space-y-6 max-w-7xl mx-auto">
      {/* Header */}
      <div>
        <h2 className="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
          Dashboard Petugas Pengantar Kerja (Operator)
        </h2>
        <p className="text-xs sm:text-sm text-slate-500 mt-1">
          Antrean verifikasi profil skill, bimbingan penempatan, dan validasi berkas kandidat.
        </p>
      </div>

      {/* Operator Metrics */}
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div className="bg-white p-5 rounded-lg border border-slate-200 shadow-xs">
          <div className="flex items-center justify-between text-slate-500 mb-2">
            <span className="text-xs font-semibold">Antrean Verifikasi Hari Ini</span>
            <Clock className="w-4 h-4 text-amber-500" />
          </div>
          <div className="text-2xl font-bold text-slate-900">18 Berkas</div>
          <span className="text-[11px] text-amber-600 font-semibold">6 berkas prioritas tinggi</span>
        </div>

        <div className="bg-white p-5 rounded-lg border border-slate-200 shadow-xs">
          <div className="flex items-center justify-between text-slate-500 mb-2">
            <span className="text-xs font-semibold">Selesai Diverifikasi (Minggu Ini)</span>
            <CheckCircle2 className="w-4 h-4 text-emerald-600" />
          </div>
          <div className="text-2xl font-bold text-slate-900">64 Kandidat</div>
          <span className="text-[11px] text-emerald-600 font-semibold">98% tepat waktu (SLA)</span>
        </div>

        <div className="bg-white p-5 rounded-lg border border-slate-200 shadow-xs">
          <div className="flex items-center justify-between text-slate-500 mb-2">
            <span className="text-xs font-semibold">Penempatan Berhasil Bimbingan</span>
            <ShieldCheck className="w-4 h-4 text-blue-600" />
          </div>
          <div className="text-2xl font-bold text-slate-900">32 Orang</div>
          <span className="text-[11px] text-blue-600 font-semibold">Bulan September 2026</span>
        </div>
      </div>

      {/* Operational Task Table */}
      <div className="bg-white rounded-lg border border-slate-200 shadow-xs overflow-hidden">
        <div className="p-4 border-b border-slate-200 flex items-center justify-between">
          <h3 className="font-bold text-slate-900 text-sm">
            Daftar Antrean Tugas Verifikasi Operasional
          </h3>
          <span className="text-xs text-slate-500">Auto-refresh setiap 5 menit</span>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs sm:text-sm">
            <thead className="bg-[#F8FAFC] border-b border-[#DEE2E6] text-slate-700 font-semibold">
              <tr>
                <th className="py-3 px-4">No. Antrean</th>
                <th className="py-3 px-4">Nama Kandidat</th>
                <th className="py-3 px-4">Jenis Tugas / Verifikasi</th>
                <th className="py-3 px-4">Prioritas</th>
                <th className="py-3 px-4">Waktu Pengajuan</th>
                <th className="py-3 px-4 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100 text-slate-700">
              {tasks.map((task) => (
                <tr key={task.id} className="hover:bg-slate-50 transition-colors">
                  <td className="py-3.5 px-4 font-mono text-xs text-blue-700 font-bold">{task.id}</td>
                  <td className="py-3.5 px-4 font-medium text-slate-900">{task.candidate}</td>
                  <td className="py-3.5 px-4 text-slate-600">{task.type}</td>
                  <td className="py-3.5 px-4">
                    <span
                      className={`px-2 py-0.5 rounded text-[10px] font-bold ${
                        task.priority === 'Tinggi'
                          ? 'bg-red-50 text-red-700 border border-red-200'
                          : 'bg-blue-50 text-blue-700 border border-blue-200'
                      }`}
                    >
                      {task.priority}
                    </span>
                  </td>
                  <td className="py-3.5 px-4 text-slate-500">{task.time}</td>
                  <td className="py-3.5 px-4 text-right">
                    <button
                      type="button"
                      className="px-3 py-1 rounded bg-[#0E385E] text-white text-xs font-semibold hover:bg-[#163A5F] transition-colors"
                    >
                      Proses Verifikasi
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
