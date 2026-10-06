'use client';

import React from 'react';
import Link from 'next/link';
import { GraduationCap, ArrowLeft, Construction } from 'lucide-react';
import AuthGuard from '@/components/auth/AuthGuard';

export default function PelatihanPage() {
  return (
    <AuthGuard requiredPermission="pelatihan.view">
      <div className="p-4 sm:p-6 lg:p-8 max-w-5xl mx-auto">
        <div className="min-h-[60vh] flex flex-col items-center justify-center text-center rounded-2xl border border-slate-200 bg-white p-8 sm:p-12 shadow-2xs">
          <div className="relative mb-5 flex h-20 w-20 items-center justify-center rounded-2xl bg-blue-50 text-[#1F5A88] border border-blue-100">
            <GraduationCap className="h-10 w-10 text-[#1F5A88]" />
            <div className="absolute -bottom-1.5 -right-1.5 flex h-7 w-7 items-center justify-center rounded-full bg-amber-500 text-white shadow-xs">
              <Construction className="h-4 w-4" />
            </div>
          </div>

          <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200 mb-3">
            Sedang Dalam Pengembangan
          </span>

          <h1 className="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
            Modul Pelatihan
          </h1>

          <p className="mt-2 max-w-md text-sm text-slate-500 leading-relaxed">
            Halaman pelatihan saat ini sedang dalam tahap pengembangan dan akan segera tersedia.
          </p>

          <div className="mt-6">
            <Link
              href="/dashboard"
              className="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-[#0E385E] text-white text-xs sm:text-sm font-semibold hover:bg-[#163A5F] transition-colors shadow-2xs"
            >
              <ArrowLeft className="h-4 w-4" />
              Kembali ke Dashboard
            </Link>
          </div>
        </div>
      </div>
    </AuthGuard>
  );
}
