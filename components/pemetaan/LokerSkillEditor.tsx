'use client';

import React, { useEffect, useMemo, useRef, useState } from 'react';
import { AlertCircle, Check, Loader2, Plus, Search, Trash2, X } from 'lucide-react';
import {
  getLokerSkillDetailApi,
  searchEscoSkillsApi,
  updateLokerSkillsApi,
} from '@/services/lowongan-skill';
import {
  EscoSkillOption,
  LokerSkillDetail,
  LowonganSkillItem,
  TipeKeahlian,
} from '@/types/lowongan-skill';

interface Props {
  vacId: string;
  onClose: () => void;
  onSaved: (vacId: string, skills: LowonganSkillItem[]) => void;
}

const METODE_STYLE: Record<string, string> = {
  leksikal: 'bg-emerald-50 text-emerald-700 border-emerald-200',
  semantik: 'bg-blue-50 text-blue-700 border-blue-200',
  manual: 'bg-violet-50 text-violet-700 border-violet-200',
};

export function MetodeBadge({ metode }: { metode: string | null }) {
  if (!metode) return null;
  return (
    <span className={`rounded border px-1.5 py-0.5 text-[10px] font-semibold ${METODE_STYLE[metode] ?? 'bg-slate-50 text-slate-600 border-slate-200'}`}>
      {metode}
    </span>
  );
}

export default function LokerSkillEditor({ vacId, onClose, onSaved }: Props) {
  const [detail, setDetail] = useState<LokerSkillDetail | null>(null);
  const [loadError, setLoadError] = useState<string | null>(null);
  const [selected, setSelected] = useState<LowonganSkillItem[]>([]);
  const [initialKey, setInitialKey] = useState('');

  const [query, setQuery] = useState('');
  const [results, setResults] = useState<EscoSkillOption[]>([]);
  const [searching, setSearching] = useState(false);

  const [saving, setSaving] = useState(false);
  const [saveError, setSaveError] = useState<string | null>(null);
  const searchSeq = useRef(0);

  const stateKey = (items: LowonganSkillItem[]) =>
    items.map((s) => `${s.esco_skill_id}:${s.tipe_keahlian ?? 'wajib'}`).sort().join('|');
  const isDirty = detail !== null && stateKey(selected) !== initialKey;

  useEffect(() => {
    getLokerSkillDetailApi(vacId)
      .then((d) => {
        setDetail(d);
        setSelected(d.skills);
        setInitialKey(stateKey(d.skills));
      })
      .catch((err: Error) => setLoadError(err.message));
  }, [vacId]);

  // Pencarian skill ESCO (debounce 300 ms)
  useEffect(() => {
    const q = query.trim();
    if (q.length < 2) {
      setResults([]);
      return;
    }
    const seq = ++searchSeq.current;
    setSearching(true);
    const timer = setTimeout(() => {
      searchEscoSkillsApi(q)
        .then((data) => seq === searchSeq.current && setResults(data))
        .catch(() => seq === searchSeq.current && setResults([]))
        .finally(() => seq === searchSeq.current && setSearching(false));
    }, 300);
    return () => clearTimeout(timer);
  }, [query]);

  const requestClose = () => {
    if (isDirty && !window.confirm('Perubahan belum disimpan. Tutup tanpa menyimpan?')) return;
    onClose();
  };

  useEffect(() => {
    const onKey = (e: KeyboardEvent) => e.key === 'Escape' && requestClose();
    window.addEventListener('keydown', onKey);
    return () => window.removeEventListener('keydown', onKey);
  });

  const selectedIds = useMemo(() => new Set(selected.map((s) => s.esco_skill_id)), [selected]);

  const addSkill = (skill: EscoSkillOption) => {
    if (selectedIds.has(skill.id)) return;
    setSelected((prev) => [
      ...prev,
      {
        esco_skill_id: skill.id,
        title: skill.title,
        title_en: skill.title_en,
        tipe_keahlian: 'wajib',
        skor: 1,
        metode: 'manual',
        teks_bukti: null,
        versi: 'manual',
      },
    ]);
  };

  const removeSkill = (id: number) => setSelected((prev) => prev.filter((s) => s.esco_skill_id !== id));

  const setTipe = (id: number, tipe: TipeKeahlian) =>
    setSelected((prev) => prev.map((s) => (s.esco_skill_id === id ? { ...s, tipe_keahlian: tipe } : s)));

  const handleSave = async () => {
    setSaving(true);
    setSaveError(null);
    try {
      const saved = await updateLokerSkillsApi(
        vacId,
        selected.map((s) => ({ esco_skill_id: s.esco_skill_id, tipe_keahlian: s.tipe_keahlian ?? 'wajib' }))
      );
      onSaved(vacId, saved);
      onClose();
    } catch (err) {
      setSaveError(err instanceof Error ? err.message : 'Gagal menyimpan.');
    } finally {
      setSaving(false);
    }
  };

  return (
    <div className="fixed inset-0 z-60 flex items-center justify-center bg-slate-900/50 p-2 sm:p-4" onMouseDown={requestClose}>
      <div
        role="dialog"
        aria-modal="true"
        aria-label="Edit pemetaan skill lowongan"
        onMouseDown={(e) => e.stopPropagation()}
        className="flex max-h-[95vh] w-full max-w-6xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl"
      >
        {/* Header */}
        <div className="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4">
          <div className="min-w-0">
            <h2 className="truncate text-base font-bold text-slate-900">
              {detail?.judul_pekerjaan ?? 'Memuat lowongan...'}
            </h2>
            {detail && (
              <p className="mt-0.5 truncate text-xs text-slate-500">
                {detail.nama_perusahaan} · {detail.bidang_pekerjaan} · {detail.vac_id}
              </p>
            )}
          </div>
          <button type="button" onClick={requestClose} className="rounded-lg p-1.5 text-slate-500 hover:bg-slate-100 cursor-pointer" aria-label="Tutup">
            <X className="h-5 w-5" />
          </button>
        </div>

        {loadError ? (
          <div className="p-10 text-center text-sm text-red-600">
            <AlertCircle className="mx-auto mb-2 h-6 w-6" />
            {loadError}
          </div>
        ) : !detail ? (
          <div className="p-16 text-center text-sm text-slate-500">
            <Loader2 className="mx-auto mb-2 h-7 w-7 animate-spin text-[#0E385E]" />
            Memuat detail lowongan...
          </div>
        ) : (
          <div className="grid min-h-0 flex-1 grid-cols-1 lg:grid-cols-2">
            {/* Kiri: deskripsi lowongan */}
            <div className="min-h-0 overflow-y-auto border-b border-slate-200 p-5 lg:border-b-0 lg:border-r">
              <h3 className="mb-2 text-xs font-bold uppercase tracking-wider text-slate-500">Deskripsi Lowongan</h3>
              {detail.deskripsi_teks ? (
                <p className="whitespace-pre-line text-sm leading-relaxed text-slate-700">{detail.deskripsi_teks}</p>
              ) : (
                <p className="text-sm italic text-slate-400">Lowongan ini tidak memiliki deskripsi.</p>
              )}
            </div>

            {/* Kanan: skill terpilih + pencarian */}
            <div className="flex min-h-0 flex-col p-5">
              <div className="relative">
                <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                <input
                  type="text"
                  value={query}
                  onChange={(e) => setQuery(e.target.value)}
                  placeholder="Cari skill ESCO untuk ditambahkan (mis. excel, forklift)..."
                  className="w-full rounded-lg border border-slate-300 py-2 pl-9 pr-3 text-sm focus:border-blue-500 focus:outline-hidden focus:ring-1 focus:ring-blue-500"
                />
              </div>

              {query.trim().length >= 2 && (
                <div className="mt-2 max-h-48 overflow-y-auto rounded-lg border border-slate-200">
                  {searching ? (
                    <p className="p-3 text-center text-xs text-slate-500">Mencari...</p>
                  ) : results.length === 0 ? (
                    <p className="p-3 text-center text-xs text-slate-500">Tidak ada skill yang cocok.</p>
                  ) : (
                    results.map((skill) => {
                      const added = selectedIds.has(skill.id);
                      return (
                        <button
                          key={skill.id}
                          type="button"
                          disabled={added}
                          onClick={() => addSkill(skill)}
                          className="flex w-full items-center justify-between gap-3 border-b border-slate-100 px-3 py-2 text-left last:border-0 hover:bg-blue-50 disabled:cursor-default disabled:bg-slate-50 cursor-pointer"
                        >
                          <span className="min-w-0">
                            <span className="block truncate text-sm font-medium text-slate-800">{skill.title}</span>
                            <span className="block truncate text-xs text-slate-500">{skill.title_en}</span>
                          </span>
                          {added ? (
                            <Check className="h-4 w-4 shrink-0 text-emerald-600" />
                          ) : (
                            <Plus className="h-4 w-4 shrink-0 text-blue-600" />
                          )}
                        </button>
                      );
                    })
                  )}
                </div>
              )}

              <div className="mt-4 mb-2 flex items-center justify-between">
                <h3 className="text-xs font-bold uppercase tracking-wider text-slate-500">
                  Skill ESCO Terpilih ({selected.length})
                </h3>
                {selected.length > 0 && (
                  <button
                    type="button"
                    onClick={() => setSelected([])}
                    className="inline-flex items-center gap-1 text-xs font-semibold text-red-600 hover:text-red-700 cursor-pointer"
                  >
                    <Trash2 className="h-3.5 w-3.5" />
                    Kosongkan semua
                  </button>
                )}
              </div>

              <div className="min-h-0 flex-1 overflow-y-auto">
                {selected.length === 0 ? (
                  <p className="rounded-lg border border-dashed border-slate-300 p-6 text-center text-xs text-slate-500">
                    Belum ada skill. Lowongan akan disimpan tanpa skill ESCO.
                  </p>
                ) : (
                  <ul className="space-y-2">
                    {selected.map((s) => (
                      <li key={s.esco_skill_id} className="rounded-lg border border-slate-200 p-2.5">
                        <div className="flex items-start justify-between gap-2">
                          <div className="min-w-0">
                            <p className="text-sm font-semibold text-slate-800">{s.title}</p>
                            <p className="text-xs text-slate-500">{s.title_en}</p>
                          </div>
                          <button
                            type="button"
                            onClick={() => removeSkill(s.esco_skill_id)}
                            className="rounded p-1 text-slate-400 hover:bg-red-50 hover:text-red-600 cursor-pointer"
                            aria-label={`Hapus ${s.title}`}
                          >
                            <X className="h-4 w-4" />
                          </button>
                        </div>
                        <div className="mt-2 flex flex-wrap items-center gap-2">
                          <select
                            value={s.tipe_keahlian ?? 'wajib'}
                            onChange={(e) => setTipe(s.esco_skill_id, e.target.value as TipeKeahlian)}
                            className="rounded border border-slate-300 px-1.5 py-0.5 text-xs"
                            aria-label="Tipe keahlian"
                          >
                            <option value="wajib">Wajib</option>
                            <option value="diutamakan">Diutamakan</option>
                          </select>
                          <MetodeBadge metode={s.metode} />
                          {s.metode !== 'manual' && s.skor !== null && (
                            <span className="text-[11px] text-slate-500">skor {s.skor.toFixed(2)}</span>
                          )}
                        </div>
                        {s.teks_bukti && (
                          <p className="mt-1.5 line-clamp-2 text-[11px] italic text-slate-500" title={s.teks_bukti}>
                            “{s.teks_bukti}”
                          </p>
                        )}
                      </li>
                    ))}
                  </ul>
                )}
              </div>
            </div>
          </div>
        )}

        {/* Footer */}
        <div className="flex items-center justify-between gap-3 border-t border-slate-200 bg-slate-50 px-5 py-3">
          <p className="text-xs text-red-600">{saveError}</p>
          <div className="flex gap-2">
            <button
              type="button"
              onClick={requestClose}
              className="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 cursor-pointer"
            >
              Batal
            </button>
            <button
              type="button"
              onClick={handleSave}
              disabled={!detail || saving || !isDirty}
              className="inline-flex items-center gap-1.5 rounded-lg bg-[#0E385E] px-4 py-2 text-sm font-semibold text-white hover:bg-[#163A5F] disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
            >
              {saving && <Loader2 className="h-4 w-4 animate-spin" />}
              Simpan
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}
