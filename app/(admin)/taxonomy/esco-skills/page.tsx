'use client';

import React, { useState, useEffect } from 'react';
import Link from 'next/link';
import {
  GitMerge,
  ChevronRight,
  RefreshCw,
  FolderTree,
  AlertCircle,
  Loader2,
} from 'lucide-react';
import TaxonomyStatsCards from '@/components/taxonomy/TaxonomyStatsCards';
import SkillSearchBar from '@/components/taxonomy/SkillSearchBar';
import SkillTreeNode from '@/components/taxonomy/SkillTreeNode';
import SkillDetailDrawer from '@/components/taxonomy/SkillDetailDrawer';
import { getTaxonomyStats, getCategories } from '@/services/taxonomy';
import { TaxonomyCategory, TaxonomyStats } from '@/types/taxonomy';

export default function EscoSkillsPage() {
  const [stats, setStats] = useState<TaxonomyStats | null>(null);
  const [statsLoading, setStatsLoading] = useState(true);

  const [categories, setCategories] = useState<TaxonomyCategory[]>([]);
  const [categoriesLoading, setCategoriesLoading] = useState(true);
  const [categoriesError, setCategoriesError] = useState<string | null>(null);

  const [selectedNodeId, setSelectedNodeId] = useState<number | null>(null);

  // Load initial data (Stats & 4 Main Categories)
  const loadData = async () => {
    setStatsLoading(true);
    setCategoriesLoading(true);
    setCategoriesError(null);

    // 1. Fetch Stats
    getTaxonomyStats()
      .then((data) => setStats(data))
      .catch((err) => console.error('Gagal memuat statistik:', err))
      .finally(() => setStatsLoading(false));

    // 2. Fetch Categories (4 Pillars)
    try {
      const cats = await getCategories();
      setCategories(cats);
    } catch (err: any) {
      setCategoriesError(err.message || 'Gagal memuat pilar taksonomi');
    } finally {
      setCategoriesLoading(false);
    }
  };

  useEffect(() => {
    loadData();
  }, []);

  return (
    <div className="mx-auto max-w-7xl p-3 sm:p-5 lg:p-6 space-y-3">
      {/* 1. Compact Header Bar: Title, Stats Pills, and Refresh */}
      <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-2.5 pb-1">
        {/* Left: Breadcrumb & Title */}
        <div className="flex items-center gap-2.5 flex-wrap">
          <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 border border-blue-200 text-blue-700 shadow-2xs">
            <GitMerge className="h-4 w-4" />
          </div>
          <div>
            <div className="flex items-center gap-1.5 text-[11px] text-slate-400">
              <Link href="/taxonomy/esco-skills" className="hover:text-blue-700 transition-colors">
                Taxonomy
              </Link>
              <ChevronRight className="h-2.5 w-2.5 text-slate-300" />
              <span className="font-semibold text-slate-600">ESCO Skill</span>
            </div>
            <h1 className="text-base sm:text-lg font-bold text-slate-900 tracking-tight leading-tight">
              Pohon Hierarki Taksonomi Keahlian
            </h1>
          </div>
        </div>

        {/* Right: Inline Compact Stats Pills & Refresh Button */}
        <div className="flex items-center gap-2 flex-wrap">
          <TaxonomyStatsCards stats={stats} loading={statsLoading} />

          <button
            type="button"
            onClick={loadData}
            title="Muat ulang data taksonomi"
            className="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-slate-700 shadow-2xs hover:bg-slate-50 hover:text-slate-900 transition-colors cursor-pointer"
          >
            <RefreshCw className={`h-3 w-3 text-slate-500 ${categoriesLoading ? 'animate-spin' : ''}`} />
            <span className="hidden sm:inline">Refresh</span>
          </button>
        </div>
      </div>

      {/* 2. Compact Search Bar */}
      <div>
        <SkillSearchBar onSelectNode={(id) => setSelectedNodeId(id)} />
      </div>

      {/* 3. Main Area (2 Kolom): Tree View (Kiri) & Inspector Detail (Kanan) */}
      <div className="grid grid-cols-1 lg:grid-cols-12 gap-4 items-start">
        {/* Kolom Kiri: Pohon Hierarki Taksonomi (7 Kolom) */}
        <div className="lg:col-span-7 flex flex-col rounded-xl border border-slate-200 bg-white shadow-2xs overflow-hidden h-[calc(100vh-180px)] min-h-[500px]">
          {/* Header Panel Tree */}
          <div className="flex items-center justify-between border-b border-slate-100 bg-slate-50/80 px-3.5 py-2.5 shrink-0">
            <div className="flex items-center gap-2">
              <FolderTree className="h-4 w-4 text-blue-700" />
              <h2 className="text-xs font-bold uppercase tracking-wider text-slate-700">
                Struktur 4 Pilar Utama
              </h2>
            </div>
            <span className="text-[11px] text-slate-400">
              Klik tanda panah untuk membuka sub-bidang
            </span>
          </div>

          {/* Isi Tree View dengan Internal Scroll */}
          <div className="p-2.5 flex-1 overflow-y-auto">
            {categoriesLoading ? (
              <div className="py-20 text-center">
                <Loader2 className="mx-auto h-7 w-7 text-blue-600 animate-spin mb-2" />
                <p className="text-xs text-slate-500 font-medium">Memuat struktur taksonomi...</p>
              </div>
            ) : categoriesError ? (
              <div className="p-6 text-center text-xs text-red-600">
                <AlertCircle className="mx-auto h-6 w-6 text-red-500 mb-2" />
                <p className="font-semibold">{categoriesError}</p>
                <button
                  type="button"
                  onClick={loadData}
                  className="mt-3 inline-flex items-center rounded-lg bg-red-600 px-3 py-1.5 text-xs text-white hover:bg-red-700 cursor-pointer"
                >
                  Coba Lagi
                </button>
              </div>
            ) : categories.length === 0 ? (
              <div className="py-12 text-center text-xs text-slate-400">
                Tidak ada kategori yang ditemukan.
              </div>
            ) : (
              <div className="space-y-0.5">
                {categories.map((cat) => (
                  <SkillTreeNode
                    key={cat.id}
                    node={cat}
                    level={0}
                    selectedId={selectedNodeId}
                    onSelectNode={(id) => setSelectedNodeId(id)}
                  />
                ))}
              </div>
            )}
          </div>
        </div>

        {/* Kolom Kanan: Panel Inspector Detail Node (5 Kolom) */}
        <div className="lg:col-span-5 h-[calc(100vh-180px)] min-h-[500px]">
          <SkillDetailDrawer
            nodeId={selectedNodeId}
            onClose={() => setSelectedNodeId(null)}
            onSelectNode={(id) => setSelectedNodeId(id)}
          />
        </div>
      </div>
    </div>
  );
}
