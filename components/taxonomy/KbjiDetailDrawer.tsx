'use client';

import React, { useState, useEffect, useCallback } from 'react';
import { X, ChevronRight, FileText, GitFork, Loader2, Info, Globe, Briefcase } from 'lucide-react';
import { KbjiDetail } from '@/types/kbji';
import { getKbjiDetail } from '@/services/kbji';
import { KBJI_LEVELS } from '@/constants/kbji';

interface KbjiDetailDrawerProps {
  code: string | null;
  onClose: () => void;
  onSelectNode: (code: string) => void;
}

export default function KbjiDetailDrawer({ code, onClose, onSelectNode }: KbjiDetailDrawerProps) {
  const [detail, setDetail] = useState<KbjiDetail | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [reloadKey, setReloadKey] = useState(0);

  const retry = useCallback(() => setReloadKey((k) => k + 1), []);

  useEffect(() => {
    if (!code) {
      setDetail(null);
      return;
    }

    let isMounted = true;
    setLoading(true);
    setError(null);

    getKbjiDetail(code)
      .then((data) => {
        if (isMounted) setDetail(data);
      })
      .catch((err) => {
        if (isMounted) setError(err.message || 'Gagal memuat detail KBJI');
      })
      .finally(() => {
        if (isMounted) setLoading(false);
      });

    return () => {
      isMounted = false;
    };
  }, [code, reloadKey]);

  if (!code) {
    return (
      <div className="flex h-full flex-col items-center justify-center rounded-xl border border-dashed border-slate-200 bg-white p-8 text-center text-slate-400">
        <div className="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400 mb-3">
          <Info className="h-6 w-6" />
        </div>
        <p className="text-xs font-semibold text-slate-700">Belum Ada Jabatan Terpilih</p>
        <p className="mt-1 text-[11px] text-slate-400 max-w-xs">
          Klik salah satu golongan atau jabatan pada pohon KBJI di sebelah kiri untuk melihat deskripsi tugas, padanan ISCO-08, dan hierarkinya.
        </p>
      </div>
    );
  }

  if (loading) {
    return (
      <div className="flex h-full flex-col items-center justify-center rounded-xl border border-slate-200 bg-white p-8 text-center">
        <Loader2 className="h-8 w-8 text-blue-600 animate-spin mb-3" />
        <p className="text-xs text-slate-500">Memuat detail jabatan...</p>
      </div>
    );
  }

  if (error || !detail) {
    return (
      <div className="rounded-xl border border-red-200 bg-red-50 p-6 text-center text-xs text-red-600">
        <p className="font-semibold">{error || 'Data tidak ditemukan'}</p>
        <button
          type="button"
          onClick={retry}
          className="mt-3 inline-flex items-center rounded-md bg-red-600 px-3 py-1.5 text-xs text-white hover:bg-red-700 cursor-pointer"
        >
          Coba Lagi
        </button>
      </div>
    );
  }

  const levelInfo = KBJI_LEVELS[detail.level];

  return (
    <div className="flex h-full flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xs">
      {/* Header Inspector */}
      <div className="border-b border-slate-100 bg-slate-50/70 p-4">
        <div className="flex items-start justify-between gap-3">
          <div className="flex flex-wrap items-center gap-2">
            <span className="rounded bg-blue-600 px-2 py-0.5 font-mono text-xs font-bold text-white shadow-xs">
              {detail.code}
            </span>
            <span
              className={`rounded border px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider ${levelInfo.badgeClass}`}
            >
              {levelInfo.label} ({levelInfo.digits})
            </span>
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

        <h3 className="mt-2.5 text-base font-bold text-slate-900 leading-snug">{detail.title}</h3>

        {detail.iscoCode && (
          <p className="mt-1 flex items-center gap-1.5 text-xs text-slate-500">
            <Globe className="h-3.5 w-3.5 text-slate-400" />
            Padanan ISCO-08:
            <span className="font-mono font-semibold text-slate-700">{detail.iscoCode}</span>
          </p>
        )}
      </div>

      {/* Konten Scrollable */}
      <div className="flex-1 overflow-y-auto p-4 space-y-5 text-xs">
        {/* 1. Jalur Hierarki Induk */}
        {detail.ancestors.length > 0 && (
          <div>
            <div className="flex items-center gap-1.5 font-semibold text-slate-700 mb-2">
              <GitFork className="h-3.5 w-3.5 text-blue-600" />
              <span>Hierarki Induk</span>
            </div>
            <div className="space-y-1 pl-1">
              {detail.ancestors.map((a, idx) => (
                <button
                  key={a.code}
                  type="button"
                  onClick={() => onSelectNode(a.code)}
                  className="flex w-full items-center justify-between rounded-lg border border-slate-100 bg-slate-50 p-2 text-left hover:border-blue-300 hover:bg-blue-50/50 transition-colors group cursor-pointer"
                  style={{ marginLeft: `${idx * 10}px`, width: `calc(100% - ${idx * 10}px)` }}
                >
                  <div className="flex items-center gap-2 min-w-0">
                    <span className="font-mono text-[10px] font-bold text-blue-700 bg-white px-1.5 py-0.2 rounded border border-slate-200">
                      {a.code}
                    </span>
                    <span className="truncate font-medium text-slate-800 group-hover:text-blue-700">
                      {a.title}
                    </span>
                  </div>
                  <span className="shrink-0 text-[10px] text-slate-400">
                    {KBJI_LEVELS[a.level].label}
                  </span>
                </button>
              ))}
            </div>
          </div>
        )}

        {/* 2. Deskripsi Tugas */}
        <div>
          <div className="flex items-center gap-1.5 font-semibold text-slate-700 mb-1.5">
            <FileText className="h-3.5 w-3.5 text-blue-600" />
            <span>Deskripsi Tugas Umum</span>
          </div>
          <div className="rounded-lg bg-slate-50 p-3 leading-relaxed text-slate-700 border border-slate-100">
            {detail.description ? (
              <p className="whitespace-pre-line">{detail.description}</p>
            ) : (
              <p className="italic text-slate-400">Deskripsi belum tersedia.</p>
            )}
          </div>
        </div>

        {/* 3. Turunan Langsung */}
        {detail.children.length > 0 && (
          <div>
            <div className="flex items-center justify-between font-semibold text-slate-700 mb-2">
              <div className="flex items-center gap-1.5">
                <Briefcase className="h-3.5 w-3.5 text-blue-600" />
                <span>Turunan Langsung</span>
              </div>
              <span className="font-mono text-[10px] text-slate-400">
                {detail.children.length} item
              </span>
            </div>
            <div className="max-h-56 overflow-y-auto space-y-1 rounded-lg border border-slate-100 p-1">
              {detail.children.map((child) => (
                <button
                  key={child.code}
                  type="button"
                  onClick={() => onSelectNode(child.code)}
                  className="flex w-full items-center justify-between gap-2 rounded-md p-1.5 text-left hover:bg-slate-100 transition-colors group cursor-pointer"
                >
                  <div className="flex items-center gap-2 min-w-0">
                    <span className="font-mono text-[9px] font-bold text-slate-600 bg-slate-200/80 px-1 py-0.2 rounded">
                      {child.code}
                    </span>
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
