import React from 'react';
import { BarChart3, TrendingUp, Users, Building2, CheckCircle2, ArrowUpRight } from 'lucide-react';
import AuthGuard from '@/components/auth/AuthGuard';

export default function DashboardPimpinanPage() {
  const stats = [
    {
      label: 'Total Pencari Kerja Aktif',
      value: '24.850',
      change: '+12.4% bln ini',
      icon: Users,
    },
    {
      label: 'Perusahaan Mitra Terdaftar',
      value: '1.420',
      change: '+8.1% bln ini',
      icon: Building2,
    },
    {
      label: 'Tingkat Keberhasilan Matching',
      value: '84.6%',
      change: '+3.2% efisiensi',
      icon: CheckCircle2,
    },
    {
      label: 'Total Penempatan Kerja',
      value: '6.120',
      change: '+15.8% target tahunan',
      icon: TrendingUp,
    },
  ];

  return (
    <AuthGuard requiredPermission="dashboard.pimpinan.view">
      <div className="p-4 sm:p-6 lg:p-8 space-y-6 max-w-7xl mx-auto">
      {/* Header */}
      <div>
        <h2 className="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
          Dashboard Eksekutif Pimpinan
        </h2>
        <p className="text-xs sm:text-sm text-slate-500 mt-1">
          Ringkasan makro dinamika pasar kerja, efektivitas penempatan, dan serapan tenaga kerja nasional.
        </p>
      </div>

      {/* KPI Cards (design.md Card/Elevation patterns) */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {stats.map((s, idx) => {
          const Icon = s.icon;
          return (
            <div
              key={idx}
              className="bg-white p-5 rounded-lg border border-slate-200 shadow-xs hover:border-blue-400 transition-colors"
            >
              <div className="flex items-center justify-between text-slate-500 mb-3">
                <span className="text-xs font-semibold">{s.label}</span>
                <div className="p-2 rounded-md bg-blue-50 text-[#0E385E]">
                  <Icon className="w-4 h-4" />
                </div>
              </div>
              <div className="text-2xl font-bold text-slate-900">{s.value}</div>
              <div className="mt-2 text-[11px] font-semibold text-emerald-600 flex items-center gap-1">
                <span>{s.change}</span>
              </div>
            </div>
          );
        })}
      </div>

      {/* Overview Panels */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div className="bg-white p-6 rounded-lg border border-slate-200 shadow-xs space-y-4">
          <div className="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 className="text-sm font-bold text-slate-900">
              Top 5 Kebutuhan Skill Tertinggi (Pasar Kerja)
            </h3>
            <span className="text-xs text-blue-700 font-semibold cursor-pointer flex items-center gap-1">
              Lihat Analisis <ArrowUpRight className="w-3.5 h-3.5" />
            </span>
          </div>
          <div className="space-y-3 text-xs">
            {[
              { skill: 'Data Analytics & SQL', share: 94 },
              { skill: 'Cloud & Infrastructure (AWS/GCP)', share: 88 },
              { skill: 'Fullstack Web (Next.js/React)', share: 82 },
              { skill: 'Supply Chain & ERP Operations', share: 76 },
              { skill: 'Industrial Automation (PLC)', share: 69 },
            ].map((item, i) => (
              <div key={i} className="space-y-1">
                <div className="flex justify-between font-medium text-slate-700">
                  <span>{item.skill}</span>
                  <span className="font-bold text-[#0E385E]">{item.share}% Indeks Kebutuhan</span>
                </div>
                <div className="h-2 w-full bg-slate-100 rounded-full overflow-hidden">
                  <div
                    className="h-full bg-[#1F5A88] rounded-full"
                    style={{ width: `${item.share}%` }}
                  />
                </div>
              </div>
            ))}
          </div>
        </div>

        <div className="bg-white p-6 rounded-lg border border-slate-200 shadow-xs space-y-4">
          <div className="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 className="text-sm font-bold text-slate-900">
              Distribusi Serapan Penempatan per Sektor
            </h3>
            <span className="text-xs text-slate-500">Q3 2026</span>
          </div>
          <div className="space-y-3 text-xs">
            {[
              { sector: 'Teknologi Informasi & Digital', count: '2.140 Tenaga Kerja' },
              { sector: 'Manufaktur & Otomotif', count: '1.680 Tenaga Kerja' },
              { sector: 'Keuangan & Jasa Perbankan', count: '1.250 Tenaga Kerja' },
              { sector: 'Logistik & Distribusi', count: '1.050 Tenaga Kerja' },
            ].map((sec, i) => (
              <div
                key={i}
                className="flex items-center justify-between p-3 rounded-md bg-slate-50 border border-slate-100"
              >
                <span className="font-medium text-slate-800">{sec.sector}</span>
                <span className="font-bold text-emerald-700">{sec.count}</span>
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  </AuthGuard>
);
}
