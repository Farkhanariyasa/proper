'use client';

import React, { useState, useEffect, useRef } from 'react';
import { Search, Loader2, X, Plus, Layers, Check } from 'lucide-react';
import { EscoSkillItem } from '@/types/job-seeker';
import { searchEscoSkills } from '@/services/job-seeker';

export interface SelectedSkillRequirement {
  esco_skill_id: number;
  title: string;
  title_en?: string;
  tipe_keahlian: 'wajib' | 'tambahan';
  level_kemahiran: 'pemula' | 'menengah' | 'ahli';
}

interface LowonganSkillPickerProps {
  skills: SelectedSkillRequirement[];
  onChange: (skills: SelectedSkillRequirement[]) => void;
  error?: string;
}

export default function LowonganSkillPicker({
  skills,
  onChange,
  error,
}: LowonganSkillPickerProps) {
  const [query, setQuery] = useState('');
  const [results, setResults] = useState<EscoSkillItem[]>([]);
  const [isOpen, setIsOpen] = useState(false);
  const [loading, setLoading] = useState(false);
  const containerRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    if (!query.trim() || query.length < 2) {
      setResults([]);
      return;
    }

    const timer = setTimeout(async () => {
      setLoading(true);
      try {
        const data = await searchEscoSkills(query, 12);
        setResults(data);
      } catch {
        setResults([]);
      } finally {
        setLoading(false);
      }
    }, 300);

    return () => clearTimeout(timer);
  }, [query]);

  // Close dropdown on outside click
  useEffect(() => {
    const handleOutsideClick = (e: MouseEvent) => {
      if (containerRef.current && !containerRef.current.contains(e.target as Node)) {
        setIsOpen(false);
      }
    };
    document.addEventListener('mousedown', handleOutsideClick);
    return () => document.removeEventListener('mousedown', handleOutsideClick);
  }, []);

  const handleSelectSkill = (item: EscoSkillItem) => {
    if (skills.some((s) => s.esco_skill_id === item.id)) {
      return;
    }

    const newSkill: SelectedSkillRequirement = {
      esco_skill_id: item.id,
      title: item.title,
      title_en: item.title_en,
      tipe_keahlian: 'wajib',
      level_kemahiran: 'menengah',
    };

    onChange([...skills, newSkill]);
    setQuery('');
    setIsOpen(false);
  };

  const handleRemoveSkill = (id: number) => {
    onChange(skills.filter((s) => s.esco_skill_id !== id));
  };

  const handleUpdateRequirement = (
    id: number,
    field: 'tipe_keahlian' | 'level_kemahiran',
    val: string
  ) => {
    onChange(
      skills.map((s) => {
        if (s.esco_skill_id === id) {
          return { ...s, [field]: val };
        }
        return s;
      })
    );
  };

  return (
    <div className="space-y-3" ref={containerRef}>
      <div className="flex items-center justify-between">
        <label className="block text-xs font-semibold text-slate-700">
          Keahlian Standar ESCO yang Disyaratkan <span className="text-red-500">*</span>
        </label>
        <span className="text-[11px] text-slate-500">
          {skills.length} keahlian terpilih
        </span>
      </div>

      {/* Autocomplete Input */}
      <div className="relative">
        <input
          type="text"
          placeholder="Cari keahlian standar ESCO (misal: Python, SQL, Analisis Data)..."
          value={query}
          onFocus={() => setIsOpen(true)}
          onChange={(e) => {
            setQuery(e.target.value);
            if (!isOpen) setIsOpen(true);
          }}
          className={`w-full pl-9 pr-8 py-2 text-xs sm:text-sm rounded-md border ${
            error ? 'border-red-400 bg-red-50/20' : 'border-slate-300 bg-white'
          } text-slate-800 placeholder-slate-400 focus:outline-hidden focus:border-blue-600 focus:ring-1 focus:ring-blue-600`}
        />
        <Search className="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />

        {loading && (
          <Loader2 className="w-4 h-4 text-blue-600 animate-spin absolute right-3 top-1/2 -translate-y-1/2" />
        )}

        {isOpen && (
          <div className="absolute z-50 left-0 right-0 mt-1 bg-white border border-slate-200 rounded-lg shadow-lg max-h-56 overflow-y-auto">
            {loading ? (
              <div className="p-3 text-center text-xs text-slate-500 flex items-center justify-center gap-2">
                <Loader2 className="w-4 h-4 animate-spin text-blue-600" />
                Mencari taksonomi keahlian...
              </div>
            ) : results.length > 0 ? (
              <ul className="divide-y divide-slate-100">
                {results.map((item) => {
                  const alreadyChosen = skills.some((s) => s.esco_skill_id === item.id);
                  return (
                    <li
                      key={item.id}
                      onClick={() => !alreadyChosen && handleSelectSkill(item)}
                      className={`p-2.5 flex items-center justify-between gap-2 text-xs transition-colors ${
                        alreadyChosen
                          ? 'bg-slate-50 text-slate-400 cursor-not-allowed'
                          : 'hover:bg-blue-50 cursor-pointer text-slate-800'
                      }`}
                    >
                      <div className="truncate">
                        <div className="font-medium truncate">{item.title}</div>
                        {item.title_en && (
                          <div className="text-[10px] text-slate-400 italic truncate">
                            {item.title_en}
                          </div>
                        )}
                      </div>
                      {alreadyChosen ? (
                        <span className="text-[10px] bg-slate-200 text-slate-600 px-1.5 py-0.5 rounded">
                          Sudah dipilih
                        </span>
                      ) : (
                        <Plus className="w-4 h-4 text-blue-600 shrink-0" />
                      )}
                    </li>
                  );
                })}
              </ul>
            ) : query.length >= 2 ? (
              <div className="p-3 text-center text-xs text-slate-500">
                Tidak ada keahlian yang cocok dengan &quot;{query}&quot;.
              </div>
            ) : (
              <div className="p-3 text-center text-xs text-slate-400">
                Ketik nama keahlian untuk memuat daftar taksonomi.
              </div>
            )}
          </div>
        )}
      </div>

      {error && <p className="text-xs text-red-500">{error}</p>}

      {/* Selected Skills List */}
      {skills.length > 0 ? (
        <div className="space-y-2 max-h-60 overflow-y-auto pr-1">
          {skills.map((sk) => (
            <div
              key={sk.esco_skill_id}
              className="p-2.5 rounded-lg border border-slate-200 bg-slate-50/70 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs"
            >
              <div className="flex-1 min-w-0">
                <span className="font-semibold text-slate-800 block truncate">{sk.title}</span>
                {sk.title_en && (
                  <span className="text-[10px] text-slate-400 italic block truncate">
                    {sk.title_en}
                  </span>
                )}
              </div>

              <div className="flex items-center gap-2 shrink-0">
                {/* Tipe Keahlian (Wajib vs Tambahan) */}
                <select
                  value={sk.tipe_keahlian}
                  onChange={(e) =>
                    handleUpdateRequirement(
                      sk.esco_skill_id,
                      'tipe_keahlian',
                      e.target.value as 'wajib' | 'tambahan'
                    )
                  }
                  className={`text-[11px] py-1 px-2 rounded-md font-medium border ${
                    sk.tipe_keahlian === 'wajib'
                      ? 'border-blue-300 bg-blue-50 text-blue-700'
                      : 'border-slate-300 bg-white text-slate-700'
                  } focus:outline-hidden`}
                >
                  <option value="wajib">Wajib (Must-have)</option>
                  <option value="tambahan">Tambahan (Nice-to-have)</option>
                </select>

                {/* Level Kemahiran */}
                <select
                  value={sk.level_kemahiran}
                  onChange={(e) =>
                    handleUpdateRequirement(
                      sk.esco_skill_id,
                      'level_kemahiran',
                      e.target.value as 'pemula' | 'menengah' | 'ahli'
                    )
                  }
                  className="text-[11px] py-1 px-2 rounded-md border border-slate-300 bg-white text-slate-700 focus:outline-hidden"
                >
                  <option value="pemula">Pemula</option>
                  <option value="menengah">Menengah</option>
                  <option value="ahli">Ahli</option>
                </select>

                {/* Hapus Skill */}
                <button
                  type="button"
                  onClick={() => handleRemoveSkill(sk.esco_skill_id)}
                  className="p-1 text-slate-400 hover:text-red-600 rounded transition-colors"
                  title="Hapus skill"
                >
                  <X className="w-4 h-4" />
                </button>
              </div>
            </div>
          ))}
        </div>
      ) : (
        <div className="p-4 border border-dashed border-slate-200 rounded-lg text-center text-xs text-slate-400">
          Belum ada keahlian yang disyaratkan. Cari dan pilih minimal 1 skill ESCO di atas agar lowongan dapat dijodohkan oleh matching engine.
        </div>
      )}
    </div>
  );
}
