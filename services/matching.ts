import {
  CandidateRecommendationItem,
  JobRecommendationItem,
  PairwiseAnalysisResponse,
  RecommendationPairItem,
} from '@/types/matching';
import { getAuthHeaders } from './auth';

const getApiBaseUrl = (): string => {
  if (typeof window !== 'undefined') {
    return process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000';
  }
  return process.env.API_URL || process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000';
};

/**
 * Mengambil daftar rekomendasi pasangan terpadu (Pelamar ⟷ Lowongan)
 */
export async function getUnifiedRecommendationsApi(
  filters: {
    job_seeker_id?: number | string;
    lowongan_id?: string;
    category?: string;
    min_score?: number | string;
    search?: string;
  } = {}
): Promise<{
  status: string;
  total_results: number;
  data: RecommendationPairItem[];
}> {
  const baseUrl = getApiBaseUrl();
  const params = new URLSearchParams();

  if (filters.job_seeker_id) params.set('job_seeker_id', String(filters.job_seeker_id));
  if (filters.lowongan_id) params.set('lowongan_id', String(filters.lowongan_id));
  if (filters.category) params.set('category', filters.category);
  if (filters.min_score !== undefined && filters.min_score !== '') params.set('min_score', String(filters.min_score));
  if (filters.search) params.set('search', filters.search);

  const res = await fetch(`${baseUrl}/api/rekomendasi/pairs?${params.toString()}`, {
    headers: getAuthHeaders(),
    cache: 'no-store',
  });

  if (!res.ok) {
    const json = await res.json().catch(() => ({}));
    throw new Error(json.message || `Gagal memuat rekomendasi terpadu (${res.status})`);
  }

  return res.json();
}

/**
 * Mengambil rekomendasi lowongan pekerjaan yang cocok untuk kandidat pencari kerja
 */
export async function getJobsForSeekerApi(
  jobSeekerId: number,
  filters: Record<string, string> = {}
): Promise<{
  status: string;
  job_seeker_id: number;
  total_results: number;
  data: JobRecommendationItem[];
}> {
  const baseUrl = getApiBaseUrl();
  const query = new URLSearchParams(filters);

  const res = await fetch(`${baseUrl}/api/rekomendasi/jobs-for-seeker/${jobSeekerId}?${query.toString()}`, {
    headers: getAuthHeaders(),
    cache: 'no-store',
  });

  if (!res.ok) {
    const json = await res.json().catch(() => ({}));
    throw new Error(json.message || `Gagal memuat rekomendasi lowongan (${res.status})`);
  }

  return res.json();
}

/**
 * Mengambil rekomendasi kandidat pencari kerja yang cocok untuk formasi lowongan kerja
 */
export async function getCandidatesForJobApi(
  lowonganId: string,
  filters: Record<string, string> = {}
): Promise<{
  status: string;
  lowongan_id: string;
  total_results: number;
  data: CandidateRecommendationItem[];
}> {
  const baseUrl = getApiBaseUrl();
  const query = new URLSearchParams(filters);

  const res = await fetch(`${baseUrl}/api/rekomendasi/candidates-for-job/${lowonganId}?${query.toString()}`, {
    headers: getAuthHeaders(),
    cache: 'no-store',
  });

  if (!res.ok) {
    const json = await res.json().catch(() => ({}));
    throw new Error(json.message || `Gagal memuat rekomendasi kandidat (${res.status})`);
  }

  return res.json();
}

/**
 * Analisis pairwise mendalam kesesuaian antara 1 kandidat dan 1 lowongan
 */
export async function getPairAnalysisApi(
  jobSeekerId: number,
  lowonganId: string
): Promise<PairwiseAnalysisResponse> {
  const baseUrl = getApiBaseUrl();
  const res = await fetch(
    `${baseUrl}/api/rekomendasi/analysis?job_seeker_id=${jobSeekerId}&lowongan_id=${encodeURIComponent(lowonganId)}`,
    {
      headers: getAuthHeaders(),
      cache: 'no-store',
    }
  );

  if (!res.ok) {
    const json = await res.json().catch(() => ({}));
    throw new Error(json.message || `Gagal menganalisis kesesuaian profil (${res.status})`);
  }

  const json = await res.json();
  return json.data;
}

/**
 * Mencari rekomendasi lowongan pekerjaan khusus untuk wizard operator
 */
export async function recommendLowonganApi(payload: {
  pencaker_id: number;
  pekerjaan?: string;
  judul_pekerjaan?: string;
  kbji_code?: string;
  provinsi_id?: string;
  kabkota_id?: string;
  skills: number[];
  filter_pendidikan?: boolean;
}): Promise<any[]> {
  const baseUrl = getApiBaseUrl();
  const res = await fetch(`${baseUrl}/api/rekomendasi/lowongan`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...getAuthHeaders(),
    },
    body: JSON.stringify(payload),
    cache: 'no-store',
  });

  if (!res.ok) {
    const json = await res.json().catch(() => ({}));
    throw new Error(json.message || `Gagal mencari rekomendasi lowongan (${res.status})`);
  }

  const json = await res.json();
  const list = json.data || [];
  Object.assign(list, {
    total_found: json.total_found ?? list.length,
    total_published_available: json.total_published_available ?? 0,
    keyword: json.keyword ?? payload.pekerjaan ?? '',
  });
  return list;
}
