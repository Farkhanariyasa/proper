'use client';

import React from 'react';
import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { Shield, LogIn } from 'lucide-react';

const NAV_ITEMS = [
  { label: 'Dashboard', href: '/' },
  { label: 'Taxonomy', href: '/taksonomi' },
];

export default function PublicHeader() {
  const pathname = usePathname();

  const isActive = (href: string) =>
    href === '/' ? pathname === '/' : pathname.startsWith(href);

  return (
    <header className="sticky top-0 z-50 bg-[#0E385E] border-b border-[#1F5A88] text-white">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-3">
        <div className="flex items-center gap-4 sm:gap-8 min-w-0">
          <Link href="/" className="flex items-center gap-2.5 shrink-0">
            <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-[#1F5A88] border border-blue-300/30">
              <Shield className="h-5 w-5 text-amber-300" />
            </div>
            <span className="hidden sm:block text-base font-extrabold tracking-tight">
              e-Pengantar Kerja
            </span>
          </Link>

          <nav className="flex items-center gap-1" aria-label="Navigasi utama">
            {NAV_ITEMS.map((item) => (
              <Link
                key={item.href}
                href={item.href}
                aria-current={isActive(item.href) ? 'page' : undefined}
                className={`px-3 py-1.5 rounded-lg text-sm font-semibold transition-colors ${
                  isActive(item.href)
                    ? 'bg-white/15 text-white'
                    : 'text-blue-100 hover:bg-white/10 hover:text-white'
                }`}
              >
                {item.label}
              </Link>
            ))}
          </nav>
        </div>

        {/* Belum ada sistem autentikasi: sementara langsung menuju area admin */}
        <Link
          href="/dashboard/operator"
          className="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg bg-amber-400 text-[#0E385E] text-sm font-bold hover:bg-amber-300 transition-colors shrink-0"
        >
          <LogIn className="w-4 h-4" />
          <span>Login</span>
        </Link>
      </div>
    </header>
  );
}
