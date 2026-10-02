'use client';

import React, { useState, useEffect, useRef } from 'react';
import { Search, Loader2, X, Briefcase, FolderOpen, ChevronRight } from 'lucide-react';
import { searchKbji } from '@/services/kbji';
import { KbjiNode } from '@/types/kbji';
import { KBJI_LEVELS } from '@/constants/kbji';

interface KbjiSearchBarProps {
  onSelectNode: (code: string) => void;
}

export default function KbjiSearchBar({ onSelectNode }: KbjiSearchBarProps) {
  const [query, setQuery] = useState('');
  const [results, setResults] = useState<KbjiNode[]>([]);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(false);
  const [isOpen, setIsOpen] = useState(false);
  const containerRef = useRef<HTMLDivElement>(null);

  // Debounced search effect (kode KBJI boleh 1 karakter, misal "2")
  useEffect(() => {
    const trimmed = query.trim();
    const isCode = /^\d/.test(trimmed);
    if (trimmed.length < (isCode ? 1 : 2)) {
      setResults([]);
      setTotal(0);
      setIsOpen(false);
      return;
    }

    setLoading(true);
    const timer = setTimeout(async () => {
      try {
        const res = await searchKbji(trimmed, 1, 15);
        setResults(res.data);
        setTotal(res.total);
        setIsOpen(true);
      } catch (err) {
        console.error('Pencarian KBJI gagal:', err);
      } finally {
        setLoading(false);
      }
    }, 300);

    return () => clearTimeout(timer);
  }, [query]);

  // Click outside to close
  useEffect(() => {
    const handleClickOutside = (e: MouseEvent) => {
      if (containerRef.current && !containerRef.current.contains(e.target as Node)) {
        setIsOpen(false);
      }
    };

    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  const handleSelect = (code: string) => {
    onSelectNode(code);
    setIsOpen(false);
  };

  return (
    <div ref={containerRef} className="relative w-full">
      <div className="relative flex items-center">
        <Search className="absolute left-3.5 h-4 w-4 text-slate-400 pointer-events-none" />
        <input
          type="text"
          value={query}
          onChange={(e) => setQuery(e.target.value)}
          onFocus={() => {
            if (results.length > 0) setIsOpen(true);
          }}
          placeholder="Cari jabatan atau kode KBJI (misal: Programmer, Perawat, 2512)..."
          className="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-10 text-xs sm:text-sm text-slate-800 placeholder-slate-400 shadow-xs transition-all focus:border-blue-600 focus:outline-hidden focus:ring-2 focus:ring-blue-100"
        />
        {loading && <Loader2 className="absolute right-3.5 h-4 w-4 text-blue-600 animate-spin" />}
        {!loading && query && (
          <button
            type="button"
            onClick={() => {
              setQuery('');
              setResults([]);
              setIsOpen(false);
            }}
            className="absolute right-3 rounded-md p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 cursor-pointer"
          >
            <X className="h-3.5 w-3.5" />
          </button>
        )}
      </div>

      {/* Dropdown Live Results */}
      {isOpen && (
        <div className="absolute left-0 right-0 top-full z-50 mt-1.5 max-h-96 overflow-y-auto rounded-xl border border-slate-200 bg-white p-1.5 shadow-xl animate-in fade-in zoom-in-95">
          <div className="flex items-center justify-between px-3 py-1.5 text-[11px] font-semibold text-slate-500 border-b border-slate-100">
            <span>Hasil Pencarian: &ldquo;{query}&rdquo;</span>
            <span className="font-mono text-blue-600">{total} ditemukan</span>
          </div>

          {results.length === 0 ? (
            <div className="p-4 text-center text-xs text-slate-500">
              Tidak ada golongan atau jabatan yang cocok.
            </div>
          ) : (
            <div className="divide-y divide-slate-100">
              {results.map((item) => {
                const isOccupation = item.level === 'occupation';
                const levelInfo = KBJI_LEVELS[item.level];

                return (
                  <button
                    key={item.code}
                    type="button"
                    onClick={() => handleSelect(item.code)}
                    className="flex w-full items-center justify-between gap-3 rounded-lg px-3 py-2 text-left hover:bg-slate-50 transition-colors cursor-pointer group"
                  >
                    <div className="flex items-center gap-2.5 min-w-0 flex-1">
                      {isOccupation ? (
                        <Briefcase className="h-4 w-4 shrink-0 text-amber-600" />
                      ) : (
                        <FolderOpen className="h-4 w-4 shrink-0 text-blue-500" />
                      )}
                      <span className="shrink-0 font-mono text-[10px] font-bold text-slate-600 bg-slate-100 border border-slate-200 px-1.5 py-0.2 rounded">
                        {item.code}
                      </span>
                      <p className="truncate text-xs font-semibold text-slate-900 group-hover:text-blue-700">
                        {item.title}
                      </p>
                      <span
                        className={`hidden sm:inline-flex shrink-0 rounded border px-1.5 py-0.2 text-[9px] font-bold uppercase tracking-wider ${levelInfo.badgeClass}`}
                      >
                        {levelInfo.label}
                      </span>
                    </div>
                    <ChevronRight className="h-4 w-4 text-slate-300 group-hover:text-blue-600 shrink-0" />
                  </button>
                );
              })}
            </div>
          )}
        </div>
      )}
    </div>
  );
}
