export interface LowonganSkillItem {
  id: number;
  title: string;
  title_en?: string;
  tipe_keahlian: 'wajib' | 'tambahan';
  level_kemahiran: 'pemula' | 'menengah' | 'ahli';
}

export interface LowonganItem {
  id: string;
  slug: string;
  judul_lowongan: string;
  nama_perusahaan: string;
  deskripsi_pekerjaan: string;
  tipe_pekerjaan: string;
  sistem_kerja: string;
  jumlah_kebutuhan: number;
  jurusan_studi: string | null;
  pengalaman_minimal_tahun: number;
  usia_minimal: number | null;
  usia_maksimal: number | null;
  jenis_kelamin: string;
  is_disabilitas: boolean;
  persyaratan_tambahan: string | null;
  alamat_lengkap_penempatan: string | null;
  gaji_tampilkan: boolean;
  gaji_minimal: number | null;
  gaji_maksimal: number | null;
  status_lowongan: 'Draft' | 'Published' | 'Closed' | 'Archived';
  tanggal_buka: string | null;
  tanggal_tutup: string;
  created_at: string;
  updated_at: string;
  kbji?: {
    id: number;
    code: string;
    title: string;
    level: string;
    isco_code?: string | null;
  };
  education_level?: {
    id: number;
    name: string;
  };
  province?: {
    id: string;
    name: string;
  };
  regency?: {
    id: string;
    name: string;
  };
  skills?: LowonganSkillItem[];
  creator?: {
    id: number;
    name: string;
  };
}

export interface CreateLowonganPayload {
  judul_lowongan: string;
  nama_perusahaan: string;
  kbji_id: number;
  deskripsi_pekerjaan: string;
  tipe_pekerjaan: string;
  sistem_kerja: string;
  jumlah_kebutuhan: number;
  education_level_id: number;
  jurusan_studi?: string;
  pengalaman_minimal_tahun: number;
  usia_minimal?: number;
  usia_maksimal?: number;
  jenis_kelamin: string;
  is_disabilitas?: boolean;
  persyaratan_tambahan?: string;
  provinsi_id: string;
  regency_id: string;
  alamat_lengkap_penempatan?: string;
  gaji_tampilkan?: boolean;
  gaji_minimal?: number;
  gaji_maksimal?: number;
  status_lowongan?: 'Draft' | 'Published' | 'Closed' | 'Archived';
  tanggal_buka?: string;
  tanggal_tutup: string;
  skills: Array<{
    esco_skill_id: number;
    tipe_keahlian: 'wajib' | 'tambahan';
    level_kemahiran: 'pemula' | 'menengah' | 'ahli';
  }>;
}

export type UpdateLowonganPayload = Partial<CreateLowonganPayload>;

export interface LowonganListParams {
  search?: string;
  provinsi_id?: string;
  regency_id?: string;
  tipe_pekerjaan?: string;
  sistem_kerja?: string;
  status_lowongan?: string;
  education_level_id?: number | string;
  kbji_id?: number | string;
  page?: number;
  per_page?: number;
}

export interface LowonganListResponse {
  status: string;
  data: LowonganItem[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

export interface LowonganOptionsResponse {
  status: string;
  data: {
    tipe_pekerjaan: string[];
    sistem_kerja: string[];
    jenis_kelamin: string[];
    status_lowongan: string[];
    tipe_keahlian: string[];
    level_kemahiran: string[];
  };
}

export interface KbjiOption {
  id: number;
  code: string;
  title: string;
  level: string;
  iscoCode?: string | null;
  hasChildren: boolean;
}
