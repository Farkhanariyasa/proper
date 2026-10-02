import { KbjiLevel } from '@/types/kbji';

/**
 * Label & warna badge untuk setiap level hierarki KBJI 2020
 */
export const KBJI_LEVELS: Record<
  KbjiLevel,
  { label: string; digits: string; badgeClass: string }
> = {
  major_group: {
    label: 'Golongan Pokok',
    digits: '1 Digit',
    badgeClass: 'bg-indigo-50 text-indigo-700 border-indigo-200',
  },
  sub_major_group: {
    label: 'Golongan Menengah',
    digits: '2 Digit',
    badgeClass: 'bg-blue-50 text-blue-700 border-blue-200',
  },
  minor_group: {
    label: 'Golongan',
    digits: '3 Digit',
    badgeClass: 'bg-sky-50 text-sky-700 border-sky-200',
  },
  unit_group: {
    label: 'Sub-Golongan',
    digits: '4 Digit',
    badgeClass: 'bg-slate-100 text-slate-700 border-slate-200',
  },
  occupation: {
    label: 'Jabatan',
    digits: '5-7 Digit',
    badgeClass: 'bg-amber-50 text-amber-700 border-amber-200',
  },
};
