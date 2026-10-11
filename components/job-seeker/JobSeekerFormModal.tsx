'use client';

import React, { useEffect, useMemo, useState } from 'react';
import { AlertCircle, Loader2, X } from 'lucide-react';
import SearchableSelect from '@/components/ui/SearchableSelect';
import {
  createJobSeeker,
  getEducationLevels,
  getJobSeekerDetail,
  updateJobSeeker,
} from '@/services/job-seeker';
import { getProvinces, getRegencies } from '@/services/wilayah';
import { EducationLevel, JobSeeker, Province, Regency } from '@/types/job-seeker';

/**
 * Form Tambah & Edit profil pencari kerja. Kolom mengikuti tabel req_pk_pencaker.
 * - Tambah: isOpen = true, jobSeekerId = null
 * - Edit  : isOpen = true, jobSeekerId = <id>  (hanya kolom yang berubah yang dikirim)
 */
interface Props {
  isOpen: boolean;
  jobSeekerId?: number | null;
  onClose: () => void;
  onSaved: () => void;
}

// Nilai kategori sesuai data req_pk_pencaker
const STATUS_BEKERJA = ['Fresh Graduate', 'Ter-PHK', 'Masih Bekerja'];
const MARITAL = ['BELUM MENIKAH', 'MENIKAH', 'JANDA', 'DUDA'];
const JENIS_DISABILITAS = ['Tuna Daksa', 'Tuna Rungu', 'Tuna Netra', 'Tuna Wicara', 'Tuna Grahita'];

interface FormState {
  name: string;
  jenis_kelamin: string;
  umur: string;
  marital: string;
  province_id: string;
  regency_id: string;
  kondisi_fisik: string;
  jenis_disabilitas: string;
  education_level_id: string;
  jurusan: string;
  nama_sekolah: string;
  status_bekerja: string;
  experience: string;
  sertifikasi: string;
  lembaga_pelatihan: string;
  progpel: string;
  keahlian: string;
  bahasa: string;
  rencana_kerja_luar_negeri: string;
  country_wish: string;
}

const EMPTY_FORM: FormState = {
  name: '',
  jenis_kelamin: '',
  umur: '',
  marital: '',
  province_id: '',
  regency_id: '',
  kondisi_fisik: 'Non Disabilitas',
  jenis_disabilitas: '',
  education_level_id: '',
  jurusan: '',
  nama_sekolah: '',
  status_bekerja: '',
  experience: '',
  sertifikasi: '',
  lembaga_pelatihan: '',
  progpel: '',
  keahlian: '',
  bahasa: 'Bahasa Indonesia',
  rencana_kerja_luar_negeri: 'Tidak',
  country_wish: '',
};

const toForm = (d: JobSeeker): FormState => ({
  name: d.name ?? '',
  jenis_kelamin: d.jenis_kelamin ?? '',
  umur: d.umur != null ? String(d.umur) : '',
  marital: d.marital ?? '',
  province_id: d.province_id ?? '',
  regency_id: d.regency_id ?? '',
  kondisi_fisik: d.kondisi_fisik ?? '',
  jenis_disabilitas: d.jenis_disabilitas ?? '',
  education_level_id: d.education_level_id != null ? String(d.education_level_id) : '',
  jurusan: d.jurusan ?? '',
  nama_sekolah: d.nama_sekolah ?? '',
  status_bekerja: d.status_bekerja ?? '',
  experience: d.experience ?? '',
  sertifikasi: d.sertifikasi ?? '',
  lembaga_pelatihan: d.lembaga_pelatihan ?? '',
  progpel: d.progpel ?? '',
  keahlian: d.keahlian ?? '',
  bahasa: d.bahasa ?? '',
  rencana_kerja_luar_negeri: d.rencana_kerja_luar_negeri ?? '',
  country_wish: d.country_wish ?? '',
});

// Kolom teks yang dikirim apa adanya (string kosong = dikosongkan)
const TEXT_FIELDS = [
  'marital', 'kondisi_fisik', 'jenis_disabilitas', 'jurusan', 'nama_sekolah', 'status_bekerja',
  'experience', 'sertifikasi', 'lembaga_pelatihan', 'progpel', 'keahlian', 'bahasa',
  'rencana_kerja_luar_negeri', 'country_wish',
] as const;

const inputClass =
  'w-full px-3 py-2 text-xs sm:text-sm rounded-md border border-slate-300 bg-white focus:outline-hidden focus:border-[#2563EB] focus:ring-1 focus:ring-[#2563EB]';

function Field({ label, error, children }: { label: string; error?: string; children: React.ReactNode }) {
  return (
    <label className="block">
      <span className="block text-xs font-semibold text-slate-600 mb-1">{label}</span>
      {children}
      {error && <span className="mt-1 block text-[11px] text-red-600">{error}</span>}
    </label>
  );
}

/** Pilihan dropdown, menyertakan nilai lama yang tidak ada di daftar baku */
const withCurrent = (options: string[], current: string) =>
  current && !options.includes(current) ? [current, ...options] : options;

export default function JobSeekerFormModal({ isOpen, jobSeekerId = null, onClose, onSaved }: Props) {
  const isEdit = jobSeekerId != null;

  const [detail, setDetail] = useState<JobSeeker | null>(null);
  const [initial, setInitial] = useState<FormState | null>(null);
  const [form, setForm] = useState<FormState | null>(null);
  const [loadError, setLoadError] = useState<string | null>(null);

  const [provinces, setProvinces] = useState<Province[]>([]);
  const [regencies, setRegencies] = useState<Regency[]>([]);
  const [regenciesLoading, setRegenciesLoading] = useState(false);
  const [educationLevels, setEducationLevels] = useState<EducationLevel[]>([]);

  const [saving, setSaving] = useState(false);
  const [saveError, setSaveError] = useState<string | null>(null);
  const [fieldErrors, setFieldErrors] = useState<Record<string, string[]>>({});

  // Siapkan form setiap kali jendela dibuka
  useEffect(() => {
    if (!isOpen) return;
    setDetail(null);
    setLoadError(null);
    setSaveError(null);
    setFieldErrors({});

    if (isEdit) {
      setForm(null);
      getJobSeekerDetail(jobSeekerId)
        .then((d) => {
          const f = toForm(d);
          setDetail(d);
          setForm(f);
          setInitial(f);
        })
        .catch((err: Error) => setLoadError(err.message));
    } else {
      setForm(EMPTY_FORM);
      setInitial(EMPTY_FORM);
    }

    getProvinces().then(setProvinces).catch(() => setProvinces([]));
    getEducationLevels().then(setEducationLevels).catch(() => setEducationLevels([]));
  }, [isOpen, isEdit, jobSeekerId]);

  // Daftar kab/kota mengikuti provinsi terpilih
  const provinceId = form?.province_id ?? '';
  useEffect(() => {
    if (!provinceId) {
      setRegencies([]);
      return;
    }
    setRegenciesLoading(true);
    getRegencies(provinceId)
      .then(setRegencies)
      .catch(() => setRegencies([]))
      .finally(() => setRegenciesLoading(false));
  }, [provinceId]);

  // Data yang dikirim: Tambah = semua kolom terisi; Edit = kolom yang berubah saja
  const payload = useMemo(() => {
    if (!form || !initial) return {};
    const changed = (key: keyof FormState) => !isEdit || form[key] !== initial[key];
    const p: Record<string, unknown> = {};

    if (changed('name')) p.full_name = form.name.trim();
    if (changed('jenis_kelamin') && form.jenis_kelamin) p.gender = form.jenis_kelamin;
    if (changed('umur') && (isEdit || form.umur !== '')) p.umur = form.umur === '' ? null : Number(form.umur);
    if (changed('province_id') && form.province_id) p.province_id = form.province_id;
    if (changed('regency_id') && form.regency_id) p.regency_id = form.regency_id;
    if (changed('education_level_id') && form.education_level_id) p.education_level_id = Number(form.education_level_id);

    TEXT_FIELDS.forEach((key) => {
      if (!changed(key)) return;
      const value = form[key].trim();
      if (isEdit || value !== '') p[key] = value === '' ? null : value;
    });
    return p;
  }, [form, initial, isEdit]);

  const isDirty = isEdit ? Object.keys(payload).length > 0 : true;

  const requestClose = () => {
    const hasInput = isEdit ? isDirty : JSON.stringify(form) !== JSON.stringify(EMPTY_FORM);
    if (hasInput && !window.confirm('Perubahan belum disimpan. Tutup tanpa menyimpan?')) return;
    onClose();
  };

  const set = (key: keyof FormState, value: string) => setForm((prev) => (prev ? { ...prev, [key]: value } : prev));

  // Tambah: semua kolom bertanda * wajib. Edit: data impor lama bisa belum lengkap,
  // jadi hanya nama yang wajib (dan kab/kota bila provinsi dipilih).
  const validate = (f: FormState): Record<string, string[]> => {
    const errors: Record<string, string[]> = {};
    if (!f.name.trim()) errors.full_name = ['Nama lengkap wajib diisi.'];
    if (f.province_id && !f.regency_id) errors.regency_id = ['Kabupaten/Kota wajib dipilih.'];
    if (!isEdit) {
      if (!f.jenis_kelamin) errors.gender = ['Jenis kelamin wajib dipilih.'];
      if (!f.province_id) errors.province_id = ['Provinsi wajib dipilih.'];
      if (!f.regency_id) errors.regency_id = ['Kabupaten/Kota wajib dipilih.'];
      if (!f.education_level_id) errors.education_level_id = ['Pendidikan terakhir wajib dipilih.'];
    }
    return errors;
  };

  const handleSave = async () => {
    if (!form) return;
    const errors = validate(form);
    if (Object.keys(errors).length > 0) {
      setFieldErrors(errors);
      setSaveError('Lengkapi kolom yang wajib diisi.');
      return;
    }
    setSaving(true);
    setSaveError(null);
    setFieldErrors({});
    try {
      if (isEdit) {
        await updateJobSeeker(jobSeekerId, payload);
      } else {
        await createJobSeeker(payload);
      }
      onSaved();
      onClose();
    } catch (err) {
      const e = err as Error & { errors?: Record<string, string[]> };
      setSaveError(e.message || 'Gagal menyimpan profil.');
      setFieldErrors(e.errors ?? {});
    } finally {
      setSaving(false);
    }
  };

  if (!isOpen) return null;

  const err = (key: string) => fieldErrors[key]?.[0];

  return (
    <div className="fixed inset-0 z-60 flex items-center justify-center bg-slate-900/50 p-2 sm:p-4" onMouseDown={requestClose}>
      <div
        role="dialog"
        aria-modal="true"
        aria-label={isEdit ? 'Edit profil pencari kerja' : 'Tambah profil pencari kerja'}
        onMouseDown={(e) => e.stopPropagation()}
        className="flex max-h-[95vh] w-full max-w-4xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl"
      >
        {/* Header */}
        <div className="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4">
          <div className="min-w-0">
            <h2 className="text-base font-bold text-slate-900">
              {isEdit ? 'Edit Profil Pencari Kerja' : 'Tambah Profil Pencari Kerja'}
            </h2>
            <p className="mt-0.5 truncate text-xs text-slate-500">
              {isEdit && detail
                ? `${detail.profile_id || detail.id} · ${detail.name}`
                : 'Kolom bertanda * wajib diisi.'}
            </p>
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
        ) : !form ? (
          <div className="p-16 text-center text-sm text-slate-500">
            <Loader2 className="mx-auto mb-2 h-7 w-7 animate-spin text-[#0E385E]" />
            Memuat data pencari kerja...
          </div>
        ) : (
          <div className="min-h-0 flex-1 overflow-y-auto px-5 py-4 space-y-6">
            {/* Data diri */}
            <section>
              <h3 className="mb-3 text-xs font-bold uppercase tracking-wider text-slate-500">Data Diri</h3>
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div className="sm:col-span-2">
                  <Field label="Nama Lengkap *" error={err('full_name')}>
                    <input className={inputClass} value={form.name} onChange={(e) => set('name', e.target.value)} />
                  </Field>
                </div>
                <Field label="Jenis Kelamin *" error={err('gender')}>
                  <select className={inputClass} value={form.jenis_kelamin} onChange={(e) => set('jenis_kelamin', e.target.value)}>
                    <option value="">-- Pilih --</option>
                    <option value="Laki-laki">Laki-laki</option>
                    <option value="Perempuan">Perempuan</option>
                  </select>
                </Field>
                <Field label="Umur (tahun)" error={err('umur')}>
                  <input type="number" min={15} max={80} className={inputClass} value={form.umur} onChange={(e) => set('umur', e.target.value)} />
                </Field>
                <Field label="Status Perkawinan" error={err('marital')}>
                  <select className={inputClass} value={form.marital} onChange={(e) => set('marital', e.target.value)}>
                    <option value="">-- Pilih --</option>
                    {withCurrent(MARITAL, form.marital).map((m) => (
                      <option key={m} value={m}>{m}</option>
                    ))}
                  </select>
                </Field>
                <Field label="Kondisi Fisik" error={err('kondisi_fisik')}>
                  <select
                    className={inputClass}
                    value={form.kondisi_fisik}
                    onChange={(e) => setForm((prev) => prev ? {
                      ...prev,
                      kondisi_fisik: e.target.value,
                      jenis_disabilitas: e.target.value === 'Disabilitas' ? prev.jenis_disabilitas : '',
                    } : prev)}
                  >
                    <option value="">-- Pilih --</option>
                    <option value="Non Disabilitas">Non Disabilitas</option>
                    <option value="Disabilitas">Disabilitas</option>
                  </select>
                </Field>
                {form.kondisi_fisik === 'Disabilitas' && (
                  <Field label="Jenis Disabilitas" error={err('jenis_disabilitas')}>
                    <select className={inputClass} value={form.jenis_disabilitas} onChange={(e) => set('jenis_disabilitas', e.target.value)}>
                      <option value="">-- Pilih --</option>
                      {withCurrent(JENIS_DISABILITAS, form.jenis_disabilitas).map((j) => (
                        <option key={j} value={j}>{j}</option>
                      ))}
                    </select>
                  </Field>
                )}
              </div>
            </section>

            {/* Domisili */}
            <section>
              <h3 className="mb-3 text-xs font-bold uppercase tracking-wider text-slate-500">Domisili</h3>
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <span className="block text-xs font-semibold text-slate-600 mb-1">Provinsi *</span>
                  <SearchableSelect
                    options={provinces.map((p) => ({ value: p.id, label: p.name, code: p.id }))}
                    value={form.province_id}
                    onChange={(value) => setForm((prev) => (prev ? { ...prev, province_id: value, regency_id: '' } : prev))}
                    placeholder="Pilih provinsi"
                    searchPlaceholder="Cari provinsi..."
                    hasError={Boolean(err('province_id'))}
                  />
                  {err('province_id') && <span className="mt-1 block text-[11px] text-red-600">{err('province_id')}</span>}
                </div>
                <div>
                  <span className="block text-xs font-semibold text-slate-600 mb-1">Kabupaten / Kota *</span>
                  <SearchableSelect
                    options={regencies.map((r) => ({ value: r.id, label: r.name, code: r.id }))}
                    value={form.regency_id}
                    onChange={(value) => set('regency_id', value)}
                    placeholder={form.province_id ? 'Pilih kabupaten/kota' : 'Pilih provinsi dulu'}
                    searchPlaceholder="Cari kabupaten/kota..."
                    disabled={!form.province_id}
                    isLoading={regenciesLoading}
                    hasError={Boolean(err('regency_id'))}
                  />
                  {err('regency_id') && <span className="mt-1 block text-[11px] text-red-600">{err('regency_id')}</span>}
                </div>
              </div>
            </section>

            {/* Pendidikan & pekerjaan */}
            <section>
              <h3 className="mb-3 text-xs font-bold uppercase tracking-wider text-slate-500">Pendidikan & Pekerjaan</h3>
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <Field label="Pendidikan Terakhir *" error={err('education_level_id')}>
                  <select className={inputClass} value={form.education_level_id} onChange={(e) => set('education_level_id', e.target.value)}>
                    <option value="">-- Pilih --</option>
                    {educationLevels.map((lvl) => (
                      <option key={lvl.id} value={lvl.id}>{lvl.name}</option>
                    ))}
                  </select>
                </Field>
                <Field label="Status Bekerja" error={err('status_bekerja')}>
                  <select className={inputClass} value={form.status_bekerja} onChange={(e) => set('status_bekerja', e.target.value)}>
                    <option value="">-- Pilih --</option>
                    {withCurrent(STATUS_BEKERJA, form.status_bekerja).map((s) => (
                      <option key={s} value={s}>{s}</option>
                    ))}
                  </select>
                </Field>
                <Field label="Jurusan" error={err('jurusan')}>
                  <input className={inputClass} value={form.jurusan} onChange={(e) => set('jurusan', e.target.value)} />
                </Field>
                <Field label="Nama Sekolah / Kampus" error={err('nama_sekolah')}>
                  <input className={inputClass} value={form.nama_sekolah} onChange={(e) => set('nama_sekolah', e.target.value)} />
                </Field>
                <div className="sm:col-span-2">
                  <Field label="Pengalaman Kerja" error={err('experience')}>
                    <textarea
                      rows={3}
                      className={inputClass}
                      value={form.experience}
                      onChange={(e) => set('experience', e.target.value)}
                      placeholder="Contoh: Staf Admin; PT Maju Jaya; Januari 2023 - Desember 2024"
                    />
                  </Field>
                </div>
              </div>
            </section>

            {/* Keahlian, pelatihan, bahasa */}
            <section>
              <h3 className="mb-3 text-xs font-bold uppercase tracking-wider text-slate-500">Keahlian, Pelatihan & Bahasa</h3>
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div className="sm:col-span-2">
                  <Field label="Keahlian" error={err('keahlian')}>
                    <textarea
                      rows={2}
                      className={inputClass}
                      value={form.keahlian}
                      onChange={(e) => set('keahlian', e.target.value)}
                      placeholder="Pisahkan dengan koma. Contoh: Microsoft Excel, Akuntansi, Komunikasi"
                    />
                  </Field>
                </div>
                <Field label="Sertifikasi" error={err('sertifikasi')}>
                  <textarea rows={2} className={inputClass} value={form.sertifikasi} onChange={(e) => set('sertifikasi', e.target.value)} />
                </Field>
                <Field label="Lembaga Pelatihan" error={err('lembaga_pelatihan')}>
                  <textarea rows={2} className={inputClass} value={form.lembaga_pelatihan} onChange={(e) => set('lembaga_pelatihan', e.target.value)} />
                </Field>
                <Field label="Program Pelatihan" error={err('progpel')}>
                  <textarea rows={2} className={inputClass} value={form.progpel} onChange={(e) => set('progpel', e.target.value)} />
                </Field>
                <Field label="Bahasa" error={err('bahasa')}>
                  <input
                    className={inputClass}
                    value={form.bahasa}
                    onChange={(e) => set('bahasa', e.target.value)}
                    placeholder="Contoh: Bahasa Indonesia, English (Bahasa Inggris)"
                  />
                </Field>
              </div>
            </section>

            {/* Rencana kerja luar negeri */}
            <section>
              <h3 className="mb-3 text-xs font-bold uppercase tracking-wider text-slate-500">Rencana Kerja Luar Negeri</h3>
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <Field label="Berminat bekerja di luar negeri?" error={err('rencana_kerja_luar_negeri')}>
                  <select
                    className={inputClass}
                    value={form.rencana_kerja_luar_negeri}
                    onChange={(e) => setForm((prev) => prev ? {
                      ...prev,
                      rencana_kerja_luar_negeri: e.target.value,
                      country_wish: e.target.value === 'Ya' ? prev.country_wish : '',
                    } : prev)}
                  >
                    <option value="">-- Pilih --</option>
                    <option value="Ya">Ya</option>
                    <option value="Tidak">Tidak</option>
                  </select>
                </Field>
                {form.rencana_kerja_luar_negeri === 'Ya' && (
                  <Field label="Negara Tujuan" error={err('country_wish')}>
                    <input
                      className={inputClass}
                      value={form.country_wish}
                      onChange={(e) => set('country_wish', e.target.value)}
                      placeholder="Contoh: Jepang"
                    />
                  </Field>
                )}
              </div>
            </section>
          </div>
        )}

        {/* Footer */}
        <div className="flex items-center justify-between gap-3 border-t border-slate-200 bg-slate-50 px-5 py-3">
          <p className="text-xs text-red-600">{saveError}</p>
          <div className="flex gap-2">
            <button
              type="button"
              onClick={requestClose}
              className="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 cursor-pointer"
            >
              Batal
            </button>
            <button
              type="button"
              onClick={handleSave}
              disabled={!form || saving || !isDirty}
              className="inline-flex items-center gap-1.5 rounded-md bg-[#0E385E] px-4 py-2 text-sm font-semibold text-white hover:bg-[#163A5F] disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
            >
              {saving && <Loader2 className="h-4 w-4 animate-spin" />}
              {isEdit ? 'Simpan Perubahan' : 'Simpan Profil'}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}
