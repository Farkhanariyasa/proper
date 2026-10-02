'use client';

import React, { useState, useEffect, useRef, useMemo } from 'react';
import SearchableSelect from '@/components/ui/SearchableSelect';
import {
  X,
  Plus,
  Trash2,
  Search,
  Check,
  AlertCircle,
  Loader2,
  BookOpen,
  Award,
  MapPin,
  User,
  Briefcase,
  ChevronRight,
  ChevronLeft,
  GraduationCap,
} from 'lucide-react';
import {
  Province,
  Regency,
  EducationLevel,
  EscoSkillItem,
  CreateJobSeekerPayload,
  TrainingItem,
  CertificationItem,
} from '@/types/job-seeker';
import { getProvinces, getRegencies } from '@/services/wilayah';
import {
  getEducationLevels,
  getJobSeekerOptions,
  searchEscoSkills,
  createJobSeeker,
} from '@/services/job-seeker';

interface JobSeekerFormModalProps {
  isOpen: boolean;
  onClose: () => void;
  onSuccess: () => void;
}

export default function JobSeekerFormModal({
  isOpen,
  onClose,
  onSuccess,
}: JobSeekerFormModalProps) {
  // Stepper State
  const [currentStep, setCurrentStep] = useState<1 | 2 | 3>(1);

  // Master options state
  const [provinces, setProvinces] = useState<Province[]>([]);
  const [regencies, setRegencies] = useState<Regency[]>([]);
  const [educationLevels, setEducationLevels] = useState<EducationLevel[]>([]);
  const [studyFieldGroups, setStudyFieldGroups] = useState<string[]>([]);
  const [experienceRanges, setExperienceRanges] = useState<string[]>([]);

  // Loading states
  const [isLoadingMaster, setIsLoadingMaster] = useState(false);
  const [isLoadingRegencies, setIsLoadingRegencies] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);
  const [fieldErrors, setFieldErrors] = useState<Record<string, string[]>>({});

  // Form State
  const [formData, setFormData] = useState({
    nik: '',
    full_name: '',
    phone: '',
    birth_date: '',
    gender: 'L' as 'L' | 'P',
    province_id: '',
    regency_id: '',
    education_level_id: '' as string | number,
    study_field_group: '',
    study_field_detail: '',
    experience_range: 'fresh_graduate',
    desired_occupation: '',
  });

  const [selectedSkills, setSelectedSkills] = useState<EscoSkillItem[]>([]);
  const [trainings, setTrainings] = useState<TrainingItem[]>([]);
  const [certifications, setCertifications] = useState<CertificationItem[]>([]);

  // Skill Search State
  const [skillSearchQuery, setSkillSearchQuery] = useState('');
  const [skillSearchResults, setSkillSearchResults] = useState<EscoSkillItem[]>([]);
  const [isSearchingSkills, setIsSearchingSkills] = useState(false);
  const [isSkillDropdownOpen, setIsSkillDropdownOpen] = useState(false);
  const skillDropdownRef = useRef<HTMLDivElement>(null);

  // Reset Stepper & Error on Open
  useEffect(() => {
    if (isOpen) {
      setCurrentStep(1);
      setErrorMessage(null);
      setFieldErrors({});
    }
  }, [isOpen]);

  // Load Initial Master Data
  useEffect(() => {
    if (!isOpen) return;

    let isMounted = true;
    setIsLoadingMaster(true);
    setErrorMessage(null);
    setFieldErrors({});

    Promise.all([getProvinces(), getEducationLevels(), getJobSeekerOptions()])
      .then(([provData, eduData, optData]) => {
        if (isMounted) {
          setProvinces(provData);
          setEducationLevels(eduData);
          setStudyFieldGroups(optData.study_field_groups);
          setExperienceRanges(optData.experience_ranges);
          if (optData.study_field_groups.length > 0) {
            setFormData((prev) => ({
              ...prev,
              study_field_group: prev.study_field_group || optData.study_field_groups[0],
            }));
          }
          if (eduData.length > 0) {
            setFormData((prev) => ({
              ...prev,
              education_level_id: prev.education_level_id || eduData[0].id,
            }));
          }
        }
      })
      .catch((err) => {
        if (isMounted) setErrorMessage(err.message || 'Gagal memuat master data wilayah');
      })
      .finally(() => {
        if (isMounted) setIsLoadingMaster(false);
      });

    return () => {
      isMounted = false;
    };
  }, [isOpen]);

  // Opsi wilayah untuk SearchableSelect (urut berdasarkan ID)
  const provinceOptions = useMemo(
    () =>
      provinces.map((p) => ({
        value: p.id,
        label: p.name,
        code: p.id,
      })),
    [provinces]
  );

  const regencyOptions = useMemo(
    () =>
      regencies.map((r) => ({
        value: r.id,
        label: r.name,
        code: r.id,
      })),
    [regencies]
  );

  // Load Regencies when Province changes
  useEffect(() => {
    if (!formData.province_id) {
      setRegencies([]);
      setFormData((prev) => ({ ...prev, regency_id: '' }));
      return;
    }

    let isMounted = true;
    setIsLoadingRegencies(true);
    getRegencies(formData.province_id)
      .then((data) => {
        if (isMounted) {
          setRegencies(data);
          setFormData((prev) => ({ ...prev, regency_id: '' }));
        }
      })
      .catch((err) => {
        console.error('Error fetching regencies:', err);
      })
      .finally(() => {
        if (isMounted) setIsLoadingRegencies(false);
      });

    return () => {
      isMounted = false;
    };
  }, [formData.province_id]);

  // Debounced ESCO Skill Search
  useEffect(() => {
    if (!skillSearchQuery.trim()) {
      setSkillSearchResults([]);
      return;
    }

    const timer = setTimeout(async () => {
      setIsSearchingSkills(true);
      try {
        const results = await searchEscoSkills(skillSearchQuery, 10);
        setSkillSearchResults(results);
        setIsSkillDropdownOpen(true);
      } catch (err) {
        console.error('Skill search error:', err);
      } finally {
        setIsSearchingSkills(false);
      }
    }, 250);

    return () => clearTimeout(timer);
  }, [skillSearchQuery]);

  // Close skill dropdown on click outside
  useEffect(() => {
    function handleClickOutside(e: MouseEvent) {
      if (
        skillDropdownRef.current &&
        !skillDropdownRef.current.contains(e.target as Node)
      ) {
        setIsSkillDropdownOpen(false);
      }
    }
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  const handleAddSkill = (skill: EscoSkillItem) => {
    if (!selectedSkills.some((s) => s.id === skill.id)) {
      setSelectedSkills((prev) => [...prev, skill]);
    }
    setSkillSearchQuery('');
    setIsSkillDropdownOpen(false);
  };

  const handleRemoveSkill = (skillId: number) => {
    setSelectedSkills((prev) => prev.filter((s) => s.id !== skillId));
  };

  // Repeater handlers
  const handleAddTraining = () => {
    setTrainings((prev) => [...prev, { name: '', organizer: '', year: new Date().getFullYear() }]);
  };

  const handleUpdateTraining = (
    index: number,
    field: keyof TrainingItem,
    value: string | number | null
  ) => {
    setTrainings((prev) => {
      const updated = [...prev];
      updated[index] = { ...updated[index], [field]: value };
      return updated;
    });
  };

  const handleRemoveTraining = (index: number) => {
    setTrainings((prev) => prev.filter((_, i) => i !== index));
  };

  const handleAddCertification = () => {
    setCertifications((prev) => [...prev, { name: '', type: 'Nasional', year: new Date().getFullYear() }]);
  };

  const handleUpdateCertification = (
    index: number,
    field: keyof CertificationItem,
    value: string | number | null
  ) => {
    setCertifications((prev) => {
      const updated = [...prev];
      updated[index] = { ...updated[index], [field]: value };
      return updated;
    });
  };

  const handleRemoveCertification = (index: number) => {
    setCertifications((prev) => prev.filter((_, i) => i !== index));
  };

  // Step Validation & Navigation
  const validateStep1 = (): boolean => {
    setErrorMessage(null);
    const errors: Record<string, string[]> = {};

    if (!formData.nik || formData.nik.length !== 16 || !/^\d+$/.test(formData.nik)) {
      errors.nik = ['NIK wajib diisi tepat 16 digit numerik.'];
    }
    if (!formData.full_name.trim()) {
      errors.full_name = ['Nama lengkap wajib diisi.'];
    }
    if (!formData.phone.trim()) {
      errors.phone = ['Nomor HP / WhatsApp wajib diisi.'];
    }
    if (!formData.birth_date) {
      errors.birth_date = ['Tanggal lahir wajib diisi.'];
    }
    if (!formData.province_id) {
      errors.province_id = ['Provinsi wajib dipilih.'];
    }
    if (!formData.regency_id) {
      errors.regency_id = ['Kabupaten / Kota wajib dipilih.'];
    }

    if (Object.keys(errors).length > 0) {
      setFieldErrors(errors);
      setErrorMessage('Harap lengkapi semua kolom wajib di Step 1 dengan benar.');
      return false;
    }

    setFieldErrors({});
    return true;
  };

  const validateStep2 = (): boolean => {
    setErrorMessage(null);
    const errors: Record<string, string[]> = {};

    if (!formData.education_level_id) {
      errors.education_level_id = ['Jenjang pendidikan wajib dipilih.'];
    }
    if (!formData.study_field_group) {
      errors.study_field_group = ['Rumpun bidang pendidikan wajib dipilih.'];
    }
    if (!formData.experience_range) {
      errors.experience_range = ['Rentang pengalaman wajib dipilih.'];
    }

    if (Object.keys(errors).length > 0) {
      setFieldErrors(errors);
      setErrorMessage('Harap lengkapi informasi pendidikan & pengalaman kerja.');
      return false;
    }

    setFieldErrors({});
    return true;
  };

  const handleNext = () => {
    if (currentStep === 1) {
      if (validateStep1()) setCurrentStep(2);
    } else if (currentStep === 2) {
      if (validateStep2()) setCurrentStep(3);
    }
  };

  const handlePrev = () => {
    setErrorMessage(null);
    if (currentStep === 3) setCurrentStep(2);
    else if (currentStep === 2) setCurrentStep(1);
  };

  // Submit Handler
  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setErrorMessage(null);
    setFieldErrors({});

    if (!validateStep1() || !validateStep2()) {
      return;
    }

    if (selectedSkills.length === 0) {
      setErrorMessage('Minimal pilih 1 Keahlian (Skill ESCO) pada Step 3.');
      return;
    }

    const payload: CreateJobSeekerPayload = {
      nik: formData.nik,
      full_name: formData.full_name,
      phone: formData.phone,
      birth_date: formData.birth_date,
      gender: formData.gender,
      regency_id: formData.regency_id,
      education_level_id: Number(formData.education_level_id),
      study_field_group: formData.study_field_group,
      study_field_detail: formData.study_field_detail || undefined,
      experience_range: formData.experience_range as any,
      desired_occupation: formData.desired_occupation || undefined,
      trainings: trainings.filter((t) => t.name.trim() !== ''),
      certifications: certifications.filter((c) => c.name.trim() !== ''),
      skills: selectedSkills.map((s) => s.id),
    };

    setIsSubmitting(true);
    try {
      await createJobSeeker(payload);
      onSuccess();
      onClose();
    } catch (err: any) {
      setErrorMessage(err.message || 'Terjadi kesalahan saat menyimpan data');
      if (err.errors) {
        setFieldErrors(err.errors);
        // Jika error berasal dari field step 1 atau 2, lompatkan ke step terkait
        if (err.errors.nik || err.errors.full_name || err.errors.regency_id) {
          setCurrentStep(1);
        } else if (err.errors.education_level_id || err.errors.study_field_group) {
          setCurrentStep(2);
        }
      }
    } finally {
      setIsSubmitting(false);
    }
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4">
      <div className="bg-white rounded-xl shadow-2xl border border-slate-200 w-full max-w-3xl max-h-[92vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-200">
        
        {/* Header Modal */}
        <div className="px-5 py-3.5 bg-gradient-to-r from-[#0E385E] to-[#1E5282] text-white flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div className="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center">
              <User className="w-4 h-4 text-sky-200" />
            </div>
            <div>
              <h3 className="text-base font-bold">Registrasi Profil Pencari Kerja</h3>
            </div>
          </div>
          <button
            type="button"
            onClick={onClose}
            className="p-1.5 rounded-lg text-white/80 hover:text-white hover:bg-white/10 transition-colors"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* 3-Step Wizard Navigation Bar */}
        <div className="bg-slate-50 border-b border-slate-200 px-4 sm:px-8 py-3">
          <div className="flex items-center justify-between max-w-xl mx-auto">
            {/* Step 1 Tab */}
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
                    currentStep === 1 ? 'text-[#0E385E]' : 'text-slate-600'
                  }`}
                >
                  Data Diri & Domisili
                </div>
                <div className="text-[10px] text-slate-400">Biodata & Wilayah</div>
              </div>
            </button>

            {/* Separator 1 */}
            <div
              className={`flex-1 h-0.5 mx-3 sm:mx-4 transition-colors ${
                currentStep > 1 ? 'bg-emerald-500' : 'bg-slate-200'
              }`}
            />

            {/* Step 2 Tab */}
            <button
              type="button"
              onClick={() => {
                if (currentStep > 2) setCurrentStep(2);
                else if (currentStep === 1) validateStep1() && setCurrentStep(2);
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
                    currentStep === 2 ? 'text-[#0E385E]' : 'text-slate-600'
                  }`}
                >
                  Pendidikan & Karir
                </div>
                <div className="text-[10px] text-slate-400">Rumpun & Target</div>
              </div>
            </button>

            {/* Separator 2 */}
            <div
              className={`flex-1 h-0.5 mx-3 sm:mx-4 transition-colors ${
                currentStep > 2 ? 'bg-emerald-500' : 'bg-slate-200'
              }`}
            />

            {/* Step 3 Tab */}
            <button
              type="button"
              onClick={() => {
                if (currentStep < 3 && validateStep1() && validateStep2()) {
                  setCurrentStep(3);
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
                    currentStep === 3 ? 'text-[#0E385E]' : 'text-slate-600'
                  }`}
                >
                  Skill & Portofolio
                </div>
                <div className="text-[10px] text-slate-400">ESCO & Sertifikasi</div>
              </div>
            </button>
          </div>
        </div>

        {/* Modal Form Content */}
        <form onSubmit={handleSubmit} className="flex-1 overflow-y-auto p-5 sm:p-6 space-y-5">
          {errorMessage && (
            <div className="p-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-xs sm:text-sm flex items-start gap-2.5">
              <AlertCircle className="w-4 h-4 text-red-600 mt-0.5 shrink-0" />
              <span>{errorMessage}</span>
            </div>
          )}

          {isLoadingMaster ? (
            <div className="py-16 flex flex-col items-center justify-center text-slate-500 gap-3">
              <Loader2 className="w-7 h-7 animate-spin text-[#0E385E]" />
              <p className="text-sm">Memuat referensi wilayah & data...</p>
            </div>
          ) : (
            <>
              {/* ======================================================== */}
              {/* STEP 1: DATA DIRI & DOMISILI WILAYAH                    */}
              {/* ======================================================== */}
              {currentStep === 1 && (
                <div className="space-y-5 animate-in fade-in duration-150">
                  {/* Bagian Identitas */}
                  <div className="space-y-3">
                    <div className="flex items-center gap-2 text-slate-800 font-semibold text-xs sm:text-sm pb-1.5 border-b border-slate-200">
                      <User className="w-4 h-4 text-[#0E385E]" />
                      <span>Identitas Pribadi & Kontak</span>
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                      <div>
                        <label className="block text-xs font-semibold text-slate-700 mb-1">
                          NIK (16 Digit Numerik) <span className="text-red-500">*</span>
                        </label>
                        <input
                          type="text"
                          maxLength={16}
                          value={formData.nik}
                          onChange={(e) =>
                            setFormData({
                              ...formData,
                              nik: e.target.value.replace(/\D/g, ''),
                            })
                          }
                          placeholder="Contoh: 3171012345670001"
                          className={`w-full px-3 py-2 text-xs sm:text-sm rounded-md border ${
                            fieldErrors.nik ? 'border-red-400 bg-red-50/50' : 'border-slate-300'
                          } focus:outline-hidden focus:border-[#2563EB] focus:ring-1 focus:ring-[#2563EB] font-mono`}
                          required
                        />
                        {fieldErrors.nik && (
                          <p className="text-[11px] text-red-600 mt-1">{fieldErrors.nik[0]}</p>
                        )}
                      </div>

                      <div>
                        <label className="block text-xs font-semibold text-slate-700 mb-1">
                          Nama Lengkap <span className="text-red-500">*</span>
                        </label>
                        <input
                          type="text"
                          value={formData.full_name}
                          onChange={(e) => setFormData({ ...formData, full_name: e.target.value })}
                          placeholder="Nama lengkap sesuai KTP"
                          className={`w-full px-3 py-2 text-xs sm:text-sm rounded-md border ${
                            fieldErrors.full_name ? 'border-red-400 bg-red-50/50' : 'border-slate-300'
                          } focus:outline-hidden focus:border-[#2563EB]`}
                          required
                        />
                      </div>

                      <div>
                        <label className="block text-xs font-semibold text-slate-700 mb-1">
                          Nomor HP / WhatsApp <span className="text-red-500">*</span>
                        </label>
                        <input
                          type="text"
                          value={formData.phone}
                          onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                          placeholder="Contoh: 08123456789"
                          className="w-full px-3 py-2 text-xs sm:text-sm rounded-md border border-slate-300 focus:outline-hidden focus:border-[#2563EB]"
                          required
                        />
                      </div>

                      <div className="grid grid-cols-2 gap-3">
                        <div>
                          <label className="block text-xs font-semibold text-slate-700 mb-1">
                            Tanggal Lahir <span className="text-red-500">*</span>
                          </label>
                          <input
                            type="date"
                            value={formData.birth_date}
                            onChange={(e) =>
                              setFormData({ ...formData, birth_date: e.target.value })
                            }
                            className="w-full px-2.5 py-2 text-xs rounded-md border border-slate-300 focus:outline-hidden focus:border-[#2563EB]"
                            required
                          />
                        </div>

                        <div>
                          <label className="block text-xs font-semibold text-slate-700 mb-1">
                            Jenis Kelamin <span className="text-red-500">*</span>
                          </label>
                          <div className="flex items-center gap-3 mt-2.5">
                            <label className="inline-flex items-center gap-1.5 text-xs cursor-pointer">
                              <input
                                type="radio"
                                name="gender"
                                value="L"
                                checked={formData.gender === 'L'}
                                onChange={() => setFormData({ ...formData, gender: 'L' })}
                                className="text-[#0E385E] focus:ring-[#0E385E]"
                              />
                              <span>Laki-laki</span>
                            </label>
                            <label className="inline-flex items-center gap-1.5 text-xs cursor-pointer">
                              <input
                                type="radio"
                                name="gender"
                                value="P"
                                checked={formData.gender === 'P'}
                                onChange={() => setFormData({ ...formData, gender: 'P' })}
                                className="text-[#0E385E] focus:ring-[#0E385E]"
                              />
                              <span>Perempuan</span>
                            </label>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  {/* Bagian Domisili Wilayah */}
                  <div className="space-y-3 pt-2">
                    <div className="flex items-center gap-2 text-slate-800 font-semibold text-xs sm:text-sm pb-1.5 border-b border-slate-200">
                      <MapPin className="w-4 h-4 text-[#0E385E]" />
                      <span>Domisili Wilayah (Standar Kepmendagri)</span>
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                      <div>
                        <label className="block text-xs font-semibold text-slate-700 mb-1">
                          Provinsi <span className="text-red-500">*</span>
                        </label>
                        <SearchableSelect
                          options={provinceOptions}
                          value={formData.province_id}
                          onChange={(val) => setFormData({ ...formData, province_id: val })}
                          placeholder="-- Cari & Pilih Provinsi --"
                          searchPlaceholder="Ketik nama atau kode (contoh: 31 / Jakarta)..."
                          hasError={!!fieldErrors.province_id}
                        />
                      </div>

                      <div>
                        <label className="block text-xs font-semibold text-slate-700 mb-1">
                          Kabupaten / Kota <span className="text-red-500">*</span>
                        </label>
                        <SearchableSelect
                          options={regencyOptions}
                          value={formData.regency_id}
                          onChange={(val) => setFormData({ ...formData, regency_id: val })}
                          disabled={!formData.province_id || isLoadingRegencies}
                          isLoading={isLoadingRegencies}
                          placeholder={
                            !formData.province_id
                              ? '-- Pilih Provinsi Terlebih Dahulu --'
                              : '-- Cari & Pilih Kabupaten / Kota --'
                          }
                          searchPlaceholder="Ketik nama atau kode (contoh: 31.71 / Bogor)..."
                          hasError={!!fieldErrors.regency_id}
                        />
                      </div>
                    </div>
                  </div>
                </div>
              )}

              {/* ======================================================== */}
              {/* STEP 2: PENDIDIKAN & TARGET KARIR                        */}
              {/* ======================================================== */}
              {currentStep === 2 && (
                <div className="space-y-5 animate-in fade-in duration-150">
                  <div className="flex items-center gap-2 text-slate-800 font-semibold text-xs sm:text-sm pb-1.5 border-b border-slate-200">
                    <GraduationCap className="w-4 h-4 text-[#0E385E]" />
                    <span>Latar Belakang Pendidikan & Minat Karir</span>
                  </div>

                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                      <label className="block text-xs font-semibold text-slate-700 mb-1">
                        Jenjang Pendidikan Terakhir <span className="text-red-500">*</span>
                      </label>
                      <select
                        value={formData.education_level_id}
                        onChange={(e) =>
                          setFormData({
                            ...formData,
                            education_level_id: Number(e.target.value),
                          })
                        }
                        className="w-full px-3 py-2 text-xs sm:text-sm rounded-md border border-slate-300 bg-white focus:outline-hidden focus:border-[#2563EB]"
                        required
                      >
                        {educationLevels.map((lvl) => (
                          <option key={lvl.id} value={lvl.id}>
                            {lvl.name}
                          </option>
                        ))}
                      </select>
                    </div>

                    <div>
                      <label className="block text-xs font-semibold text-slate-700 mb-1">
                        Rumpun Bidang Pendidikan <span className="text-red-500">*</span>
                      </label>
                      <select
                        value={formData.study_field_group}
                        onChange={(e) =>
                          setFormData({ ...formData, study_field_group: e.target.value })
                        }
                        className="w-full px-3 py-2 text-xs sm:text-sm rounded-md border border-slate-300 bg-white focus:outline-hidden focus:border-[#2563EB]"
                        required
                      >
                        {studyFieldGroups.map((group) => (
                          <option key={group} value={group}>
                            {group}
                          </option>
                        ))}
                      </select>
                    </div>

                    <div>
                      <label className="block text-xs font-semibold text-slate-700 mb-1">
                        Program Studi / Jurusan Spesifik
                      </label>
                      <input
                        type="text"
                        value={formData.study_field_detail}
                        onChange={(e) =>
                          setFormData({ ...formData, study_field_detail: e.target.value })
                        }
                        placeholder="Contoh: Teknik Informatika / Manajemen SDM"
                        className="w-full px-3 py-2 text-xs sm:text-sm rounded-md border border-slate-300 focus:outline-hidden focus:border-[#2563EB]"
                      />
                    </div>

                    <div>
                      <label className="block text-xs font-semibold text-slate-700 mb-1">
                        Rentang Pengalaman Kerja <span className="text-red-500">*</span>
                      </label>
                      <select
                        value={formData.experience_range}
                        onChange={(e) =>
                          setFormData({ ...formData, experience_range: e.target.value as any })
                        }
                        className="w-full px-3 py-2 text-xs sm:text-sm rounded-md border border-slate-300 bg-white focus:outline-hidden focus:border-[#2563EB]"
                        required
                      >
                        <option value="fresh_graduate">Fresh Graduate (Belum Berpengalaman)</option>
                        <option value="<1">&lt; 1 Tahun</option>
                        <option value="1-3">1 - 3 Tahun</option>
                        <option value="3-5">3 - 5 Tahun</option>
                        <option value=">5">&gt; 5 Tahun</option>
                      </select>
                    </div>

                    <div className="sm:col-span-2">
                      <label className="block text-xs font-semibold text-slate-700 mb-1">
                        Target Jabatan / Pekerjaan yang Diminati
                      </label>
                      <input
                        type="text"
                        value={formData.desired_occupation}
                        onChange={(e) =>
                          setFormData({ ...formData, desired_occupation: e.target.value })
                        }
                        placeholder="Contoh: Backend Developer, Data Analyst, Admin HRD, Teknisi Listrik"
                        className="w-full px-3 py-2 text-xs sm:text-sm rounded-md border border-slate-300 focus:outline-hidden focus:border-[#2563EB]"
                      />
                      <p className="text-[11px] text-slate-400 mt-1">
                        Jabatan ini akan dicocokkan dengan profil lowongan kerja yang relevan.
                      </p>
                    </div>
                  </div>
                </div>
              )}

              {/* ======================================================== */}
              {/* STEP 3: KEAHLIAN ESCO, PELATIHAN & SERTIFIKASI          */}
              {/* ======================================================== */}
              {currentStep === 3 && (
                <div className="space-y-5 animate-in fade-in duration-150">
                  {/* Skill ESCO Autocomplete */}
                  <div className="space-y-2.5">
                    <div className="flex items-center justify-between pb-1.5 border-b border-slate-200">
                      <div className="flex items-center gap-2 text-slate-800 font-semibold text-xs sm:text-sm">
                        <Briefcase className="w-4 h-4 text-[#0E385E]" />
                        <span>Keahlian & Skill Terstandar ESCO</span>
                        <span className="text-red-500">*</span>
                      </div>
                      <span className="text-xs text-sky-800 font-medium bg-sky-50 px-2 py-0.5 rounded-full border border-sky-200">
                        {selectedSkills.length} skill terpilih
                      </span>
                    </div>

                    {/* Autocomplete Input */}
                    <div className="relative" ref={skillDropdownRef}>
                      <div className="relative">
                        <Search className="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                        <input
                          type="text"
                          value={skillSearchQuery}
                          onChange={(e) => {
                            setSkillSearchQuery(e.target.value);
                            setIsSkillDropdownOpen(true);
                          }}
                          onFocus={() => {
                            if (skillSearchResults.length > 0) setIsSkillDropdownOpen(true);
                          }}
                          placeholder="Cari keahlian (contoh: python, data analysis, public speaking)..."
                          className="w-full pl-9 pr-9 py-2 text-xs sm:text-sm rounded-md border border-slate-300 focus:outline-hidden focus:border-[#2563EB] focus:ring-1 focus:ring-[#2563EB]"
                        />
                        {isSearchingSkills && (
                          <Loader2 className="w-4 h-4 animate-spin text-slate-400 absolute right-3 top-1/2 -translate-y-1/2" />
                        )}
                      </div>

                      {/* Dropdown Options */}
                      {isSkillDropdownOpen && skillSearchResults.length > 0 && (
                        <div className="absolute left-0 right-0 top-full mt-1 bg-white rounded-lg border border-slate-200 shadow-xl max-h-52 overflow-y-auto z-30 divide-y divide-slate-100">
                          {skillSearchResults.map((skill) => {
                            const isAlreadySelected = selectedSkills.some((s) => s.id === skill.id);
                            return (
                              <button
                                type="button"
                                key={skill.id}
                                disabled={isAlreadySelected}
                                onClick={() => handleAddSkill(skill)}
                                className={`w-full px-3.5 py-2 text-left flex items-center justify-between text-xs sm:text-sm transition-colors ${
                                  isAlreadySelected
                                    ? 'bg-slate-50 text-slate-400 cursor-not-allowed'
                                    : 'hover:bg-sky-50 text-slate-800'
                                }`}
                              >
                                <div>
                                  <div className="font-medium">{skill.title}</div>
                                  {skill.title_en && skill.title_en !== skill.title && (
                                    <div className="text-[11px] text-slate-400 italic">
                                      {skill.title_en}
                                    </div>
                                  )}
                                </div>
                                <span className="text-[10px] px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 font-mono">
                                  {skill.type}
                                </span>
                              </button>
                            );
                          })}
                        </div>
                      )}
                    </div>

                    {/* Selected Tag Pills */}
                    <div className="p-3 bg-slate-50 rounded-lg border border-slate-200 min-h-[50px] flex flex-wrap gap-1.5 items-center">
                      {selectedSkills.length === 0 ? (
                        <span className="text-xs text-slate-400 italic">
                          Belum ada skill yang dipilih. Silakan ketik nama keahlian pada kotak pencarian di atas.
                        </span>
                      ) : (
                        selectedSkills.map((skill) => (
                          <span
                            key={skill.id}
                            className="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-[#0E385E] text-white shadow-2xs"
                          >
                            <span>{skill.title}</span>
                            <button
                              type="button"
                              onClick={() => handleRemoveSkill(skill.id)}
                              className="hover:text-red-300 transition-colors ml-0.5"
                            >
                              <X className="w-3.5 h-3.5" />
                            </button>
                          </span>
                        ))
                      )}
                    </div>
                  </div>

                  {/* Riwayat Pelatihan & Sertifikasi Tabs / Grid */}
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4 pt-1">
                    {/* Pelatihan Repeater */}
                    <div className="p-3.5 rounded-lg border border-slate-200 bg-white space-y-2.5">
                      <div className="flex items-center justify-between pb-1.5 border-b border-slate-100">
                        <div className="flex items-center gap-1.5 text-slate-800 font-semibold text-xs">
                          <BookOpen className="w-3.5 h-3.5 text-[#0E385E]" />
                          <span>Riwayat Pelatihan (Opsional)</span>
                        </div>
                        <button
                          type="button"
                          onClick={handleAddTraining}
                          className="inline-flex items-center gap-0.5 text-xs text-[#0E385E] font-medium hover:underline"
                        >
                          <Plus className="w-3.5 h-3.5" />
                          <span>Tambah</span>
                        </button>
                      </div>

                      <div className="space-y-2 max-h-40 overflow-y-auto pr-1">
                        {trainings.length === 0 ? (
                          <p className="text-xs text-slate-400 italic py-2">
                            Belum ada riwayat pelatihan.
                          </p>
                        ) : (
                          trainings.map((t, idx) => (
                            <div
                              key={idx}
                              className="p-2 rounded bg-slate-50 border border-slate-200 space-y-1.5 relative text-xs"
                            >
                              <button
                                type="button"
                                onClick={() => handleRemoveTraining(idx)}
                                className="absolute right-1.5 top-1.5 text-slate-400 hover:text-red-600"
                              >
                                <Trash2 className="w-3.5 h-3.5" />
                              </button>
                              <input
                                type="text"
                                placeholder="Nama Pelatihan"
                                value={t.name}
                                onChange={(e) => handleUpdateTraining(idx, 'name', e.target.value)}
                                className="w-[88%] px-2 py-1 text-xs rounded border border-slate-300 bg-white"
                              />
                              <div className="grid grid-cols-2 gap-1.5">
                                <input
                                  type="text"
                                  placeholder="Penyelenggara"
                                  value={t.organizer || ''}
                                  onChange={(e) =>
                                    handleUpdateTraining(idx, 'organizer', e.target.value)
                                  }
                                  className="px-2 py-1 text-xs rounded border border-slate-300 bg-white"
                                />
                                <input
                                  type="number"
                                  placeholder="Tahun"
                                  value={t.year || ''}
                                  onChange={(e) =>
                                    handleUpdateTraining(
                                      idx,
                                      'year',
                                      e.target.value ? Number(e.target.value) : null
                                    )
                                  }
                                  className="px-2 py-1 text-xs rounded border border-slate-300 bg-white"
                                />
                              </div>
                            </div>
                          ))
                        )}
                      </div>
                    </div>

                    {/* Sertifikasi Repeater */}
                    <div className="p-3.5 rounded-lg border border-slate-200 bg-white space-y-2.5">
                      <div className="flex items-center justify-between pb-1.5 border-b border-slate-100">
                        <div className="flex items-center gap-1.5 text-slate-800 font-semibold text-xs">
                          <Award className="w-3.5 h-3.5 text-[#0E385E]" />
                          <span>Sertifikasi Kompetensi (Opsional)</span>
                        </div>
                        <button
                          type="button"
                          onClick={handleAddCertification}
                          className="inline-flex items-center gap-0.5 text-xs text-[#0E385E] font-medium hover:underline"
                        >
                          <Plus className="w-3.5 h-3.5" />
                          <span>Tambah</span>
                        </button>
                      </div>

                      <div className="space-y-2 max-h-40 overflow-y-auto pr-1">
                        {certifications.length === 0 ? (
                          <p className="text-xs text-slate-400 italic py-2">
                            Belum ada sertifikasi.
                          </p>
                        ) : (
                          certifications.map((c, idx) => (
                            <div
                              key={idx}
                              className="p-2 rounded bg-slate-50 border border-slate-200 space-y-1.5 relative text-xs"
                            >
                              <button
                                type="button"
                                onClick={() => handleRemoveCertification(idx)}
                                className="absolute right-1.5 top-1.5 text-slate-400 hover:text-red-600"
                              >
                                <Trash2 className="w-3.5 h-3.5" />
                              </button>
                              <input
                                type="text"
                                placeholder="Nama Sertifikasi (BNSP / AWS / dll)"
                                value={c.name}
                                onChange={(e) =>
                                  handleUpdateCertification(idx, 'name', e.target.value)
                                }
                                className="w-[88%] px-2 py-1 text-xs rounded border border-slate-300 bg-white"
                              />
                              <div className="grid grid-cols-2 gap-1.5">
                                <input
                                  type="text"
                                  placeholder="Jenis (Nasional / Internasional)"
                                  value={c.type || ''}
                                  onChange={(e) =>
                                    handleUpdateCertification(idx, 'type', e.target.value)
                                  }
                                  className="px-2 py-1 text-xs rounded border border-slate-300 bg-white"
                                />
                                <input
                                  type="number"
                                  placeholder="Tahun"
                                  value={c.year || ''}
                                  onChange={(e) =>
                                    handleUpdateCertification(
                                      idx,
                                      'year',
                                      e.target.value ? Number(e.target.value) : null
                                    )
                                  }
                                  className="px-2 py-1 text-xs rounded border border-slate-300 bg-white"
                                />
                              </div>
                            </div>
                          ))
                        )}
                      </div>
                    </div>
                  </div>
                </div>
              )}
            </>
          )}

          {/* Wizard Footer Controls */}
          <div className="pt-4 border-t border-slate-200 flex items-center justify-between">
            {currentStep > 1 ? (
              <button
                type="button"
                onClick={handlePrev}
                disabled={isSubmitting}
                className="inline-flex items-center gap-1.5 px-4 py-2 rounded-md border border-slate-300 text-xs sm:text-sm font-medium text-slate-700 hover:bg-slate-100 transition-colors"
              >
                <ChevronLeft className="w-4 h-4" />
                <span>Kembali</span>
              </button>
            ) : (
              <button
                type="button"
                onClick={onClose}
                disabled={isSubmitting}
                className="px-4 py-2 rounded-md border border-slate-300 text-xs sm:text-sm font-medium text-slate-700 hover:bg-slate-100 transition-colors"
              >
                Batal
              </button>
            )}

            <div className="flex items-center gap-2">
              {currentStep < 3 ? (
                <button
                  type="button"
                  onClick={handleNext}
                  className="inline-flex items-center gap-1.5 px-5 py-2 rounded-md bg-[#0E385E] text-white text-xs sm:text-sm font-semibold hover:bg-[#163A5F] transition-colors shadow-xs"
                >
                  <span>Lanjut</span>
                  <ChevronRight className="w-4 h-4" />
                </button>
              ) : (
                <button
                  type="submit"
                  disabled={isSubmitting || isLoadingMaster}
                  className="inline-flex items-center gap-2 px-5 py-2 rounded-md bg-emerald-700 text-white text-xs sm:text-sm font-semibold hover:bg-emerald-800 transition-colors shadow-xs disabled:opacity-60 disabled:cursor-not-allowed"
                >
                  {isSubmitting ? (
                    <>
                      <Loader2 className="w-4 h-4 animate-spin" />
                      <span>Menyimpan...</span>
                    </>
                  ) : (
                    <>
                      <Check className="w-4 h-4" />
                      <span>Simpan Profil Pencari Kerja</span>
                    </>
                  )}
                </button>
              )}
            </div>
          </div>
        </form>
      </div>
    </div>
  );
}
