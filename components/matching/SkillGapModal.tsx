'use client';

import React from 'react';
import {
  X,
  CheckCircle2,
  AlertTriangle,
  Layers,
  Building2,
  User,
  MapPin,
  Briefcase,
  GraduationCap,
  Send,
  BarChart3,
  Target,
} from 'lucide-react';
import { PairwiseAnalysisResponse } from '@/types/matching';

interface SkillGapModalProps {
  isOpen: boolean;
  onClose: () => void;
  data: PairwiseAnalysisResponse | null;
  onRecommend?: (data: PairwiseAnalysisResponse) => void;
}

export default function SkillGapModal({
  isOpen,
  onClose,
  data,
  onRecommend,
}: SkillGapModalProps) {
  if (!isOpen || !data) return null;

  const { candidate, job, score, classification, matched_skills, gap_skills } = data;

  const getScoreColor = (sc: number) => {
    if (sc >= 70) return 'text-emerald-700 bg-emerald-50 border-emerald-200';
    if (sc >= 40) return 'text-amber-700 bg-amber-50 border-amber-200';
    return 'text-slate-600 bg-slate-100 border-slate-200';
  };

  const getProgressBarColor = (sc: number) => {
    if (sc >= 70) return 'bg-emerald-600';
    if (sc >= 40) return 'bg-amber-500';
    return 'bg-slate-400';
  };

  return (
    <div className="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
      <div className="bg-white rounded-xl shadow-2xl border border-slate-200 w-full max-w-4xl max-h-[92vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
        {/* Header Modal */}
        <div className="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50/70">
          <div>
            <h3 className="text-base sm:text-lg font-bold text-slate-900 flex items-center gap-2">
              <BarChart3 className="w-5 h-5 text-[#0E385E]" />
              <span>Analisis Komparasi & Kesenjangan Skill (Gap Analysis)</span>
            </h3>
            <p className="text-xs text-slate-500 mt-0.5">
              Rincian kesesuaian kompetensi pelamar terhadap kualifikasi lowongan industri
            </p>
          </div>
          <button
            onClick={onClose}
            className="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-200 transition-colors"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Content Body */}
        <div className="flex-1 overflow-y-auto p-6 space-y-6 text-xs sm:text-sm">
          {/* Ringkasan Skor & Persentase Bar */}
          <div className="p-4 rounded-xl border border-slate-200 bg-slate-50/60 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div className="space-y-1.5 flex-1">
              <div className="flex items-center gap-2">
                <span
                  className={`text-xs px-2.5 py-0.5 rounded-full font-bold border ${getScoreColor(
                    score
                  )}`}
                >
                  {classification.label}
                </span>
                <span className="text-xs text-slate-500">
                  {matched_skills.length} dari {matched_skills.length + gap_skills.length} skill terpenuhi
                </span>
              </div>
              <p className="text-xs text-slate-600">{classification.description}</p>

              {/* Progress Bar */}
              <div className="w-full bg-slate-200 h-2.5 rounded-full overflow-hidden mt-2">
                <div
                  className={`h-full transition-all duration-500 ${getProgressBarColor(score)}`}
                  style={{ width: `${score}%` }}
                />
              </div>
            </div>

            <div className="sm:text-right shrink-0 border-t sm:border-t-0 sm:border-l border-slate-200 pt-3 sm:pt-0 sm:pl-6">
              <div className="text-3xl sm:text-4xl font-black text-[#0E385E] tracking-tight">
                {score}%
              </div>
              <div className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                Match Score
              </div>
            </div>
          </div>

          {/* Rincian Evaluasi 5 Dimensi Multi-Kriteria */}
          <div className="space-y-2">
            <div className="flex items-center justify-between">
              <h4 className="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                <BarChart3 className="w-4 h-4 text-[#0E385E]" />
                <span>Rincian Evaluasi 5 Dimensi Multi-Kriteria</span>
              </h4>
              <span className="text-[11px] text-slate-400 font-medium">Bobot Komposit Total: 100%</span>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5">
              {/* Dimensi 1: Kompetensi & Keahlian (40%) */}
              <div className="p-3 bg-white rounded-xl border border-slate-200 shadow-2xs space-y-1.5">
                <div className="flex items-center justify-between text-xs font-semibold text-slate-600">
                  <span className="flex items-center gap-1 text-emerald-800">
                    <Target className="w-3.5 h-3.5 text-emerald-600" />
                    Kompetensi / Skill
                  </span>
                  <span className="text-[10px] px-1.5 py-0.2 bg-emerald-50 text-emerald-700 rounded font-bold">40%</span>
                </div>
                <div className="text-xl font-black text-slate-900">
                  {data.score_breakdown?.skill_score ?? Math.round((matched_skills.length / Math.max(1, matched_skills.length + gap_skills.length)) * 100)}%
                </div>
                <div className="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                  <div 
                    className="h-full bg-emerald-600 rounded-full" 
                    style={{ width: `${data.score_breakdown?.skill_score ?? Math.round((matched_skills.length / Math.max(1, matched_skills.length + gap_skills.length)) * 100)}%` }} 
                  />
                </div>
                <p className="text-[10px] text-slate-400 leading-tight">
                  {matched_skills.length} dari {matched_skills.length + gap_skills.length} skill terpenuhi
                </p>
              </div>

              {/* Dimensi 2: Kualifikasi Pendidikan (30%) */}
              <div className="p-3 bg-white rounded-xl border border-slate-200 shadow-2xs space-y-1.5">
                <div className="flex items-center justify-between text-xs font-semibold text-slate-600">
                  <span className="flex items-center gap-1 text-purple-800">
                    <GraduationCap className="w-3.5 h-3.5 text-purple-600" />
                    Pendidikan
                  </span>
                  <span className="text-[10px] px-1.5 py-0.2 bg-purple-50 text-purple-700 rounded font-bold">30%</span>
                </div>
                <div className="text-xl font-black text-slate-900">
                  {data.score_breakdown?.education_score ?? (data.education_match?.is_matched ? 100 : 0)}%
                </div>
                <div className="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                  <div 
                    className="h-full bg-purple-600 rounded-full" 
                    style={{ width: `${data.score_breakdown?.education_score ?? (data.education_match?.is_matched ? 100 : 0)}%` }} 
                  />
                </div>
                <p className="text-[10px] text-slate-400 leading-tight">
                  {data.education_match?.is_matched ? 'Memenuhi Syarat' : 'Di Bawah Syarat (0%)'}
                </p>
              </div>

              {/* Dimensi 3: Pengalaman Kerja (20%) */}
              <div className="p-3 bg-white rounded-xl border border-slate-200 shadow-2xs space-y-1.5">
                <div className="flex items-center justify-between text-xs font-semibold text-slate-600">
                  <span className="flex items-center gap-1 text-amber-800">
                    <Layers className="w-3.5 h-3.5 text-amber-600" />
                    Pengalaman Kerja
                  </span>
                  <span className="text-[10px] px-1.5 py-0.2 bg-amber-50 text-amber-700 rounded font-bold">20%</span>
                </div>
                <div className="text-xl font-black text-slate-900">
                  {data.score_breakdown?.experience_score ?? 80}%
                </div>
                <div className="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                  <div 
                    className="h-full bg-amber-500 rounded-full" 
                    style={{ width: `${data.score_breakdown?.experience_score ?? 80}%` }} 
                  />
                </div>
                <p className="text-[10px] text-slate-400 leading-tight">
                  Masa kerja & relevansi rekam jejak industri
                </p>
              </div>

              {/* Dimensi 4: Wilayah & Lokasi (10%) */}
              <div className="p-3 bg-white rounded-xl border border-slate-200 shadow-2xs space-y-1.5">
                <div className="flex items-center justify-between text-xs font-semibold text-slate-600">
                  <span className="flex items-center gap-1 text-indigo-800">
                    <MapPin className="w-3.5 h-3.5 text-indigo-600" />
                    Lokasi Wilayah
                  </span>
                  <span className="text-[10px] px-1.5 py-0.2 bg-indigo-50 text-indigo-700 rounded font-bold">10%</span>
                </div>
                <div className="text-xl font-black text-slate-900">
                  {data.score_breakdown?.location_score ?? 100}%
                </div>
                <div className="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                  <div 
                    className="h-full bg-indigo-600 rounded-full" 
                    style={{ width: `${data.score_breakdown?.location_score ?? 100}%` }} 
                  />
                </div>
                <p className="text-[10px] text-slate-400 leading-tight">
                  Kesesuaian domisili dengan lokasi penempatan
                </p>
              </div>
            </div>
          </div>

          {/* Profil Kandidat vs Spesifikasi Lowongan */}
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            {/* Kartu Kandidat */}
            <div className="p-4 rounded-xl border border-slate-200 bg-white space-y-2">
              <div className="text-xs font-bold text-slate-500 uppercase tracking-wider flex items-center gap-1.5 border-b border-slate-100 pb-2">
                <User className="w-3.5 h-3.5 text-blue-600" />
                <span>Kandidat Pencari Kerja</span>
              </div>
              <div>
                <h4 className="font-bold text-slate-900 text-sm">{candidate.full_name}</h4>
                <div className="text-xs text-slate-500 font-mono">NIK: {candidate.nik}</div>
              </div>
              <div className="space-y-1 text-xs text-slate-600 pt-1">
                <div className="flex items-center gap-1.5">
                  <GraduationCap className="w-3.5 h-3.5 text-slate-400 shrink-0" />
                  <span>{candidate.education_level || '-'}</span>
                </div>
                <div className="flex items-center gap-1.5">
                  <Briefcase className="w-3.5 h-3.5 text-slate-400 shrink-0" />
                  <span>Minat: {candidate.desired_occupation || 'Umum'}</span>
                </div>
                <div className="flex items-center gap-1.5">
                  <MapPin className="w-3.5 h-3.5 text-slate-400 shrink-0" />
                  <span>{candidate.location || '-'}</span>
                </div>
              </div>
            </div>

            {/* Kartu Lowongan */}
            <div className="p-4 rounded-xl border border-slate-200 bg-white space-y-2">
              <div className="text-xs font-bold text-slate-500 uppercase tracking-wider flex items-center gap-1.5 border-b border-slate-100 pb-2">
                <Building2 className="w-3.5 h-3.5 text-blue-600" />
                <span>Posisi Lowongan Industri</span>
              </div>
              <div>
                <h4 className="font-bold text-slate-900 text-sm">{job.judul_lowongan}</h4>
                <div className="text-xs font-semibold text-slate-700">{job.nama_perusahaan}</div>
              </div>
              <div className="space-y-1 text-xs text-slate-600 pt-1">
                <div className="flex items-center gap-1.5">
                  <Briefcase className="w-3.5 h-3.5 text-slate-400 shrink-0" />
                  <span>
                    {job.tipe_pekerjaan} &bull; {job.sistem_kerja}
                  </span>
                </div>
                <div className="flex items-center gap-1.5">
                  <MapPin className="w-3.5 h-3.5 text-slate-400 shrink-0" />
                  <span>{job.location || '-'}</span>
                </div>
              </div>
            </div>
          </div>

          {/* Keterangan Bobot Wajib & Tambahan */}
          <div className="flex flex-wrap items-center justify-between gap-2 text-[11px] text-slate-600 bg-slate-50 border border-slate-200/90 rounded-xl px-3.5 py-2">
            <div className="flex items-center gap-2 flex-wrap">
              <span className="font-semibold text-slate-700">Aturan Bobot Keahlian:</span>
              <span className="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200 uppercase">
                Wajib (Bobot 1.0)
              </span>
              <span className="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded bg-slate-100 text-slate-600 border border-slate-200 uppercase">
                Tambahan (Bobot 0.7)
              </span>
            </div>
            <div className="text-[11px] text-emerald-700 font-medium">
              Pencocokan Semantik & Konsep Industri (Bebas ID Taksonomi)
            </div>
          </div>

          {/* Rincian Komparasi Dua Kolom: Matched vs Gap */}
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            {/* Kolom Kiri: Kompetensi Memenuhi (Matched Skills) */}
            <div className="border border-emerald-200 bg-emerald-50/30 rounded-xl p-4 space-y-3">
              <div className="flex items-center justify-between border-b border-emerald-200/60 pb-2">
                <span className="font-bold text-emerald-900 text-xs sm:text-sm flex items-center gap-1.5">
                  <CheckCircle2 className="w-4 h-4 text-emerald-600 shrink-0" />
                  Kompetensi Terpenuhi ({matched_skills.length})
                </span>
                <span className="text-[10px] text-emerald-700 font-semibold bg-emerald-100 px-2 py-0.5 rounded-full">
                  Cocok
                </span>
              </div>

              {matched_skills.length > 0 ? (
                <div className="space-y-2.5 max-h-72 overflow-y-auto pr-1">
                  {matched_skills.map((sk) => (
                    <div
                      key={sk.id}
                      className="p-3 bg-white border border-emerald-200 rounded-lg shadow-2xs space-y-1.5"
                    >
                      <div className="flex items-start justify-between gap-2">
                        <span className="font-bold text-slate-900 text-xs leading-snug">
                          {sk.title}
                        </span>
                        <span
                          className={`text-[9px] px-1.5 py-0.5 rounded font-bold uppercase shrink-0 ${
                            sk.tipe_keahlian === 'wajib'
                              ? 'bg-blue-50 text-blue-700 border border-blue-200'
                              : 'bg-slate-100 text-slate-600 border border-slate-200'
                          }`}
                          title={sk.tipe_keahlian === 'wajib' ? 'Keahlian Wajib (Bobot 1.0)' : 'Keahlian Tambahan (Bobot 0.7)'}
                        >
                          {sk.tipe_keahlian === 'wajib' ? 'Wajib' : 'Tambahan'}
                        </span>
                      </div>

                      <div className="flex flex-wrap items-center gap-2 text-[10px] text-slate-500 pt-0.5">
                        <span className="bg-emerald-50 text-emerald-800 border border-emerald-200 px-1.5 py-0.2 rounded font-medium">
                          {sk.match_type}
                        </span>
                        {sk.matched_with && sk.matched_with !== sk.title && (
                          <span className="text-slate-400 italic">
                            (Padanan: {sk.matched_with})
                          </span>
                        )}
                        <span className="ml-auto text-slate-400 capitalize">
                          Level: {sk.level_kemahiran}
                        </span>
                      </div>
                    </div>
                  ))}
                </div>
              ) : (
                <div className="p-6 text-center text-xs text-slate-400 italic">
                  Belum ada kompetensi yang memenuhi kriteria lowongan ini.
                </div>
              )}
            </div>

            {/* Kolom Kanan: Kesenjangan Skill (Missing Skills / GAP) */}
            <div className="border border-amber-200 bg-amber-50/30 rounded-xl p-4 space-y-3">
              <div className="flex items-center justify-between border-b border-amber-200/60 pb-2">
                <span className="font-bold text-amber-900 text-xs sm:text-sm flex items-center gap-1.5">
                  <AlertTriangle className="w-4 h-4 text-amber-600 shrink-0" />
                  Kesenjangan Skill / Gap ({gap_skills.length})
                </span>
                <span className="text-[10px] text-amber-700 font-semibold bg-amber-100 px-2 py-0.5 rounded-full">
                  Belum Dimiliki
                </span>
              </div>

              {gap_skills.length > 0 ? (
                <div className="space-y-2.5 max-h-72 overflow-y-auto pr-1">
                  {gap_skills.map((sk) => (
                    <div
                      key={sk.id}
                      className="p-3 bg-white border border-amber-200 rounded-lg shadow-2xs space-y-1.5"
                    >
                      <div className="flex items-start justify-between gap-2">
                        <span className="font-bold text-slate-900 text-xs leading-snug">
                          {sk.title}
                        </span>
                        <span
                          className={`text-[9px] px-1.5 py-0.5 rounded font-bold uppercase shrink-0 ${
                            sk.tipe_keahlian === 'wajib'
                              ? 'bg-red-50 text-red-700 border border-red-200'
                              : 'bg-slate-100 text-slate-600 border border-slate-200'
                          }`}
                          title={sk.tipe_keahlian === 'wajib' ? 'Keahlian Wajib (Bobot 1.0)' : 'Keahlian Tambahan (Bobot 0.7)'}
                        >
                          {sk.tipe_keahlian === 'wajib' ? 'Wajib Dimiliki' : 'Tambahan'}
                        </span>
                      </div>

                      <div className="flex items-center justify-between text-[10px] text-slate-500 pt-0.5">
                        <span className="text-amber-800 bg-amber-50 px-1.5 py-0.2 rounded font-medium">
                          Perlu Pelatihan / Upskilling
                        </span>
                        <span className="text-slate-400 capitalize">
                          Ekspektasi: {sk.level_kemahiran}
                        </span>
                      </div>
                    </div>
                  ))}
                </div>
              ) : (
                <div className="p-6 text-center text-xs text-emerald-700 bg-emerald-50 rounded-lg font-medium">
                 Semua kualifikasi lowongan telah dipenuhi pelamar.
                </div>
              )}
            </div>
          </div>
        </div>

        {/* Footer Actions */}
        <div className="px-6 py-4 border-t border-slate-200 bg-slate-50 flex items-center justify-between">
          <button
            type="button"
            onClick={onClose}
            className="px-4 py-2 text-xs sm:text-sm font-medium rounded-md border border-slate-300 text-slate-700 bg-white hover:bg-slate-100 transition-colors"
          >
            Tutup
          </button>

          <div className="flex items-center gap-2">
            {score >= 70 && (
              <button
                type="button"
                onClick={() => {
                  if (onRecommend) onRecommend(data);
                  else alert(`Kandidat ${candidate.full_name} berhasil direkomendasikan ke ${job.nama_perusahaan}!`);
                }}
                className="inline-flex items-center gap-1.5 px-4 py-2 text-xs sm:text-sm font-semibold rounded-md bg-[#0E385E] text-white hover:bg-[#163A5F] transition-colors shadow-xs"
              >
                <Send className="w-3.5 h-3.5" />
                <span>Rekomendasikan ke Perusahaan</span>
              </button>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
