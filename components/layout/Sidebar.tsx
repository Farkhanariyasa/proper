'use client';

import React, { useState, useEffect, useRef } from 'react';
import Link from 'next/link';
import { usePathname } from 'next/navigation';
import {
  Bone,
  UserCheck,
  GitMerge,
  Briefcase,
  GraduationCap,
  Sparkles,
  LayoutDashboard,
  ChevronDown,
  BarChart3,
  ShieldCheck,
  Shield,
  User,
  Home,
  LogOut,
  Building2,
  ChevronsUpDown,
  X,
  FileText,
  VectorPolygon,
  IdCard,
  Users,
  KeyRound,
} from 'lucide-react';
import { useAuth } from '@/hooks/useAuth';

interface SidebarProps {
  mobileOpen: boolean;
  onMobileClose: () => void;
  desktopOpen: boolean;
}

export default function Sidebar({
  mobileOpen,
  onMobileClose,
  desktopOpen,
}: SidebarProps) {
  const pathname = usePathname();
  const { user: authUser, logout, hasPermission } = useAuth();
  const [dashboardOpen, setDashboardOpen] = useState(false);
  const [taxonomyOpen, setTaxonomyOpen] = useState(false);
  const [profileMenuOpen, setProfileMenuOpen] = useState(false);
  const profileContainerRef = useRef<HTMLDivElement>(null);

  // Otomatis buka accordion dashboard jika rute saat ini berada di /dashboard/*
  useEffect(() => {
    if (pathname.startsWith('/dashboard')) {
      setDashboardOpen(true);
    }
    if (pathname.startsWith('/taxonomy')) {
      setTaxonomyOpen(true);
    }
  }, [pathname]);

  // Tutup popover profil saat rute berganti
  useEffect(() => {
    setProfileMenuOpen(false);
  }, [pathname]);

  // Event listener untuk klik di luar popover profil (click-outside)
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (
        profileContainerRef.current &&
        !profileContainerRef.current.contains(event.target as Node)
      ) {
        setProfileMenuOpen(false);
      }
    };

    if (profileMenuOpen) {
      document.addEventListener('mousedown', handleClickOutside);
    }
    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
    };
  }, [profileMenuOpen]);

  const navItemsBeforeTaxonomy = [
    {
      label: 'Profil Skill',
      href: '/profil-skill',
      icon: UserCheck,
    },
  ];

  const taxonomySubItems = [
    {
      label: 'ESCO Skill',
      href: '/taxonomy/esco-skills',
      icon: Bone,
    },
    {
      label: 'KBJI (Jabatan)',
      href: '/taxonomy/kbji',
      icon: IdCard,
    },
  ];

  const navItemsAfterTaxonomy = [
    {
      label: 'Lowongan',
      href: '/lowongan',
      icon: Briefcase,
    },
    {
      label: 'Pelatihan',
      href: '/pelatihan',
      icon: GraduationCap,
    },
    {
      label: 'Rekomendasi & Matching',
      href: '/rekomendasi',
      icon: VectorPolygon,
    },
    {
      label: 'Laporan & Statistik',
      href: '/laporan',
      icon: FileText,
    },
  ];

  const dashboardSubItems = [
    {
      label: 'Dashboard Operator',
      href: '/dashboard/operator',
      icon: ShieldCheck,
    },
    {
      label: 'Dashboard Pimpinan',
      href: '/dashboard/pimpinan',
      icon: BarChart3,
    },
  ];

  const availableRoles = [
    {
      id: 'operator',
      label: 'Operator Pengantar Kerja',
      roleTitle: 'Operator Pengantar Kerja',
      unit: 'Pusat Pasar Kerja',
      href: '/dashboard/operator',
      icon: ShieldCheck,
    },
    {
      id: 'pimpinan',
      label: 'Pimpinan',
      roleTitle: 'Pimpinan',
      unit: 'Ditjen Binapenta Kemnaker',
      href: '/dashboard/pimpinan',
      icon: BarChart3,
    },
  ];

  // Tentukan role saat ini berdasarkan rute aktif (Hanya 2 Role)
  const getCurrentRole = () => {
    if (pathname.includes('/pimpinan')) return availableRoles[1];
    return availableRoles[0]; // Default ke Operator Pengantar Kerja
  };

  const currentRole = getCurrentRole();
  const isItemActive = (href: string) => {
    return pathname === href || pathname.startsWith(`${href}/`);
  };

  const isDashboardActive = pathname.startsWith('/dashboard');
  const isTaxonomyActive = pathname.startsWith('/taxonomy');

  return (
    <>
      {/* Mobile Backdrop Overlay (Khusus Layar Small & Medium) */}
      {mobileOpen && (
        <div
          className="fixed inset-0 z-40 bg-black/60 backdrop-blur-xs transition-opacity lg:hidden"
          onClick={onMobileClose}
          aria-hidden="true"
        />
      )}

      {/* Sidebar Container */}
      <aside
        className={`fixed top-0 bottom-0 left-0 z-50 flex w-64 flex-col bg-[#0E385E] text-slate-100 border-r border-[#1F5A88] transition-all duration-300 ease-in-out ${
          mobileOpen ? 'translate-x-0 shadow-2xl' : '-translate-x-full'
        } ${desktopOpen ? 'lg:translate-x-0' : 'lg:-translate-x-full'}`}
        aria-label="Sidebar Navigasi"
      >
        {/* Brand / Logo Header */}
        <div className="flex h-16 items-center justify-between border-b border-[#1F5A88] px-4">
          <Link
            href="/"
            className="flex items-center gap-3 rounded-lg focus:outline-hidden focus:ring-2 focus:ring-blue-400"
          >
            <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-[#1F5A88] border border-blue-300/30 shadow-inner">
              <Shield className="h-5 w-5 text-amber-300" />
            </div>
            <div>
              <span className="text-base font-extrabold tracking-tight text-white block leading-tight">
                e-Pengantar Kerja
              </span>
              <p className="text-[10px] text-blue-200">Skill Taxonomy Platform</p>
            </div>
          </Link>

          {/* Tombol Tutup Khusus Layar Small & Medium (lg:hidden) */}
          <button
            type="button"
            onClick={onMobileClose}
            className="rounded-lg p-1.5 text-blue-200 hover:bg-[#1F5A88] hover:text-white transition-colors lg:hidden cursor-pointer"
            title="Tutup Menu"
            aria-label="Tutup navigasi sidebar"
          >
            <X className="h-5 w-5" />
          </button>
        </div>

        {/* Navigation List */}
        <div className="flex-1 overflow-y-auto px-3 py-4 space-y-1">
          <div className="px-3 pb-2 text-[10px] font-bold uppercase tracking-wider text-blue-300/70">
            Menu Utama
          </div>

          {/* 1. Dashboard Paling Atas */}
          <div className="pb-1">
            <button
              type="button"
              onClick={() => setDashboardOpen(!dashboardOpen)}
              className={`flex w-full items-center justify-between rounded-lg px-3 py-2 text-[13px] font-medium transition-colors cursor-pointer ${
                isDashboardActive
                  ? 'text-white font-semibold'
                  : 'text-blue-100 hover:bg-[#1F5A88]/50 hover:text-white'
              }`}
              aria-expanded={dashboardOpen}
            >
              <div className="flex items-center gap-3">
                <LayoutDashboard
                  className={`h-4 w-4 shrink-0 ${
                    isDashboardActive ? 'text-amber-300' : 'text-blue-200'
                  }`}
                />
                <span>Dashboard</span>
              </div>
              <ChevronDown
                className={`h-4 w-4 text-blue-300 transition-transform duration-200 ${
                  dashboardOpen ? 'rotate-180 text-white' : ''
                }`}
              />
            </button>

            {/* Submenu Dropdown / Accordion */}
            {dashboardOpen && (
              <div className="mt-1 space-y-1 pl-7 pr-1">
                {dashboardSubItems.map((sub) => {
                  const subActive = pathname === sub.href;
                  const SubIcon = sub.icon;

                  return (
                    <Link
                      key={sub.href}
                      href={sub.href}
                      className={`group flex items-center gap-2.5 rounded-md px-2.5 py-1.5 text-[12px] font-medium transition-colors ${
                        subActive
                          ? 'bg-[#1F5A88] text-white font-semibold shadow-xs'
                          : 'text-blue-200 hover:bg-[#1F5A88]/40 hover:text-white'
                      }`}
                    >
                      <SubIcon
                        className={`h-3.5 w-3.5 shrink-0 ${
                          subActive ? 'text-amber-300' : 'text-blue-300 group-hover:text-white'
                        }`}
                      />
                      <span className="truncate">{sub.label}</span>
                    </Link>
                  );
                })}
              </div>
            )}
          </div>

          {/* 2. Menu Navigasi Sebelum Taxonomy (Profil Skill) */}
          {navItemsBeforeTaxonomy.map((item) => {
            const active = isItemActive(item.href);
            const Icon = item.icon;

            return (
              <Link
                key={item.href}
                href={item.href}
                className={`group flex items-center gap-3 rounded-lg px-3 py-2 text-[13px] font-medium transition-colors ${
                  active
                    ? 'bg-[#1F5A88] text-white shadow-xs font-semibold'
                    : 'text-blue-100 hover:bg-[#1F5A88]/50 hover:text-white'
                }`}
              >
                <Icon
                  className={`h-4 w-4 shrink-0 transition-colors ${
                    active ? 'text-amber-300' : 'text-blue-200 group-hover:text-white'
                  }`}
                />
                <span className="truncate">{item.label}</span>
              </Link>
            );
          })}

          {/* 3. Taxonomy Accordion Dropdown */}
          <div className="pb-1">
            <button
              type="button"
              onClick={() => setTaxonomyOpen(!taxonomyOpen)}
              className={`flex w-full items-center justify-between rounded-lg px-3 py-2 text-[13px] font-medium transition-colors cursor-pointer ${
                isTaxonomyActive
                  ? 'text-white font-semibold'
                  : 'text-blue-100 hover:bg-[#1F5A88]/50 hover:text-white'
              }`}
              aria-expanded={taxonomyOpen}
            >
              <div className="flex items-center gap-3">
                <GitMerge
                  className={`h-4 w-4 shrink-0 ${
                    isTaxonomyActive ? 'text-amber-300' : 'text-blue-200'
                  }`}
                />
                <span>Taxonomy</span>
              </div>
              <ChevronDown
                className={`h-4 w-4 text-blue-300 transition-transform duration-200 ${
                  taxonomyOpen ? 'rotate-180 text-white' : ''
                }`}
              />
            </button>

            {/* Submenu ESCO Skill & KBJI */}
            {taxonomyOpen && (
              <div className="mt-1 space-y-1 pl-7 pr-1">
                {taxonomySubItems.map((sub) => {
                  const subActive = pathname === sub.href;
                  const SubIcon = sub.icon;

                  return (
                    <Link
                      key={sub.href}
                      href={sub.href}
                      className={`group flex items-center gap-2.5 rounded-md px-2.5 py-1.5 text-[12px] font-medium transition-colors ${
                        subActive
                          ? 'bg-[#1F5A88] text-white font-semibold shadow-xs'
                          : 'text-blue-200 hover:bg-[#1F5A88]/40 hover:text-white'
                      }`}
                    >
                      <SubIcon
                        className={`h-3.5 w-3.5 shrink-0 ${
                          subActive ? 'text-amber-300' : 'text-blue-300 group-hover:text-white'
                        }`}
                      />
                      <span className="truncate">{sub.label}</span>
                    </Link>
                  );
                })}
              </div>
            )}
          </div>

          {/* 4. Menu Navigasi Setelah Taxonomy */}
          {navItemsAfterTaxonomy.map((item) => {
            const active = isItemActive(item.href);
            const Icon = item.icon;

            return (
              <Link
                key={item.href}
                href={item.href}
                className={`group flex items-center gap-3 rounded-lg px-3 py-2 text-[13px] font-medium transition-colors ${
                  active
                    ? 'bg-[#1F5A88] text-white shadow-xs font-semibold'
                    : 'text-blue-100 hover:bg-[#1F5A88]/50 hover:text-white'
                }`}
              >
                <Icon
                  className={`h-4 w-4 shrink-0 transition-colors ${
                    active ? 'text-amber-300' : 'text-blue-200 group-hover:text-white'
                  }`}
                />
                <span className="truncate">{item.label}</span>
              </Link>
            );
          })}

          {/* 5. Menu Manajemen Akses & Pengguna (Hanya Tampil Jika Memiliki Izin) */}
          {(hasPermission('users.view') || hasPermission('roles.view')) && (
            <div className="pt-3">
              <div className="px-3 pb-2 text-[10px] font-bold uppercase tracking-wider text-blue-300/70">
                Manajemen Akses
              </div>
              <div className="space-y-1">
                {hasPermission('users.view') && (
                  <Link
                    href="/users"
                    className={`group flex items-center gap-3 rounded-lg px-3 py-2 text-[13px] font-medium transition-colors ${
                      isItemActive('/users')
                        ? 'bg-[#1F5A88] text-white shadow-xs font-semibold'
                        : 'text-blue-100 hover:bg-[#1F5A88]/50 hover:text-white'
                    }`}
                  >
                    <Users
                      className={`h-4 w-4 shrink-0 transition-colors ${
                        isItemActive('/users') ? 'text-amber-300' : 'text-blue-200 group-hover:text-white'
                      }`}
                    />
                    <span className="truncate">Pengguna</span>
                  </Link>
                )}

                {hasPermission('roles.view') && (
                  <Link
                    href="/roles"
                    className={`group flex items-center gap-3 rounded-lg px-3 py-2 text-[13px] font-medium transition-colors ${
                      isItemActive('/roles')
                        ? 'bg-[#1F5A88] text-white shadow-xs font-semibold'
                        : 'text-blue-100 hover:bg-[#1F5A88]/50 hover:text-white'
                    }`}
                  >
                    <KeyRound
                      className={`h-4 w-4 shrink-0 transition-colors ${
                        isItemActive('/roles') ? 'text-amber-300' : 'text-blue-200 group-hover:text-white'
                      }`}
                    />
                    <span className="truncate">Peran & Hak Akses</span>
                  </Link>
                )}
              </div>
            </div>
          )}
        </div>

        {/* Footer Profil Pengguna dengan Sub-banner / Popover Switch Role */}
        <div ref={profileContainerRef} className="relative border-t border-[#1F5A88] p-3">
          {/* Sub-banner Popover Pindah Peran & Aksi Profil */}
          {profileMenuOpen && (
            <div
              onClick={(e) => e.stopPropagation()}
              className="absolute bottom-full left-3 right-3 mb-2 rounded-xl bg-[#09223A] border border-[#1F5A88] shadow-2xl p-3 z-50 text-white animate-in fade-in zoom-in-95"
            >
              {/* Header Info Akun */}
              <div className="flex items-center gap-2.5 pb-2.5 border-b border-[#1F5A88]/80">
                <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#1F5A88] text-white font-bold text-xs border border-blue-300/30">
                  {authUser?.name ? authUser.name.charAt(0).toUpperCase() : <User className="h-4 w-4 text-amber-300" />}
                </div>
                <div className="min-w-0 flex-1">
                  <div className="text-xs font-bold text-white truncate">
                    {authUser?.name || currentRole.roleTitle}
                  </div>
                  <div className="text-[10px] text-blue-300 truncate">
                    {authUser ? `@${authUser.username} (${authUser.role_names?.[0] || 'Pengguna'})` : currentRole.unit}
                  </div>
                </div>
              </div>

              {/* Menu Pindah Peran (Role Switcher - Bersih Tanpa Ikon) */}
              <div className="pt-2">
                <div className="px-1 pb-1 text-[10px] font-bold uppercase tracking-wider text-blue-300/80">
                  Pindah Peran (Role)
                </div>
                <div className="space-y-1">
                  {availableRoles.map((r) => {
                    const isSelected = currentRole.id === r.id;

                    return (
                      <Link
                        key={r.id}
                        href={r.href}
                        onClick={() => setProfileMenuOpen(false)}
                        className={`flex items-center justify-between rounded-lg px-2.5 py-1.5 text-xs font-medium transition-colors ${
                          isSelected
                            ? 'bg-[#1F5A88] text-white font-semibold shadow-xs'
                            : 'text-blue-100 hover:bg-[#163A5F] hover:text-white'
                        }`}
                      >
                        <span className="truncate">{r.label}</span>
                        {isSelected && (
                          <span className="text-[9px] bg-blue-900 border border-blue-400/40 text-amber-300 px-1.5 py-0.2 rounded font-bold shrink-0">
                            Aktif
                          </span>
                        )}
                      </Link>
                    );
                  })}
                </div>
              </div>

              {/* Tautan Beranda & Logout */}
              <div className="pt-2 mt-2 border-t border-[#1F5A88]/80 space-y-1">
                <Link
                  href="/"
                  onClick={() => setProfileMenuOpen(false)}
                  className="flex w-full items-center gap-2 rounded-lg px-2.5 py-1.5 text-xs font-medium text-blue-100 hover:bg-[#163A5F] hover:text-white transition-colors"
                >
                  <Home className="h-3.5 w-3.5 text-amber-300 shrink-0" />
                  <span>Kembali ke Beranda</span>
                </Link>

                <button
                  type="button"
                  onClick={() => {
                    setProfileMenuOpen(false);
                    logout();
                  }}
                  className="flex w-full items-center gap-2 rounded-lg px-2.5 py-1.5 text-xs font-medium text-red-300 hover:bg-red-950/60 hover:text-red-100 transition-colors cursor-pointer"
                >
                  <LogOut className="h-3.5 w-3.5 text-red-400 shrink-0" />
                  <span>Keluar (Logout)</span>
                </button>
              </div>
            </div>
          )}

          {/* Trigger Card Profil Pengguna (Dapat Diklik untuk Buka Sub-banner) */}
          <button
            type="button"
            onClick={(e) => {
              e.stopPropagation();
              setProfileMenuOpen(!profileMenuOpen);
            }}
            className={`flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left transition-colors cursor-pointer border ${
              profileMenuOpen
                ? 'bg-[#1F5A88] border-blue-300/50 shadow-md'
                : 'bg-[#163A5F]/80 hover:bg-[#163A5F] border-blue-400/20'
            }`}
            aria-expanded={profileMenuOpen}
            title="Klik untuk membuka menu akun & pindah peran"
          >
            <div className="relative flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#1F5A88] text-white border border-blue-300/30">
              <span className="text-xs font-bold text-amber-300">
                {authUser?.name ? authUser.name.charAt(0).toUpperCase() : <User className="h-4 w-4 text-blue-200" />}
              </span>
              <span className="absolute -bottom-0.5 -right-0.5 h-2 w-2 rounded-full bg-emerald-400 ring-2 ring-[#0E385E]" />
            </div>
            <div className="min-w-0 flex-1">
              <p className="truncate text-xs font-semibold text-white">
                {authUser?.name || currentRole.roleTitle}
              </p>
              <p className="truncate text-[10px] text-blue-300">
                {authUser ? `${authUser.role_names?.[0] || 'Pengguna'}` : currentRole.unit}
              </p>
            </div>
            <ChevronsUpDown className="h-4 w-4 text-blue-300 shrink-0" />
          </button>
        </div>
      </aside>
    </>
  );
}
