export interface MatchedSkillItem {
  id: number;
  title: string;
  title_en?: string;
  tipe_keahlian: 'wajib' | 'tambahan';
  level_kemahiran: 'pemula' | 'menengah' | 'ahli';
  match_type: string; // Exact Match, Padanan Taksonomi (Hierarki), Padanan Leksikal
  matched_with?: string;
  status_verifikasi?: string;
}

export interface GapSkillItem {
  id: number;
  title: string;
  title_en?: string;
  tipe_keahlian: 'wajib' | 'tambahan';
  level_kemahiran: 'pemula' | 'menengah' | 'ahli';
  status?: string;
}

export interface MatchClassification {
  category: 'perfect' | 'high' | 'gap' | 'low';
  label: string;
  badge_color: 'emerald' | 'blue' | 'amber' | 'slate';
  action_label: string;
  action_key: 'recommend_company' | 'skill_gap_details' | 'career_guidance';
  description: string;
}

export interface ScoreBreakdown {
  role_score: number;
  skill_score: number;
  education_score: number;
  experience_score: number;
  location_score: number;
}

export interface EducationMatchInfo {
  is_matched: boolean;
  score: number;
  status: string;
  label: string;
  candidate_level_name?: string;
  required_level_name?: string;
  reason?: string;
}

export interface KbjiMatchInfo {
  is_compatible: boolean;
  match_level: 'exact' | 'unit_group' | 'sub_major' | 'different_domain' | 'unspecified';
  label: string;
  code: string | null;
  compatibility_score: number;
}

export interface JobRecommendationItem {
  job_id: string;
  slug: string;
  judul_lowongan: string;
  nama_perusahaan: string;
  tipe_pekerjaan: string;
  sistem_kerja: string;
  kbji?: {
    id: number;
    code: string;
    title: string;
  } | null;
  education_level?: string;
  location?: string;
  gaji_tampilkan: boolean;
  gaji_minimal?: number | null;
  gaji_maksimal?: number | null;
  tanggal_tutup?: string;
  status_lowongan: string;
  score: number;
  total_required: number;
  total_matched: number;
  total_gap: number;
  classification: MatchClassification;
  kbji_match?: KbjiMatchInfo;
  matched_skills: MatchedSkillItem[];
  gap_skills: GapSkillItem[];
}

export interface CandidateRecommendationItem {
  candidate_id: number;
  full_name: string;
  nik: string;
  phone?: string;
  gender?: string;
  education_level?: string;
  study_field_group?: string;
  experience_range?: string;
  desired_occupation?: string;
  kbji?: {
    id?: number;
    code: string;
    title: string;
  } | null;
  location?: string;
  score: number;
  total_required: number;
  total_matched: number;
  total_gap: number;
  classification: MatchClassification;
  kbji_match?: KbjiMatchInfo;
  matched_skills: MatchedSkillItem[];
  gap_skills: GapSkillItem[];
}

export interface PairwiseAnalysisResponse {
  candidate: {
    id: number;
    full_name: string;
    nik: string;
    phone?: string;
    education_level?: string;
    desired_occupation?: string;
    kbji?: {
      code: string;
      title: string;
    } | null;
    location?: string;
  };
  job: {
    id: string;
    judul_lowongan: string;
    nama_perusahaan: string;
    tipe_pekerjaan: string;
    sistem_kerja: string;
    kbji?: {
      code: string;
      title: string;
    } | null;
    education_level?: string;
    location?: string;
    status_lowongan: string;
  };
  score: number;
  total_required: number;
  total_matched: number;
  total_gap: number;
  classification: MatchClassification;
  kbji_match?: KbjiMatchInfo;
  education_match?: EducationMatchInfo;
  score_breakdown?: ScoreBreakdown;
  matched_skills: MatchedSkillItem[];
  gap_skills: GapSkillItem[];
}

export interface RecommendationPairItem {
  id: string; // `${candidate.id}_${job.id}`
  score: number;
  total_required: number;
  total_matched: number;
  total_gap: number;
  classification: MatchClassification;
  kbji_match?: KbjiMatchInfo;
  candidate: {
    id: number;
    nik: string;
    full_name: string;
    phone?: string;
    education_level?: string;
    desired_occupation?: string;
    kbji?: {
      id?: number;
      code: string;
      title: string;
    } | null;
    location: string;
  };
  job: {
    id: string;
    slug: string;
    judul_lowongan: string;
    nama_perusahaan: string;
    tipe_pekerjaan: string;
    sistem_kerja: string;
    kbji?: {
      id?: number;
      code: string;
      title: string;
    } | null;
    education_level?: string;
    location: string;
    gaji_tampilkan: boolean;
    gaji_minimal?: number | null;
    gaji_maksimal?: number | null;
  };
  matched_skills: MatchedSkillItem[];
  gap_skills: GapSkillItem[];
}
