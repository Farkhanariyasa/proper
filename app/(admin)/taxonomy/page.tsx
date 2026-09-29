import React from 'react';
import { GitMerge, Search, Layers, BookOpen, ExternalLink } from 'lucide-react';

export default function TaxonomyPage() {
  const categories = [
    {
      code: 'ICT-01',
      title: 'Teknologi Informasi & Komunikasi',
      occupations: 142,
      skillsCount: 1850,
      standard: 'ESCO & SKKNI',
    },
    {
      code: 'MAN-02',
      title: 'Manufaktur & Rekayasa Industri',
      occupations: 98,
      skillsCount: 1240,
      standard: 'KBLI & KBJI',
    },
    {
      code: 'LOG-03',
      title: 'Logistik, Rantai Pasok & Distribusi',
      occupations: 64,
      skillsCount: 780,
      standard: 'SKKNI Terintegrasi',
    },
    {
      code: 'FIN-04',
      title: 'Keuangan, Akuntansi & Perbankan',
      occupations: 85,
      skillsCount: 950,
      standard: 'SKKNI & IFRS',
    },
  ];

  return (
    <div className="p-4 sm:p-6 lg:p-8 space-y-6 max-w-7xl mx-auto">
      {/* Header */}
      <div>
        <h2 className="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
          Taksonomi Okupasi & Skill Nasional
        </h2>
        <p className="text-xs sm:text-sm text-slate-500 mt-1">
          Kamus kompetensi terstandar yang menghubungkan KBJI, KBLI, SKKNI, dan standar internasional ESCO.
        </p>
      </div>

      {/* Search Bar */}
      <div className="bg-white p-4 rounded-lg border border-slate-200 shadow-xs flex flex-col sm:flex-row gap-3">
        <div className="relative flex-1">
          <Search className="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
          <input
            type="text"
            placeholder="Cari kode skill, nama okupasi, atau kompetensi spesifik (misal: Python, SEO, Audit)..."
            className="w-full pl-9 pr-3 py-2 text-xs sm:text-sm rounded-md border border-slate-300 focus:outline-hidden focus:border-[#2563EB]"
          />
        </div>
        <button className="px-5 py-2 rounded-md bg-[#0E385E] text-white text-xs sm:text-sm font-semibold hover:bg-[#163A5F]">
          Jelajahi Taksonomi
        </button>
      </div>

      {/* Category Cards Grid */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {categories.map((cat) => (
          <div
            key={cat.code}
            className="bg-white p-5 rounded-lg border border-slate-200 hover:border-blue-400 transition-all shadow-xs hover:shadow-sm"
          >
            <div className="flex items-center justify-between text-xs text-blue-600 font-mono font-bold mb-2">
              <span>{cat.code}</span>
              <span className="text-[10px] bg-blue-50 px-2 py-0.5 rounded text-blue-700 font-sans">
                {cat.standard}
              </span>
            </div>
            <h3 className="font-bold text-slate-900 text-sm mb-3">{cat.title}</h3>
            <div className="space-y-1.5 text-xs text-slate-600 border-t border-slate-100 pt-3">
              <div className="flex justify-between">
                <span>Total Okupasi:</span>
                <span className="font-semibold text-slate-900">{cat.occupations}</span>
              </div>
              <div className="flex justify-between">
                <span>Unit Kompetensi:</span>
                <span className="font-semibold text-slate-900">{cat.skillsCount}</span>
              </div>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}
