'use client';

import React from 'react';
import { Layers, Award } from 'lucide-react';
import { TaxonomyStats } from '@/types/taxonomy';

interface TaxonomyStatsCardsProps {
  stats: TaxonomyStats | null;
  loading: boolean;
}

export default function TaxonomyStatsCards({ stats, loading }: TaxonomyStatsCardsProps) {
  return (
    <div className="flex flex-wrap items-center gap-2">
      {/* 1. Kategori Pill */}
      <div className="inline-flex items-center gap-1.5 rounded-lg bg-blue-50/90 border border-blue-200/70 px-2.5 py-1 text-xs shadow-2xs">
        <Layers className="h-3.5 w-3.5 text-blue-600 shrink-0" />
        <span className="text-slate-600 font-medium">Kategori:</span>
        {loading ? (
          <span className="inline-block h-3.5 w-8 animate-pulse rounded bg-slate-200" />
        ) : (
          <span className="font-bold font-mono text-blue-800">
            {stats?.totalCategories?.toLocaleString('id-ID') ?? '-'}
          </span>
        )}
      </div>

      {/* 2. Keahlian Pill */}
      <div className="inline-flex items-center gap-1.5 rounded-lg bg-amber-50/90 border border-amber-200/70 px-2.5 py-1 text-xs shadow-2xs">
        <Award className="h-3.5 w-3.5 text-amber-600 shrink-0" />
        <span className="text-slate-600 font-medium">Keahlian:</span>
        {loading ? (
          <span className="inline-block h-3.5 w-12 animate-pulse rounded bg-slate-200" />
        ) : (
          <span className="font-bold font-mono text-amber-800">
            {stats?.totalSkills?.toLocaleString('id-ID') ?? '-'}
          </span>
        )}
      </div>
    </div>
  );
}
