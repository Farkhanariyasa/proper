import React from 'react';
import { Sparkles, Target, ArrowRight, CheckCircle2, AlertTriangle, Layers } from 'lucide-react';

export default function RekomendasiPage() {
  const matches = [
    {
      candidate: 'Budi Santoso (Data Analyst)',
      targetRole: 'Junior Business Intelligence Specialist',
      company: 'PT Data Nusantara',
      matchScore: 92,
      matchedList: ['SQL Querying', 'Data Visualization', 'Python Scripting', 'Dashboard Design'],
      gapList: ['Apache Airflow'],
      recommendation: 'Siap Penempatan Langsung (Rekomendasi Utama)',
    },
    {
      candidate: 'Siti Rahmawati (Frontend Dev)',
      targetRole: 'Next.js Frontend Developer',
      company: 'PT Global Solusindo',
      matchScore: 88,
      matchedList: ['React', 'TypeScript', 'Tailwind CSS', 'REST API'],
      gapList: ['Docker Containerization'],
      recommendation: 'Penempatan Bersyarat / Pelatihan Singkat',
    },
  ];

  return (
    <div className="p-4 sm:p-6 lg:p-8 space-y-6 max-w-7xl mx-auto">
      <div>
        <h2 className="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
          Rekomendasi & Semantic Matching
        </h2>
        <p className="text-xs sm:text-sm text-slate-500 mt-1">
          Kalkulasi skor kecocokan antara kompetensi kandidat pencari kerja dan kriteria lowongan perusahaan secara transparan.
        </p>
      </div>

      <div className="space-y-4">
        {matches.map((item, idx) => (
          <div
            key={idx}
            className="bg-white rounded-lg border border-slate-200 p-5 shadow-xs space-y-4"
          >
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
              <div>
                <span className="text-xs text-slate-500 font-medium">Kandidat:</span>
                <h3 className="text-base font-bold text-slate-900">{item.candidate}</h3>
                <p className="text-xs text-blue-700 font-medium">
                  Rekomendasi Lowongan: {item.targetRole} &bull; {item.company}
                </p>
              </div>
              <div className="flex items-center gap-3">
                <div className="text-right">
                  <div className="text-2xl font-extrabold text-[#0E385E]">
                    {item.matchScore}%
                  </div>
                  <div className="text-[10px] text-slate-500 font-medium uppercase tracking-wider">
                    Skor Kecocokan
                  </div>
                </div>
              </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
              <div className="bg-emerald-50/60 border border-emerald-100 rounded-lg p-3">
                <div className="flex items-center gap-1.5 font-bold text-emerald-800 mb-2">
                  <CheckCircle2 className="w-4 h-4 text-emerald-600" />
                  <span>Kompetensi Memenuhi ({item.matchedList.length})</span>
                </div>
                <div className="flex flex-wrap gap-1.5">
                  {item.matchedList.map((m) => (
                    <span
                      key={m}
                      className="bg-white border border-emerald-200 text-emerald-900 px-2 py-0.5 rounded text-[11px] font-medium"
                    >
                      {m}
                    </span>
                  ))}
                </div>
              </div>

              <div className="bg-amber-50/60 border border-amber-100 rounded-lg p-3">
                <div className="flex items-center gap-1.5 font-bold text-amber-800 mb-2">
                  <AlertTriangle className="w-4 h-4 text-amber-600" />
                  <span>Kesenjangan Skill / Gap ({item.gapList.length})</span>
                </div>
                <div className="flex flex-wrap gap-1.5">
                  {item.gapList.map((g) => (
                    <span
                      key={g}
                      className="bg-white border border-amber-200 text-amber-900 px-2 py-0.5 rounded text-[11px] font-medium"
                    >
                      {g}
                    </span>
                  ))}
                </div>
              </div>
            </div>

            <div className="flex items-center justify-between pt-2">
              <span className="text-xs font-semibold text-slate-700">
                Status: <span className="text-blue-700">{item.recommendation}</span>
              </span>
              <button
                type="button"
                className="px-4 py-2 rounded-md bg-[#0E385E] text-white text-xs font-semibold hover:bg-[#163A5F] transition-colors"
              >
                Proses Penempatan
              </button>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}
