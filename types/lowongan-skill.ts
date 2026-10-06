export type TipeKeahlian = 'wajib' | 'diutamakan';

export interface LowonganSkillItem {
  esco_skill_id: number;
  title: string;
  title_en: string | null;
  tipe_keahlian: TipeKeahlian | null;
  skor: number | null;
  metode: 'leksikal' | 'semantik' | 'manual' | string | null;
  teks_bukti: string | null;
  versi: string | null;
}

export interface LokerSkillRow {
  vac_id: string;
  judul_pekerjaan: string | null;
  nama_perusahaan: string | null;
  bidang_pekerjaan: string | null;
  status_loker: string | null;
  jumlah_skill: number;
  skills: LowonganSkillItem[];
}

export interface LokerSkillDetail {
  vac_id: string;
  judul_pekerjaan: string | null;
  nama_perusahaan: string | null;
  bidang_pekerjaan: string | null;
  industri: string | null;
  status_loker: string | null;
  deskripsi_teks: string;
  skills: LowonganSkillItem[];
}

export interface LokerSkillListResponse {
  data: LokerSkillRow[];
  current_page: number;
  last_page: number;
  total: number;
}

export interface EscoSkillOption {
  id: number;
  code: string | null;
  title: string;
  title_en: string | null;
  type: string;
}
