'use client';

import React, { useState, useEffect } from 'react';
import {
  X,
  BookOpen,
  ChevronRight,
  Tag,
  FileText,
  GitFork,
  Loader2,
  Info,
} from 'lucide-react';
import { TaxonomyNodeDetail } from '@/types/taxonomy';
import { getNodeDetail } from '@/services/taxonomy';

interface SkillDetailDrawerProps {
  nodeId: number | null;
  onClose: () => void;
  onSelectNode: (id: number) => void;
}

export default function SkillDetailDrawer({
  nodeId,
  onClose,
  onSelectNode,
}: SkillDetailDrawerProps) {
  const [detail, setDetail] = useState<TaxonomyNodeDetail | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!nodeId) {
      setDetail(null);
      return;
    }

    let isMounted = true;
    setLoading(true);
    setError(null);

    getNodeDetail(nodeId)
      .then((data) => {
        if (isMounted) setDetail(data);
      })
      .catch((err) => {
        if (isMounted) setError(err.message || 'Gagal memuat detail node');
      })
      .finally(() => {
        if (isMounted) setLoading(false);
      });

    return () => {
      isMounted = false;
    };
  }, [nodeId]);

  if (!nodeId) {
    return (
      <div className="flex h-full flex-col items-center justify-center rounded-xl border border-dashed border-slate-200 bg-white p-8 text-center text-slate-400">
        <div className="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400 mb-3">
          <Info className="h-6 w-6" />
        </div>
        <p className="text-xs font-semibold text-slate-700">Belum Ada Node Terpilih</p>
        <p className="mt-1 text-[11px] text-slate-400 max-w-xs">
          Klik salah satu kategori atau keahlian pada pohon hierarki di sebelah kiri untuk melihat deskripsi, sinonim, dan relasi lengkapnya.
        </p>
      </div>
    );
  }

  if (loading) {
    return (
      <div className="flex h-full flex-col items-center justify-center rounded-xl border border-slate-200 bg-white p-8 text-center">
        <Loader2 className="h-8 w-8 text-blue-600 animate-spin mb-3" />
        <p className="text-xs text-slate-500">Memuat detail taksonomi...</p>
      </div>
    );
  }

  if (error || !detail) {
    return (
      <div className="rounded-xl border border-red-200 bg-red-50 p-6 text-center text-xs text-red-600">
        <p className="font-semibold">{error || 'Data tidak ditemukan'}</p>
        <button
          type="button"
          onClick={() => nodeId && getNodeDetail(nodeId)}
          className="mt-3 inline-flex items-center rounded-md bg-red-600 px-3 py-1.5 text-xs text-white hover:bg-red-700"
        >
          Coba Lagi
        </button>
      </div>
    );
  }

  const isSkill = detail.type === 'skill';

  return (
    <div className="flex h-full flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xs">
      {/* Header Inspector */}
      <div className="border-b border-slate-100 bg-slate-50/70 p-4">
        <div className="flex items-start justify-between gap-3">
          <div className="flex items-center gap-2">
            {detail.code && (
              <span className="rounded bg-blue-600 px-2 py-0.5 font-mono text-xs font-bold text-white shadow-xs">
                {detail.code}
              </span>
            )}
            <span
              className={`rounded px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider ${
                isSkill
                  ? 'bg-amber-100 text-amber-800 border border-amber-200'
                  : 'bg-blue-100 text-blue-800 border border-blue-200'
              }`}
            >
              {isSkill ? 'Unit Keahlian (Skill)' : 'Kategori / Konsep'}
            </span>
            {detail.isLayer1 && (
              <span className="rounded bg-indigo-50 px-2 py-0.5 text-[10px] font-semibold text-indigo-700 border border-indigo-200">
                Pilar Utama (Layer 1)
              </span>
            )}
          </div>
          <button
            type="button"
            onClick={onClose}
            className="rounded-lg p-1 text-slate-400 hover:bg-slate-200 hover:text-slate-700 transition-colors cursor-pointer"
            title="Tutup detail"
          >
            <X className="h-4 w-4" />
          </button>
        </div>

        {/* Judul Utama Indonesia */}
        <h3 className="mt-2.5 text-base font-bold text-slate-900 leading-snug">
          {detail.title}
        </h3>

        {/* Judul Bahasa Inggris */}
        {detail.titleEn && detail.titleEn !== detail.title && (
          <p className="mt-0.5 text-xs text-slate-500 italic">
            English: <span className="font-medium text-slate-700">{detail.titleEn}</span>
          </p>
        )}
      </div>

      {/* Konten Scrollable */}
      <div className="flex-1 overflow-y-auto p-4 space-y-5 text-xs">
        {/* 1. Breadcrumbs Induk / Parents */}
        {detail.parents && detail.parents.length > 0 && (
          <div>
            <div className="flex items-center gap-1.5 font-semibold text-slate-700 mb-2">
              <GitFork className="h-3.5 w-3.5 text-blue-600" />
              <span>Hierarki Induk (Parents)</span>
            </div>
            <div className="space-y-1 pl-1">
              {detail.parents.map((parent) => (
                <button
                  key={parent.id}
                  type="button"
                  onClick={() => onSelectNode(parent.id)}
                  className="flex w-full items-center justify-between rounded-lg border border-slate-100 bg-slate-50 p-2 text-left hover:border-blue-300 hover:bg-blue-50/50 transition-colors group cursor-pointer"
                >
                  <div className="flex items-center gap-2 min-w-0">
                    {parent.code && (
                      <span className="font-mono text-[10px] font-bold text-blue-700 bg-white px-1.5 py-0.2 rounded border border-slate-200">
                        {parent.code}
                      </span>
                    )}
                    <span className="truncate font-medium text-slate-800 group-hover:text-blue-700">
                      {parent.title}
                    </span>
                  </div>
                  <ChevronRight className="h-3.5 w-3.5 text-slate-400 group-hover:text-blue-600 shrink-0" />
                </button>
              ))}
            </div>
          </div>
        )}

        {/* 2. Deskripsi Bahasa Indonesia */}
        <div>
          <div className="flex items-center gap-1.5 font-semibold text-slate-700 mb-1.5">
            <FileText className="h-3.5 w-3.5 text-blue-600" />
            <span>Deskripsi (Bahasa Indonesia)</span>
          </div>
          <div className="rounded-lg bg-slate-50 p-3 leading-relaxed text-slate-700 border border-slate-100">
            {detail.description ? (
              <p className="whitespace-pre-line">{detail.description}</p>
            ) : (
              <p className="italic text-slate-400">Deskripsi belum tersedia dalam Bahasa Indonesia.</p>
            )}
          </div>
        </div>

        {/* 3. Deskripsi Asli Bahasa Inggris */}
        {detail.descriptionEn && (
          <div>
            <div className="flex items-center gap-1.5 font-semibold text-slate-700 mb-1.5">
              <FileText className="h-3.5 w-3.5 text-slate-400" />
              <span>Deskripsi Asli (English)</span>
            </div>
            <div className="rounded-lg bg-slate-50/60 p-3 leading-relaxed text-slate-600 border border-slate-100 italic">
              <p className="whitespace-pre-line">{detail.descriptionEn}</p>
            </div>
          </div>
        )}

        {/* 4. Kata Kunci / Sinonim Alternatif (Alt Labels) */}
        {((detail.altLabels && detail.altLabels.length > 0) ||
          (detail.altLabelsEn && detail.altLabelsEn.length > 0)) && (
          <div>
            <div className="flex items-center gap-1.5 font-semibold text-slate-700 mb-2">
              <Tag className="h-3.5 w-3.5 text-amber-500" />
              <span>Sinonim &amp; Kata Kunci Terkait</span>
            </div>

            {/* Alt Labels ID */}
            {detail.altLabels && detail.altLabels.length > 0 && (
              <div className="flex flex-wrap gap-1.5 mb-2">
                {detail.altLabels.map((label, idx) => (
                  <span
                    key={idx}
                    className="inline-flex items-center rounded-md bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-amber-800 border border-amber-200"
                  >
                    {label}
                  </span>
                ))}
              </div>
            )}

            {/* Alt Labels EN */}
            {detail.altLabelsEn && detail.altLabelsEn.length > 0 && (
              <div className="flex flex-wrap gap-1.5">
                {detail.altLabelsEn.map((label, idx) => (
                  <span
                    key={idx}
                    className="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-[11px] text-slate-600 border border-slate-200 italic"
                  >
                    {label}
                  </span>
                ))}
              </div>
            )}
          </div>
        )}

        {/* 5. Sub-elemen Turunan Langsung (Children) */}
        {detail.children && detail.children.length > 0 && (
          <div>
            <div className="flex items-center justify-between font-semibold text-slate-700 mb-2">
              <div className="flex items-center gap-1.5">
                <BookOpen className="h-3.5 w-3.5 text-blue-600" />
                <span>Sub-elemen Langsung</span>
              </div>
              <span className="font-mono text-[10px] text-slate-400">
                {detail.children.length} item
              </span>
            </div>
            <div className="max-h-56 overflow-y-auto space-y-1 rounded-lg border border-slate-100 p-1">
              {detail.children.map((child) => (
                <button
                  key={child.id}
                  type="button"
                  onClick={() => onSelectNode(child.id)}
                  className="flex w-full items-center justify-between gap-2 rounded-md p-1.5 text-left hover:bg-slate-100 transition-colors group cursor-pointer"
                >
                  <div className="flex items-center gap-2 min-w-0">
                    {child.code && (
                      <span className="font-mono text-[9px] font-bold text-slate-600 bg-slate-200/80 px-1 py-0.2 rounded">
                        {child.code}
                      </span>
                    )}
                    <span className="truncate text-[11px] text-slate-700 group-hover:text-blue-700">
                      {child.title}
                    </span>
                  </div>
                  <ChevronRight className="h-3.5 w-3.5 text-slate-300 group-hover:text-blue-600 shrink-0" />
                </button>
              ))}
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
