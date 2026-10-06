import {
  CreateJobSeekerPayload,
  EducationLevel,
  EscoSkillItem,
  JobSeeker,
  JobSeekerOptionsResponse,
} from '@/types/job-seeker';

const getApiBaseUrl = (): string => {
  if (typeof window !== 'undefined') {
    return process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000';
  }
  return process.env.API_URL || process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000';
};

/**
 * Mengambil master jenjang pendidikan
 */
export async function getEducationLevels(): Promise<EducationLevel[]> {
  const baseUrl = getApiBaseUrl();
  const res = await fetch(`${baseUrl}/api/education-levels`, {
    headers: { Accept: 'application/json' },
    cache: 'no-store',
  });

  if (!res.ok) {
    throw new Error(`Gagal memuat jenjang pendidikan (${res.status})`);
  }

  const json = await res.json();
  return json.data || [];
}

/**
 * Autocomplete pencarian skill ESCO
 */
export async function searchEscoSkills(query: string, limit: number = 20): Promise<EscoSkillItem[]> {
  const baseUrl = getApiBaseUrl();
  const res = await fetch(
    `${baseUrl}/api/esco-skills?q=${encodeURIComponent(query)}&limit=${limit}`,
    {
      headers: { Accept: 'application/json' },
      cache: 'no-store',
    }
  );

  if (!res.ok) {
    throw new Error(`Gagal mencari skill ESCO (${res.status})`);
  }

  const json = await res.json();
  return json.data || [];
}

/**
 * Mengambil opsi standar (rumpun pendidikan & rentang pengalaman)
 */
export async function getJobSeekerOptions(): Promise<JobSeekerOptionsResponse> {
  const baseUrl = getApiBaseUrl();
  const res = await fetch(`${baseUrl}/api/job-seekers/options`, {
    headers: { Accept: 'application/json' },
    cache: 'no-store',
  });

  if (!res.ok) {
    throw new Error(`Gagal memuat opsi pencari kerja (${res.status})`);
  }

  const json = await res.json();
  return json.data;
}

/**
 * Mengambil list pencari kerja dengan pagination & filter
 */
export async function getJobSeekers(params?: {
  q?: string;
  province_id?: string;
  regency_id?: string;
  study_field_group?: string;
  experience_range?: string;
  page?: number;
  per_page?: number;
}): Promise<{
  data: JobSeeker[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}> {
  const baseUrl = getApiBaseUrl();
  const query = new URLSearchParams();

  if (params?.q) query.set('q', params.q);
  if (params?.province_id) query.set('province_id', params.province_id);
  if (params?.regency_id) query.set('regency_id', params.regency_id);
  if (params?.study_field_group) query.set('study_field_group', params.study_field_group);
  if (params?.experience_range) query.set('experience_range', params.experience_range);
  if (params?.page) query.set('page', String(params.page));
  if (params?.per_page) query.set('per_page', String(params.per_page));

  const url = `${baseUrl}/api/job-seekers?${query.toString()}`;
  const res = await fetch(url, {
    headers: { Accept: 'application/json' },
    cache: 'no-store',
  });

  if (!res.ok) {
    throw new Error(`Gagal memuat daftar pencari kerja (${res.status})`);
  }

  return res.json();
}

/**
 * Mengambil detail pencari kerja
 */
export async function getJobSeekerDetail(id: number | string): Promise<JobSeeker> {
  const baseUrl = getApiBaseUrl();
  const res = await fetch(`${baseUrl}/api/job-seekers/${id}`, {
    headers: { Accept: 'application/json' },
    cache: 'no-store',
  });

  if (!res.ok) {
    throw new Error(`Gagal memuat detail pencari kerja (${res.status})`);
  }

  const json = await res.json();
  return json.data;
}

/**
 * Mendaftarkan profil pencari kerja baru
 */
export async function createJobSeeker(payload: CreateJobSeekerPayload): Promise<JobSeeker> {
  const baseUrl = getApiBaseUrl();
  const res = await fetch(`${baseUrl}/api/job-seekers`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
    },
    body: JSON.stringify(payload),
  });

  const json = await res.json();

  if (!res.ok) {
    const firstError = json.errors
      ? ((Object.values(json.errors) as unknown as string[][])?.[0]?.[0])
      : undefined;
    const errorMsg = json.message || firstError || 'Gagal menyimpan profil';
    const err = new Error(errorMsg) as Error & { errors?: Record<string, string[]> };
    err.errors = json.errors;
    throw err;
  }

  return json.data;
}

/**
 * Mengambil skill pencari kerja (akan auto extract jika kosong)
 */
export async function getJobSeekerSkills(id: number | string): Promise<EscoSkillItem[]> {
  const baseUrl = getApiBaseUrl();
  const res = await fetch(`${baseUrl}/api/job-seekers/${id}/skills`, {
    headers: { Accept: 'application/json' },
    cache: 'no-store',
  });

  if (!res.ok) {
    throw new Error(`Gagal memuat skill pencari kerja (${res.status})`);
  }

  const json = await res.json();
  return json.data;
}

/**
 * Menyimpan update skill pencari kerja
 */
export async function updateJobSeekerSkills(id: number | string, skills: number[]): Promise<EscoSkillItem[]> {
  const baseUrl = getApiBaseUrl();
  const res = await fetch(`${baseUrl}/api/job-seekers/${id}/skills`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
    },
    body: JSON.stringify({ skills }),
  });

  if (!res.ok) {
    throw new Error(`Gagal menyimpan skill pencari kerja (${res.status})`);
  }

  const json = await res.json();
  return json.data;
}
