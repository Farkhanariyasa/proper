import {
  EscoSkillOption,
  LokerSkillDetail,
  LokerSkillListResponse,
  LowonganSkillItem,
  TipeKeahlian,
} from '@/types/lowongan-skill';
import { getAuthHeaders } from './auth';

const getApiBaseUrl = (): string => {
  if (typeof window !== 'undefined') {
    return process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000';
  }
  return process.env.API_URL || process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000';
};

async function parseError(res: Response, fallback: string): Promise<never> {
  const json = await res.json().catch(() => null);
  throw new Error(json?.message || `${fallback} (${res.status})`);
}

export interface GetLokerSkillsParams {
  page?: number;
  search?: string;
  status?: 'all' | 'terpetakan' | 'kosong';
}

/** Daftar lowongan beserta skill ESCO hasil pemetaan */
export async function getLokerSkillsApi(params: GetLokerSkillsParams = {}): Promise<LokerSkillListResponse> {
  const query = new URLSearchParams();
  if (params.page) query.set('page', String(params.page));
  if (params.search) query.set('search', params.search);
  if (params.status && params.status !== 'all') query.set('status', params.status);

  const res = await fetch(`${getApiBaseUrl()}/api/admin/lowongan-skills?${query.toString()}`, {
    headers: getAuthHeaders(),
    cache: 'no-store',
  });
  if (!res.ok) return parseError(res, 'Gagal memuat pemetaan skill lowongan');
  return res.json();
}

/** Detail satu lowongan (deskripsi teks polos + skill) */
export async function getLokerSkillDetailApi(vacId: string): Promise<LokerSkillDetail> {
  const res = await fetch(`${getApiBaseUrl()}/api/admin/lowongan-skills/${encodeURIComponent(vacId)}`, {
    headers: getAuthHeaders(),
    cache: 'no-store',
  });
  if (!res.ok) return parseError(res, 'Gagal memuat detail lowongan');
  return (await res.json()).data;
}

/** Simpan daftar skill lowongan (array kosong = kosongkan semua) */
export async function updateLokerSkillsApi(
  vacId: string,
  skills: { esco_skill_id: number; tipe_keahlian: TipeKeahlian }[]
): Promise<LowonganSkillItem[]> {
  const res = await fetch(`${getApiBaseUrl()}/api/admin/lowongan-skills/${encodeURIComponent(vacId)}`, {
    method: 'PUT',
    headers: getAuthHeaders(),
    body: JSON.stringify({ skills }),
  });
  if (!res.ok) return parseError(res, 'Gagal menyimpan pemetaan skill');
  return (await res.json()).data;
}

/** Cari skill ESCO (hanya tipe 'skill', bukan kategori/konsep) */
export async function searchEscoSkillsApi(q: string): Promise<EscoSkillOption[]> {
  const res = await fetch(`${getApiBaseUrl()}/api/esco-skills?q=${encodeURIComponent(q)}&limit=30`, {
    headers: { Accept: 'application/json' },
    cache: 'no-store',
  });
  if (!res.ok) return parseError(res, 'Gagal mencari skill ESCO');
  const json = await res.json();
  return (json.data as EscoSkillOption[]).filter((s) => s.type === 'skill');
}
