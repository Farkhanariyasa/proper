import {
  TaxonomyCategory,
  CategoryChildrenResponse,
  TaxonomyNodeDetail,
  SkillSearchResponse,
  TaxonomyStats,
} from '@/types/taxonomy';

const getApiBaseUrl = (): string => {
  if (typeof window !== 'undefined') {
    return process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000';
  }
  return process.env.API_URL || process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000';
};

/**
 * Mengambil ringkasan statistik taksonomi (Kategori, Skill, Relasi)
 */
export async function getTaxonomyStats(): Promise<TaxonomyStats> {
  const baseUrl = getApiBaseUrl();
  const res = await fetch(`${baseUrl}/api/v1/stats`, {
    headers: { Accept: 'application/json' },
    cache: 'no-store',
  });

  if (!res.ok) {
    throw new Error(`Gagal memuat statistik taksonomi (${res.status})`);
  }

  return res.json();
}

/**
 * Mengambil 4 pilar kategori utama (K, L, S, T)
 */
export async function getCategories(): Promise<TaxonomyCategory[]> {
  const baseUrl = getApiBaseUrl();
  const res = await fetch(`${baseUrl}/api/v1/categories`, {
    headers: { Accept: 'application/json' },
    cache: 'no-store',
  });

  if (!res.ok) {
    throw new Error(`Gagal memuat kategori taksonomi (${res.status})`);
  }

  const json = await res.json();
  return json.data || [];
}

/**
 * Mengambil anak langsung dari suatu node/kategori
 */
export async function getCategoryChildren(
  idOrCode: string | number
): Promise<CategoryChildrenResponse> {
  const baseUrl = getApiBaseUrl();
  const res = await fetch(`${baseUrl}/api/v1/categories/${idOrCode}/children`, {
    headers: { Accept: 'application/json' },
    cache: 'no-store',
  });

  if (!res.ok) {
    throw new Error(`Gagal memuat turunan kategori (${res.status})`);
  }

  return res.json();
}

/**
 * Mengambil detail lengkap suatu node taksonomi
 */
export async function getNodeDetail(id: number): Promise<TaxonomyNodeDetail> {
  const baseUrl = getApiBaseUrl();
  const res = await fetch(`${baseUrl}/api/v1/nodes/${id}`, {
    headers: { Accept: 'application/json' },
    cache: 'no-store',
  });

  if (!res.ok) {
    throw new Error(`Gagal memuat detail node (${res.status})`);
  }

  const json = await res.json();
  return json.data;
}

/**
 * Pencarian cepat keahlian berdasarkan kata kunci
 */
export async function searchSkills(
  query: string,
  page: number = 1,
  limit: number = 20,
  type: string = 'all'
): Promise<SkillSearchResponse> {
  const baseUrl = getApiBaseUrl();
  const params = new URLSearchParams({
    q: query,
    page: String(page),
    limit: String(limit),
    type,
  });

  const res = await fetch(`${baseUrl}/api/v1/skills/search?${params.toString()}`, {
    headers: { Accept: 'application/json' },
    cache: 'no-store',
  });

  if (!res.ok) {
    throw new Error(`Gagal melakukan pencarian keahlian (${res.status})`);
  }

  return res.json();
}
