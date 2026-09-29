'use client';

import React, { useState, useEffect, useCallback } from 'react';
import Sidebar from './Sidebar';
import { Menu, Bell, ShieldCheck } from 'lucide-react';
import { usePathname } from 'next/navigation';

export default function AppShell({ children }: { children: React.ReactNode }) {
  const [mobileOpen, setMobileOpen] = useState(false);
  const [desktopOpen, setDesktopOpen] = useState(true);
  const pathname = usePathname();

  // Otomatis tutup sidebar mobile hanya saat rute URL berpindah
  useEffect(() => {
    setMobileOpen(false);
  }, [pathname]);

  const handleToggleSidebar = () => {
    if (typeof window !== 'undefined' && window.innerWidth < 1024) {
      setMobileOpen((prev) => !prev);
    } else {
      setDesktopOpen((prev) => !prev);
    }
  };

  const handleMobileClose = useCallback(() => {
    setMobileOpen(false);
  }, []);

  // Mapping judul berdasarkan pathname aktif
  const getPageTitle = (path: string) => {
    if (path === '/') return 'Beranda Platform';
    if (path.startsWith('/profil-skill')) return 'Profil Skill Pencari Kerja';
    if (path.startsWith('/taxonomy')) return 'Taksonomi Okupasi & Skill';
    if (path.startsWith('/lowongan')) return 'Daftar Lowongan Kerja';
    if (path.startsWith('/pelatihan')) return 'Rekomendasi Pelatihan';
    if (path.startsWith('/rekomendasi')) return 'Rekomendasi & Matching';
    if (path.startsWith('/laporan')) return 'Laporan & Statistik'; 
    if (path.startsWith('/dashboard/pimpinan')) return 'Dashboard Pimpinan';
    if (path.startsWith('/dashboard/operator')) return 'Dashboard Operator';
    if (path.startsWith('/dashboard')) return 'Dashboard';
    return 'Platform Penempatan Kerja';
  };

  return (
    <div className="min-h-screen bg-slate-50 text-slate-800 flex flex-col font-sans">
      {/* Sidebar Navigasi */}
      <Sidebar
        mobileOpen={mobileOpen}
        onMobileClose={handleMobileClose}
        desktopOpen={desktopOpen}
      />

      {/* Area Konten Utama & Topbar */}
      <div
        className={`flex flex-1 flex-col transition-all duration-300 ease-in-out ${
          desktopOpen ? 'lg:pl-64' : 'lg:pl-0'
        }`}
      >
        {/* Top Minimal Bar */}
        <header className="sticky top-0 z-30 flex h-16 shrink-0 items-center justify-between border-b border-slate-200 bg-white/95 px-4 backdrop-blur-xs sm:px-6">
          <div className="flex items-center gap-3">
            {/* Tombol Toggle Buka/Tutup Sidebar (Responsif: Drawer di SM/MD, Collapse di LG) */}
            <button
              type="button"
              onClick={handleToggleSidebar}
              className="rounded-lg p-2 text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition-colors cursor-pointer focus:outline-hidden focus:ring-2 focus:ring-blue-500"
              title="Buka / Tutup Sidebar Navigasi"
              aria-label="Toggle navigasi sidebar"
            >
              <Menu className="h-5 w-5" />
            </button>

            {/* Breadcrumb / Title */}
            <div>
              <h1 className="text-sm sm:text-base font-bold text-slate-900">
                {getPageTitle(pathname)}
              </h1>
            </div>
          </div>

          {/* Quick Header Actions */}
          <div className="flex items-center gap-3">
            <div className="hidden sm:flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-blue-50 border border-blue-200 text-blue-700 text-xs font-semibold">
              <ShieldCheck className="w-3.5 h-3.5 text-blue-600" />
              <span>Skill Taxonomy Kemnaker</span>
            </div>

            {/* Notification Bell Badge */}
            <button
              type="button"
              className="relative rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-700 transition-colors cursor-pointer"
              aria-label="Notifikasi"
            >
              <Bell className="h-4 w-4" />
              <span className="absolute top-1.5 right-1.5 flex h-2 w-2 rounded-full bg-[#DC3545] ring-2 ring-white" />
            </button>
          </div>
        </header>

        {/* Content Container */}
        <main className="flex-1">
          {children}
        </main>
      </div>
    </div>
  );
}
