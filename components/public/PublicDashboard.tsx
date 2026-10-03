'use client';

import React, { useEffect, useMemo, useState } from 'react';
import {
  Users,
  TrendingUp,
  Briefcase,
  BadgeCheck,
  MapPin,
  RotateCcw,
  Info,
  Loader2,
} from 'lucide-react';
import SearchableSelect from '@/components/ui/SearchableSelect';
import { getDashboardWilayah, getLaborMarketDashboard } from '@/services/labor-market';
import {
  ContractType,
  CountShare,
  LaborMarketDashboard,
  WilayahProvince,
} from '@/types/labor-market';

const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
const MONTHS_LONG = [
  'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
];

const CONTRACT_META: { key: ContractType; label: string; color: string }[] = [
  { key: 'full_time', label: 'Tetap (Full-time)', color: 'bg-blue-600' },
  { key: 'part_time', label: 'Paruh waktu', color: 'bg-amber-400' },
  { key: 'contract', label: 'Kontrak', color: 'bg-violet-500' },
];

const EDUCATION_COLORS: Record<string, string> = {
  S1: '#2563EB',
  D3: '#0EA5E9',
  SMK: '#10B981',
  SMA: '#F59E0B',
};

const fmt = (n: number) => n.toLocaleString('id-ID');
const pct = (share: number) =>
  `${(share * 100).toLocaleString('id-ID', { maximumFractionDigits: 1 })}%`;

export default function PublicDashboard() {
  const [wilayah, setWilayah] = useState<WilayahProvince[]>([]);
  const [provinceId, setProvinceId] = useState('');
  const [regencyId, setRegencyId] = useState('');
  const [data, setData] = useState<LaborMarketDashboard | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    getDashboardWilayah().then(setWilayah);
  }, []);

  useEffect(() => {
    setLoading(true);
    getLaborMarketDashboard({
      provinceId: provinceId || undefined,
      regencyId: regencyId || undefined,
    })
      .then(setData)
      .finally(() => setLoading(false));
  }, [provinceId, regencyId]);

  const provinceOptions = useMemo(
    () => wilayah.map((p) => ({ value: p.id, label: p.name })),
    [wilayah]
  );
  const regencyOptions = useMemo(
    () =>
      (wilayah.find((p) => p.id === provinceId)?.regencies ?? []).map((r) => ({
        value: r.id,
        label: r.name,
      })),
    [wilayah, provinceId]
  );

  const handleProvinceChange = (value: string) => {
    setProvinceId(value);
    setRegencyId('');
  };

  const resetFilter = () => {
    setProvinceId('');
    setRegencyId('');
  };

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-5">
      {/* Judul */}
      <div className="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
        <div>
          <h1 className="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight">
            Dashboard Pasar Kerja 2026
          </h1>
          <p className="text-sm text-slate-500 mt-1">
            Gambaran kebutuhan tenaga kerja: sektor, pendidikan, pengalaman, dan keterampilan yang dicari.
          </p>
        </div>
        <span className="inline-flex items-center gap-1.5 self-start sm:self-auto rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-800">
          <Info className="h-3.5 w-3.5" />
          Data contoh (dummy)
        </span>
      </div>

      {/* Filter wilayah */}
      <section
        aria-label="Filter wilayah"
        className="rounded-xl border border-slate-200 bg-white p-4 shadow-2xs"
      >
        <div className="flex items-center gap-2 mb-3 text-sm font-bold text-slate-800">
          <MapPin className="h-4 w-4 text-blue-700" />
          Filter Wilayah
        </div>
        <div className="grid grid-cols-1 sm:grid-cols-[1fr_1fr_auto] gap-3 items-end">
          <div>
            <span className="block text-xs font-medium text-slate-500 mb-1">Provinsi</span>
            <SearchableSelect
              options={provinceOptions}
              value={provinceId}
              onChange={handleProvinceChange}
              placeholder="Semua Provinsi"
              searchPlaceholder="Cari provinsi..."
            />
          </div>
          <div>
            <span className="block text-xs font-medium text-slate-500 mb-1">Kabupaten / Kota</span>
            <SearchableSelect
              options={regencyOptions}
              value={regencyId}
              onChange={setRegencyId}
              placeholder={provinceId ? 'Semua Kabupaten/Kota' : 'Pilih provinsi dulu'}
              searchPlaceholder="Cari kabupaten/kota..."
              disabled={!provinceId}
            />
          </div>
          <button
            type="button"
            onClick={resetFilter}
            disabled={!provinceId}
            className="inline-flex items-center justify-center gap-1.5 rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
          >
            <RotateCcw className="h-3.5 w-3.5" />
            Reset
          </button>
        </div>
      </section>

      {loading && !data ? (
        <div className="py-24 text-center text-sm text-slate-500">
          <Loader2 className="mx-auto mb-2 h-7 w-7 animate-spin text-blue-600" />
          Memuat data dashboard...
        </div>
      ) : !data || data.vacancyCount === 0 ? (
        <div className="rounded-xl border border-slate-200 bg-white py-20 text-center text-sm text-slate-500">
          Belum ada data lowongan untuk wilayah ini.
        </div>
      ) : (
        <DashboardContent data={data} />
      )}
    </div>
  );
}

function DashboardContent({ data }: { data: LaborMarketDashboard }) {
  return (
    <>
      {/* Ringkasan */}
      <section className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <StatCard
          icon={Users}
          accent="border-t-blue-600"
          iconColor="text-blue-600"
          label="Total Pencari Kerja"
          value={fmt(data.jobSeekers.total)}
          unit="orang"
          note="Terdaftar di wilayah ini"
        />
        <StatCard
          icon={Briefcase}
          accent="border-t-emerald-500"
          iconColor="text-emerald-600"
          label="Total Lowongan"
          value={fmt(data.vacancyCount)}
          unit="lowongan"
          note={`${fmt(data.totalFormasi)} formasi dibutuhkan`}
        />
        <StatCard
          icon={BadgeCheck}
          accent="border-t-amber-400"
          iconColor="text-amber-500"
          label="Pencari Kerja Memenuhi Kriteria"
          value={pct(data.jobSeekers.qualifiedShare)}
          note={`${fmt(data.jobSeekers.qualified)} orang dengan kecocokan skill ≥ 90%`}
        />
        <StatCard
          icon={TrendingUp}
          accent="border-t-violet-500"
          iconColor="text-violet-600"
          label="Sektor Paling Banyak Membuka Lowongan"
          value={data.topSector?.name ?? '-'}
          valueSmall
          note={
            data.topSector
              ? `${fmt(data.topSector.formasi)} formasi · ${pct(data.topSector.share)} dari total`
              : ''
          }
        />
      </section>

      {/* Sektor & tren */}
      <section className="grid grid-cols-1 lg:grid-cols-12 gap-4">
        <Card
          className="lg:col-span-7"
          title="Formasi per Sektor dan Tipe Kontrak"
          subtitle="Sektor mana yang paling banyak membuka lowongan, dan seberapa stabil pekerjaannya"
        >
          <div className="flex flex-wrap gap-x-4 gap-y-1 mb-4">
            {CONTRACT_META.map((c) => (
              <span key={c.key} className="inline-flex items-center gap-1.5 text-xs text-slate-600">
                <span className={`h-2.5 w-2.5 rounded-sm ${c.color}`} />
                {c.label}
              </span>
            ))}
          </div>
          <SectorContractBars rows={data.bySectorContract} />
        </Card>

        <Card
          className="lg:col-span-5"
          title="Tren Pembukaan Lowongan per Bulan"
          subtitle="Jumlah formasi yang dibuka setiap bulan pada 2026"
        >
          <MonthlyTrend monthly={data.monthly} />
        </Card>
      </section>

      {/* Pendidikan, pengalaman, jabatan */}
      <section className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <Card title="Pendidikan Minimal" subtitle="Jenjang pendidikan yang disyaratkan">
          <EducationDonut items={data.education} total={data.totalFormasi} />
        </Card>

        <Card title="Pengalaman Kerja" subtitle="Lama pengalaman yang diminta pemberi kerja">
          <ShareBars items={data.experience} color="bg-sky-500" />
        </Card>

        <Card
          className="md:col-span-2 lg:col-span-1"
          title="5 Jabatan Paling Dicari"
          subtitle="Jabatan dengan jumlah formasi terbesar"
        >
          <ol className="space-y-2">
            {data.topJobs.map((job, i) => (
              <li
                key={`${job.title}-${job.sector}`}
                className="flex items-center gap-3 rounded-lg border border-slate-100 bg-slate-50/70 px-3 py-2.5"
              >
                <span className="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-blue-50 text-xs font-bold text-blue-700">
                  {i + 1}
                </span>
                <div className="min-w-0 flex-1">
                  <p className="truncate text-sm font-semibold text-slate-900">{job.title}</p>
                  <p className="truncate text-xs text-slate-500">
                    {job.sector} · {job.education}
                  </p>
                </div>
                <div className="text-right shrink-0">
                  <p className="text-sm font-bold text-slate-900 tabular-nums">{fmt(job.formasi)}</p>
                  <p className="text-[10px] text-slate-400">formasi</p>
                </div>
              </li>
            ))}
          </ol>
        </Card>
      </section>

      {/* Keterampilan */}
      <Card
        title="Keterampilan Paling Dibutuhkan"
        subtitle="Persentase formasi yang mensyaratkan keterampilan tersebut (satu lowongan bisa meminta lebih dari satu keterampilan)"
      >
        <div className="grid grid-cols-1 md:grid-cols-2 gap-x-8">
          <ShareBars items={data.topSkills} color="bg-blue-600" ranked />
        </div>
      </Card>
    </>
  );
}

function Card({
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
    <div className={`rounded-xl border border-slate-200 bg-white p-4 sm:p-5 shadow-2xs ${className}`}>
      <h2 className="text-sm sm:text-base font-bold text-slate-900">{title}</h2>
      {subtitle && <p className="mt-0.5 text-xs text-slate-500">{subtitle}</p>}
      <div className="mt-4">{children}</div>
    </div>
  );
}

function StatCard({
  icon: Icon,
  accent,
  iconColor,
  label,
  value,
  valueSmall = false,
  unit,
  note,
}: {
  icon: React.ElementType;
  accent: string;
  iconColor: string;
  label: string;
  value: string;
  valueSmall?: boolean;
  unit?: string;
  note?: string;
}) {
  return (
    <div className={`rounded-xl border border-slate-200 border-t-4 ${accent} bg-white p-4 shadow-2xs`}>
      <div className="flex items-center justify-between text-xs font-semibold text-slate-500">
        <span>{label}</span>
        <Icon className={`h-4 w-4 ${iconColor}`} />
      </div>
      <p className="mt-2 text-slate-900">
        <span className={`font-extrabold tracking-tight ${valueSmall ? 'text-lg' : 'text-2xl'}`}>
          {value}
        </span>
        {unit && <span className="ml-1.5 text-xs font-medium text-slate-500">{unit}</span>}
      </p>
      {note && <p className="mt-1 text-xs text-slate-500">{note}</p>}
    </div>
  );
}

function SectorContractBars({ rows }: { rows: LaborMarketDashboard['bySectorContract'] }) {
  const max = Math.max(...rows.map((r) => r.total), 1);

  return (
    <ul className="space-y-3">
      {rows.map((row) => (
        <li key={row.sector}>
          <div className="flex items-baseline justify-between gap-2 text-xs">
            <span className="font-medium text-slate-700 truncate">{row.sector}</span>
            <span className="shrink-0 font-bold text-slate-900 tabular-nums">{fmt(row.total)}</span>
          </div>
          <div
            className="mt-1 flex h-2.5 overflow-hidden rounded-full bg-slate-100"
            title={CONTRACT_META.map((c) => `${c.label}: ${fmt(row[c.key])}`).join(' · ')}
          >
            <div className="flex h-full" style={{ width: `${(row.total / max) * 100}%` }}>
              {CONTRACT_META.map((c) => (
                <div
                  key={c.key}
                  className={`h-full ${c.color}`}
                  style={{ width: `${(row[c.key] / row.total) * 100}%` }}
                />
              ))}
            </div>
          </div>
        </li>
      ))}
    </ul>
  );
}

function MonthlyTrend({ monthly }: { monthly: LaborMarketDashboard['monthly'] }) {
  const width = 360;
  const height = 180;
  const pad = { top: 16, right: 12, bottom: 24, left: 12 };
  const max = Math.max(...monthly.map((m) => m.formasi), 1);
  const stepX = (width - pad.left - pad.right) / (monthly.length - 1);
  const y = (v: number) => pad.top + (1 - v / max) * (height - pad.top - pad.bottom);
  const points = monthly.map((m, i) => [pad.left + i * stepX, y(m.formasi)] as const);
  const line = points.map(([px, py]) => `${px},${py}`).join(' ');
  const area = `${pad.left},${height - pad.bottom} ${line} ${pad.left + (monthly.length - 1) * stepX},${height - pad.bottom}`;

  const sorted = [...monthly].sort((a, b) => b.formasi - a.formasi);
  const peaks = sorted.slice(0, 2);
  const low = sorted[sorted.length - 1];

  return (
    <div>
      <svg
        viewBox={`0 0 ${width} ${height}`}
        className="w-full h-auto"
        role="img"
        aria-label="Grafik tren formasi bulanan"
      >
        <defs>
          <linearGradient id="trendFill" x1="0" x2="0" y1="0" y2="1">
            <stop offset="0%" stopColor="#2563EB" stopOpacity="0.25" />
            <stop offset="100%" stopColor="#2563EB" stopOpacity="0" />
          </linearGradient>
        </defs>
        <line
          x1={pad.left}
          x2={width - pad.right}
          y1={height - pad.bottom}
          y2={height - pad.bottom}
          stroke="#E2E8F0"
        />
        <polygon points={area} fill="url(#trendFill)" />
        <polyline points={line} fill="none" stroke="#2563EB" strokeWidth="2.5" strokeLinejoin="round" />
        {points.map(([px, py], i) => (
          <g key={monthly[i].month}>
            <circle cx={px} cy={py} r="3.5" fill="#fff" stroke="#2563EB" strokeWidth="2">
              <title>{`${MONTHS_LONG[i]}: ${fmt(monthly[i].formasi)} formasi`}</title>
            </circle>
            <text x={px} y={height - 6} textAnchor="middle" fontSize="10" fill="#64748B">
              {MONTHS[i]}
            </text>
          </g>
        ))}
      </svg>

      <div className="mt-3 rounded-lg border border-blue-100 bg-blue-50/60 p-3 text-xs leading-relaxed text-slate-700">
        <p>
          Puncak pembukaan lowongan terjadi pada{' '}
          {peaks.map((p, i) => (
            <React.Fragment key={p.month}>
              {i > 0 && ' dan '}
              <strong>
                {MONTHS_LONG[p.month - 1]} ({fmt(p.formasi)})
              </strong>
            </React.Fragment>
          ))}
          . Paling sepi pada <strong>{MONTHS_LONG[low.month - 1]}</strong> ({fmt(low.formasi)}).
        </p>
      </div>
    </div>
  );
}

function EducationDonut({ items, total }: { items: CountShare[]; total: number }) {
  const radius = 42;
  const circumference = 2 * Math.PI * radius;
  let offset = 0;

  return (
    <div className="flex flex-col sm:flex-row md:flex-col items-center gap-5">
      <svg viewBox="0 0 120 120" className="h-36 w-36 shrink-0 -rotate-90" role="img" aria-label="Komposisi pendidikan minimal">
        <circle cx="60" cy="60" r={radius} fill="none" stroke="#F1F5F9" strokeWidth="16" />
        {items.map((item) => {
          const length = item.share * circumference;
          const segment = (
            <circle
              key={item.label}
              cx="60"
              cy="60"
              r={radius}
              fill="none"
              stroke={EDUCATION_COLORS[item.label] ?? '#94A3B8'}
              strokeWidth="16"
              strokeDasharray={`${length} ${circumference - length}`}
              strokeDashoffset={-offset}
            >
              <title>{`${item.label}: ${fmt(item.value)} (${pct(item.share)})`}</title>
            </circle>
          );
          offset += length;
          return segment;
        })}
      </svg>
      <ul className="w-full space-y-2">
        {items.map((item) => (
          <li key={item.label} className="flex items-center justify-between gap-2 text-sm">
            <span className="inline-flex items-center gap-2 text-slate-700">
              <span
                className="h-2.5 w-2.5 rounded-full"
                style={{ backgroundColor: EDUCATION_COLORS[item.label] ?? '#94A3B8' }}
              />
              {item.label}
            </span>
            <span className="tabular-nums">
              <span className="font-bold text-slate-900">{fmt(item.value)}</span>{' '}
              <span className="text-xs text-slate-500">({pct(item.share)})</span>
            </span>
          </li>
        ))}
        <li className="flex justify-between border-t border-slate-100 pt-2 text-xs text-slate-500">
          <span>Total</span>
          <span className="tabular-nums">{fmt(total)} formasi</span>
        </li>
      </ul>
    </div>
  );
}

function ShareBars({
  items,
  color,
  ranked = false,
}: {
  items: CountShare[];
  color: string;
  ranked?: boolean;
}) {
  const max = Math.max(...items.map((i) => i.share), 0.0001);

  return (
    <>
      {items.map((item, i) => (
        <div key={item.label} className="py-1.5">
          <div className="flex items-baseline justify-between gap-2 text-sm">
            <span className="text-slate-700 truncate">
              {ranked && <span className="mr-1.5 text-xs text-slate-400">#{i + 1}</span>}
              {item.label}
            </span>
            <span className="shrink-0 tabular-nums">
              <span className="font-bold text-slate-900">{fmt(item.value)}</span>{' '}
              <span className="text-xs text-slate-500">({pct(item.share)})</span>
            </span>
          </div>
          <div className="mt-1 h-2 rounded-full bg-slate-100">
            <div
              className={`h-full rounded-full ${color}`}
              style={{ width: `${(item.share / max) * 100}%` }}
            />
          </div>
        </div>
      ))}
    </>
  );
}
