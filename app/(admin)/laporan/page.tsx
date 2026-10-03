'use client';

import React, { useState } from 'react';
import AuthGuard from '@/components/auth/AuthGuard';
import { useAuth } from '@/hooks/useAuth';
import {
  FileText,
  Download,
  Calendar,
  Filter,
  TrendingUp,
  Users,
  Briefcase,
  Award,
  CheckCircle2,
  FileSpreadsheet,
} from 'lucide-react';

export default function LaporanPage() {
  const { hasPermission } = useAuth();
  const [downloading, setDownloading] = useState(false);

  const handleExport = (format: string) => {
    setDownloading(true);
    setTimeout(() => {
      setDownloading(false);
      alert(`Laporan format ${format.toUpperCase()} berhasil diunduh.`);
    }, 1000);
  };

  const summaries = [
    {
      label: 'Total Pencari Kerja Terdaftar',
      value: '2.840',
      change: '+14% bulan ini',
      icon: Users,
    },
    {
      label: 'Lowongan Kerja Terverifikasi',
      value: '420',
      change: '+8% bulan ini',
      icon: Briefcase,
    },
    {
      label: 'Pencari Kerja Siap Tempat',
      value: '1.250',
      change: '+22% efisiensi',
      icon: CheckCircle2,
    },
    {
      label: 'Peserta Pelatihan Vokasi',
      value: '380',
      change: '+5% peningkatan',
      icon: Award,
    },
  ];

  return (
    <AuthGuard requiredPermission="laporan.view">
      <div className="p-4 sm:p-6 lg:p-8 space-y-6 max-w-7xl mx-auto">
        {/* Header */}
        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <h1 className="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
              <FileText className="w-6 h-6 text-[#0E385E]" />
              <span>Laporan & Statistik Ketenagakerjaan</span>
            </h1>
            <p className="text-xs sm:text-sm text-slate-500 mt-1">
              Rekapitulasi data penempatan tenaga kerja, pemetaan kompetensi, dan analisis pasar kerja.
            </p>
          </div>

          {/* Tombol Export (Hanya untuk yang memiliki izin laporan.export: Pimpinan & Superadmin) */}
          {hasPermission('laporan.export') && (
            <div className="flex items-center gap-2">
              <button
                type="button"
                onClick={() => handleExport('xlsx')}
                disabled={downloading}
                className="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-xs sm:text-sm font-semibold text-slate-700 shadow-2xs transition-colors cursor-pointer"
              >
                <FileSpreadsheet className="w-4 h-4 text-emerald-600" />
                <span>Export Excel</span>
              </button>
              <button
                type="button"
                onClick={() => handleExport('pdf')}
                disabled={downloading}
                className="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-[#0E385E] hover:bg-[#163A5F] text-xs sm:text-sm font-semibold text-white shadow-xs transition-colors cursor-pointer"
              >
                <Download className="w-4 h-4" />
                <span>Unduh Laporan PDF</span>
              </button>
            </div>
          )}
        </div>

        {/* KPI Summaries */}
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          {summaries.map((item, idx) => {
            const Icon = item.icon;
            return (
              <div
                key={idx}
                className="bg-white p-5 rounded-xl border border-slate-200/80 shadow-2xs space-y-3"
              >
                <div className="flex items-center justify-between">
                  <span className="text-xs font-semibold text-slate-500">{item.label}</span>
                  <div className="p-2 rounded-lg bg-blue-50 text-[#0E385E]">
                    <Icon className="w-4 h-4" />
                  </div>
                </div>
                <div>
                  <div className="text-2xl font-bold text-slate-900">{item.value}</div>
                  <div className="text-xs font-semibold text-emerald-600 mt-1 flex items-center gap-1">
                    <TrendingUp className="w-3.5 h-3.5" />
                    <span>{item.change}</span>
                  </div>
                </div>
              </div>
            );
          })}
        </div>

        {/* Tabel Ringkasan Eksekutif */}
        <div className="bg-white rounded-xl border border-slate-200/80 shadow-2xs overflow-hidden">
          <div className="p-5 border-b border-slate-100 flex items-center justify-between">
            <h2 className="text-sm font-bold text-slate-900">
              Statistik Penempatan Kerja Berdasarkan Kategori Bidang (KBJI)
            </h2>
            <span className="text-xs text-slate-500">Periode Berjalan 2026</span>
          </div>
          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs sm:text-sm">
              <thead className="bg-slate-50 text-slate-500 uppercase tracking-wider text-[11px] font-bold">
                <tr>
                  <th className="py-3 px-5">Kode & Nama Bidang Okupasi</th>
                  <th className="py-3 px-4 text-center">Permintaan Lowongan</th>
                  <th className="py-3 px-4 text-center">Pencari Kerja Terdaftar</th>
                  <th className="py-3 px-4 text-center">Tingkat Kesesuaian</th>
                  <th className="py-3 px-4 text-right">Status Penyerapan</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100 text-slate-700">
                <tr className="hover:bg-slate-50/60">
                  <td className="py-3 px-5 font-semibold text-slate-900">
                    2512 - Pengembang Perangkat Lunak (Software Developer)
                  </td>
                  <td className="py-3 px-4 text-center font-medium">185</td>
                  <td className="py-3 px-4 text-center font-medium">320</td>
                  <td className="py-3 px-4 text-center font-semibold text-emerald-600">86.4%</td>
                  <td className="py-3 px-4 text-right">
                    <span className="px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                      Tinggi
                    </span>
                  </td>
                </tr>
                <tr className="hover:bg-slate-50/60">
                  <td className="py-3 px-5 font-semibold text-slate-900">
                    2120 - Matematikawan, Aktuaris, dan Statistikawan
                  </td>
                  <td className="py-3 px-4 text-center font-medium">94</td>
                  <td className="py-3 px-4 text-center font-medium">142</td>
                  <td className="py-3 px-4 text-center font-semibold text-emerald-600">79.2%</td>
                  <td className="py-3 px-4 text-right">
                    <span className="px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                      Optimal
                    </span>
                  </td>
                </tr>
                <tr className="hover:bg-slate-50/60">
                  <td className="py-3 px-5 font-semibold text-slate-900">
                    3322 - Agen Komersial & Tenaga Penjual Teknis
                  </td>
                  <td className="py-3 px-4 text-center font-medium">120</td>
                  <td className="py-3 px-4 text-center font-medium">85</td>
                  <td className="py-3 px-4 text-center font-semibold text-amber-600">62.8%</td>
                  <td className="py-3 px-4 text-right">
                    <span className="px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                      Defisit Skill
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </AuthGuard>
  );
}
