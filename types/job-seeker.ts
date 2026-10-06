export interface Province {
  id: string;
  name: string;
}

export interface Regency {
  id: string;
  province_id: string;
  name: string;
  province?: Province;
}

export interface EducationLevel {
  id: number;
  name: string;
  sort_order: number;
}

export interface TrainingItem {
  name: string;
  organizer?: string;
  year?: number | null;
}

export interface CertificationItem {
  name: string;
  type?: string;
  year?: number | null;
}

export interface EscoSkillItem {
  id: number;
  code?: string | null;
  title: string;
  title_en: string;
  type: string;
  description?: string;
  source?: string;
  pivot?: {
    pencaker_id?: number;
    esco_skill_id?: number;
    is_manual?: boolean;
    source?: string;
    created_at?: string;
    updated_at?: string;
  };
}

export interface JobSeeker {
  id: number;
  name?: string;
  profile_id?: string;
  nik: string;
  full_name?: string;
  phone?: string;
  birth_date?: string;
  gender?: 'L' | 'P';
  umur?: number;
  jenis_kelamin?: string;
  marital?: string;
  kondisi_fisik?: string;
  jenis_disabilitas?: string;
  provinsi?: string;
  province_id?: string;
  kab_kota?: string;
  regency_id?: string;
  region_name?: string;
  pendidikan?: string;
  education_level_id?: number;
  nama_sekolah?: string;
  jurusan?: string;
  experience?: string;
  sertifikasi?: string;
  lembaga_pelatihan?: string;
  progpel?: string;
  keahlian?: string;
  bahasa?: string;
  status_bekerja?: string;
  status_sekarang?: string;
  rencana_kerja_luar_negeri?: string;
  country_wish?: string;
  lamaran_diajukan?: number;
  start_date?: string;
  recent_start?: string;
  tanggal_kedaluwarsa?: string;
  study_field_group?: string;
  study_field_detail?: string | null;
  experience_range?: 'fresh_graduate' | '<1' | '1-3' | '3-5' | '>5';
  desired_occupation?: string | null;
  kbji_id?: number | null;
  trainings?: TrainingItem[] | null;
  certifications?: CertificationItem[] | null;
  created_by?: number | null;
  created_at?: string;
  updated_at?: string;
  skills_count?: number;
  regency?: Regency;
  education_level?: EducationLevel;
  kbji?: {
    id: number;
    code: string;
    title: string;
    description?: string;
  } | null;
  skills?: EscoSkillItem[];
}

export interface CreateJobSeekerPayload {
  nik: string;
  full_name: string;
  phone: string;
  birth_date: string;
  gender: 'L' | 'P';
  regency_id: string;
  education_level_id: number;
  study_field_group: string;
  study_field_detail?: string;
  experience_range: 'fresh_graduate' | '<1' | '1-3' | '3-5' | '>5';
  desired_occupation?: string;
  kbji_id?: number | null;
  trainings?: TrainingItem[];
  certifications?: CertificationItem[];
  skills: number[];
}

export interface JobSeekerOptionsResponse {
  study_field_groups: string[];
  experience_ranges: string[];
  pendidikan: string[];
}
