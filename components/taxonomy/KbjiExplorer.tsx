'use client';

import React, { useState, useEffect } from 'react';
import Link from 'next/link';
import {
  Briefcase,
  ChevronRight,
  RefreshCw,
  FolderTree,
  AlertCircle,
  Loader2,
  Layers,
} from 'lucide-react';
import KbjiTreeNode from '@/components/taxonomy/KbjiTreeNode';
import KbjiSearchBar from '@/components/taxonomy/KbjiSearchBar';
import KbjiDetailDrawer from '@/components/taxonomy/KbjiDetailDrawer';
import { getKbjiStats, getKbjiMajorGroups } from '@/services/kbji';
import { KbjiNode, KbjiStats } from '@/types/kbji';

// Dipakai oleh halaman admin (/taxonomy/kbji) dan halaman publik (/taksonomi)
export default function KbjiExplorer({
  breadcrumbHref = '/taxonomy/kbji',
}: {
  breadcrumbHref?: string;
}) {
  const [stats, setStats] = useState<KbjiStats | null>(null);
  const [statsLoading, setStatsLoading] = useState(true);

  const [majorGroups, setMajorGroups] = useState<KbjiNode[]>([]);
  const [groupsLoading, setGroupsLoading] = useState(true);
  const [groupsError, setGroupsError] = useState<string | null>(null);

  const [selectedCode, setSelectedCode] = useState<string | null>(null);

  // Load initial data (Stats & 10 Golongan Pokok)
  const loadData = async () => {
    setStatsLoading(true);
    setGroupsLoading(true);
    setGroupsError(null);

    // 1. Fetch Stats
    getKbjiStats()
      .then((data) => setStats(data))
      .catch((err) => console.error('Gagal memuat statistik KBJI:', err))
      .finally(() => setStatsLoading(false));

    // 2. Fetch Golongan Pokok
    try {
      const groups = await getKbjiMajorGroups();
      setMajorGroups(groups);
    } catch (err: any) {
      setGroupsError(err.message || 'Gagal memuat golongan pokok KBJI');
    } finally {
      setGroupsLoading(false);
    }
  };

  useEffect(() => {
    loadData();
  }, []);

  const groupCount = stats
    ? stats.total - stats.byLevel.occupation
    : null;

  return (
    <div className="mx-auto max-w-7xl p-3 sm:p-5 lg:p-6 space-y-3">
      {/* 1. Compact Header Bar: Title, Stats Pills, and Refresh */}
      <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-2.5 pb-1">
        {/* Left: Breadcrumb & Title */}
        <div className="flex items-center gap-2.5 flex-wrap">
          <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 border border-blue-200 text-blue-700 shadow-2xs">
            <Briefcase className="h-4 w-4" />
          </div>
          <div>
            <div className="flex items-center gap-1.5 text-[11px] text-slate-400">
              <Link href={breadcrumbHref}className="hover:text-blue-700 transition-colors">
                Taxonomy
              </Link>
              <ChevronRight className="h-2.5 w-2.5 text-slate-300" />
              <span className="font-semibold text-slate-600">KBJI</span>
            </div>
            <h1 className="text-base sm:text-lg font-bold text-slate-900 tracking-tight leading-tight">
              Klasifikasi Baku Jabatan Indonesia (KBJI 2026)
            </h1>
          </div>
        </div>

        {/* Right: Inline Compact Stats Pills & Refresh Button */}
        <div className="flex items-center gap-2 flex-wrap">
          <div className="inline-flex items-center gap-1.5 rounded-lg bg-blue-50/90 border border-blue-200/70 px-2.5 py-1 text-xs shadow-2xs">
            <Layers className="h-3.5 w-3.5 text-blue-600 shrink-0" />
            <span className="text-slate-600 font-medium">Golongan:</span>
            {statsLoading ? (
              <span className="inline-block h-3.5 w-8 animate-pulse rounded bg-slate-200" />
            ) : (
              <span className="font-bold font-mono text-blue-800">
                {groupCount?.toLocaleString('id-ID') ?? '-'}
              </span>
            )}
          </div>

          <div className="inline-flex items-center gap-1.5 rounded-lg bg-amber-50/90 border border-amber-200/70 px-2.5 py-1 text-xs shadow-2xs">
            <Briefcase className="h-3.5 w-3.5 text-amber-600 shrink-0" />
            <span className="text-slate-600 font-medium">Jabatan:</span>
            {statsLoading ? (
              <span className="inline-block h-3.5 w-8 animate-pulse rounded bg-slate-200" />
            ) : (
              <span className="font-bold font-mono text-amber-800">
                {stats?.byLevel.occupation.toLocaleString('id-ID') ?? '-'}
              </span>
            )}
          </div>

          <button
            type="button"
            onClick={loadData}
            title="Muat ulang data KBJI"
            className="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-slate-700 shadow-2xs hover:bg-slate-50 hover:text-slate-900 transition-colors cursor-pointer"
          >
            <RefreshCw className={`h-3 w-3 text-slate-500 ${groupsLoading ? 'animate-spin' : ''}`} />
            <span className="hidden sm:inline">Refresh</span>
          </button>
        </div>
      </div>

      {/* 2. Compact Search Bar */}
      <div>
        <KbjiSearchBar onSelectNode={(code) => setSelectedCode(code)} />
      </div>

      {/* 3. Main Area (2 Kolom): Tree View (Kiri) & Inspector Detail (Kanan) */}
      <div className="grid grid-cols-1 lg:grid-cols-12 gap-4 items-start">
        {/* Kolom Kiri: Pohon Hierarki KBJI (7 Kolom) */}
        <div className="lg:col-span-7 flex flex-col rounded-xl border border-slate-200 bg-white shadow-2xs overflow-hidden h-[calc(100vh-180px)] min-h-[500px]">
          {/* Header Panel Tree */}
          <div className="flex items-center justify-between border-b border-slate-100 bg-slate-50/80 px-3.5 py-2.5 shrink-0">
            <div className="flex items-center gap-2">
              <FolderTree className="h-4 w-4 text-blue-700" />
              <h2 className="text-xs font-bold uppercase tracking-wider text-slate-700">
                10 Golongan Pokok
              </h2>
            </div>
            <span className="text-[11px] text-slate-400">
              Klik tanda panah untuk membuka sub-golongan
            </span>
          </div>

          {/* Isi Tree View dengan Internal Scroll */}
          <div className="p-2.5 flex-1 overflow-y-auto">
            {groupsLoading ? (
              <div className="py-20 text-center">
                <Loader2 className="mx-auto h-7 w-7 text-blue-600 animate-spin mb-2" />
                <p className="text-xs text-slate-500 font-medium">Memuat struktur KBJI...</p>
              </div>
            ) : groupsError ? (
              <div className="p-6 text-center text-xs text-red-600">
                <AlertCircle className="mx-auto h-6 w-6 text-red-500 mb-2" />
                <p className="font-semibold">{groupsError}</p>
                <button
                  type="button"
                  onClick={loadData}
                  className="mt-3 inline-flex items-center rounded-lg bg-red-600 px-3 py-1.5 text-xs text-white hover:bg-red-700 cursor-pointer"
                >
                  Coba Lagi
                </button>
              </div>
            ) : majorGroups.length === 0 ? (
              <div className="py-12 text-center text-xs text-slate-400">
                Belum ada data KBJI. Jalankan seeder KbjiSeeder terlebih dahulu.
              </div>
            ) : (
              <div className="space-y-0.5">
                {majorGroups.map((group) => (
                  <KbjiTreeNode
                    key={group.code}
                    node={group}
                    level={0}
                    selectedCode={selectedCode}
                    onSelectNode={(code) => setSelectedCode(code)}
                  />
                ))}
              </div>
            )}
          </div>
        </div>

        {/* Kolom Kanan: Panel Inspector Detail Jabatan (5 Kolom) */}
        <div className="lg:col-span-5 h-[calc(100vh-180px)] min-h-[500px]">
          <KbjiDetailDrawer
            code={selectedCode}
            onClose={() => setSelectedCode(null)}
            onSelectNode={(code) => setSelectedCode(code)}
          />
        </div>
      </div>
    </div>
  );
}
