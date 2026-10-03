import type { Metadata } from 'next';
import Link from 'next/link';
import EscoSkillsExplorer from '@/components/taxonomy/EscoSkillsExplorer';
import KbjiExplorer from '@/components/taxonomy/KbjiExplorer';

export const metadata: Metadata = {
  title: 'Taxonomy · e-Pengantar Kerja',
};

const TABS = [
  { key: 'esco', label: 'Keahlian (ESCO)' },
  { key: 'kbji', label: 'Jabatan (KBJI 2020)' },
] as const;

export default async function PublicTaxonomyPage({
  searchParams,
}: {
  searchParams: Promise<{ tab?: string }>;
}) {
  const { tab } = await searchParams;
  const activeTab = tab === 'kbji' ? 'kbji' : 'esco';

  return (
    <div className="max-w-7xl mx-auto pt-6 sm:pt-8">
      <div className="px-4 sm:px-6 lg:px-8">
        <h1 className="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight">
          Skill Taxonomy Nasional
        </h1>
        <p className="text-sm text-slate-500 mt-1">
          Telusuri struktur keahlian (ESCO) dan klasifikasi jabatan (KBJI 2020) yang dipakai untuk mencocokkan pencari kerja dengan lowongan.
        </p>

        <nav className="mt-4 flex gap-1 border-b border-slate-200" aria-label="Jenis taksonomi">
          {TABS.map((t) => (
            <Link
              key={t.key}
              href={`/taksonomi?tab=${t.key}`}
              aria-current={activeTab === t.key ? 'page' : undefined}
              className={`-mb-px border-b-2 px-3 py-2 text-sm font-semibold transition-colors ${
                activeTab === t.key
                  ? 'border-blue-700 text-blue-800'
                  : 'border-transparent text-slate-500 hover:text-slate-800'
              }`}
            >
              {t.label}
            </Link>
          ))}
        </nav>
      </div>

      {activeTab === 'kbji' ? (
        <KbjiExplorer breadcrumbHref="/taksonomi?tab=kbji" />
      ) : (
        <EscoSkillsExplorer breadcrumbHref="/taksonomi?tab=esco" />
      )}
    </div>
  );
}
