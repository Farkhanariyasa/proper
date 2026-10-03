'use client';

import React, { useState, useEffect, useMemo } from 'react';
import {
  X,
  Loader2,
  AlertCircle,
  Briefcase,
  GraduationCap,
  Layers,
  MapPin,
  DollarSign,
  Save,
  Check,
  ChevronRight,
  ChevronLeft,
} from 'lucide-react';
import {
  CreateLowonganPayload,
  KbjiOption,
  LowonganItem,
} from '@/types/lowongan';
import { Province, Regency, EducationLevel } from '@/types/job-seeker';
import { getProvinces, getRegencies } from '@/services/wilayah';
import { getEducationLevels } from '@/services/job-seeker';
import {
  createLowonganApi,
  getLowonganOptionsApi,
  updateLowonganApi,
} from '@/services/lowongan';
import SearchableSelect from '@/components/ui/SearchableSelect';
import KbjiSelector from './KbjiSelector';
import LowonganSkillPicker, { SelectedSkillRequirement } from './LowonganSkillPicker';

interface LowonganFormModalProps {
  isOpen: boolean;
  onClose: () => void;
  onSuccess: () => void;
  initialData?: LowonganItem | null;
}

export default function LowonganFormModal({
  isOpen,
  onClose,
  onSuccess,
  initialData,
}: LowonganFormModalProps) {
  const isEdit = Boolean(initialData);

  // Wizard Stepper State: 1 | 2 | 3
  const [currentStep, setCurrentStep] = useState<1 | 2 | 3>(1);

  // Master Data
  const [provinces, setProvinces] = useState<Province[]>([]);
  const [regencies, setRegencies] = useState<Regency[]>([]);
  const [educationLevels, setEducationLevels] = useState<EducationLevel[]>([]);
  const [tipePekerjaanOptions, setTipePekerjaanOptions] = useState<string[]>([
    'Full-Time',
    'Part-Time',
    'Kontrak',
    'Magang',
    'Freelance',
  ]);
  const [sistemKerjaOptions, setSistemKerjaOptions] = useState<string[]>([
    'WFO',
    'WFH',
    'Hybrid',
  ]);
  const [statusOptions, setStatusOptions] = useState<string[]>([
    'Draft',
    'Published',
    'Closed',
    'Archived',
  ]);

  // Loading States
  const [isLoadingMaster, setIsLoadingMaster] = useState(false);
  const [isLoadingRegencies, setIsLoadingRegencies] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);

  // Form State - Step 1: Identitas & Posisi
  const [judulLowongan, setJudulLowongan] = useState('');
  const [namaPerusahaan, setNamaPerusahaan] = useState('');
  const [kbjiId, setKbjiId] = useState<number | null>(null);
  const [kbjiLabel, setKbjiLabel] = useState('');
  const [deskripsiPekerjaan, setDeskripsiPekerjaan] = useState('');
  const [tipePekerjaan, setTipePekerjaan] = useState('Full-Time');
  const [sistemKerja, setSistemKerja] = useState('WFO');
  const [jumlahKebutuhan, setJumlahKebutuhan] = useState(1);

  // Form State - Step 2: Kualifikasi & Skill
  const [educationLevelId, setEducationLevelId] = useState<number | ''>('');
  const [jurusanStudi, setJurusanStudi] = useState('');
  const [pengalamanMinimalTahun, setPengalamanMinimalTahun] = useState(0);
  const [usiaMinimal, setUsiaMinimal] = useState<number | ''>('');
  const [usiaMaksimal, setUsiaMaksimal] = useState<number | ''>('');
  const [jenisKelamin, setJenisKelamin] = useState('Semua');
  const [isDisabilitas, setIsDisabilitas] = useState(false);
  const [persyaratanTambahan, setPersyaratanTambahan] = useState('');
  const [skills, setSkills] = useState<SelectedSkillRequirement[]>([]);

  // Form State - Step 3: Lokasi, Gaji & Publikasi
  const [provinsiId, setProvinsiId] = useState('');
  const [regencyId, setRegencyId] = useState('');
  const [alamatLengkap, setAlamatLengkap] = useState('');
  const [gajiTampilkan, setGajiTampilkan] = useState(true);
  const [gajiMinimal, setGajiMinimal] = useState<number | ''>('');
  const [gajiMaksimal, setGajiMaksimal] = useState<number | ''>('');
  const [statusLowongan, setStatusLowongan] = useState<'Draft' | 'Published' | 'Closed' | 'Archived'>('Draft');
  const [tanggalBuka, setTanggalBuka] = useState('');
  const [tanggalTutup, setTanggalTutup] = useState('');

  // Opsi wilayah untuk SearchableSelect diurutkan berdasarkan ID
  const provinceOptions = useMemo(
    () =>
      provinces
        .slice()
        .sort((a, b) => a.id.localeCompare(b.id, undefined, { numeric: true }))
        .map((p) => ({
          value: p.id,
          label: p.name,
          code: p.id,
        })),
    [provinces]
  );

  const regencyOptions = useMemo(
    () =>
      regencies
        .slice()
        .sort((a, b) => a.id.localeCompare(b.id, undefined, { numeric: true }))
        .map((r) => ({
          value: r.id,
          label: r.name,
          code: r.id,
        })),
    [regencies]
  );

  // Load master data on modal open
  useEffect(() => {
    if (!isOpen) return;

    const loadMasters = async () => {
      setIsLoadingMaster(true);
      try {
        const [provList, eduList, opts] = await Promise.all([
          getProvinces(),
          getEducationLevels(),
          getLowonganOptionsApi().catch(() => null),
        ]);
        setProvinces(provList);
        setEducationLevels(eduList);
        if (opts) {
          if (opts.tipe_pekerjaan) setTipePekerjaanOptions(opts.tipe_pekerjaan);
          if (opts.sistem_kerja) setSistemKerjaOptions(opts.sistem_kerja);
          if (opts.status_lowongan) setStatusOptions(opts.status_lowongan);
        }
      } catch (err) {
        console.error('Gagal memuat master data:', err);
      } finally {
        setIsLoadingMaster(false);
      }
    };

    loadMasters();
  }, [isOpen]);

  // Load regencies when provinsiId changes
  useEffect(() => {
    if (!provinsiId) {
      setRegencies([]);
      return;
    }

    const loadRegs = async () => {
      setIsLoadingRegencies(true);
      try {
        const regs = await getRegencies(provinsiId);
        setRegencies(regs);
      } catch (err) {
        console.error('Gagal memuat kabupaten:', err);
      } finally {
        setIsLoadingRegencies(false);
      }
    };

    loadRegs();
  }, [provinsiId]);

  // Populate initialData if edit mode or reset on open
  useEffect(() => {
    if (!isOpen) return;

    setCurrentStep(1);
    setErrorMessage(null);

    if (initialData) {
      setJudulLowongan(initialData.judul_lowongan || '');
      setNamaPerusahaan(initialData.nama_perusahaan || '');
      setKbjiId(initialData.kbji?.id || null);
      setKbjiLabel(initialData.kbji ? `${initialData.kbji.code} - ${initialData.kbji.title}` : '');
      setDeskripsiPekerjaan(initialData.deskripsi_pekerjaan || '');
      setTipePekerjaan(initialData.tipe_pekerjaan || 'Full-Time');
      setSistemKerja(initialData.sistem_kerja || 'WFO');
      setJumlahKebutuhan(initialData.jumlah_kebutuhan || 1);

      setEducationLevelId(initialData.education_level?.id || '');
      setJurusanStudi(initialData.jurusan_studi || '');
      setPengalamanMinimalTahun(initialData.pengalaman_minimal_tahun || 0);
      setUsiaMinimal(initialData.usia_minimal ?? '');
      setUsiaMaksimal(initialData.usia_maksimal ?? '');
      setJenisKelamin(initialData.jenis_kelamin || 'Semua');
      setIsDisabilitas(Boolean(initialData.is_disabilitas));
      setPersyaratanTambahan(initialData.persyaratan_tambahan || '');

      setProvinsiId(initialData.province?.id || '');
      setRegencyId(initialData.regency?.id || '');
      setAlamatLengkap(initialData.alamat_lengkap_penempatan || '');

      setGajiTampilkan(initialData.gaji_tampilkan ?? true);
      setGajiMinimal(initialData.gaji_minimal ?? '');
      setGajiMaksimal(initialData.gaji_maksimal ?? '');

      setStatusLowongan(initialData.status_lowongan || 'Draft');
      setTanggalBuka(initialData.tanggal_buka ? initialData.tanggal_buka.substring(0, 10) : '');
      setTanggalTutup(initialData.tanggal_tutup ? initialData.tanggal_tutup.substring(0, 10) : '');

      if (initialData.skills) {
        setSkills(
          initialData.skills.map((s) => ({
            esco_skill_id: s.id,
            title: s.title,
            title_en: s.title_en,
            tipe_keahlian: s.tipe_keahlian,
            level_kemahiran: s.level_kemahiran,
          }))
        );
      } else {
        setSkills([]);
      }
    } else {
      setJudulLowongan('');
      setNamaPerusahaan('');
      setKbjiId(null);
      setKbjiLabel('');
      setDeskripsiPekerjaan('');
      setTipePekerjaan('Full-Time');
      setSistemKerja('WFO');
      setJumlahKebutuhan(1);
      setEducationLevelId('');
      setJurusanStudi('');
      setPengalamanMinimalTahun(0);
      setUsiaMinimal('');
      setUsiaMaksimal('');
      setJenisKelamin('Semua');
      setIsDisabilitas(false);
      setPersyaratanTambahan('');
      setProvinsiId('');
      setRegencyId('');
      setAlamatLengkap('');
      setGajiTampilkan(true);
      setGajiMinimal('');
      setGajiMaksimal('');
      setStatusLowongan('Draft');
      setTanggalBuka(new Date().toISOString().substring(0, 10));
      const defaultClose = new Date();
      defaultClose.setDate(defaultClose.getDate() + 30);
      setTanggalTutup(defaultClose.toISOString().substring(0, 10));
      setSkills([]);
    }
  }, [isOpen, initialData]);

  if (!isOpen) return null;

  // Step Validations
  const validateStep1 = (): boolean => {
    setErrorMessage(null);
    if (!judulLowongan.trim()) {
      setErrorMessage('Judul posisi lowongan wajib diisi.');
      return false;
    }
    if (!namaPerusahaan.trim()) {
      setErrorMessage('Nama perusahaan/instansi wajib diisi.');
      return false;
    }
    if (!kbjiId) {
      setErrorMessage('Klasifikasi jabatan standar KBJI wajib dipilih.');
      return false;
    }
    if (!deskripsiPekerjaan.trim()) {
      setErrorMessage('Deskripsi pekerjaan wajib diisi.');
      return false;
    }
    return true;
  };

  const validateStep2 = (): boolean => {
    setErrorMessage(null);
    if (!educationLevelId) {
      setErrorMessage('Pendidikan minimal wajib dipilih.');
      return false;
    }
    if (skills.length === 0) {
      setErrorMessage('Minimal tentukan 1 keahlian standar ESCO yang disyaratkan.');
      return false;
    }
    return true;
  };

  const handleNextFromStep1 = () => {
    if (validateStep1()) {
      setCurrentStep(2);
    }
  };

  const handleNextFromStep2 = () => {
    if (validateStep2()) {
      setCurrentStep(3);
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setErrorMessage(null);

    if (!validateStep1()) {
      setCurrentStep(1);
      return;
    }
    if (!validateStep2()) {
      setCurrentStep(2);
      return;
    }

    if (!provinsiId) {
      setErrorMessage('Provinsi penempatan wajib dipilih.');
      return;
    }
    if (!regencyId) {
      setErrorMessage('Kabupaten / Kota penempatan wajib dipilih.');
      return;
    }
    if (!tanggalTutup) {
      setErrorMessage('Tanggal batas akhir (tutup) wajib diisi.');
      return;
    }

    const payload: CreateLowonganPayload = {
      judul_lowongan: judulLowongan,
      nama_perusahaan: namaPerusahaan,
      kbji_id: kbjiId!,
      deskripsi_pekerjaan: deskripsiPekerjaan,
      tipe_pekerjaan: tipePekerjaan,
      sistem_kerja: sistemKerja,
      jumlah_kebutuhan: Number(jumlahKebutuhan) || 1,
      education_level_id: Number(educationLevelId),
      jurusan_studi: jurusanStudi || undefined,
      pengalaman_minimal_tahun: Number(pengalamanMinimalTahun) || 0,
      usia_minimal: usiaMinimal !== '' ? Number(usiaMinimal) : undefined,
      usia_maksimal: usiaMaksimal !== '' ? Number(usiaMaksimal) : undefined,
      jenis_kelamin: jenisKelamin,
      is_disabilitas: isDisabilitas,
      persyaratan_tambahan: persyaratanTambahan || undefined,
      provinsi_id: provinsiId,
      regency_id: regencyId,
      alamat_lengkap_penempatan: alamatLengkap || undefined,
      gaji_tampilkan: gajiTampilkan,
      gaji_minimal: gajiMinimal !== '' ? Number(gajiMinimal) : undefined,
      gaji_maksimal: gajiMaksimal !== '' ? Number(gajiMaksimal) : undefined,
      status_lowongan: statusLowongan,
      tanggal_buka: tanggalBuka ? `${tanggalBuka} 00:00:00` : undefined,
      tanggal_tutup: `${tanggalTutup} 23:59:59`,
      skills: skills.map((s) => ({
        esco_skill_id: s.esco_skill_id,
        tipe_keahlian: s.tipe_keahlian,
        level_kemahiran: s.level_kemahiran,
      })),
    };

    setIsSubmitting(true);
    try {
      if (isEdit && initialData) {
        await updateLowonganApi(initialData.id, payload);
      } else {
        await createLowonganApi(payload);
      }
      onSuccess();
      onClose();
    } catch (err: unknown) {
      if (err instanceof Error) {
        setErrorMessage(err.message);
      } else {
        setErrorMessage('Terjadi kesalahan saat menyimpan data.');
      }
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
      <div className="bg-white rounded-xl shadow-2xl border border-slate-200 w-full max-w-4xl max-h-[92vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
        {/* Header Modal */}
        <div className="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50/70">
          <div>
            <h3 className="text-base sm:text-lg font-bold text-slate-900">
              {isEdit ? 'Perbarui Lowongan Kerja' : 'Tambah Lowongan Kerja Baru'}
            </h3>
          </div>
          <button
            onClick={onClose}
            className="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-200 transition-colors"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* 3-Step Wizard Navigation Bar */}
        <div className="bg-slate-50 border-b border-slate-200 px-4 sm:px-8 py-3">
          <div className="flex items-center justify-between max-w-2xl mx-auto">
            {/* Step 1 */}
            <button
              type="button"
              onClick={() => {
                if (currentStep > 1) setCurrentStep(1);
              }}
              className="flex items-center gap-2 group text-left cursor-pointer focus:outline-hidden"
            >
              <div
                className={`w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold transition-colors ${
                  currentStep === 1
                    ? 'bg-[#0E385E] text-white ring-4 ring-[#0E385E]/15'
                    : currentStep > 1
                    ? 'bg-emerald-600 text-white'
                    : 'bg-slate-200 text-slate-500'
                }`}
              >
                {currentStep > 1 ? <Check className="w-3.5 h-3.5" /> : '1'}
              </div>
              <div className="hidden sm:block">
                <div
                  className={`text-xs font-semibold ${
                    currentStep === 1 ? 'text-[#0E385E]' : 'text-slate-700'
                  }`}
                >
                  Posisi & Perusahaan
                </div>
                <div className="text-[10px] text-slate-400">Identitas & KBJI</div>
              </div>
            </button>

            {/* Separator 1 */}
            <div
              className={`flex-1 h-0.5 mx-3 sm:mx-4 transition-colors ${
                currentStep > 1 ? 'bg-emerald-500' : 'bg-slate-200'
              }`}
            />

            {/* Step 2 */}
            <button
              type="button"
              onClick={() => {
                if (currentStep > 2) setCurrentStep(2);
                else if (currentStep === 1 && validateStep1()) setCurrentStep(2);
              }}
              className="flex items-center gap-2 group text-left cursor-pointer focus:outline-hidden"
            >
              <div
                className={`w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold transition-colors ${
                  currentStep === 2
                    ? 'bg-[#0E385E] text-white ring-4 ring-[#0E385E]/15'
                    : currentStep > 2
                    ? 'bg-emerald-600 text-white'
                    : 'bg-slate-200 text-slate-500'
                }`}
              >
                {currentStep > 2 ? <Check className="w-3.5 h-3.5" /> : '2'}
              </div>
              <div className="hidden sm:block">
                <div
                  className={`text-xs font-semibold ${
                    currentStep === 2 ? 'text-[#0E385E]' : 'text-slate-700'
                  }`}
                >
                  Kualifikasi & Skill
                </div>
                <div className="text-[10px] text-slate-400">Pendidikan & ESCO</div>
              </div>
            </button>

            {/* Separator 2 */}
            <div
              className={`flex-1 h-0.5 mx-3 sm:mx-4 transition-colors ${
                currentStep > 2 ? 'bg-emerald-500' : 'bg-slate-200'
              }`}
            />

            {/* Step 3 */}
            <button
              type="button"
              onClick={() => {
                if (currentStep < 3) {
                  if (currentStep === 1 && validateStep1()) {
                    setCurrentStep(2);
                  } else if (currentStep === 2 && validateStep2()) {
                    setCurrentStep(3);
                  }
                }
              }}
              className="flex items-center gap-2 group text-left cursor-pointer focus:outline-hidden"
            >
              <div
                className={`w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold transition-colors ${
                  currentStep === 3
                    ? 'bg-[#0E385E] text-white ring-4 ring-[#0E385E]/15'
                    : 'bg-slate-200 text-slate-500'
                }`}
              >
                3
              </div>
              <div className="hidden sm:block">
                <div
                  className={`text-xs font-semibold ${
                    currentStep === 3 ? 'text-[#0E385E]' : 'text-slate-700'
                  }`}
                >
                  Lokasi & Gaji
                </div>
                <div className="text-[10px] text-slate-400">Penempatan & Publikasi</div>
              </div>
            </button>
          </div>
        </div>

        {/* Form Body */}
        <form onSubmit={handleSubmit} className="flex-1 overflow-y-auto p-6 space-y-6">
          {errorMessage && (
            <div className="p-3.5 rounded-lg bg-red-50 border border-red-200 text-red-700 text-xs flex items-start gap-2.5">
              <AlertCircle className="w-4 h-4 shrink-0 mt-0.5" />
              <span>{errorMessage}</span>
            </div>
          )}

          {/* ================= STEP 1: IDENTITAS & POSISI PEKERJAAN ================= */}
          {currentStep === 1 && (
            <div className="space-y-4 animate-in fade-in duration-150">
              <div className="border-b border-slate-100 pb-2">
                <h4 className="text-sm font-bold text-slate-900 flex items-center gap-2">
                  <Briefcase className="w-4 h-4 text-blue-600" />
                 Identitas Posisi & Perusahaan
                </h4>
                <p className="text-xs text-slate-500">
                  Masukkan informasi utama lowongan kerja dan klasifikasi standar jabatan.
                </p>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label className="block text-xs font-semibold text-slate-700 mb-1">
                    Judul Posisi Lowongan <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="text"
                    required
                    placeholder="Contoh: Senior Fullstack Engineer"
                    value={judulLowongan}
                    onChange={(e) => setJudulLowongan(e.target.value)}
                    className="w-full px-3 py-2 text-xs sm:text-sm rounded-md border border-slate-300 text-slate-800 placeholder-slate-400 focus:outline-hidden focus:border-blue-600"
                  />
                </div>

                <div>
                  <label className="block text-xs font-semibold text-slate-700 mb-1">
                    Nama Perusahaan / Instansi <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="text"
                    required
                    placeholder="Contoh: PT Telkom Digital Solusi"
                    value={namaPerusahaan}
                    onChange={(e) => setNamaPerusahaan(e.target.value)}
                    className="w-full px-3 py-2 text-xs sm:text-sm rounded-md border border-slate-300 text-slate-800 placeholder-slate-400 focus:outline-hidden focus:border-blue-600"
                  />
                </div>
              </div>

              {/* KBJI Selector */}
              <KbjiSelector
                value={kbjiId}
                selectedLabel={kbjiLabel}
                onChange={(id, opt) => {
                  setKbjiId(id);
                  setKbjiLabel(`${opt.code} - ${opt.title}`);
                }}
              />

              <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                  <label className="block text-xs font-semibold text-slate-700 mb-1">
                    Tipe Pekerjaan <span className="text-red-500">*</span>
                  </label>
                  <select
                    value={tipePekerjaan}
                    onChange={(e) => setTipePekerjaan(e.target.value)}
                    className="w-full px-3 py-2 text-xs sm:text-sm rounded-md border border-slate-300 bg-white text-slate-800 focus:outline-hidden focus:border-blue-600"
                  >
                    {tipePekerjaanOptions.map((opt) => (
                      <option key={opt} value={opt}>
                        {opt}
                      </option>
                    ))}
                  </select>
                </div>

                <div>
                  <label className="block text-xs font-semibold text-slate-700 mb-1">
                    Sistem Kerja <span className="text-red-500">*</span>
                  </label>
                  <select
                    value={sistemKerja}
                    onChange={(e) => setSistemKerja(e.target.value)}
                    className="w-full px-3 py-2 text-xs sm:text-sm rounded-md border border-slate-300 bg-white text-slate-800 focus:outline-hidden focus:border-blue-600"
                  >
                    {sistemKerjaOptions.map((opt) => (
                      <option key={opt} value={opt}>
                        {opt}
                      </option>
                    ))}
                  </select>
                </div>

                <div>
                  <label className="block text-xs font-semibold text-slate-700 mb-1">
                    Jumlah Kebutuhan (Orang) <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="number"
                    min="1"
                    required
                    value={jumlahKebutuhan}
                    onChange={(e) => setJumlahKebutuhan(Math.max(1, parseInt(e.target.value) || 1))}
                    className="w-full px-3 py-2 text-xs sm:text-sm rounded-md border border-slate-300 text-slate-800 focus:outline-hidden focus:border-blue-600"
                  />
                </div>
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-700 mb-1">
                  Deskripsi Pekerjaan & Tanggung Jawab <span className="text-red-500">*</span>
                </label>
                <textarea
                  required
                  rows={4}
                  placeholder="Jelaskan ruang lingkup pekerjaan dan tanggung jawab utama..."
                  value={deskripsiPekerjaan}
                  onChange={(e) => setDeskripsiPekerjaan(e.target.value)}
                  className="w-full px-3 py-2 text-xs sm:text-sm rounded-md border border-slate-300 text-slate-800 placeholder-slate-400 focus:outline-hidden focus:border-blue-600"
                />
              </div>
            </div>
          )}

          {/* ================= STEP 2: KUALIFIKASI & SKILL ESCO ================= */}
          {currentStep === 2 && (
            <div className="space-y-4 animate-in fade-in duration-150">
              <div className="border-b border-slate-100 pb-2">
                <h4 className="text-sm font-bold text-slate-900 flex items-center gap-2">
                  <GraduationCap className="w-4 h-4 text-blue-600" />
                  Kualifikasi & Kriteria Keahlian
                </h4>
                <p className="text-xs text-slate-500">
                  Tentukan syarat pendidikan, pengalaman, dan kriteria keahlian.
                </p>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                  <label className="block text-xs font-semibold text-slate-700 mb-1">
                    Pendidikan Minimal <span className="text-red-500">*</span>
                  </label>
                  <select
                    required
                    value={educationLevelId}
                    onChange={(e) => setEducationLevelId(e.target.value ? Number(e.target.value) : '')}
                    className="w-full px-3 py-2 text-xs sm:text-sm rounded-md border border-slate-300 bg-white text-slate-800 focus:outline-hidden focus:border-blue-600"
                  >
                    <option value="">Pilih Jenjang...</option>
                    {educationLevels.map((lvl) => (
                      <option key={lvl.id} value={lvl.id}>
                        {lvl.name}
                      </option>
                    ))}
                  </select>
                </div>

                <div>
                  <label className="block text-xs font-semibold text-slate-700 mb-1">
                    Jurusan Studi (Opsional)
                  </label>
                  <input
                    type="text"
                    placeholder="Contoh: Teknik Informatika / Komputer"
                    value={jurusanStudi}
                    onChange={(e) => setJurusanStudi(e.target.value)}
                    className="w-full px-3 py-2 text-xs sm:text-sm rounded-md border border-slate-300 text-slate-800 placeholder-slate-400 focus:outline-hidden focus:border-blue-600"
                  />
                </div>

                <div>
                  <label className="block text-xs font-semibold text-slate-700 mb-1">
                    Pengalaman Minimal (Tahun) <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="number"
                    min="0"
                    required
                    placeholder="0 untuk Fresh Graduate"
                    value={pengalamanMinimalTahun}
                    onChange={(e) => setPengalamanMinimalTahun(Math.max(0, parseInt(e.target.value) || 0))}
                    className="w-full px-3 py-2 text-xs sm:text-sm rounded-md border border-slate-300 text-slate-800 focus:outline-hidden focus:border-blue-600"
                  />
                </div>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                  <label className="block text-xs font-semibold text-slate-700 mb-1">
                    Batasan Usia (Min - Max)
                  </label>
                  <div className="flex items-center gap-2">
                    <input
                      type="number"
                      min="15"
                      max="70"
                      placeholder="Min (18)"
                      value={usiaMinimal}
                      onChange={(e) => setUsiaMinimal(e.target.value ? parseInt(e.target.value) : '')}
                      className="w-full px-3 py-2 text-xs sm:text-sm rounded-md border border-slate-300 text-slate-800 focus:outline-hidden focus:border-blue-600"
                    />
                    <span className="text-slate-400">-</span>
                    <input
                      type="number"
                      min="15"
                      max="70"
                      placeholder="Max (35)"
                      value={usiaMaksimal}
                      onChange={(e) => setUsiaMaksimal(e.target.value ? parseInt(e.target.value) : '')}
                      className="w-full px-3 py-2 text-xs sm:text-sm rounded-md border border-slate-300 text-slate-800 focus:outline-hidden focus:border-blue-600"
                    />
                  </div>
                </div>

                <div>
                  <label className="block text-xs font-semibold text-slate-700 mb-1">
                    Jenis Kelamin
                  </label>
                  <select
                    value={jenisKelamin}
                    onChange={(e) => setJenisKelamin(e.target.value)}
                    className="w-full px-3 py-2 text-xs sm:text-sm rounded-md border border-slate-300 bg-white text-slate-800 focus:outline-hidden focus:border-blue-600"
                  >
                    <option value="Semua">Semua Gender</option>
                    <option value="Laki-laki">Laki-laki</option>
                    <option value="Perempuan">Perempuan</option>
                  </select>
                </div>

                <div className="flex items-center pt-5">
                  <label className="flex items-center gap-2 cursor-pointer text-xs font-medium text-slate-800 select-none">
                    <input
                      type="checkbox"
                      checked={isDisabilitas}
                      onChange={(e) => setIsDisabilitas(e.target.checked)}
                      className="w-4 h-4 rounded text-blue-600 border-slate-300 focus:ring-blue-500"
                    />
                    <span>Menerima Disabilitas (Inklusif)</span>
                  </label>
                </div>
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-700 mb-1">
                  Persyaratan Tambahan / Keterangan Lain
                </label>
                <textarea
                  rows={2}
                  placeholder="Contoh: Bersedia ditempatkan di luar kota, memiliki SIM A/C..."
                  value={persyaratanTambahan}
                  onChange={(e) => setPersyaratanTambahan(e.target.value)}
                  className="w-full px-3 py-2 text-xs sm:text-sm rounded-md border border-slate-300 text-slate-800 placeholder-slate-400 focus:outline-hidden focus:border-blue-600"
                />
              </div>

              {/* Pemetaan Skill ESCO */}
              <div className="pt-2">
                <LowonganSkillPicker skills={skills} onChange={setSkills} />
              </div>
            </div>
          )}

          {/* ================= STEP 3: LOKASI PENEMPATAN, GAJI & PUBLIKASI ================= */}
          {currentStep === 3 && (
            <div className="space-y-4 animate-in fade-in duration-150">
              <div className="border-b border-slate-100 pb-2">
                <h4 className="text-sm font-bold text-slate-900 flex items-center gap-2">
                  <MapPin className="w-4 h-4 text-blue-600" />
                    Lokasi Penempatan, Gaji & Masa Pembukaan Lowongan
                </h4>
                <p className="text-xs text-slate-500">
                  Tentukan wilayah penempatan kerja, kompensasi gaji, dan masa periode pembukaan lowongan.
                </p>
              </div>

              {/* SearchableSelect Wilayah */}
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label className="block text-xs font-semibold text-slate-700 mb-1">
                    Provinsi Penempatan <span className="text-red-500">*</span>
                  </label>
                  <SearchableSelect
                    options={provinceOptions}
                    value={provinsiId}
                    onChange={(val) => {
                      setProvinsiId(val);
                      setRegencyId('');
                    }}
                    placeholder="-- Pilih Provinsi --"
                    searchPlaceholder="Cari provinsi (nama/kode)..."
                  />
                </div>

                <div>
                  <label className="block text-xs font-semibold text-slate-700 mb-1">
                    Kabupaten / Kota Penempatan <span className="text-red-500">*</span>
                  </label>
                  <SearchableSelect
                    options={regencyOptions}
                    value={regencyId}
                    onChange={(val) => setRegencyId(val)}
                    disabled={!provinsiId || isLoadingRegencies}
                    isLoading={isLoadingRegencies}
                    placeholder={
                      !provinsiId
                        ? 'Pilih provinsi terlebih dahulu'
                        : isLoadingRegencies
                        ? 'Memuat kabupaten/kota...'
                        : '-- Pilih Kabupaten / Kota --'
                    }
                    searchPlaceholder="Cari kabupaten/kota (nama/kode)..."
                  />
                </div>
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-700 mb-1">
                  Alamat Lengkap Kantor / Lokasi Kerja
                </label>
                <input
                  type="text"
                  placeholder="Contoh: Gedung Graha Lantai 8, Jl. Sudirman No. 45"
                  value={alamatLengkap}
                  onChange={(e) => setAlamatLengkap(e.target.value)}
                  className="w-full px-3 py-2 text-xs sm:text-sm rounded-md border border-slate-300 text-slate-800 placeholder-slate-400 focus:outline-hidden focus:border-blue-600"
                />
              </div>

              {/* Gaji */}
              <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                <div>
                  <label className="block text-xs font-semibold text-slate-700 mb-1">
                    Gaji Minimal (IDR)
                  </label>
                  <input
                    type="number"
                    min="0"
                    step="500000"
                    placeholder="Contoh: 8000000"
                    value={gajiMinimal}
                    onChange={(e) => setGajiMinimal(e.target.value ? parseFloat(e.target.value) : '')}
                    className="w-full px-3 py-2 text-xs sm:text-sm rounded-md border border-slate-300 text-slate-800 focus:outline-hidden focus:border-blue-600"
                  />
                </div>

                <div>
                  <label className="block text-xs font-semibold text-slate-700 mb-1">
                    Gaji Maksimal (IDR)
                  </label>
                  <input
                    type="number"
                    min="0"
                    step="500000"
                    placeholder="Contoh: 12000000"
                    value={gajiMaksimal}
                    onChange={(e) => setGajiMaksimal(e.target.value ? parseFloat(e.target.value) : '')}
                    className="w-full px-3 py-2 text-xs sm:text-sm rounded-md border border-slate-300 text-slate-800 focus:outline-hidden focus:border-blue-600"
                  />
                </div>

                <div className="pb-2">
                  <label className="flex items-center gap-2 cursor-pointer text-xs font-medium text-slate-700 select-none">
                    <input
                      type="checkbox"
                      checked={gajiTampilkan}
                      onChange={(e) => setGajiTampilkan(e.target.checked)}
                      className="w-4 h-4 rounded text-blue-600 border-slate-300 focus:ring-blue-500"
                    />
                    <span>Tampilkan Gaji ke Publik</span>
                  </label>
                </div>
              </div>

              {/* Masa Tayang & Status */}
              <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                  <label className="block text-xs font-semibold text-slate-700 mb-1">
                    Tanggal Buka Tayang
                  </label>
                  <input
                    type="date"
                    value={tanggalBuka}
                    onChange={(e) => setTanggalBuka(e.target.value)}
                    className="w-full px-3 py-2 text-xs sm:text-sm rounded-md border border-slate-300 text-slate-800 focus:outline-hidden focus:border-blue-600"
                  />
                </div>

                <div>
                  <label className="block text-xs font-semibold text-slate-700 mb-1">
                    Tanggal Batas Akhir (Tutup) <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="date"
                    required
                    value={tanggalTutup}
                    onChange={(e) => setTanggalTutup(e.target.value)}
                    className="w-full px-3 py-2 text-xs sm:text-sm rounded-md border border-slate-300 text-slate-800 focus:outline-hidden focus:border-blue-600"
                  />
                </div>

                <div>
                  <label className="block text-xs font-semibold text-slate-700 mb-1">
                    Status Publikasi <span className="text-red-500">*</span>
                  </label>
                  <select
                    value={statusLowongan}
                    onChange={(e) => setStatusLowongan(e.target.value as 'Draft' | 'Published' | 'Closed' | 'Archived')}
                    className="w-full px-3 py-2 text-xs sm:text-sm rounded-md border border-slate-300 bg-white text-slate-800 focus:outline-hidden focus:border-blue-600"
                  >
                    {statusOptions.map((st) => (
                      <option key={st} value={st}>
                        {st}
                      </option>
                    ))}
                  </select>
                </div>
              </div>
            </div>
          )}
        </form>

        {/* Footer Actions with Stepper Controls */}
        <div className="px-6 py-4 border-t border-slate-200 bg-slate-50 flex items-center justify-between">
          <div>
            {currentStep === 1 ? (
              <button
                type="button"
                onClick={onClose}
                disabled={isSubmitting}
                className="px-4 py-2 text-xs sm:text-sm font-medium rounded-md border border-slate-300 text-slate-700 bg-white hover:bg-slate-100 transition-colors"
              >
                Batal
              </button>
            ) : (
              <button
                type="button"
                onClick={() => setCurrentStep((prev) => (prev > 1 ? ((prev - 1) as 1 | 2 | 3) : 1))}
                disabled={isSubmitting}
                className="inline-flex items-center gap-1.5 px-4 py-2 text-xs sm:text-sm font-medium rounded-md border border-slate-300 text-slate-700 bg-white hover:bg-slate-100 transition-colors"
              >
                <ChevronLeft className="w-4 h-4" />
                <span>Sebelumnya</span>
              </button>
            )}
          </div>

          <div className="flex items-center gap-2">
            {currentStep === 1 && (
              <button
                type="button"
                onClick={handleNextFromStep1}
                className="inline-flex items-center gap-1.5 px-5 py-2 text-xs sm:text-sm font-semibold rounded-md bg-[#0E385E] text-white hover:bg-[#163A5F] transition-colors"
              >
                <span>Lanjut: Kualifikasi & Skill</span>
                <ChevronRight className="w-4 h-4" />
              </button>
            )}

            {currentStep === 2 && (
              <button
                type="button"
                onClick={handleNextFromStep2}
                className="inline-flex items-center gap-1.5 px-5 py-2 text-xs sm:text-sm font-semibold rounded-md bg-[#0E385E] text-white hover:bg-[#163A5F] transition-colors"
              >
                <span>Lanjut: Lokasi & Gaji</span>
                <ChevronRight className="w-4 h-4" />
              </button>
            )}

            {currentStep === 3 && (
              <button
                type="button"
                onClick={handleSubmit}
                disabled={isSubmitting}
                className="inline-flex items-center gap-2 px-5 py-2 text-xs sm:text-sm font-semibold rounded-md bg-[#0E385E] text-white hover:bg-[#163A5F] transition-colors disabled:opacity-50 shadow-xs"
              >
                {isSubmitting ? (
                  <>
                    <Loader2 className="w-4 h-4 animate-spin" />
                    <span>Menyimpan...</span>
                  </>
                ) : (
                  <>
                    <Save className="w-4 h-4" />
                    <span>{isEdit ? 'Simpan Perubahan' : 'Tambahkan Lowongan'}</span>
                  </>
                )}
              </button>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
