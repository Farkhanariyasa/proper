import React from 'react';
import Link from 'next/link';
import {
  FileText,
  GitMerge,
  Target,
  Sparkles,
  CheckCircle2,
  UserCheck,
  Search,
  LayoutDashboard,
  Building2,
  LineChart,
  Network,
  ArrowRight,
  ShieldCheck,
} from 'lucide-react';

export default function Home() {
  const caraKerjaSteps = [
    {
      no: 1,
      title: 'Profil Skill',
      desc: 'Tambah skill manual atau upload CV (AI extraction)',
      icon: FileText,
    },
    {
      no: 2,
      title: 'Taxonomy Matching',
      desc: 'Skill dipetakan ke taksonomi nasional terintegrasi ESCO',
      icon: GitMerge,
    },
    {
      no: 3,
      title: 'Skill Gap Analysis',
      desc: 'Analisis kesenjangan skill vs target pekerjaan',
      icon: Target,
    },
    {
      no: 4,
      title: 'Rekomendasi',
      desc: 'Lowongan, pelatihan, dan career path personalisasi',
      icon: Sparkles,
    },
    {
      no: 5,
      title: 'Penempatan',
      desc: 'Proses penempatan dengan bimbingan petugas',
      icon: CheckCircle2,
    },
  ];

  const fiturPlatform = [
    {
      title: 'Profil Skill Pencari Kerja',
      desc: 'Bangun profil kompetensi terstruktur',
      icon: UserCheck,
    },
    {
      title: 'Rekomendasi Lowongan',
      desc: 'Skor kecocokan transparan berbasis semantik',
      icon: Search,
    },
    {
      title: 'Analisis Skill Gap',
      desc: 'Identifikasi kekurangan + rekomendasi pelatihan',
      icon: Target,
    },
    {
      title: 'Dashboard Petugas',
      desc: 'Pantau status kandidat & binaan',
      icon: LayoutDashboard,
    },
    {
      title: 'Pencarian Talenta',
      desc: 'Perusahaan cari kandidat presisi',
      icon: Building2,
    },
    {
      title: 'Dashboard Makro & PaskerID',
      desc: 'Analitik pasar kerja & tren industri',
      icon: LineChart,
    },
  ];

  const ekosistemData = [
    {
      name: 'SIAPkerja Kemnaker',
      desc: 'Integrasi akun & data tenaga kerja nasional',
    },
    {
      name: 'PaskerID / Pasar Kerja',
      desc: 'Sinkronisasi lowongan & tren kebutuhan industri',
    },
    {
      name: 'Standar ESCO & SKKNI',
      desc: 'Taksonomi terstandar internasional & nasional',
    },
    {
      name: 'Lembaga Pelatihan / BLK',
      desc: 'Katalog program upskilling & reskilling terakreditasi',
    },
  ];

  return (
    <div className="min-h-screen bg-slate-50 text-slate-900 flex flex-col font-sans">
      {/* Top Navbar Publik */}
      <header className="sticky top-0 z-50 bg-white/95 backdrop-blur-xs border-b border-slate-200">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div className="w-9 h-9 rounded-lg bg-[#0E385E] flex items-center justify-center text-white font-bold shadow-xs">
              <ShieldCheck className="w-5 h-5 text-amber-300" />
            </div>
            <span className="font-bold text-lg text-slate-900 tracking-tight">
              Skill Taxonomy Platform
            </span>
          </div>

          <nav className="flex items-center gap-6 text-sm font-medium text-slate-600">
            <a href="#cara-kerja" className="hover:text-blue-700 transition-colors">
              Cara Kerja
            </a>
            <a href="#fitur-platform" className="hover:text-blue-700 transition-colors">
              Fitur Platform
            </a>
            <a href="#ekosistem-data" className="hover:text-blue-700 transition-colors">
              Ekosistem Data
            </a>
            <Link
              href="/dashboard/operator"
              className="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg bg-[#0E385E] text-white text-xs font-semibold hover:bg-[#163A5F] transition-colors shadow-xs"
            >
              <span>Akses Dashboard</span>
              <ArrowRight className="w-3.5 h-3.5" />
            </Link>
          </nav>
        </div>
      </header>

      <main className="flex-1">
        {/* 1. Judul & Tagline + 2. Nilai Utama */}
        <section className="py-20 lg:py-24 bg-gradient-to-b from-blue-900 via-blue-800 to-slate-900 text-white">
          <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-6">
            {/* 2. Nilai Utama Badge */}
            <div className="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-blue-700/60 border border-blue-400/30 text-blue-200 text-xs sm:text-sm font-medium shadow-xs">
              <Sparkles className="w-4 h-4 text-amber-300 shrink-0" />
              <span>Didukung Skill Taxonomy Nasional untuk akurasi penempatan yang lebih tinggi.</span>
            </div>

            {/* 1. Judul Utama */}
            <h1 className="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight text-white leading-tight">
              Platform Penempatan Kerja Berbasis Skill Taxonomy
            </h1>

            {/* 1. Tagline */}
            <p className="text-base sm:text-xl text-slate-200 max-w-3xl mx-auto leading-relaxed">
              Menghubungkan pencari kerja, perusahaan, dan petugas pengantar kerja melalui data skill yang terstandar.
            </p>

            {/* Action Buttons */}
            <div className="pt-4 flex flex-wrap justify-center gap-4">
              <a
                href="#cara-kerja"
                className="inline-flex items-center gap-2 px-6 py-3 rounded-lg bg-blue-600 hover:bg-blue-500 text-white font-semibold text-sm transition-colors shadow-xs"
              >
                <span>Pelajari 5 Langkah Kerja</span>
                <ArrowRight className="w-4 h-4" />
              </a>
              <Link
                href="/dashboard/operator"
                className="inline-flex items-center gap-2 px-6 py-3 rounded-lg bg-slate-800/80 hover:bg-slate-800 text-slate-200 border border-slate-700 font-semibold text-sm transition-colors"
              >
                <span>Masuk ke Template Admin</span>
              </Link>
            </div>
          </div>
        </section>

        {/* 3. Cara Kerja (5 Langkah) */}
        <section id="cara-kerja" className="py-16 sm:py-20 bg-white border-b border-slate-200">
          <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div className="text-center max-w-2xl mx-auto mb-12">
              <span className="text-xs font-bold uppercase tracking-wider text-blue-700">
                Alur Sistem
              </span>
              <h2 className="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-1">
                Cara Kerja (5 Langkah)
              </h2>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-5 gap-4 lg:gap-6">
              {caraKerjaSteps.map((step) => {
                const IconComponent = step.icon;
                return (
                  <div
                    key={step.no}
                    className="relative bg-slate-50 border border-slate-200 rounded-xl p-5 flex flex-col justify-between hover:border-blue-400 hover:shadow-xs transition-all"
                  >
                    <div>
                      <div className="flex items-center justify-between mb-4">
                        <span className="w-7 h-7 rounded-full bg-blue-700 text-white text-xs font-bold flex items-center justify-center">
                          {step.no}
                        </span>
                        <IconComponent className="w-5 h-5 text-blue-700" />
                      </div>
                      <h3 className="font-bold text-slate-900 text-base mb-1">
                        {step.title}
                      </h3>
                      <p className="text-xs text-slate-600 leading-relaxed">
                        {step.desc}
                      </p>
                    </div>
                  </div>
                );
              })}
            </div>
          </div>
        </section>

        {/* 4. Fitur Platform (6 Fitur) */}
        <section id="fitur-platform" className="py-16 sm:py-20 bg-slate-50 border-b border-slate-200">
          <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div className="text-center max-w-2xl mx-auto mb-12">
              <span className="text-xs font-bold uppercase tracking-wider text-blue-700">
                Fitur Utama
              </span>
              <h2 className="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-1">
                Fitur Platform
              </h2>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
              {fiturPlatform.map((fitur, index) => {
                const IconComponent = fitur.icon;
                return (
                  <div
                    key={index}
                    className="bg-white border border-slate-200 rounded-xl p-6 hover:shadow-md hover:border-blue-300 transition-all space-y-3"
                  >
                    <div className="w-10 h-10 rounded-lg bg-blue-50 text-blue-700 flex items-center justify-center">
                      <IconComponent className="w-5 h-5" />
                    </div>
                    <h3 className="font-bold text-slate-900 text-lg">
                      {fitur.title}
                    </h3>
                    <p className="text-sm text-slate-600 leading-relaxed">
                      {fitur.desc}
                    </p>
                  </div>
                );
              })}
            </div>
          </div>
        </section>

        {/* 5. Ekosistem Data Terintegrasi (4 Sistem) */}
        <section id="ekosistem-data" className="py-16 sm:py-20 bg-white">
          <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div className="max-w-2xl mx-auto mb-12">
              <span className="text-xs font-bold uppercase tracking-wider text-blue-700">
                Konektivitas
              </span>
              <h2 className="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-1">
                Ekosistem Data Terintegrasi
              </h2>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 text-left mb-12">
              {ekosistemData.map((item, index) => (
                <div
                  key={index}
                  className="p-5 rounded-xl border border-slate-200 bg-slate-50 hover:bg-blue-50/50 hover:border-blue-200 transition-colors"
                >
                  <div className="flex items-center gap-2 mb-2 font-bold text-slate-900">
                    <CheckCircle2 className="w-4 h-4 text-emerald-600 shrink-0" />
                    <h4>{item.name}</h4>
                  </div>
                  <p className="text-xs text-slate-600 leading-relaxed">
                    {item.desc}
                  </p>
                </div>
              ))}
            </div>

            <div className="p-8 sm:p-10 rounded-2xl bg-gradient-to-r from-blue-50 via-slate-50 to-blue-50 border border-blue-200 shadow-xs max-w-4xl mx-auto">
              <div className="w-12 h-12 rounded-xl bg-blue-700 text-white flex items-center justify-center mx-auto mb-4 shadow-xs">
                <Network className="w-6 h-6" />
              </div>
              <p className="text-base sm:text-lg font-medium text-slate-800 leading-relaxed">
                Skill Taxonomy Nasional sebagai canonical model yang menyambungkan semua sistem ketenagakerjaan Indonesia.
              </p>
            </div>
          </div>
        </section>
      </main>

      {/* Footer Publik */}
      <footer className="py-8 bg-slate-900 text-slate-400 border-t border-slate-800 text-xs">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4">
          <p>
            Platform Penempatan Kerja Berbasis Skill Taxonomy
          </p>
          <p>
            Menghubungkan pencari kerja, perusahaan, dan petugas pengantar kerja.
          </p>
        </div>
      </footer>
    </div>
  );
}