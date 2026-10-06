import {
  CreateLowonganPayload,
  KbjiOption,
  LowonganItem,
  LowonganListParams,
  LowonganListResponse,
  LowonganOptionsResponse,
  LowonganStatus,
  UpdateLowonganPayload,
} from '@/types/lowongan';
import { getAuthHeaders } from './auth';

const getApiBaseUrl = (): string => {
  if (typeof window !== 'undefined') {
    return process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000';
  }
  return process.env.API_URL || process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000';
};

/**
 * Mengambil daftar lowongan dengan filter dan pagination
 */
export async function getLowonganListApi(
  params: LowonganListParams = {}
): Promise<LowonganListResponse> {
  const baseUrl = getApiBaseUrl();
  const query = new URLSearchParams();

  if (params.search) query.append('search', params.search);
  if (params.provinsi_id) query.append('provinsi_id', params.provinsi_id);
  if (params.regency_id) query.append('regency_id', params.regency_id);
  if (params.tipe_pekerjaan) query.append('tipe_pekerjaan', params.tipe_pekerjaan);
  if (params.sistem_kerja) query.append('sistem_kerja', params.sistem_kerja);
  if (params.status_lowongan) query.append('status_lowongan', params.status_lowongan);
  if (params.education_level_id) query.append('education_level_id', String(params.education_level_id));
  if (params.kbji_id) query.append('kbji_id', String(params.kbji_id));
  if (params.page) query.append('page', String(params.page));
  if (params.per_page) query.append('per_page', String(params.per_page));

  const res = await fetch(`${baseUrl}/api/lowongan?${query.toString()}`, {
    headers: getAuthHeaders(),
    cache: 'no-store',
  });

  if (!res.ok) {
    const json = await res.json().catch(() => ({}));
    throw new Error(json.message || `Gagal memuat daftar lowongan (${res.status})`);
  }

  return res.json();
}

/**
 * Mengambil detail satu lowongan berdasarkan ID atau Slug
 */
export async function getLowonganDetailApi(idOrSlug: string): Promise<LowonganItem> {
  const baseUrl = getApiBaseUrl();
  const res = await fetch(`${baseUrl}/api/lowongan/${idOrSlug}`, {
    headers: getAuthHeaders(),
    cache: 'no-store',
  });

  if (!res.ok) {
    const json = await res.json().catch(() => ({}));
    throw new Error(json.message || `Gagal memuat detail lowongan (${res.status})`);
  }

  const json = await res.json();
  return json.data;
}

/**
 * Menambahkan lowongan baru
 */
export async function createLowonganApi(
  payload: CreateLowonganPayload
): Promise<LowonganItem> {
  const baseUrl = getApiBaseUrl();
  const res = await fetch(`${baseUrl}/api/lowongan`, {
    method: 'POST',
    headers: getAuthHeaders(),
    body: JSON.stringify(payload),
  });

  const json = await res.json().catch(() => ({}));
  if (!res.ok) {
    if (json.errors) {
      const errorMsg = Object.values(json.errors).flat().join(', ');
      throw new Error(errorMsg || json.message || 'Validasi gagal');
    }
    throw new Error(json.message || `Gagal menambahkan lowongan (${res.status})`);
  }

  return json.data;
}

/**
 * Memperbarui data lowongan
 */
export async function updateLowonganApi(
  id: string,
  payload: UpdateLowonganPayload
): Promise<LowonganItem> {
  const baseUrl = getApiBaseUrl();
  const res = await fetch(`${baseUrl}/api/lowongan/${id}`, {
    method: 'PUT',
    headers: getAuthHeaders(),
    body: JSON.stringify(payload),
  });

  const json = await res.json().catch(() => ({}));
  if (!res.ok) {
    if (json.errors) {
      const errorMsg = Object.values(json.errors).flat().join(', ');
      throw new Error(errorMsg || json.message || 'Validasi gagal');
    }
    throw new Error(json.message || `Gagal memperbarui lowongan (${res.status})`);
  }

  return json.data;
}

/**
 * Menghapus lowongan
 */
export async function deleteLowonganApi(id: string): Promise<void> {
  const baseUrl = getApiBaseUrl();
  const res = await fetch(`${baseUrl}/api/lowongan/${id}`, {
    method: 'DELETE',
    headers: getAuthHeaders(),
  });

  if (!res.ok) {
    const json = await res.json().catch(() => ({}));
    throw new Error(json.message || `Gagal menghapus lowongan (${res.status})`);
  }
}

/**
 * Mengubah status lowongan langsung
 */
export async function toggleLowonganStatusApi(
  id: string,
  status: LowonganStatus
): Promise<void> {
  const baseUrl = getApiBaseUrl();
  const res = await fetch(`${baseUrl}/api/lowongan/${id}/status`, {
    method: 'PATCH',
    headers: getAuthHeaders(),
    body: JSON.stringify({ status_lowongan: status }),
  });

  if (!res.ok) {
    const json = await res.json().catch(() => ({}));
    throw new Error(json.message || `Gagal mengubah status lowongan (${res.status})`);
  }
}

/**
 * Mengambil opsi standar untuk form dropdown
 */
export async function getLowonganOptionsApi(): Promise<LowonganOptionsResponse['data']> {
  const baseUrl = getApiBaseUrl();
  const res = await fetch(`${baseUrl}/api/lowongan/options`, {
    headers: { Accept: 'application/json' },
    cache: 'no-store',
  });

  if (!res.ok) {
    throw new Error(`Gagal memuat opsi form lowongan (${res.status})`);
  }

  const json = await res.json();
  return json.data;
}

/**
 * Pencarian jabatan KBJI (mengembalikan id, code, title, level)
 */
export async function searchKbjiApi(query: string): Promise<KbjiOption[]> {
  const baseUrl = getApiBaseUrl();
  const res = await fetch(`${baseUrl}/api/v1/kbji/search?q=${encodeURIComponent(query)}&limit=15`, {
    headers: { Accept: 'application/json' },
    cache: 'no-store',
  });

  if (!res.ok) {
    return [];
  }

  const json = await res.json();
  return json.data || [];
}
