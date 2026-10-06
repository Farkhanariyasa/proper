'use client';

import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { usePathname, useRouter, useSearchParams } from 'next/navigation';
import {
  AlertCircle,
  Briefcase,
  Building2,
  Calculator,
  GraduationCap,
  Accessibility,
  Loader2,
  SlidersHorizontal,
  Plane,
  RotateCcw,
  School,
  UserCheck,
  Users,
  ClipboardList,
} from 'lucide-react';
import SearchableSelect from '@/components/ui/SearchableSelect';
import {
  Card,
  ColumnChart,
  DonutChart,
  GroupedColumnChart,
  HBarList,
  Leaderboard,
  StatCard,
  fmt,
  pct,
  toTitleCase,
} from '@/components/public/DashboardCharts';
import {
  getDashboardRegions,
  getDashboardYears,
  getKebutuhanIndustri,
  getProfilPencaker,
  getRingkasan,
} from '@/services/public-dashboard';
import {
  DashboardArea,
  DashboardRegion,
  KebutuhanIndustriData,
  ProfilPencakerData,
  RingkasanData,
} from '@/types/public-dashboard';

const TABS = [
  { key: 'ringkasan', label: 'Ringkasan Nasional' },
  { key: 'profil', label: 'Profil Pencaker' },
  { key: 'industri', label: 'Kebutuhan Industri' },
] as const;
type TabKey = (typeof TABS)[number]['key'];

const asIs = (label: string | null) => label ?? 'Tidak diketahui';

const EDUCATION_SHORT: Record<string, string> = {
  'Tidak Tamat SD/Tidak Sekolah': '< SD',
  'SD atau Sederajat': 'SD',
  'SMP atau Sederajat': 'SMP',
  'SMA atau Sederajat': 'SMA',
  'Tidak diketahui': 'N/A',
};
const educationLabel = (label: string | null) => EDUCATION_SHORT[label ?? 'Tidak diketahui'] ?? label ?? 'N/A';

export default function PublicDashboard() {
  const router = useRouter();
  const pathname = usePathname();
  const searchParams = useSearchParams();

  const tabParam = searchParams.get('tab');
  const tab: TabKey = TABS.some((t) => t.key === tabParam) ? (tabParam as TabKey) : 'ringkasan';
  const tahun = /^\d{4}$/.test(searchParams.get('tahun') ?? '') ? searchParams.get('tahun')! : '';
  const provinsi = searchParams.get('provinsi') ?? '';
  const kabKota = provinsi ? searchParams.get('kab_kota') ?? '' : '';

  const [years, setYears] = useState<number[]>([]);
  const [yearsLoading, setYearsLoading] = useState(true);
  const [regions, setRegions] = useState<DashboardRegion[]>([]);
  const [regionsLoading, setRegionsLoading] = useState(true);

  useEffect(() => {
    getDashboardYears()
      .then(setYears)
      .catch((err) => console.error('Gagal memuat daftar tahun:', err))
      .finally(() => setYearsLoading(false));
    getDashboardRegions()
      .then(setRegions)
      .catch((err) => console.error('Gagal memuat daftar wilayah:', err))
      .finally(() => setRegionsLoading(false));
  }, []);

  // Tab & filter disimpan di URL agar tampilan bisa dibagikan
  const updateQuery = (changes: Record<string, string>) => {
    const params = new URLSearchParams(searchParams.toString());
    Object.entries(changes).forEach(([key, value]) => {
      if (value) params.set(key, value);
      else params.delete(key);
    });
    const query = params.toString();
    router.replace(query ? `${pathname}?${query}` : pathname, { scroll: false });
  };

  const yearOptions = useMemo(
    () => years.map((y) => ({ value: String(y), label: String(y) })),
    [years]
  );
  const provinceOptions = useMemo(
    () => regions.map((r) => ({ value: r.name, label: toTitleCase(r.name) })),
    [regions]
  );
  const regencyOptions = useMemo(
    () =>
      (regions.find((r) => r.name === provinsi)?.regencies ?? []).map((name) => ({
        value: name,
        label: toTitleCase(name),
      })),
    [regions, provinsi]
  );

  const area: DashboardArea = {
    tahun: tahun || undefined,
    provinsi: provinsi || undefined,
    kabKota: kabKota || undefined,
  };
  const areaLabel =
    (kabKota ? toTitleCase(kabKota) : provinsi ? toTitleCase(provinsi) : 'Nasional') +
    (tahun ? ` · ${tahun}` : '');

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-5">
      <div>
        <h1 className="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight">
          Dashboard Pasar Kerja
        </h1>
        <p className="text-sm text-slate-500 mt-1">
          Gambaran pencari kerja dan kebutuhan tenaga kerja industri berdasarkan data pencari kerja dan lowongan yang tercatat.
        </p>
      </div>

      {/* Filter tahun & wilayah (berlaku untuk semua tab) */}
      <section aria-label="Filter tahun dan wilayah" className="rounded-xl border border-slate-200 bg-white p-4 shadow-2xs">
        <div className="flex items-center gap-2 mb-3 text-sm font-bold text-slate-800">
          <SlidersHorizontal className="h-4 w-4 text-blue-700" />
          Filter
        </div>
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-[180px_1fr_1fr_auto] gap-3 items-end">
          <div>
            <span className="block text-xs font-medium text-slate-500 mb-1">Tahun</span>
            <SearchableSelect
              options={yearOptions}
              value={tahun}
              onChange={(value) => updateQuery({ tahun: value })}
              placeholder="Semua Tahun"
              searchPlaceholder="Cari tahun..."
              isLoading={yearsLoading}
            />
          </div>
          <div>
            <span className="block text-xs font-medium text-slate-500 mb-1">Provinsi</span>
            <SearchableSelect
              options={provinceOptions}
              value={provinsi}
              onChange={(value) => updateQuery({ provinsi: value, kab_kota: '' })}
              placeholder="Semua Provinsi"
              searchPlaceholder="Cari provinsi..."
              isLoading={regionsLoading}
            />
          </div>
          <div>
            <span className="block text-xs font-medium text-slate-500 mb-1">Kabupaten / Kota</span>
            <SearchableSelect
              options={regencyOptions}
              value={kabKota}
              onChange={(value) => updateQuery({ kab_kota: value })}
              placeholder={provinsi ? 'Semua Kabupaten/Kota' : 'Pilih provinsi dulu'}
              searchPlaceholder="Cari kabupaten/kota..."
              disabled={!provinsi}
            />
          </div>
          <button
            type="button"
            onClick={() => updateQuery({ tahun: '', provinsi: '', kab_kota: '' })}
            disabled={!provinsi && !tahun}
            className="inline-flex items-center justify-center gap-1.5 rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
          >
            <RotateCcw className="h-3.5 w-3.5" />
            Reset
          </button>
        </div>
      </section>

      {/* Tab */}
      <div role="tablist" aria-label="Kategori dashboard" className="flex gap-1 overflow-x-auto border-b border-slate-200">
        {TABS.map((t) => (
          <button
            key={t.key}
            type="button"
            role="tab"
            aria-selected={tab === t.key}
            onClick={() => updateQuery({ tab: t.key === 'ringkasan' ? '' : t.key })}
            className={`-mb-px shrink-0 border-b-2 px-3 py-2 text-sm font-semibold transition-colors cursor-pointer ${
              tab === t.key
                ? 'border-blue-700 text-blue-800'
                : 'border-transparent text-slate-500 hover:text-slate-800'
            }`}
          >
            {t.label}
          </button>
        ))}
      </div>

      <div role="tabpanel">
        {tab === 'ringkasan' && <RingkasanTab area={area} areaLabel={areaLabel} />}
        {tab === 'profil' && <ProfilTab area={area} />}
        {tab === 'industri' && <IndustriTab area={area} />}
      </div>

      <p className="text-[11px] text-slate-400">
        Sumber: data pencari kerja dan lowongan kerja (req_pk). Angka diperbarui berkala; pemuatan pertama untuk suatu wilayah bisa memerlukan beberapa detik.
      </p>
    </div>
  );
}

// ---------------------------------------------------------------------------

// Cache hasil per tab+wilayah selama halaman terbuka agar pindah tab tidak memuat ulang
const responseCache = new Map<string, unknown>();

function useDashboardData<T>(section: string, fetcher: (area: DashboardArea) => Promise<T>, area: DashboardArea) {
  const key = `${section}|${area.tahun ?? ''}|${area.provinsi ?? ''}|${area.kabKota ?? ''}`;
  const [state, setState] = useState<{ key: string; data: T | null; error: string | null }>({
    key: '',
    data: null,
    error: null,
  });
  const latestKey = useRef(key);
  const [reloadToken, setReloadToken] = useState(0);

  useEffect(() => {
    latestKey.current = key;
    if (responseCache.has(key)) {
      setState({ key, data: responseCache.get(key) as T, error: null });
      return;
    }
    setState((prev) => ({ ...prev, error: null }));
    fetcher(area)
      .then((data) => {
        responseCache.set(key, data);
        if (latestKey.current === key) setState({ key, data, error: null });
      })
      .catch((err: Error) => {
        if (latestKey.current === key) setState({ key, data: null, error: err.message });
      });
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [key, reloadToken]);

  const retry = useCallback(() => setReloadToken((n) => n + 1), []);
  const isCurrent = state.key === key;

  return {
    data: isCurrent ? state.data : null,
    // Tampilkan data lama dengan efek redup saat memuat wilayah baru
    staleData: state.data,
    loading: !isCurrent && !state.error,
    error: state.error,
    retry,
  };
}

function TabState<T>({
  result,
  children,
}: {
  result: ReturnType<typeof useDashboardData<T>>;
  children: (data: T) => React.ReactNode;
}) {
  if (result.error) {
    return (
      <div className="rounded-xl border border-red-200 bg-red-50 p-8 text-center text-sm text-red-700">
        <AlertCircle className="mx-auto mb-2 h-6 w-6" />
        <p className="font-semibold">{result.error}</p>
        <button
          type="button"
          onClick={result.retry}
          className="mt-3 rounded-lg bg-red-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-700 cursor-pointer"
        >
          Coba Lagi
        </button>
      </div>
    );
  }

  const shown = result.data ?? result.staleData;
  if (!shown) {
    return (
      <div className="py-24 text-center text-sm text-slate-500">
        <Loader2 className="mx-auto mb-2 h-7 w-7 animate-spin text-blue-600" />
        Memuat data dashboard...
      </div>
    );
  }

  return (
    <div className="relative">
      {result.loading && (
        <div className="absolute inset-x-0 top-0 z-10 flex justify-center">
          <span className="mt-2 inline-flex items-center gap-2 rounded-full bg-white px-3 py-1 text-xs font-medium text-slate-600 shadow-md border border-slate-200">
            <Loader2 className="h-3.5 w-3.5 animate-spin text-blue-600" />
            Memperbarui data wilayah...
          </span>
        </div>
      )}
      <div className={`space-y-4 transition-opacity ${result.loading ? 'opacity-50 pointer-events-none' : ''}`}>
        {children(shown)}
      </div>
    </div>
  );
}

// ---------------------------------------------------------------------------

function RingkasanTab({ area, areaLabel }: { area: DashboardArea; areaLabel: string }) {
  const result = useDashboardData<RingkasanData>('ringkasan', getRingkasan, area);

  return (
    <TabState result={result}>
      {(d) => (
        <>
          <section className="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <StatCard
              icon={Users}
              accent="border-t-blue-600"
              iconColor="text-blue-600"
              label="Pencari Kerja"
              value={fmt(d.kpi.pencaker)}
              unit="orang"
              note={`Terdaftar aktif · ${areaLabel}`}
            />
            <StatCard
              icon={Briefcase}
              accent="border-t-emerald-500"
              iconColor="text-emerald-600"
              label="Lowongan"
              value={fmt(d.kpi.lowongan_kuota)}
              unit="formasi"
              note="Kuota tenaga kerja pada lowongan yang sedang tayang"
            />
            <StatCard
              icon={UserCheck}
              accent="border-t-violet-500"
              iconColor="text-violet-600"
              label="Diterima"
              value={fmt(d.kpi.diterima)}
              unit="lamaran"
              note="Lamaran dengan status diterima"
            />
          </section>

          <section className="grid grid-cols-1 lg:grid-cols-12 gap-4">
            <Card className="lg:col-span-5" title="Komposisi Status Bekerja" subtitle="Status pencari kerja saat mendaftar">
              <DonutChart items={d.status_bekerja} formatLabel={asIs} />
            </Card>
            <Card
              className="lg:col-span-7"
              title={d.top_wilayah.level === 'provinsi' ? 'Top 6 Provinsi Pencari Kerja' : 'Top 6 Kabupaten/Kota Pencari Kerja'}
              subtitle="Wilayah dengan jumlah pencari kerja terbanyak"
            >
              <HBarList items={d.top_wilayah.items} unit="orang" />
            </Card>
          </section>

          <section className="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <Card title="Top 6 Bidang Pekerjaan" subtitle="Bidang pekerjaan dengan kuota formasi terbanyak">
              <ColumnChart items={d.top_bidang} color="bg-emerald-500" formatLabel={asIs} />
            </Card>
            <Card title="Top 6 Sektor Industri" subtitle="Sektor industri dengan kuota formasi terbanyak">
              <ColumnChart items={d.top_industri} color="bg-blue-600" formatLabel={asIs} />
            </Card>
          </section>
        </>
      )}
    </TabState>
  );
}

function ProfilTab({ area }: { area: DashboardArea }) {
  const result = useDashboardData<ProfilPencakerData>('profil', getProfilPencaker, area);

  return (
    <TabState result={result}>
      {(d) => {
        const total = d.pendidikan.reduce((sum, row) => sum + row.value, 0);
        const share = (n: number) => (total ? `${pct(n / total)} dari pencari kerja` : '');

        return (
          <>
            <section className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
              <StatCard
                icon={School}
                accent="border-t-blue-600"
                iconColor="text-blue-600"
                label="Lulusan SMK"
                value={fmt(d.kpi.smk)}
                unit="orang"
                note={share(d.kpi.smk)}
              />
              <StatCard
                icon={GraduationCap}
                accent="border-t-emerald-500"
                iconColor="text-emerald-600"
                label="Sarjana S1"
                value={fmt(d.kpi.s1)}
                unit="orang"
                note={share(d.kpi.s1)}
              />
              <StatCard
                icon={Plane}
                accent="border-t-amber-400"
                iconColor="text-amber-500"
                label="Minat Pekerja Migran (PMI)"
                value={fmt(d.kpi.minat_pmi)}
                unit="orang"
                note={share(d.kpi.minat_pmi)}
              />
              <StatCard
                icon={Accessibility}
                accent="border-t-violet-500"
                iconColor="text-violet-600"
                label="Penyandang Disabilitas"
                value={fmt(d.kpi.disabilitas)}
                unit="orang"
                note={share(d.kpi.disabilitas)}
              />
            </section>

            <Card title="Distribusi Jenjang Pendidikan" subtitle="Pendidikan terakhir pencari kerja, dari jenjang terendah">
              <ColumnChart items={d.pendidikan} color="bg-blue-600" formatLabel={educationLabel} />
            </Card>

            <section className="grid grid-cols-1 lg:grid-cols-2 gap-4">
              <Card title="Kelompok Umur per Gender" subtitle="Jumlah pencari kerja per kelompok umur (tahun)">
                <GroupedColumnChart
                  groups={d.umur_gender.map((g) => ({ label: g.label, values: [g.laki_laki, g.perempuan] }))}
                  series={[
                    { name: 'Laki-laki', color: 'bg-blue-600' },
                    { name: 'Perempuan', color: 'bg-rose-400' },
                  ]}
                />
              </Card>
              <Card title="Negara Tujuan Pekerja Migran" subtitle="Negara tujuan yang diinginkan pencari kerja berminat kerja di luar negeri">
                <HBarList items={d.destinasi_pmi} color="bg-amber-500" unit="orang" formatLabel={asIs} />
              </Card>
            </section>

            <Card title="Top 10 Jurusan Pendidikan" subtitle="Jurusan dengan jumlah pencari kerja terbanyak">
              <Leaderboard items={d.top_jurusan} labelHeader="Jurusan" valueHeader="Pencari kerja" />
            </Card>
          </>
        );
      }}
    </TabState>
  );
}

function IndustriTab({ area }: { area: DashboardArea }) {
  const result = useDashboardData<KebutuhanIndustriData>('industri', getKebutuhanIndustri, area);

  return (
    <TabState result={result}>
      {(d) => (
        <>
          <section className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <StatCard
              icon={ClipboardList}
              accent="border-t-blue-600"
              iconColor="text-blue-600"
              label="Total Lowongan"
              value={fmt(d.kpi.loker)}
              unit="loker"
            />
            <StatCard
              icon={Building2}
              accent="border-t-emerald-500"
              iconColor="text-emerald-600"
              label="Perusahaan"
              value={fmt(d.kpi.perusahaan)}
              unit="perusahaan"
            />
            <StatCard
              icon={Users}
              accent="border-t-amber-400"
              iconColor="text-amber-500"
              label="Total Kuota"
              value={fmt(d.kpi.kuota)}
              unit="orang"
            />
            <StatCard
              icon={Calculator}
              accent="border-t-violet-500"
              iconColor="text-violet-600"
              label="Rata-rata Kuota"
              value={d.kpi.rata_kuota.toLocaleString('id-ID')}
              unit="orang / loker"
            />
          </section>

          <section className="grid grid-cols-1 lg:grid-cols-12 gap-4">
            <Card className="lg:col-span-7" title="Kebutuhan Tenaga Kerja per Sektor Industri" subtitle="Total kuota formasi per sektor (10 terbesar)">
              <HBarList items={d.kuota_per_industri} color="bg-blue-600" unit="orang" formatLabel={asIs} />
            </Card>
            <Card className="lg:col-span-5" title="Pola Waktu Kerja" subtitle="Jumlah lowongan menurut jenis pekerjaan">
              <DonutChart items={d.pola_waktu_kerja} formatLabel={asIs} />
            </Card>
          </section>

          <Card
            title="Keterampilan Paling Dibutuhkan"
            subtitle="Jumlah lowongan yang menyebut keterampilan tersebut di judul atau deskripsi pekerjaan"
          >
            <HBarList items={d.keterampilan} color="bg-emerald-500" unit="loker" formatLabel={asIs} />
          </Card>
        </>
      )}
    </TabState>
  );
}
