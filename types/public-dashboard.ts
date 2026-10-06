export interface LabelValue {
  label: string | null;
  value: number;
}

export interface DashboardRegion {
  name: string;
  regencies: string[];
}

export interface DashboardArea {
  tahun?: string;
  provinsi?: string;
  kabKota?: string;
}

export interface RingkasanData {
  kpi: {
    pencaker: number;
    lowongan_kuota: number;
    diterima: number;
  };
  status_bekerja: LabelValue[];
  top_wilayah: { level: 'provinsi' | 'kab_kota'; items: LabelValue[] };
  top_bidang: LabelValue[];
  top_industri: LabelValue[];
}

export interface ProfilPencakerData {
  kpi: { smk: number; s1: number; minat_pmi: number; disabilitas: number };
  pendidikan: LabelValue[];
  umur_gender: { label: string; laki_laki: number; perempuan: number }[];
  destinasi_pmi: LabelValue[];
  top_jurusan: LabelValue[];
}

export interface KebutuhanIndustriData {
  kpi: { loker: number; perusahaan: number; kuota: number; rata_kuota: number };
  kuota_per_industri: LabelValue[];
  pola_waktu_kerja: LabelValue[];
  keterampilan: LabelValue[];
}
