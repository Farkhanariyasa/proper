export type ContractType = 'full_time' | 'part_time' | 'contract';

export type EducationLevelCode = 'SMA' | 'SMK' | 'D3' | 'S1';

export type ExperienceBucket =
  | '0-1 tahun'
  | '1-2 tahun'
  | '1-3 tahun'
  | '2-3 tahun'
  | '2-5 tahun'
  | '3-5 tahun';

export interface Vacancy {
  id: string;
  title: string;
  sector: string;
  province_id: string;
  regency_id: string;
  education: EducationLevelCode;
  contract: ContractType;
  experience: ExperienceBucket;
  month: number; // 1-12 (tahun 2026)
  formasi: number;
  skills: string[];
}

export interface WilayahProvince {
  id: string;
  name: string;
  regencies: { id: string; name: string }[];
}

export interface DashboardFilter {
  provinceId?: string;
  regencyId?: string;
}

export interface CountShare {
  label: string;
  value: number;
  share: number; // 0-1
}

export interface SectorContractRow {
  sector: string;
  total: number;
  full_time: number;
  part_time: number;
  contract: number;
}

export interface TopJob {
  title: string;
  sector: string;
  education: EducationLevelCode;
  formasi: number;
}

// Rekap pencari kerja per kabupaten/kota
export interface JobSeekerRegencySummary {
  province_id: string;
  regency_id: string;
  total: number;
  qualified: number; // skor kecocokan skill (gap) >= 90%
}

export interface LaborMarketDashboard {
  vacancyCount: number;
  totalFormasi: number;
  jobSeekers: { total: number; qualified: number; qualifiedShare: number };
  topSector: { name: string; formasi: number; share: number } | null;
  bySectorContract: SectorContractRow[];
  monthly: { month: number; formasi: number }[];
  education: CountShare[];
  experience: CountShare[];
  topJobs: TopJob[];
  topSkills: CountShare[];
}
