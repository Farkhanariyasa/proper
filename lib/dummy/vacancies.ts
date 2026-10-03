/**
 * DATA DUMMY lowongan kerja & pencari kerja untuk dashboard publik.
 *
 * Dibangkitkan secara deterministik (seeded) sehingga angka selalu sama di
 * setiap render. Ganti dengan pemanggilan API sungguhan di
 * `services/labor-market.ts` setelah tabel lowongan tersedia di backend.
 * ID provinsi & kabupaten/kota mengikuti master wilayah (Kepmendagri).
 */
import {
  ContractType,
  EducationLevelCode,
  ExperienceBucket,
  JobSeekerRegencySummary,
  Vacancy,
  WilayahProvince,
} from '@/types/labor-market';

export const DUMMY_WILAYAH: WilayahProvince[] = [
  {
    id: '31',
    name: 'DKI Jakarta',
    regencies: [
      { id: '31.71', name: 'Kota Adm. Jakarta Pusat' },
      { id: '31.73', name: 'Kota Adm. Jakarta Barat' },
      { id: '31.74', name: 'Kota Adm. Jakarta Selatan' },
    ],
  },
  {
    id: '32',
    name: 'Jawa Barat',
    regencies: [
      { id: '32.73', name: 'Kota Bandung' },
      { id: '32.75', name: 'Kota Bekasi' },
      { id: '32.16', name: 'Kabupaten Bekasi' },
    ],
  },
  {
    id: '36',
    name: 'Banten',
    regencies: [
      { id: '36.71', name: 'Kota Tangerang' },
      { id: '36.03', name: 'Kabupaten Tangerang' },
    ],
  },
  {
    id: '33',
    name: 'Jawa Tengah',
    regencies: [
      { id: '33.74', name: 'Kota Semarang' },
      { id: '33.72', name: 'Kota Surakarta' },
    ],
  },
  {
    id: '34',
    name: 'DI Yogyakarta',
    regencies: [
      { id: '34.71', name: 'Kota Yogyakarta' },
      { id: '34.04', name: 'Kabupaten Sleman' },
    ],
  },
  {
    id: '35',
    name: 'Jawa Timur',
    regencies: [
      { id: '35.78', name: 'Kota Surabaya' },
      { id: '35.73', name: 'Kota Malang' },
      { id: '35.15', name: 'Kabupaten Sidoarjo' },
    ],
  },
  {
    id: '51',
    name: 'Bali',
    regencies: [
      { id: '51.71', name: 'Kota Denpasar' },
      { id: '51.03', name: 'Kabupaten Badung' },
    ],
  },
  {
    id: '12',
    name: 'Sumatera Utara',
    regencies: [
      { id: '12.71', name: 'Kota Medan' },
      { id: '12.07', name: 'Kabupaten Deli Serdang' },
    ],
  },
  {
    id: '64',
    name: 'Kalimantan Timur',
    regencies: [
      { id: '64.71', name: 'Kota Balikpapan' },
      { id: '64.72', name: 'Kota Samarinda' },
    ],
  },
  {
    id: '73',
    name: 'Sulawesi Selatan',
    regencies: [
      { id: '73.71', name: 'Kota Makassar' },
      { id: '73.08', name: 'Kabupaten Bone' },
    ],
  },
];

// Bobot jumlah lowongan per provinsi (kota besar lebih banyak)
const PROVINCE_WEIGHT: Record<string, number> = {
  '31': 22, '32': 18, '36': 10, '33': 9, '34': 5,
  '35': 15, '51': 7, '12': 6, '64': 4, '73': 4,
};

interface JobTemplate {
  title: string;
  education: EducationLevelCode;
}

interface SectorTemplate {
  name: string;
  weight: number;
  // Bobot tipe kontrak [full_time, part_time, contract]
  contract: [number, number, number];
  jobs: JobTemplate[];
  skills: string[];
}

const SECTORS: SectorTemplate[] = [
  {
    name: 'Perdagangan',
    weight: 155,
    contract: [36, 37, 27],
    jobs: [
      { title: 'Digital Marketing Specialist', education: 'S1' },
      { title: 'Sales Executive', education: 'SMA' },
      { title: 'Kasir', education: 'SMA' },
      { title: 'Admin Gudang', education: 'SMK' },
    ],
    skills: ['Communication', 'Negotiation', 'Sales', 'Inventory', 'Digital Marketing'],
  },
  {
    name: 'Akomodasi dan Makan Minum',
    weight: 134,
    contract: [28, 42, 30],
    jobs: [
      { title: 'Restaurant Supervisor', education: 'D3' },
      { title: 'Front Office Staff', education: 'D3' },
      { title: 'Cook Helper', education: 'SMK' },
      { title: 'Waiter / Waitress', education: 'SMA' },
    ],
    skills: ['Communication', 'Customer Service', 'Food Safety', 'Bahasa Inggris'],
  },
  {
    name: 'Informasi dan Komunikasi',
    weight: 128,
    contract: [22, 34, 44],
    jobs: [
      { title: 'Software Developer', education: 'S1' },
      { title: 'Data Analyst', education: 'S1' },
      { title: 'IT Support', education: 'D3' },
      { title: 'Content Creator', education: 'D3' },
    ],
    skills: ['Analytics', 'SQL', 'Python', 'Communication', 'Problem Solving'],
  },
  {
    name: 'Industri Pengolahan',
    weight: 125,
    contract: [34, 27, 39],
    jobs: [
      { title: 'Quality Control Staff', education: 'D3' },
      { title: 'Operator Produksi', education: 'SMK' },
      { title: 'Teknisi Mesin', education: 'SMK' },
      { title: 'Production Planner', education: 'S1' },
    ],
    skills: ['Quality Control', 'K3', 'AutoCAD', 'CNC', 'Technical Drawing'],
  },
  {
    name: 'Transportasi dan Pergudangan',
    weight: 111,
    contract: [35, 28, 37],
    jobs: [
      { title: 'Logistics Staff', education: 'D3' },
      { title: 'Driver Logistik', education: 'SMA' },
      { title: 'Warehouse Supervisor', education: 'D3' },
      { title: 'Admin Ekspedisi', education: 'SMK' },
    ],
    skills: ['Inventory', 'K3', 'Supply Chain', 'Leadership'],
  },
  {
    name: 'Konstruksi',
    weight: 102,
    contract: [30, 22, 48],
    jobs: [
      { title: 'Drafter', education: 'SMK' },
      { title: 'Site Engineer', education: 'S1' },
      { title: 'Surveyor', education: 'D3' },
      { title: 'Safety Officer', education: 'D3' },
    ],
    skills: ['K3', 'AutoCAD', 'Technical Drawing', 'Project Management'],
  },
  {
    name: 'Jasa Profesional, Ilmiah dan Teknis',
    weight: 87,
    contract: [34, 26, 40],
    jobs: [
      { title: 'Akuntan', education: 'S1' },
      { title: 'Konsultan Pajak', education: 'S1' },
      { title: 'Staf Administrasi Proyek', education: 'D3' },
    ],
    skills: ['Analytics', 'Communication', 'Presentation', 'Accounting'],
  },
  {
    name: 'Keuangan dan Asuransi',
    weight: 77,
    contract: [40, 22, 38],
    jobs: [
      { title: 'Customer Service Bank', education: 'D3' },
      { title: 'Analis Kredit', education: 'S1' },
      { title: 'Agen Asuransi', education: 'SMA' },
    ],
    skills: ['Accounting', 'Analytics', 'Customer Service', 'Negotiation'],
  },
  {
    name: 'Kesehatan dan Kegiatan Sosial',
    weight: 31,
    contract: [38, 24, 38],
    jobs: [
      { title: 'Perawat', education: 'D3' },
      { title: 'Apoteker', education: 'S1' },
      { title: 'Admin Rumah Sakit', education: 'SMK' },
    ],
    skills: ['Patient Care', 'Communication', 'K3'],
  },
  {
    name: 'Pendidikan',
    weight: 29,
    contract: [26, 46, 28],
    jobs: [
      { title: 'Guru Bimbingan Belajar', education: 'S1' },
      { title: 'Tenaga Administrasi Sekolah', education: 'SMA' },
    ],
    skills: ['Communication', 'Presentation', 'Bahasa Inggris'],
  },
  {
    name: 'Administrasi Pemerintahan',
    weight: 21,
    contract: [36, 22, 42],
    jobs: [
      { title: 'Tenaga Pendukung Administrasi', education: 'D3' },
      { title: 'Operator Data', education: 'SMK' },
    ],
    skills: ['Administrasi Perkantoran', 'Analytics', 'Communication'],
  },
];

const CONTRACTS: ContractType[] = ['full_time', 'part_time', 'contract'];

const EXPERIENCE_WEIGHT: [ExperienceBucket, number][] = [
  ['1-3 tahun', 31],
  ['2-3 tahun', 20],
  ['1-2 tahun', 16],
  ['0-1 tahun', 13],
  ['2-5 tahun', 12],
  ['3-5 tahun', 4],
];

// Pola musiman: puncak di Januari dan Oktober
const MONTH_WEIGHT = [11, 10, 7, 7, 9, 8, 9, 7, 9, 11, 7, 6];

// Skill lintas sektor yang sering disyaratkan
const GENERIC_SKILLS: [string, number][] = [
  ['Excel', 0.45],
  ['Communication', 0.18],
  ['Leadership', 0.08],
];

const VACANCY_COUNT = 1000;

// PRNG deterministik (mulberry32)
function createRandom(seed: number) {
  let a = seed;
  return () => {
    a |= 0;
    a = (a + 0x6d2b79f5) | 0;
    let t = Math.imul(a ^ (a >>> 15), 1 | a);
    t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t;
    return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
  };
}

function pickWeighted<T>(rand: () => number, items: T[], weights: number[]): T {
  const total = weights.reduce((sum, w) => sum + w, 0);
  let r = rand() * total;
  for (let i = 0; i < items.length; i++) {
    r -= weights[i];
    if (r < 0) return items[i];
  }
  return items[items.length - 1];
}

function generateVacancies(): Vacancy[] {
  const rand = createRandom(2026);
  const provinceIds = DUMMY_WILAYAH.map((p) => p.id);
  const provinceWeights = provinceIds.map((id) => PROVINCE_WEIGHT[id] ?? 1);
  const vacancies: Vacancy[] = [];

  for (let i = 0; i < VACANCY_COUNT; i++) {
    const sector = pickWeighted(rand, SECTORS, SECTORS.map((s) => s.weight));
    const job = sector.jobs[Math.floor(rand() * sector.jobs.length)];
    const provinceId = pickWeighted(rand, provinceIds, provinceWeights);
    const province = DUMMY_WILAYAH.find((p) => p.id === provinceId)!;
    const regency = province.regencies[Math.floor(rand() * province.regencies.length)];

    const skills = new Set<string>();
    const sectorSkillCount = 1 + Math.floor(rand() * 3);
    for (let s = 0; s < sectorSkillCount; s++) {
      skills.add(sector.skills[Math.floor(rand() * sector.skills.length)]);
    }
    for (const [skill, probability] of GENERIC_SKILLS) {
      if (rand() < probability) skills.add(skill);
    }

    vacancies.push({
      id: `LWG-${String(i + 1).padStart(4, '0')}`,
      title: job.title,
      sector: sector.name,
      province_id: province.id,
      regency_id: regency.id,
      education: job.education,
      contract: pickWeighted(rand, CONTRACTS, sector.contract),
      experience: pickWeighted(
        rand,
        EXPERIENCE_WEIGHT.map(([bucket]) => bucket),
        EXPERIENCE_WEIGHT.map(([, w]) => w)
      ),
      month: pickWeighted(
        rand,
        MONTH_WEIGHT.map((_, m) => m + 1),
        MONTH_WEIGHT
      ),
      formasi: 5 + Math.floor(rand() * 36),
      skills: Array.from(skills),
    });
  }

  return vacancies;
}

export const DUMMY_VACANCIES: Vacancy[] = generateVacancies();

// Rekap pencari kerja per kab/kota: jumlah terdaftar ~2x formasi lowongan,
// dan 15-35% di antaranya punya skor kecocokan skill >= 90%.
function generateJobSeekerSummary(): JobSeekerRegencySummary[] {
  const rand = createRandom(90);
  const formasiByRegency = new Map<string, number>();
  for (const v of DUMMY_VACANCIES) {
    formasiByRegency.set(v.regency_id, (formasiByRegency.get(v.regency_id) ?? 0) + v.formasi);
  }

  return DUMMY_WILAYAH.flatMap((province) =>
    province.regencies.map((regency) => {
      const formasi = formasiByRegency.get(regency.id) ?? 0;
      const total = Math.round(formasi * (1.6 + rand() * 1.0));
      const qualified = Math.round(total * (0.15 + rand() * 0.2));
      return { province_id: province.id, regency_id: regency.id, total, qualified };
    })
  );
}

export const DUMMY_JOB_SEEKER_SUMMARY: JobSeekerRegencySummary[] = generateJobSeekerSummary();
