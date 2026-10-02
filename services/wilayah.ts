import { Province, Regency } from '@/types/job-seeker';

const getApiBaseUrl = (): string => {
  if (typeof window !== 'undefined') {
    return process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000';
  }
  return process.env.API_URL || process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000';
};

/**
 * Mengambil daftar seluruh provinsi
 */
export async function getProvinces(): Promise<Province[]> {
  const baseUrl = getApiBaseUrl();
  const res = await fetch(`${baseUrl}/api/provinces`, {
    headers: { Accept: 'application/json' },
    cache: 'no-store',
  });

  if (!res.ok) {
    throw new Error(`Gagal memuat data provinsi (${res.status})`);
  }

  const json = await res.json();
  return json.data || [];
}

/**
 * Mengambil daftar kabupaten/kota (opsional berdasarkan province_id)
 */
export async function getRegencies(provinceId?: string): Promise<Regency[]> {
  const baseUrl = getApiBaseUrl();
  const url = provinceId
    ? `${baseUrl}/api/regencies?province_id=${encodeURIComponent(provinceId)}`
    : `${baseUrl}/api/regencies`;

  const res = await fetch(url, {
    headers: { Accept: 'application/json' },
    cache: 'no-store',
  });

  if (!res.ok) {
    throw new Error(`Gagal memuat data kabupaten/kota (${res.status})`);
  }

  const json = await res.json();
  return json.data || [];
}
