'use client';

import React, { useState, useEffect, useRef } from 'react';
import { Search, Loader2, Check, Briefcase } from 'lucide-react';
import { KbjiOption } from '@/types/lowongan';
import { searchKbjiApi } from '@/services/lowongan';

interface KbjiSelectorProps {
  value: number | null;
  selectedLabel?: string;
  onChange: (id: number, option: KbjiOption) => void;
  disabled?: boolean;
  error?: string;
}

export default function KbjiSelector({
  value,
  selectedLabel,
  onChange,
  disabled = false,
  error,
}: KbjiSelectorProps) {
  const [query, setQuery] = useState('');
  const [results, setResults] = useState<KbjiOption[]>([]);
  const [isOpen, setIsOpen] = useState(false);
  const [loading, setLoading] = useState(false);
  const [selectedDisplay, setSelectedDisplay] = useState(selectedLabel || '');
  const containerRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    if (selectedLabel) {
      setSelectedDisplay(selectedLabel);
    }
  }, [selectedLabel]);

  // Debounced search
  useEffect(() => {
    if (!query.trim() || query.length < 2) {
      setResults([]);
      return;
    }

    const timer = setTimeout(async () => {
      setLoading(true);
      try {
        const data = await searchKbjiApi(query);
        setResults(data);
      } catch {
        setResults([]);
      } finally {
        setLoading(false);
      }
    }, 300);

    return () => clearTimeout(timer);
  }, [query]);

  // Close on outside click
  useEffect(() => {
    const handleOutsideClick = (e: MouseEvent) => {
      if (containerRef.current && !containerRef.current.contains(e.target as Node)) {
        setIsOpen(false);
      }
    };
    document.addEventListener('mousedown', handleOutsideClick);
    return () => document.removeEventListener('mousedown', handleOutsideClick);
  }, []);

  return (
    <div className="relative" ref={containerRef}>
      <label className="block text-xs font-semibold text-slate-700 mb-1">
        Klasifikasi Jabatan KBJI <span className="text-red-500">*</span>
      </label>

      <div className="relative">
        <input
          type="text"
          disabled={disabled}
          placeholder={selectedDisplay ? selectedDisplay : "Ketik nama jabatan atau kode KBJI (misal: 2512)..."}
          value={isOpen ? query : selectedDisplay}
          onFocus={() => {
            setIsOpen(true);
            setQuery('');
          }}
          onChange={(e) => {
            setQuery(e.target.value);
            if (!isOpen) setIsOpen(true);
          }}
          className={`w-full pl-9 pr-8 py-2 text-xs sm:text-sm rounded-md border ${
            error ? 'border-red-400 bg-red-50/20' : 'border-slate-300 bg-white'
          } text-slate-800 placeholder-slate-400 focus:outline-hidden focus:border-blue-600 focus:ring-1 focus:ring-blue-600`}
        />
        <Briefcase className="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />

        {loading && (
          <Loader2 className="w-4 h-4 text-blue-600 animate-spin absolute right-3 top-1/2 -translate-y-1/2" />
        )}
      </div>

      {error && <p className="text-xs text-red-500 mt-1">{error}</p>}

      {isOpen && (
        <div className="absolute z-50 left-0 right-0 mt-1 bg-white border border-slate-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
          {loading ? (
            <div className="p-4 text-center text-xs text-slate-500 flex items-center justify-center gap-2">
              <Loader2 className="w-4 h-4 animate-spin text-blue-600" />
              Mencari jabatan di KBJI 2026...
            </div>
          ) : results.length > 0 ? (
            <ul className="divide-y divide-slate-100">
              {results.map((item) => {
                const isSelected = value === item.id;
                return (
                  <li
                    key={item.id}
                    onClick={() => {
                      setSelectedDisplay(`${item.code} - ${item.title}`);
                      onChange(item.id, item);
                      setIsOpen(false);
                    }}
                    className={`p-2.5 hover:bg-blue-50 cursor-pointer flex items-start justify-between gap-2 transition-colors ${
                      isSelected ? 'bg-blue-50/70 font-semibold' : ''
                    }`}
                  >
                    <div>
                      <div className="flex items-center gap-1.5">
                        <span className="text-[11px] font-mono font-bold bg-slate-100 text-slate-700 px-1.5 py-0.5 rounded">
                          {item.code}
                        </span>
                        <span className="text-xs text-slate-800">{item.title}</span>
                      </div>
                      <span className="text-[10px] text-slate-400 capitalize">
                        Level: {item.level.replace('_', ' ')}
                      </span>
                    </div>
                    {isSelected && <Check className="w-4 h-4 text-blue-600 shrink-0 mt-0.5" />}
                  </li>
                );
              })}
            </ul>
          ) : query.length >= 2 ? (
            <div className="p-4 text-center text-xs text-slate-500">
              Tidak ditemukan jabatan KBJI yang cocok dengan kata kunci &quot;{query}&quot;.
            </div>
          ) : (
            <div className="p-3 text-center text-xs text-slate-400">
              Ketik minimal 2 karakter untuk mencari standar jabatan KBJI.
            </div>
          )}
        </div>
      )}
    </div>
  );
}
