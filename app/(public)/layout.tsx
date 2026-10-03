import React from 'react';
import PublicHeader from '@/components/public/PublicHeader';

export default function PublicLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <div className="min-h-screen flex flex-col bg-slate-50 text-slate-900">
      <PublicHeader />
      <main className="flex-1">{children}</main>
      <footer className="py-6 bg-slate-900 text-slate-400 text-xs">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-2">
          <p>e-Pengantar Kerja · Platform Penempatan Kerja Berbasis Skill Taxonomy</p>
          <p>Menghubungkan pencari kerja, perusahaan, dan petugas pengantar kerja.</p>
        </div>
      </footer>
    </div>
  );
}
