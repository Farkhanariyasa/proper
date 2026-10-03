import {
  DUMMY_JOB_SEEKER_SUMMARY,
  DUMMY_VACANCIES,
  DUMMY_WILAYAH,
} from '@/lib/dummy/vacancies';
import {
  CountShare,
  DashboardFilter,
  JobSeekerRegencySummary,
  LaborMarketDashboard,
  SectorContractRow,
  TopJob,
  Vacancy,
  WilayahProvince,
} from '@/types/labor-market';

/**
 * Sementara masih membaca DATA DUMMY (lib/dummy/vacancies.ts).
 * Setelah API lowongan tersedia, cukup ganti isi kedua fungsi ini dengan
 * fetch ke backend — komponen dashboard tidak perlu diubah.
 */
export async function getDashboardWilayah(): Promise<WilayahProvince[]> {
  return DUMMY_WILAYAH;
}

export async function getLaborMarketDashboard(
  filter: DashboardFilter = {}
): Promise<LaborMarketDashboard> {
  const inArea = (item: { province_id: string; regency_id: string }) =>
    (!filter.provinceId || item.province_id === filter.provinceId) &&
    (!filter.regencyId || item.regency_id === filter.regencyId);

  return aggregate(
    DUMMY_VACANCIES.filter(inArea),
    DUMMY_JOB_SEEKER_SUMMARY.filter(inArea)
  );
}

function sumBy<K extends string>(vacancies: Vacancy[], key: (v: Vacancy) => K | K[]) {
  const totals = new Map<K, number>();
  for (const v of vacancies) {
    const keys = key(v);
    for (const k of Array.isArray(keys) ? keys : [keys]) {
      totals.set(k, (totals.get(k) ?? 0) + v.formasi);
    }
  }
  return totals;
}

function toShares(totals: Map<string, number>, totalFormasi: number): CountShare[] {
  return Array.from(totals, ([label, value]) => ({
    label,
    value,
    share: totalFormasi ? value / totalFormasi : 0,
  })).sort((a, b) => b.value - a.value);
}

function aggregate(
  vacancies: Vacancy[],
  jobSeekerSummary: JobSeekerRegencySummary[]
): LaborMarketDashboard {
  const totalFormasi = vacancies.reduce((sum, v) => sum + v.formasi, 0);
  const share = (value: number) => (totalFormasi ? value / totalFormasi : 0);

  // Sektor x tipe kontrak
  const sectorRows = new Map<string, SectorContractRow>();
  for (const v of vacancies) {
    const row = sectorRows.get(v.sector) ?? {
      sector: v.sector,
      total: 0,
      full_time: 0,
      part_time: 0,
      contract: 0,
    };
    row.total += v.formasi;
    row[v.contract] += v.formasi;
    sectorRows.set(v.sector, row);
  }
  const bySectorContract = Array.from(sectorRows.values()).sort((a, b) => b.total - a.total);
  const topRow = bySectorContract[0];

  const educationTotals = sumBy(vacancies, (v) => v.education);

  const jobSeekerTotal = jobSeekerSummary.reduce((sum, r) => sum + r.total, 0);
  const jobSeekerQualified = jobSeekerSummary.reduce((sum, r) => sum + r.qualified, 0);

  const monthTotals = sumBy(vacancies, (v) => String(v.month));
  const monthly = Array.from({ length: 12 }, (_, i) => ({
    month: i + 1,
    formasi: monthTotals.get(String(i + 1)) ?? 0,
  }));

  // Jabatan teratas (gabungan judul + sektor)
  const jobs = new Map<string, TopJob>();
  for (const v of vacancies) {
    const key = `${v.title}|${v.sector}`;
    const job = jobs.get(key) ?? { title: v.title, sector: v.sector, education: v.education, formasi: 0 };
    job.formasi += v.formasi;
    jobs.set(key, job);
  }
  const topJobs = Array.from(jobs.values())
    .sort((a, b) => b.formasi - a.formasi)
    .slice(0, 5);

  return {
    vacancyCount: vacancies.length,
    totalFormasi,
    jobSeekers: {
      total: jobSeekerTotal,
      qualified: jobSeekerQualified,
      qualifiedShare: jobSeekerTotal ? jobSeekerQualified / jobSeekerTotal : 0,
    },
    topSector: topRow
      ? { name: topRow.sector, formasi: topRow.total, share: share(topRow.total) }
      : null,
    bySectorContract,
    monthly,
    education: toShares(educationTotals, totalFormasi),
    experience: toShares(sumBy(vacancies, (v) => v.experience), totalFormasi),
    topJobs,
    topSkills: toShares(sumBy(vacancies, (v) => v.skills), totalFormasi).slice(0, 8),
  };
}
