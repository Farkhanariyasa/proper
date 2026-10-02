import {
  KbjiNode,
  KbjiChildrenResponse,
  KbjiDetail,
  KbjiSearchResponse,
  KbjiStats,
} from '@/types/kbji';
import { getApiBaseUrl } from '@/services/taxonomy';

/**
 * Mengambil ringkasan jumlah entri KBJI per level
 */
export async function getKbjiStats(): Promise<KbjiStats> {
  const res = await fetch(`${getApiBaseUrl()}/api/v1/kbji/stats`, {
    headers: { Accept: 'application/json' },
    cache: 'no-store',
  });

  if (!res.ok) {
    throw new Error(`Gagal memuat statistik KBJI (${res.status})`);
  }

  return res.json();
}

/**
 * Mengambil 10 Golongan Pokok KBJI (level teratas)
 */
export async function getKbjiMajorGroups(): Promise<KbjiNode[]> {
  const res = await fetch(`${getApiBaseUrl()}/api/v1/kbji`, {
    headers: { Accept: 'application/json' },
    cache: 'no-store',
  });

  if (!res.ok) {
    throw new Error(`Gagal memuat golongan pokok KBJI (${res.status})`);
  }

  const json = await res.json();
  return json.data || [];
}

/**
 * Mengambil turunan langsung dari suatu kode KBJI
 */
export async function getKbjiChildren(code: string): Promise<KbjiChildrenResponse> {
  const res = await fetch(
    `${getApiBaseUrl()}/api/v1/kbji/${encodeURIComponent(code)}/children`,
    {
      headers: { Accept: 'application/json' },
      cache: 'no-store',
    }
  );

  if (!res.ok) {
    throw new Error(`Gagal memuat turunan KBJI (${res.status})`);
  }

  return res.json();
}

/**
 * Mengambil detail lengkap suatu kode KBJI
 */
export async function getKbjiDetail(code: string): Promise<KbjiDetail> {
  const res = await fetch(`${getApiBaseUrl()}/api/v1/kbji/${encodeURIComponent(code)}`, {
    headers: { Accept: 'application/json' },
    cache: 'no-store',
  });

  if (!res.ok) {
    throw new Error(`Gagal memuat detail KBJI (${res.status})`);
  }

  const json = await res.json();
  return json.data;
}

/**
 * Pencarian jabatan KBJI berdasarkan kode atau judul
 */
export async function searchKbji(
  query: string,
  page: number = 1,
  limit: number = 20
): Promise<KbjiSearchResponse> {
  const params = new URLSearchParams({
    q: query,
    page: String(page),
    limit: String(limit),
  });

  const res = await fetch(`${getApiBaseUrl()}/api/v1/kbji/search?${params.toString()}`, {
    headers: { Accept: 'application/json' },
    cache: 'no-store',
  });

  if (!res.ok) {
    throw new Error(`Gagal melakukan pencarian KBJI (${res.status})`);
  }

  return res.json();
}
