import {
  DashboardArea,
  DashboardRegion,
  KebutuhanIndustriData,
  ProfilPencakerData,
  RingkasanData,
} from '@/types/public-dashboard';

const getApiBaseUrl = (): string => {
  if (typeof window !== 'undefined') {
    return process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000';
  }
  return process.env.API_URL || process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000';
};

async function fetchDashboard<T>(path: string, area: DashboardArea = {}): Promise<T> {
  const params = new URLSearchParams();
  if (area.tahun) params.set('tahun', area.tahun);
  if (area.provinsi) params.set('provinsi', area.provinsi);
  if (area.provinsi && area.kabKota) params.set('kab_kota', area.kabKota);
  const query = params.toString();

  const res = await fetch(
    `${getApiBaseUrl()}/api/public/dashboard/${path}${query ? `?${query}` : ''}`,
    { headers: { Accept: 'application/json' }, cache: 'no-store' }
  );

  if (!res.ok) {
    throw new Error(`Gagal memuat data dashboard (${res.status})`);
  }

  const json = await res.json();
  return json.data as T;
}

/** Daftar tahun yang tersedia (terbaru dulu) */
export const getDashboardYears = () => fetchDashboard<number[]>('years');

/** Daftar provinsi & kabupaten/kota yang tersedia pada data pencaker */
export const getDashboardRegions = () => fetchDashboard<DashboardRegion[]>('regions');

/** Tab 1 — Ringkasan Nasional */
export const getRingkasan = (area: DashboardArea) =>
  fetchDashboard<RingkasanData>('ringkasan', area);

/** Tab 2 — Profil Pencari Kerja */
export const getProfilPencaker = (area: DashboardArea) =>
  fetchDashboard<ProfilPencakerData>('profil-pencaker', area);

/** Tab 3 — Kebutuhan Industri */
export const getKebutuhanIndustri = (area: DashboardArea) =>
  fetchDashboard<KebutuhanIndustriData>('kebutuhan-industri', area);
