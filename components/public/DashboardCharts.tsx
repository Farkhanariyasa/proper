'use client';

import React from 'react';
import { LabelValue } from '@/types/public-dashboard';

export const CHART_COLORS = ['#2563EB', '#0EA5E9', '#10B981', '#F59E0B', '#8B5CF6', '#F43F5E', '#94A3B8'];

export const fmt = (n: number) => n.toLocaleString('id-ID');
export const pct = (share: number) =>
  `${(share * 100).toLocaleString('id-ID', { maximumFractionDigits: 1 })}%`;

// Singkatan yang tetap ditulis kapital saat label diubah ke huruf judul
const KEEP_UPPER = new Set([
  'DKI', 'IPA', 'IPS', 'SMK', 'SMA', 'SMP', 'SD', 'TKJ', 'RPL', 'IT', 'K3', 'HSE', 'BPO', 'EPC', 'D3', 'D4', 'S1', 'S2', 'S3',
]);

// Kata sambung yang tetap huruf kecil (kecuali di awal)
const KEEP_LOWER = new Set(['dan', 'atau', 'di', 'ke', 'dari', 'yang', 'untuk']);

/** "KAB. CIREBON" -> "Kab. Cirebon", "DKI JAKARTA" -> "DKI Jakarta" */
export function toTitleCase(text: string | null | undefined): string {
  if (!text) return 'Tidak diketahui';
  return text
    .toLowerCase()
    .split(' ')
    .map((word, i) => {
      const upper = word.toUpperCase();
      if (KEEP_UPPER.has(upper.replace(/[^A-Z0-9]/g, ''))) return upper;
      if (i > 0 && KEEP_LOWER.has(word)) return word;
      return word.charAt(0).toUpperCase() + word.slice(1);
    })
    .join(' ');
}

export function Card({
  title,
  subtitle,
  className = '',
  children,
}: {
  title: string;
  subtitle?: string;
  className?: string;
  children: React.ReactNode;
}) {
  return (
    // min-w-0: kartu di dalam grid tidak melebar mengikuti grafik yang bisa di-scroll horizontal
    <div className={`min-w-0 rounded-xl border border-slate-200 bg-white p-4 sm:p-5 shadow-2xs ${className}`}>
      <h3 className="text-sm sm:text-base font-bold text-slate-900">{title}</h3>
      {subtitle && <p className="mt-0.5 text-xs text-slate-500">{subtitle}</p>}
      <div className="mt-4">{children}</div>
    </div>
  );
}

export function StatCard({
  icon: Icon,
  accent,
  iconColor,
  label,
  value,
  unit,
  note,
}: {
  icon: React.ElementType;
  accent: string;
  iconColor: string;
  label: string;
  value: string;
  unit?: string;
  note?: string;
}) {
  return (
    <div className={`rounded-xl border border-slate-200 border-t-4 ${accent} bg-white p-4 shadow-2xs`}>
      <div className="flex items-start justify-between gap-2 text-xs font-semibold text-slate-500">
        <span>{label}</span>
        <Icon className={`h-4 w-4 shrink-0 ${iconColor}`} />
      </div>
      <p className="mt-2 text-slate-900">
        <span className="text-2xl font-extrabold tracking-tight tabular-nums">{value}</span>
        {unit && <span className="ml-1.5 text-xs font-medium text-slate-500">{unit}</span>}
      </p>
      {note && <p className="mt-1 text-xs text-slate-500">{note}</p>}
    </div>
  );
}

export function EmptyChart() {
  return <p className="py-8 text-center text-xs text-slate-400">Belum ada data untuk wilayah ini.</p>;
}

/** Donut chart + legenda dengan jumlah dan persentase */
export function DonutChart({
  items,
  formatLabel = toTitleCase,
}: {
  items: LabelValue[];
  formatLabel?: (label: string | null) => string;
}) {
  const total = items.reduce((sum, i) => sum + i.value, 0);
  if (!total) return <EmptyChart />;

  const radius = 42;
  const circumference = 2 * Math.PI * radius;
  let offset = 0;

  return (
    <div className="flex flex-col sm:flex-row items-center gap-6">
      <svg viewBox="0 0 120 120" className="h-36 w-36 shrink-0 -rotate-90" role="img" aria-label="Diagram donut">
        <circle cx="60" cy="60" r={radius} fill="none" stroke="#F1F5F9" strokeWidth="16" />
        {items.map((item, i) => {
          const length = (item.value / total) * circumference;
          const segment = (
            <circle
              key={`${item.label}-${i}`}
              cx="60"
              cy="60"
              r={radius}
              fill="none"
              stroke={CHART_COLORS[i % CHART_COLORS.length]}
              strokeWidth="16"
              strokeDasharray={`${length} ${circumference - length}`}
              strokeDashoffset={-offset}
            >
              <title>{`${formatLabel(item.label)}: ${fmt(item.value)} (${pct(item.value / total)})`}</title>
            </circle>
          );
          offset += length;
          return segment;
        })}
      </svg>
      <ul className="w-full space-y-2">
        {items.map((item, i) => (
          <li key={`${item.label}-${i}`} className="flex items-center justify-between gap-3 text-sm">
            <span className="inline-flex items-center gap-2 text-slate-700 min-w-0">
              <span
                className="h-2.5 w-2.5 shrink-0 rounded-full"
                style={{ backgroundColor: CHART_COLORS[i % CHART_COLORS.length] }}
              />
              <span className="truncate">{formatLabel(item.label)}</span>
            </span>
            <span className="shrink-0 tabular-nums">
              <span className="font-bold text-slate-900">{pct(item.value / total)}</span>{' '}
              <span className="text-xs text-slate-500">({fmt(item.value)})</span>
            </span>
          </li>
        ))}
      </ul>
    </div>
  );
}

/** Daftar batang horizontal, panjang relatif terhadap nilai terbesar */
export function HBarList({
  items,
  color = 'bg-blue-600',
  unit,
  formatLabel = toTitleCase,
}: {
  items: LabelValue[];
  color?: string;
  unit?: string;
  formatLabel?: (label: string | null) => string;
}) {
  if (!items.length) return <EmptyChart />;
  const max = Math.max(...items.map((i) => i.value), 1);

  return (
    <ul className="space-y-3">
      {items.map((item, i) => (
        <li key={`${item.label}-${i}`}>
          <div className="flex items-baseline justify-between gap-3 text-sm">
            <span className="text-slate-700 truncate">{formatLabel(item.label)}</span>
            <span className="shrink-0 font-bold text-slate-900 tabular-nums">
              {fmt(item.value)}
              {unit && <span className="ml-1 text-xs font-normal text-slate-500">{unit}</span>}
            </span>
          </div>
          <div className="mt-1 h-2 rounded-full bg-slate-100">
            <div className={`h-full rounded-full ${color}`} style={{ width: `${(item.value / max) * 100}%` }} />
          </div>
        </li>
      ))}
    </ul>
  );
}

/** Diagram batang vertikal sederhana */
export function ColumnChart({
  items,
  color = 'bg-blue-600',
  formatLabel = toTitleCase,
}: {
  items: LabelValue[];
  color?: string;
  formatLabel?: (label: string | null) => string;
}) {
  if (!items.length) return <EmptyChart />;
  const max = Math.max(...items.map((i) => i.value), 1);

  return (
    <div className="overflow-x-auto">
      {/* items-start: tinggi area batang sama sehingga dasar batang sejajar meski label beda panjang */}
      <div
        className="grid items-start gap-2 sm:gap-3 min-w-full"
        style={{ gridTemplateColumns: `repeat(${items.length}, minmax(44px, 1fr))` }}
      >
        {items.map((item, i) => (
          <div key={`${item.label}-${i}`} className="flex flex-col items-center gap-1 min-w-0">
            <div className="flex h-44 w-full flex-col items-center justify-end gap-1">
              <span className="text-[11px] font-bold text-slate-900 tabular-nums">{fmt(item.value)}</span>
              <div
                className={`w-full rounded-t-md ${color}`}
                style={{ height: `${Math.max((item.value / max) * 85, 1.5)}%` }}
                title={`${formatLabel(item.label)}: ${fmt(item.value)}`}
              />
            </div>
            <span className="w-full text-center text-[11px] leading-tight text-slate-600 break-words">
              {formatLabel(item.label)}
            </span>
          </div>
        ))}
      </div>
    </div>
  );
}

/** Diagram batang berkelompok (dua seri) */
export function GroupedColumnChart({
  groups,
  series,
}: {
  groups: { label: string; values: number[] }[];
  series: { name: string; color: string }[];
}) {
  const max = Math.max(...groups.flatMap((g) => g.values), 1);
  if (max <= 1) return <EmptyChart />;

  return (
    <div>
      <div className="mb-3 flex flex-wrap gap-4">
        {series.map((s) => (
          <span key={s.name} className="inline-flex items-center gap-1.5 text-xs text-slate-600">
            <span className={`h-2.5 w-2.5 rounded-sm ${s.color}`} />
            {s.name}
          </span>
        ))}
      </div>
      <div className="overflow-x-auto">
        <div
          className="grid items-end gap-2 sm:gap-3 min-w-full"
          style={{ gridTemplateColumns: `repeat(${groups.length}, minmax(40px, 1fr))` }}
        >
          {groups.map((group) => (
            <div key={group.label} className="flex flex-col items-center gap-1">
              <div className="flex h-44 w-full items-end justify-center gap-0.5">
                {group.values.map((value, i) => (
                  <div
                    key={series[i].name}
                    className={`w-1/2 max-w-5 rounded-t ${series[i].color}`}
                    style={{ height: `${Math.max((value / max) * 100, 1)}%` }}
                    title={`${group.label} · ${series[i].name}: ${fmt(value)}`}
                  />
                ))}
              </div>
              <span className="text-[11px] text-slate-600 tabular-nums">{group.label}</span>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}

/** Tabel peringkat dengan porsi relatif */
export function Leaderboard({
  items,
  labelHeader,
  valueHeader,
}: {
  items: LabelValue[];
  labelHeader: string;
  valueHeader: string;
}) {
  if (!items.length) return <EmptyChart />;
  const max = Math.max(...items.map((i) => i.value), 1);

  return (
    <div className="overflow-x-auto">
      <table className="w-full text-left text-sm">
        <thead className="border-b border-slate-200 text-xs font-semibold text-slate-500">
          <tr>
            <th className="py-2 pr-2 w-10">#</th>
            <th className="py-2 pr-4">{labelHeader}</th>
            <th className="py-2 text-right">{valueHeader}</th>
          </tr>
        </thead>
        <tbody className="divide-y divide-slate-100">
          {items.map((item, i) => (
            <tr key={`${item.label}-${i}`}>
              <td className="py-2.5 pr-2">
                <span className="flex h-6 w-6 items-center justify-center rounded-full bg-blue-50 text-xs font-bold text-blue-700">
                  {i + 1}
                </span>
              </td>
              <td className="py-2.5 pr-4">
                <p className="text-slate-800">{toTitleCase(item.label)}</p>
                <div className="mt-1 h-1.5 rounded-full bg-slate-100">
                  <div className="h-full rounded-full bg-blue-500" style={{ width: `${(item.value / max) * 100}%` }} />
                </div>
              </td>
              <td className="py-2.5 text-right font-bold text-slate-900 tabular-nums">{fmt(item.value)}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
