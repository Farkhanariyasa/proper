'use client';

import React from 'react';
import { useRouter } from 'next/navigation';
import {
  X,
  Building2,
  MapPin,
  Calendar,
  Briefcase,
  GraduationCap,
  Layers,
  DollarSign,
  Users,
  CheckCircle2,
  Clock,
  ExternalLink,
  Flame,
} from 'lucide-react';
import { LowonganItem } from '@/types/lowongan';

interface LowonganDetailModalProps {
  isOpen: boolean;
  onClose: () => void;
  lowongan: LowonganItem | null;
  onEdit?: (item: LowonganItem) => void;
}

export default function LowonganDetailModal({
  isOpen,
  onClose,
  lowongan,
  onEdit,
}: LowonganDetailModalProps) {
  const router = useRouter();

  if (!isOpen || !lowongan) return null;

  const formatCurrency = (val: number | null | undefined) => {
    if (val === null || val === undefined) return '-';
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      maximumFractionDigits: 0,
    }).format(val);
  };

  const formatDate = (isoString?: string | null) => {
    if (!isoString) return '-';
    try {
      return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'medium',
      }).format(new Date(isoString));
    } catch {
      return isoString;
    }
  };

  const isHtml = (str?: string | null) => {
    if (!str) return false;
    return /<[a-z][\s\S]*>/i.test(str);
  };

  const getStatusBadge = (status: string) => {
    switch (status) {
      case 'Published':
        return 'bg-emerald-50 text-emerald-700 border-emerald-200';
      case 'Closed':
        return 'bg-red-50 text-red-700 border-red-200';
      case 'Archived':
        return 'bg-slate-100 text-slate-600 border-slate-300';
      default:
        return 'bg-amber-50 text-amber-700 border-amber-200';
    }
  };

  return (
    <div className="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
      <div className="bg-white rounded-xl shadow-2xl border border-slate-200 w-full max-w-3xl max-h-[92vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
        {/* Header Modal */}
        <div className="px-6 py-5 border-b border-slate-200 flex items-start justify-between bg-slate-50/70">
          <div className="space-y-1.5 pr-4">
            <div className="flex flex-wrap items-center gap-2">
              <span
                className={`text-xs px-2.5 py-0.5 rounded-full font-semibold border ${getStatusBadge(
                  lowongan.status_lowongan
                )}`}
              >
                {lowongan.status_lowongan}
              </span>
              <span className="text-xs px-2 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200 font-medium">
                {lowongan.tipe_pekerjaan}
              </span>
              <span className="text-xs px-2 py-0.5 rounded bg-slate-100 text-slate-700 font-medium">
                {lowongan.sistem_kerja}
              </span>
              {lowongan.is_disabilitas && (
                <span className="text-xs px-2 py-0.5 rounded bg-purple-50 text-purple-700 border border-purple-200 font-medium">
                  Inklusif Disabilitas
                </span>
              )}
            </div>

            <h3 className="text-lg sm:text-xl font-bold text-slate-900 leading-snug">
              {lowongan.judul_lowongan}
            </h3>

            <div className="flex flex-wrap items-center gap-4 text-xs text-slate-600">
              <span className="flex items-center gap-1.5 font-medium text-slate-800">
                <Building2 className="w-4 h-4 text-slate-400" />
                {lowongan.nama_perusahaan}
              </span>
              <span className="flex items-center gap-1.5">
                <MapPin className="w-4 h-4 text-slate-400" />
                {lowongan.regency?.name || '-'}, {lowongan.province?.name || '-'}
              </span>
              <span className="flex items-center gap-1.5">
                <Users className="w-4 h-4 text-slate-400" />
                Dibutuhkan: {lowongan.jumlah_kebutuhan} orang
              </span>
            </div>
          </div>

          <button
            onClick={onClose}
            className="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-200 transition-colors"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Modal Content */}
        <div className="flex-1 overflow-y-auto p-6 space-y-6 text-xs sm:text-sm">
          {/* Kompensasi & Penempatan */}
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div className="p-3.5 rounded-lg border border-slate-200 bg-slate-50/60 space-y-1">
              <span className="text-[11px] font-bold text-slate-700 flex items-center gap-1">
                <MapPin className="w-3.5 h-3.5 text-slate-600" />
                Lokasi Penempatan:
              </span>
              <div className="font-semibold text-slate-900">
                {lowongan.regency?.name || '-'}, {lowongan.province?.name || '-'}
              </div>
            </div>

            <div className="p-3.5 rounded-lg border border-emerald-100 bg-emerald-50/40 space-y-1">
              <span className="text-[11px] font-bold text-emerald-900 flex items-center gap-1">
                <DollarSign className="w-3.5 h-3.5 text-emerald-700" />
                Rentang Gaji / Kompensasi:
              </span>
              <div className="font-semibold text-slate-900">
                {lowongan.gaji_tampilkan ? (
                  lowongan.gaji_minimal || lowongan.gaji_maksimal ? (
                    `${formatCurrency(lowongan.gaji_minimal)} - ${formatCurrency(
                      lowongan.gaji_maksimal
                    )}`
                  ) : (
                    'Kompetitif'
                  )
                ) : (
                  <span className="text-slate-500 italic">Disembunyikan dari publik</span>
                )}
              </div>
            </div>
          </div>

          {/* Deskripsi Pekerjaan */}
          <div>
            <h4 className="text-xs font-bold text-slate-900 uppercase tracking-wider mb-2">
              Deskripsi Pekerjaan
            </h4>
            {isHtml(lowongan.deskripsi_pekerjaan) ? (
              <div
                className="p-4 rounded-lg bg-slate-50 border border-slate-200 text-slate-700 leading-relaxed text-xs sm:text-sm space-y-2 [&_ol]:list-decimal [&_ol]:pl-5 [&_ol]:space-y-1.5 [&_ul]:list-disc [&_ul]:pl-5 [&_ul]:space-y-1.5 [&_li]:pl-1 [&_p]:mb-2 [&_p:last-child]:mb-0 [&_strong]:font-semibold [&_b]:font-semibold [&_a]:text-blue-600 [&_a]:underline"
                dangerouslySetInnerHTML={{ __html: lowongan.deskripsi_pekerjaan! }}
              />
            ) : (
              <div className="p-4 rounded-lg bg-slate-50 border border-slate-200 text-slate-700 whitespace-pre-line leading-relaxed text-xs sm:text-sm">
                {lowongan.deskripsi_pekerjaan || (
                  <span className="italic text-slate-400">Deskripsi pekerjaan belum tersedia.</span>
                )}
              </div>
            )}
          </div>

          {/* Kualifikasi & Persyaratan */}
          <div>
            <h4 className="text-xs font-bold text-slate-900 uppercase tracking-wider mb-2">
              Kualifikasi & Persyaratan Pelamar
            </h4>
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
              <div className="p-3 rounded-lg border border-slate-200 flex items-center justify-between">
                <span className="text-slate-500">Pendidikan Minimal</span>
                <span className="font-semibold text-slate-800">
                  {lowongan.education_level?.name || '-'}
                </span>
              </div>
              <div className="p-3 rounded-lg border border-slate-200 flex items-center justify-between">
                <span className="text-slate-500">Jurusan Studi</span>
                <span className="font-semibold text-slate-800">
                  {lowongan.jurusan_studi || 'Semua Jurusan'}
                </span>
              </div>
              <div className="p-3 rounded-lg border border-slate-200 flex items-center justify-between">
                <span className="text-slate-500">Pengalaman Kerja</span>
                <span className="font-semibold text-slate-800">
                  {lowongan.pengalaman_minimal_tahun === 0
                    ? 'Fresh Graduate (0 Tahun)'
                    : `Minimal ${lowongan.pengalaman_minimal_tahun} Tahun`}
                </span>
              </div>
              <div className="p-3 rounded-lg border border-slate-200 flex items-center justify-between">
                <span className="text-slate-500">Batasan Usia & Gender</span>
                <span className="font-semibold text-slate-800">
                  {lowongan.usia_minimal || lowongan.usia_maksimal
                    ? `${lowongan.usia_minimal || 18} - ${lowongan.usia_maksimal || 60} Thn`
                    : 'Fleksibel'}{' '}
                  • {lowongan.jenis_kelamin}
                </span>
              </div>
            </div>

            {lowongan.persyaratan_tambahan && (
              <div className="mt-3 p-3 rounded-lg border border-slate-200 bg-slate-50/50 text-xs">
                <span className="font-semibold text-slate-700 block mb-1">
                  Persyaratan Tambahan:
                </span>
                {isHtml(lowongan.persyaratan_tambahan) ? (
                  <div
                    className="text-slate-600 leading-relaxed space-y-1.5 [&_ol]:list-decimal [&_ol]:pl-5 [&_ol]:space-y-1 [&_ul]:list-disc [&_ul]:pl-5 [&_ul]:space-y-1 [&_li]:pl-1 [&_p]:mb-1 [&_p:last-child]:mb-0 [&_strong]:font-semibold [&_b]:font-semibold"
                    dangerouslySetInnerHTML={{ __html: lowongan.persyaratan_tambahan }}
                  />
                ) : (
                  <p className="text-slate-600 whitespace-pre-line">{lowongan.persyaratan_tambahan}</p>
                )}
              </div>
            )}
          </div>

          {/* Keahlian yang Disyaratkan */}
          <div>
            <h4 className="text-xs font-bold text-slate-900 uppercase tracking-wider mb-2 flex items-center justify-between">
              <span className="flex items-center gap-1.5">
                <Layers className="w-3.5 h-3.5 text-blue-600" />
                Spesifikasi Keahlian Formasi ({lowongan.skills?.length || 0})
              </span>
            </h4>

            {lowongan.skills && lowongan.skills.length > 0 ? (
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                {lowongan.skills.map((sk) => (
                  <div
                    key={sk.id}
                    className="p-3 rounded-lg border border-slate-200 bg-white hover:border-blue-300 transition-colors flex items-start justify-between gap-2"
                  >
                    <div>
                      <div className="font-semibold text-slate-800 text-xs">{sk.title}</div>
                      {sk.title_en && (
                        <div className="text-[10px] text-slate-400 italic">{sk.title_en}</div>
                      )}
                    </div>
                    <div className="flex flex-col items-end gap-1 shrink-0">
                      <span
                        className={`text-[10px] px-1.5 py-0.5 rounded font-semibold ${
                          sk.tipe_keahlian === 'wajib'
                            ? 'bg-blue-50 text-blue-700 border border-blue-200'
                            : 'bg-slate-100 text-slate-600'
                        }`}
                      >
                        {sk.tipe_keahlian === 'wajib' ? 'Wajib' : 'Tambahan'}
                      </span>
                      <span className="text-[10px] text-slate-400 capitalize">
                        Level: {sk.level_kemahiran}
                      </span>
                    </div>
                  </div>
                ))}
              </div>
            ) : (
              <div className="p-3 border border-dashed border-slate-200 rounded-lg text-slate-400 text-center text-xs">
                Tidak ada keahlian khusus yang didaftarkan.
              </div>
            )}
          </div>

          {/* Timeline & Penempatan */}
          <div className="p-4 rounded-lg bg-slate-50 border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4 text-xs">
            <div className="space-y-1">
              <span className="text-slate-500 font-medium block">Lokasi Lengkap Penempatan:</span>
              <span className="font-semibold text-slate-800">
                {lowongan.alamat_lengkap_penempatan ||
                  `${lowongan.regency?.name || ''}, ${lowongan.province?.name || ''}`}
              </span>
            </div>

            <div className="flex items-center gap-4 text-slate-600 sm:text-right">
              <div>
                <span className="text-slate-400 block text-[10px]">Tanggal Buka:</span>
                <span className="font-medium text-slate-700">{formatDate(lowongan.tanggal_buka)}</span>
              </div>
              <div className="border-l border-slate-200 pl-4">
                <span className="text-slate-400 block text-[10px]">Batas Tutup:</span>
                <span className="font-semibold text-red-600">{formatDate(lowongan.tanggal_tutup)}</span>
              </div>
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
            {onEdit && (
              <button
                type="button"
                onClick={() => {
                  onClose();
                  onEdit(lowongan);
                }}
                className="px-4 py-2 text-xs sm:text-sm font-semibold rounded-md border border-blue-300 bg-blue-50 text-blue-700 hover:bg-blue-100 transition-colors"
              >
                Edit Lowongan
              </button>
            )}

            <button
              type="button"
              onClick={() => {
                onClose();
                router.push(`/rekomendasi?lowongan_id=${lowongan.id}`);
              }}
              className="inline-flex items-center gap-1.5 px-4 py-2 text-xs sm:text-sm font-semibold rounded-md bg-[#0E385E] text-white hover:bg-[#163A5F] transition-colors shadow-xs"
            >
              <Flame className="w-4 h-4 text-amber-400" />
              <span>Jodohkan dengan Pelamar</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}
