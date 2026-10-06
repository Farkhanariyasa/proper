'use client';

import React, { useState, useEffect, useMemo, useCallback, useRef, Suspense } from 'react';
import { useSearchParams } from 'next/navigation';
import { 
  Users, CheckCircle2, ChevronRight, Search, Briefcase, MapPin, Target,
  Loader2, ArrowRight, Save, Plus, X, Building2, AlertTriangle, AlertCircle,
  RefreshCw, Sparkles, Award, FileText, Check
} from 'lucide-react';
import { 
  getJobSeekers, getJobSeekerDetail, getJobSeekerSkills, 
  updateJobSeekerSkills, extractJobSeekerSkills, searchEscoSkills 
} from '@/services/job-seeker';
import { searchKbji } from '@/services/kbji';
import { getProvinces, getRegencies } from '@/services/wilayah';
import { recommendLowonganApi } from '@/services/matching';
import SearchableSelect, { SearchableOption } from '@/components/ui/SearchableSelect';

export default function MatchingWizardPage() {
  return (
    <Suspense fallback={null}>
      <MatchingWizard />
    </Suspense>
  );
}

function MatchingWizard() {
  const searchParams = useSearchParams();
  const initialPencakerId = searchParams.get('pencaker_id') || '';

  const [step, setStep] = useState<number>(1);
  const [isLoading, setIsLoading] = useState<boolean>(false);
  const [isExtracting, setIsExtracting] = useState<boolean>(false);

  // Data Masters
  const [pencakerList, setPencakerList] = useState<SearchableOption[]>([]);
  const [provinces, setProvinces] = useState<SearchableOption[]>([]);
  const [regencies, setRegencies] = useState<SearchableOption[]>([]);
  
  // States
  const [selectedPencakerId, setSelectedPencakerId] = useState<string>(initialPencakerId);
  const [selectedSeeker, setSelectedSeeker] = useState<any>(null);
  const [pencakerSkills, setPencakerSkills] = useState<any[]>([]);
  const [extractionMeta, setExtractionMeta] = useState<any>(null);
  const [skillFilterTab, setSkillFilterTab] = useState<string>('all');
  
  const [kbjiQuery, setKbjiQuery] = useState('');
  const [kbjiResults, setKbjiResults] = useState<any[]>([]);
  const [selectedKbji, setSelectedKbji] = useState<string>('');
  
  const [selectedProvinsi, setSelectedProvinsi] = useState<string>('');
  const [selectedKabkota, setSelectedKabkota] = useState<string>('');

  const [recommendations, setRecommendations] = useState<any[]>([]);

  // Search Esco Skill
  const [skillQuery, setSkillQuery] = useState('');
  const [skillResults, setSkillResults] = useState<any[]>([]);
  const isSelectingKbji = useRef(false);

  // Modal Detail Recommendation
  const [selectedRecommendation, setSelectedRecommendation] = useState<any>(null);
  const [isDetailModalOpen, setIsDetailModalOpen] = useState<boolean>(false);

  useEffect(() => {
    // Load initial data
    async function loadData() {
      try {
        const [seekers, provs] = await Promise.all([
          getJobSeekers({ per_page: 50 }),
          getProvinces()
        ]);
        setPencakerList(seekers.data.map((s: any) => ({
          value: String(s.id), label: s.name || s.full_name || 'Tanpa Nama', code: s.nik || s.profile_id
        })));
        const sortedProvs = provs.map((p: any) => ({
          value: p.id, label: p.name, code: p.id
        })).sort((a: any, b: any) => String(a.code).localeCompare(String(b.code)));
        setProvinces(sortedProvs);
      } catch (e) {
        console.error("Gagal memuat master", e);
      }
    }
    loadData();
  }, []);

  // Fetch kabkota when province changes
  useEffect(() => {
    if (selectedProvinsi) {
      getRegencies(selectedProvinsi).then(regs => {
        const sortedRegs = regs.map((r: any) => ({ 
          value: r.id, label: r.name, code: r.id 
        })).sort((a: any, b: any) => String(a.code).localeCompare(String(b.code)));
        setRegencies(sortedRegs);
      });
      setSelectedKabkota('');
    } else {
      setRegencies([]);
    }
  }, [selectedProvinsi]);

  // Load skills when continuing to step 2 (auto-extracted from keahlian, experience, sertifikasi)
  const handleProceedToStep2 = async () => {
    if (!selectedPencakerId) return;
    setIsLoading(true);
    try {
      const res = await getJobSeekerSkills(selectedPencakerId);
      setPencakerSkills(res.data || []);
      setExtractionMeta(res.meta || null);
      setStep(2);
    } catch (e) {
      alert("Gagal menarik data skill");
    } finally {
      setIsLoading(false);
    }
  };

  // Re-extract skills forcefully from 3 data sources
  const handleReExtractSkills = async () => {
    if (!selectedPencakerId) return;
    setIsExtracting(true);
    try {
      const res = await extractJobSeekerSkills(selectedPencakerId);
      setPencakerSkills(res.data || []);
      setExtractionMeta(res.meta || null);
    } catch (e) {
      alert("Gagal melakukan ekstraksi ulang skill");
    } finally {
      setIsExtracting(false);
    }
  };

  // Fetch full detail when selection changes
  useEffect(() => {
    if (selectedPencakerId) {
      setIsLoading(true);
      getJobSeekerDetail(selectedPencakerId)
        .then(data => setSelectedSeeker(data))
        .catch(console.error)
        .finally(() => setIsLoading(false));
    } else {
      setSelectedSeeker(null);
    }
  }, [selectedPencakerId]);

  // Pencaker yang dipilih (mis. dari ?pencaker_id=) bisa saja tidak termasuk
  // 50 data awal; sisipkan ke opsi agar namanya tampil di dropdown.
  useEffect(() => {
    if (!selectedSeeker) return;
    const id = String(selectedSeeker.id);
    setPencakerList(prev =>
      prev.some(o => o.value === id)
        ? prev
        : [
            {
              value: id,
              label: selectedSeeker.name || selectedSeeker.full_name || 'Tanpa Nama',
              code: selectedSeeker.nik || selectedSeeker.profile_id,
            },
            ...prev,
          ]
    );
  }, [selectedSeeker, pencakerList]);

  const handleSearchSkill = async () => {
    if (skillQuery.length < 3) return;
    try {
      const res = await searchEscoSkills(skillQuery);
      setSkillResults(res);
    } catch (e) {
      console.error(e);
    }
  };

  const handleAddSkill = (skill: any) => {
    if (!pencakerSkills.find(s => s.id === skill.id)) {
      setPencakerSkills([
        ...pencakerSkills, 
        { 
          ...skill, 
          source: 'manual', 
          pivot: { ...skill.pivot, source: 'manual', is_manual: true } 
        }
      ]);
    }
    setSkillQuery('');
    setSkillResults([]);
  };

  const handleRemoveSkill = (skillId: number) => {
    setPencakerSkills(pencakerSkills.filter(s => s.id !== skillId));
  };

  const handleSaveSkills = async () => {
    setIsLoading(true);
    try {
      await updateJobSeekerSkills(selectedPencakerId, pencakerSkills.map(s => s.id));
      setStep(3);
    } catch (e) {
      alert("Gagal menyimpan skill");
    } finally {
      setIsLoading(false);
    }
  };

  // Auto search KBJI when typing
  useEffect(() => {
    if (isSelectingKbji.current) {
      isSelectingKbji.current = false;
      return;
    }
    const timer = setTimeout(() => {
      if (kbjiQuery.length >= 3) {
        searchKbji(kbjiQuery).then(res => setKbjiResults(res.data)).catch(console.error);
      } else {
        setKbjiResults([]);
      }
    }, 500);
    return () => clearTimeout(timer);
  }, [kbjiQuery]);

  const handleMatch = async () => {
    if (!selectedKbji || !selectedProvinsi) {
      alert("KBJI dan Provinsi wajib diisi");
      return;
    }
    setIsLoading(true);
    try {
      const result = await recommendLowonganApi({
        pencaker_id: Number(selectedPencakerId),
        kbji_code: selectedKbji,
        provinsi_id: selectedProvinsi,
        kabkota_id: selectedKabkota || undefined,
        skills: pencakerSkills.map(s => s.id)
      });
      setRecommendations(result);
      setStep(4);
    } catch (e) {
      alert("Gagal melakukan proses matching");
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="max-w-5xl mx-auto p-4 sm:p-6 lg:p-8 space-y-8 animate-in fade-in duration-500">
      
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold tracking-tight text-slate-900">Rekomendasi Penempatan</h1>
          <p className="text-slate-500 mt-1">Cari lowongan paling cocok untuk pencari kerja.</p>
        </div>
      </div>

      {/* Stepper */}
      <div className="flex items-center justify-between relative before:absolute before:inset-0 before:top-1/2 before:-translate-y-1/2 before:h-0.5 before:bg-slate-200 before:z-0">
        {[1, 2, 3, 4].map(s => (
          <div key={s} className={`relative z-10 w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm border-2 transition-colors ${
            step === s ? 'border-sky-600 bg-sky-600 text-white shadow-lg shadow-sky-200' :
            step > s ? 'border-sky-600 bg-white text-sky-600' : 'border-slate-300 bg-white text-slate-400'
          }`}>
            {step > s ? <CheckCircle2 className="w-5 h-5" /> : s}
          </div>
        ))}
      </div>

      <div className="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-visible">
        
        {/* STEP 1 */}
        {step === 1 && (
          <div className="p-8 space-y-6 animate-in slide-in-from-right-8 duration-300">
            <div className="space-y-2">
              <h2 className="text-xl font-semibold flex items-center gap-2">
                <Users className="w-5 h-5 text-sky-600" />
                Pilih Pencari Kerja
              </h2>
              <p className="text-sm text-slate-500">Pilih pencari kerja yang akan direkomendasikan lowongan pekerjaan.</p>
            </div>
            
            <div className="w-full space-y-6">
              <SearchableSelect 
                options={pencakerList} 
                value={selectedPencakerId} 
                onChange={setSelectedPencakerId} 
                placeholder="Cari NIK atau Nama..."
              />
              
              {selectedSeeker && (
                <div className="space-y-4 animate-in fade-in duration-300">
                  <div className="bg-white border border-slate-200 rounded-xl p-5 md:p-6 shadow-xs">
                    <h3 className="font-bold text-slate-900 border-b border-slate-100 pb-3 flex items-center gap-2 mb-5">
                      <Users className="w-4 h-4 text-sky-600" />
                      Informasi Profil
                    </h3>
                    
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5 text-sm">
                      {/* Kolom Kiri */}
                      <div className="space-y-4">
                        <div>
                          <p className="text-slate-500 text-xs mb-1 font-medium">Nama Lengkap</p>
                          <p className="font-semibold text-slate-900">{selectedSeeker.name || selectedSeeker.full_name || '-'}</p>
                        </div>
                        
                        <div>
                          <p className="text-slate-500 text-xs mb-1 font-medium">Usia / Jenis Kelamin</p>
                          <p className="font-medium text-slate-800">
                            {selectedSeeker.umur ? `${selectedSeeker.umur} Tahun` : '-'} / {selectedSeeker.jenis_kelamin === 'L' ? 'Laki-laki' : selectedSeeker.jenis_kelamin === 'P' ? 'Perempuan' : 'Tidak Diketahui'}
                          </p>
                        </div>
                        
                        <div>
                          <p className="text-slate-500 text-xs mb-1 font-medium">Domisili</p>
                          <p className="font-medium text-slate-800">
                            {selectedSeeker.regency?.name || selectedSeeker.kab_kota || '-'}, {selectedSeeker.province?.name || selectedSeeker.provinsi || '-'}
                          </p>
                        </div>
                      </div>

                      {/* Kolom Kanan */}
                      <div className="space-y-4">
                        <div>
                          <p className="text-slate-500 text-xs mb-1 font-medium">ID / NIK</p>
                          <p className="font-medium text-slate-800 font-mono">{selectedSeeker.profile_id || selectedSeeker.nik || '-'}</p>
                        </div>

                        <div>
                          <p className="text-slate-500 text-xs mb-1 font-medium">Pendidikan Terakhir</p>
                          <p className="font-medium text-slate-800">
                            {selectedSeeker.pendidikan || '-'}
                            {selectedSeeker.jurusan && <span className="text-slate-500 text-xs block mt-0.5">Jurusan: {selectedSeeker.jurusan}</span>}
                          </p>
                        </div>
                        
                        <div>
                          <p className="text-slate-500 text-xs mb-1 font-medium">Pengalaman / Status</p>
                          <p className="font-medium text-slate-800">
                            {selectedSeeker.experience || selectedSeeker.status_bekerja || '-'}
                          </p>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {/* Riwayat Keahlian & Pelatihan */}
                    <div className="bg-slate-50/50 border border-slate-200 rounded-xl p-5 shadow-xs">
                      <h4 className="font-bold text-slate-800 flex items-center gap-2 mb-4 text-sm">
                        <Briefcase className="w-4 h-4 text-[#0E385E]" />
                        Riwayat Keahlian & Pelatihan
                      </h4>
                      <div className="space-y-4 text-sm">
                        <div>
                          <p className="text-slate-500 text-xs mb-1 font-medium">Keahlian (Skill Set):</p>
                          <p className="font-medium text-slate-800">{selectedSeeker.keahlian || '-'}</p>
                        </div>
                        <div>
                          <p className="text-slate-500 text-xs mb-1 font-medium">Lembaga & Program Pelatihan:</p>
                          <p className="font-medium text-slate-800">
                            {selectedSeeker.lembaga_pelatihan || selectedSeeker.progpel ? `${selectedSeeker.lembaga_pelatihan || '-'} - ${selectedSeeker.progpel || ''}` : '-'}
                          </p>
                        </div>
                      </div>
                    </div>

                    {/* Sertifikasi & Riwayat Lain */}
                    <div className="bg-slate-50/50 border border-slate-200 rounded-xl p-5 shadow-xs">
                      <h4 className="font-bold text-slate-800 flex items-center gap-2 mb-4 text-sm">
                        <CheckCircle2 className="w-4 h-4 text-[#0E385E]" />
                        Sertifikasi & Riwayat Lain
                      </h4>
                      <div className="space-y-4 text-sm">
                        <div>
                          <p className="text-slate-500 text-xs mb-1 font-medium">Sertifikasi Dimiliki:</p>
                          <p className="font-medium text-slate-800">{selectedSeeker.sertifikasi || '-'}</p>
                        </div>
                        <div>
                          <p className="text-slate-500 text-xs mb-1 font-medium">Penguasaan Bahasa:</p>
                          <p className="font-medium text-slate-800">{selectedSeeker.bahasa || '-'}</p>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              )}

              <button 
                disabled={!selectedPencakerId || isLoading}
                onClick={handleProceedToStep2}
                className="w-full flex justify-center items-center gap-2 bg-sky-600 hover:bg-sky-700 text-white py-2.5 rounded-lg font-medium transition-all disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {isLoading ? <Loader2 className="w-5 h-5 animate-spin" /> : <ArrowRight className="w-5 h-5" />}
                Lanjutkan Validasi Skill
              </button>
            </div>
          </div>
        )}

        {/* STEP 2 */}
        {step === 2 && (
          <div className="p-8 space-y-6 animate-in slide-in-from-right-8 duration-300">
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
              <div className="space-y-1">
                <h2 className="text-xl font-bold flex items-center gap-2 text-slate-900">
                  <Target className="w-5 h-5 text-sky-600" />
                  Validasi & Penyelarasan Skill Kandidat
                </h2>
                <p className="text-sm text-slate-500">
                  Skill berikut diekstraksi otomatis berbasis 3 pilar: <strong>Keahlian</strong>, <strong>Pengalaman Kerja</strong>, dan <strong>Sertifikasi</strong>.
                </p>
              </div>

              <button
                type="button"
                disabled={isExtracting || isLoading}
                onClick={handleReExtractSkills}
                className="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold rounded-lg bg-sky-50 text-sky-700 hover:bg-sky-100 border border-sky-200 transition-all shadow-xs disabled:opacity-50 shrink-0"
                title="Ekstrak ulang kata kunci dari keahlian, pengalaman, dan sertifikasi"
              >
                <RefreshCw className={`w-3.5 h-3.5 ${isExtracting ? 'animate-spin text-sky-600' : ''}`} />
                <span>{isExtracting ? 'Mengekstrak Ulang...' : 'Ekstraksi Ulang (3 Sumber)'}</span>
              </button>
            </div>

            {/* Sumber Profil Data Mentah Pencaker */}
            {selectedSeeker && (
              <div className="bg-slate-50/80 rounded-xl p-4 border border-slate-200 grid grid-cols-1 md:grid-cols-3 gap-3 text-xs">
                <div className="bg-white p-3 rounded-lg border border-slate-200 shadow-2xs space-y-1">
                  <div className="flex items-center gap-1.5 font-bold text-sky-800">
                    <span className="w-2 h-2 rounded-full bg-sky-500" />
                    <span>🎯 Keahlian (Skill Set)</span>
                  </div>
                  <p className="text-slate-600 line-clamp-2" title={selectedSeeker.keahlian || '-'}>
                    {selectedSeeker.keahlian || <span className="text-slate-400 italic">Tidak dicantumkan</span>}
                  </p>
                </div>

                <div className="bg-white p-3 rounded-lg border border-slate-200 shadow-2xs space-y-1">
                  <div className="flex items-center gap-1.5 font-bold text-emerald-800">
                    <span className="w-2 h-2 rounded-full bg-emerald-500" />
                    <span>💼 Pengalaman (Experience)</span>
                  </div>
                  <p className="text-slate-600 line-clamp-2" title={selectedSeeker.experience || '-'}>
                    {selectedSeeker.experience || <span className="text-slate-400 italic">Tidak ada riwayat</span>}
                  </p>
                </div>

                <div className="bg-white p-3 rounded-lg border border-slate-200 shadow-2xs space-y-1">
                  <div className="flex items-center gap-1.5 font-bold text-purple-800">
                    <span className="w-2 h-2 rounded-full bg-purple-500" />
                    <span>📜 Sertifikasi (Certifications)</span>
                  </div>
                  <p className="text-slate-600 line-clamp-2" title={selectedSeeker.sertifikasi || '-'}>
                    {selectedSeeker.sertifikasi || <span className="text-slate-400 italic">Tidak ada sertifikasi</span>}
                  </p>
                </div>
              </div>
            )}

            <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
              {/* List Keahlian Saat ini */}
              <div className="bg-slate-50 rounded-xl p-5 border border-slate-200 flex flex-col">
                <div className="flex items-center justify-between mb-3">
                  <h3 className="font-semibold text-slate-800 text-sm">
                    Daftar Keahlian ({pencakerSkills.length})
                  </h3>
                  <span className="text-xs text-slate-400">Siap untuk matching</span>
                </div>

                {/* Filter Tabs Berdasarkan Sumber */}
                <div className="flex flex-wrap gap-1.5 mb-4 pb-3 border-b border-slate-200 text-xs">
                  {[
                    { id: 'all', label: `Semua (${pencakerSkills.length})` },
                    { 
                      id: 'keahlian', 
                      label: `Keahlian (${pencakerSkills.filter(s => (s.source || s.pivot?.source || '').includes('keahlian')).length})` 
                    },
                    { 
                      id: 'experience', 
                      label: `Pengalaman (${pencakerSkills.filter(s => (s.source || s.pivot?.source || '').includes('experience')).length})` 
                    },
                    { 
                      id: 'sertifikasi', 
                      label: `Sertifikasi (${pencakerSkills.filter(s => (s.source || s.pivot?.source || '').includes('sertifikasi')).length})` 
                    },
                    { 
                      id: 'manual', 
                      label: `Manual (${pencakerSkills.filter(s => (s.source || s.pivot?.source || '').includes('manual') || s.pivot?.is_manual).length})` 
                    },
                  ].map(tab => (
                    <button
                      key={tab.id}
                      type="button"
                      onClick={() => setSkillFilterTab(tab.id)}
                      className={`px-2.5 py-1 rounded-md font-medium transition-all ${
                        skillFilterTab === tab.id
                          ? 'bg-sky-600 text-white shadow-2xs'
                          : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'
                      }`}
                    >
                      {tab.label}
                    </button>
                  ))}
                </div>

                {pencakerSkills.length === 0 ? (
                  <div className="py-8 text-center text-slate-400 italic text-sm">
                    Belum ada skill yang tersimpan. Gunakan tombol &quot;Ekstraksi Ulang&quot; atau cari manual di sebelah kanan.
                  </div>
                ) : (
                  <div className="flex flex-wrap gap-2 max-h-[340px] overflow-y-auto pr-1">
                    {pencakerSkills
                      .filter(skill => {
                        if (skillFilterTab === 'all') return true;
                        const src = skill.source || skill.pivot?.source || (skill.pivot?.is_manual ? 'manual' : 'keahlian');
                        return src.includes(skillFilterTab);
                      })
                      .map(skill => {
                        const src = skill.source || skill.pivot?.source || (skill.pivot?.is_manual ? 'manual' : 'keahlian');
                        const isFromExp = src.includes('experience');
                        const isFromSert = src.includes('sertifikasi');
                        const isFromManual = src.includes('manual') || skill.pivot?.is_manual;
                        const isFromKeahlian = src.includes('keahlian');

                        let badgeBg = 'bg-sky-100 text-sky-800 border-sky-200';
                        let label = 'Keahlian';

                        if (isFromExp) {
                          badgeBg = 'bg-emerald-100 text-emerald-800 border-emerald-200';
                          label = 'Pengalaman';
                        } else if (isFromSert) {
                          badgeBg = 'bg-purple-100 text-purple-800 border-purple-200';
                          label = 'Sertifikasi';
                        } else if (isFromManual) {
                          badgeBg = 'bg-amber-100 text-amber-800 border-amber-200';
                          label = 'Manual';
                        }

                        return (
                          <div 
                            key={skill.id} 
                            className={`flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium border shadow-2xs transition-all ${badgeBg}`}
                          >
                            <span className="font-semibold">{skill.title}</span>
                            <span className="text-[10px] px-1.5 py-0.2 rounded-full bg-white/70 font-normal">
                              {label}
                            </span>
                            <button 
                              onClick={() => handleRemoveSkill(skill.id)} 
                              className="p-0.5 hover:bg-black/10 rounded-full transition-colors ml-0.5"
                              title="Hapus skill ini"
                            >
                              <X className="w-3.5 h-3.5" />
                            </button>
                          </div>
                        );
                      })}
                  </div>
                )}
              </div>

              {/* Tambah Keahlian Manual */}
              <div className="space-y-4">
                <div className="space-y-1">
                  <h3 className="font-semibold text-slate-800 text-sm">Tambah Skill ESCO Manual</h3>
                  <p className="text-xs text-slate-500">Cari dari 13.900+ taksonomi keahlian standar ESCO.</p>
                </div>
                <div className="relative">
                  <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <Search className="h-4 w-4 text-slate-400" />
                  </div>
                  <input 
                    type="text" 
                    value={skillQuery}
                    onChange={e => {
                      setSkillQuery(e.target.value);
                      if (e.target.value.length >= 3) {
                        searchEscoSkills(e.target.value, 10).then(res => setSkillResults(res));
                      } else {
                        setSkillResults([]);
                      }
                    }}
                    placeholder="Ketik nama skill minimal 3 huruf..."
                    className="w-full pl-10 pr-4 py-2.5 border-slate-300 rounded-lg text-sm focus:ring-sky-500 focus:border-sky-500 bg-white shadow-xs"
                    autoComplete="off"
                  />
                  
                  {skillResults.length > 0 && (
                    <div className="absolute top-full mt-1 left-0 right-0 z-50 bg-white border border-slate-200 rounded-lg shadow-xl max-h-60 overflow-y-auto divide-y divide-slate-100">
                      {skillResults.map(res => (
                        <div key={res.id} className="p-3 flex justify-between items-center hover:bg-slate-50 transition-colors">
                          <div>
                            <p className="text-sm font-medium text-slate-800">{res.title}</p>
                            <p className="text-xs text-slate-500">{res.type}</p>
                          </div>
                          <button 
                            onClick={() => {
                              handleAddSkill(res);
                              setSkillQuery('');
                              setSkillResults([]);
                            }} 
                            className="text-sky-600 hover:bg-sky-50 p-1.5 rounded-md border border-transparent hover:border-sky-200 transition-colors"
                          >
                            <Plus className="w-4 h-4" />
                          </button>
                        </div>
                      ))}
                    </div>
                  )}
                </div>
              </div>
            </div>

            <div className="flex justify-between items-center pt-4 border-t border-slate-100">
              <button onClick={() => setStep(1)} className="text-slate-500 hover:text-slate-700 text-sm font-medium">Klaim Kembali</button>
              <button 
                onClick={handleSaveSkills}
                disabled={isLoading}
                className="flex items-center gap-2 bg-sky-600 hover:bg-sky-700 text-white px-6 py-2.5 rounded-lg font-medium transition-all"
              >
                {isLoading ? <Loader2 className="w-5 h-5 animate-spin" /> : <Save className="w-5 h-5" />}
                Simpan & Lanjut
              </button>
            </div>
          </div>
        )}

        {/* STEP 3 */}
        {step === 3 && (
          <div className="p-8 space-y-6 animate-in slide-in-from-right-8 duration-300">
            <div className="space-y-2">
              <h2 className="text-xl font-semibold flex items-center gap-2">
                <Briefcase className="w-5 h-5 text-sky-600" />
                Preferensi Pencarian
              </h2>
              <p className="text-sm text-slate-500">Pilih jenis jabatan dan lokasi lowongan yang diinginkan kandidat.</p>
            </div>

            <div className="max-w-2xl space-y-6">
              <div className="space-y-2 relative">
                <label className="text-sm font-medium text-slate-700">Jenis Jabatan (KBJI 2026) <span className="text-red-500">*</span></label>
                
                {selectedKbji ? (
                  <div className="relative flex items-center w-full px-3 py-2.5 border border-sky-200 bg-sky-50 rounded-lg shadow-sm">
                    <CheckCircle2 className="w-5 h-5 text-sky-600 mr-2 shrink-0" />
                    <span className="flex-1 text-sm font-medium text-sky-800 line-clamp-1">
                      {selectedKbji} - {kbjiQuery}
                    </span>
                    <button 
                      type="button"
                      onClick={() => {
                        setSelectedKbji('');
                        setKbjiQuery('');
                        isSelectingKbji.current = false;
                      }}
                      className="p-1 text-sky-600 hover:bg-sky-100 rounded-md transition-colors ml-2"
                    >
                      <X className="w-4 h-4" />
                    </button>
                  </div>
                ) : (
                  <div className="relative">
                    <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                      <Search className="h-4 w-4 text-slate-400" />
                    </div>
                    <input 
                      type="text" 
                      value={kbjiQuery}
                      onChange={e => {
                        isSelectingKbji.current = false;
                        setKbjiQuery(e.target.value);
                      }}
                      placeholder="Ketik kata kunci jabatan (min 3 huruf)..."
                      className="w-full pl-10 pr-4 py-2.5 border-slate-300 rounded-lg text-sm focus:ring-sky-500 focus:border-sky-500 bg-white shadow-xs"
                      autoComplete="off"
                    />
                    {kbjiQuery.length > 0 && (
                      <button 
                        type="button"
                        onClick={() => { setKbjiQuery(''); setKbjiResults([]); }}
                        className="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600"
                      >
                        <X className="h-4 w-4" />
                      </button>
                    )}
                  </div>
                )}

                {!selectedKbji && kbjiQuery.length >= 3 && (
                  <div className="absolute bottom-full mb-1 left-0 right-0 z-50 bg-white border border-slate-200 rounded-lg shadow-xl max-h-60 overflow-y-auto">
                    {kbjiResults.length > 0 ? (
                      <div className="divide-y divide-slate-100">
                        {kbjiResults.map(res => (
                          <div 
                            key={res.code} 
                            onClick={() => {
                              isSelectingKbji.current = true;
                              setSelectedKbji(res.code);
                              setKbjiQuery(res.title);
                              setKbjiResults([]);
                            }}
                            className="flex items-start gap-3 p-3 hover:bg-sky-50 cursor-pointer transition-colors"
                          >
                            <div className="mt-0.5">
                              {selectedKbji === res.code ? (
                                <CheckCircle2 className="w-4 h-4 text-sky-600" />
                              ) : (
                                <div className="w-4 h-4 border border-slate-300 rounded-full" />
                              )}
                            </div>
                            <div>
                              <p className="text-sm font-medium text-slate-800">{res.title}</p>
                              <p className="text-xs text-slate-500 font-mono mt-0.5">Kode: {res.code}</p>
                            </div>
                          </div>
                        ))}
                      </div>
                    ) : (
                      <div className="p-4 text-center text-sm text-slate-500">
                        {isLoading ? (
                          <span className="flex items-center justify-center gap-2">
                            <Loader2 className="w-4 h-4 animate-spin" /> Mencari...
                          </span>
                        ) : (
                          'Tidak ditemukan jabatan yang cocok.'
                        )}
                      </div>
                    )}
                  </div>
                )}
                
                {selectedKbji && (
                  <div className="flex items-center gap-2 mt-3 bg-emerald-50 text-emerald-700 px-3 py-2 rounded-lg border border-emerald-100">
                    <CheckCircle2 className="w-4 h-4" />
                    <span className="text-sm font-medium">Jabatan Terpilih: [{selectedKbji}] {kbjiQuery}</span>
                  </div>
                )}
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div className="space-y-2">
                  <label className="text-sm font-medium text-slate-700">Provinsi (Wajib) <span className="text-red-500">*</span></label>
                  <SearchableSelect 
                    options={provinces}
                    value={selectedProvinsi}
                    onChange={setSelectedProvinsi}
                    placeholder="Pilih Provinsi..."
                  />
                </div>
                <div className="space-y-2">
                  <label className="text-sm font-medium text-slate-700">Kabupaten/Kota (Opsional)</label>
                  <SearchableSelect 
                    options={regencies}
                    value={selectedKabkota}
                    onChange={setSelectedKabkota}
                    placeholder="Pilih Kab/Kota..."
                    disabled={!selectedProvinsi}
                  />
                </div>
              </div>
            </div>

            <div className="flex justify-between items-center pt-4 border-t border-slate-100">
              <button onClick={() => setStep(2)} className="text-slate-500 hover:text-slate-700 text-sm font-medium">Kembali</button>
              <button 
                onClick={handleMatch}
                disabled={isLoading || !selectedKbji || !selectedProvinsi}
                className="flex items-center gap-2 bg-[#0E385E] hover:bg-[#154a79] text-white px-6 py-2.5 rounded-lg font-medium transition-all shadow-md hover:shadow-lg disabled:opacity-50"
              >
                {isLoading ? <Loader2 className="w-5 h-5 animate-spin" /> : <Target className="w-5 h-5" />}
                Mulai Matching
              </button>
            </div>
          </div>
        )}

        {/* STEP 4 */}
        {step === 4 && (
          <div className="p-8 space-y-6 animate-in slide-in-from-right-8 duration-300 bg-slate-50">
            <div className="flex items-start justify-between">
              <div>
                <h2 className="text-2xl font-bold text-slate-800">Hasil Rekomendasi</h2>
                <p className="text-sm text-slate-500 mt-1">Ditemukan {recommendations.length} lowongan terbaik berdasarkan kecocokan skill dan lokasi.</p>
              </div>
              <button onClick={() => setStep(1)} className="text-sm text-sky-600 font-medium hover:underline">
                Ulangi Pencarian Baru
              </button>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              {recommendations.length === 0 ? (
                <div className="col-span-full py-12 flex flex-col items-center justify-center text-slate-400 bg-white rounded-xl border border-dashed border-slate-300">
                  <AlertCircle className="w-12 h-12 mb-3 text-slate-300" />
                  <p>Tidak ada lowongan yang cocok dengan preferensi saat ini.</p>
                </div>
              ) : (
                recommendations.map((rec, idx) => (
                  <div key={idx} className="bg-white rounded-xl p-5 shadow-xs border border-slate-200 hover:shadow-md transition-shadow relative overflow-hidden group">
                    <div className="absolute top-0 left-0 w-1 h-full bg-sky-500" />
                    
                    <div className="flex justify-between items-start mb-4">
                      <div className="bg-emerald-50 text-emerald-700 px-3 py-1 rounded-full text-xs font-bold border border-emerald-100">
                        {rec.match_score}% MATCH
                      </div>
                    </div>
                    
                    <h3 className="text-lg font-bold text-slate-800 line-clamp-1">{rec.lowongan.judul_pekerjaan || rec.lowongan.judul_lowongan}</h3>
                    <div className="flex items-center gap-1.5 text-slate-600 mt-1 text-sm">
                      <Building2 className="w-4 h-4 shrink-0" />
                      <span className="truncate">{rec.lowongan.nama_perusahaan}</span>
                    </div>
                    <div className="flex items-center gap-1.5 text-slate-500 mt-1.5 text-sm">
                      <MapPin className="w-4 h-4 shrink-0" />
                      <span className="truncate">{rec.lowongan.regency?.name}, {rec.lowongan.province?.name}</span>
                    </div>

                    <div className="mt-4 pt-4 border-t border-slate-100 flex items-center justify-between">
                      <p className="text-xs text-slate-500 font-medium bg-slate-50 px-2 py-1 rounded">
                        {rec.matched_skills_count} skill cocok
                      </p>
                      <button 
                        onClick={() => {
                          setSelectedRecommendation(rec);
                          setIsDetailModalOpen(true);
                        }}
                        className="text-sky-600 hover:bg-sky-50 px-3 py-1.5 rounded-md text-sm font-medium transition-colors"
                      >
                        Lihat Detail
                      </button>
                    </div>
                  </div>
                ))
              )}
            </div>
          </div>
        )}
      </div>

        {/* Modal Detail Rekomendasi */}
        {isDetailModalOpen && selectedRecommendation && (
          <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
            <div className="bg-white rounded-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col shadow-2xl relative">
              
              <div className="flex justify-between items-center px-6 py-4 border-b border-slate-100">
                <div>
                  <h2 className="text-xl font-bold text-slate-800">Detail Rekomendasi Loker</h2>
                  <p className="text-sm text-slate-500">Analisis kecocokan profil dengan lowongan</p>
                </div>
                <button 
                  onClick={() => setIsDetailModalOpen(false)}
                  className="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-colors"
                >
                  <X className="w-5 h-5" />
                </button>
              </div>

              <div className="p-6 overflow-y-auto bg-slate-50 flex-1">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  
                  {/* Kolom Kiri: Profil Loker */}
                  <div className="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                    <h3 className="font-semibold text-slate-800 border-b border-slate-100 pb-3 mb-4 flex items-center gap-2">
                      <Briefcase className="w-5 h-5 text-sky-500" /> Profil Lowongan
                    </h3>
                    <div className="space-y-4">
                      <div>
                        <p className="text-xs text-slate-500 mb-1">Posisi / Jabatan</p>
                        <p className="font-semibold text-slate-800">
                          {selectedRecommendation.lowongan.judul_pekerjaan || selectedRecommendation.lowongan.judul_lowongan}
                        </p>
                      </div>
                      <div>
                        <p className="text-xs text-slate-500 mb-1">Perusahaan</p>
                        <div className="flex items-center gap-2">
                          <Building2 className="w-4 h-4 text-slate-400" />
                          <p className="text-sm font-medium text-slate-700">{selectedRecommendation.lowongan.nama_perusahaan}</p>
                        </div>
                      </div>
                      <div>
                        <p className="text-xs text-slate-500 mb-1">Lokasi</p>
                        <div className="flex items-center gap-2">
                          <MapPin className="w-4 h-4 text-slate-400" />
                          <p className="text-sm font-medium text-slate-700">
                            {selectedRecommendation.lowongan.regency?.name}, {selectedRecommendation.lowongan.province?.name}
                          </p>
                        </div>
                      </div>
                      <div>
                        <p className="text-xs text-slate-500 mb-1">Kode KBJI</p>
                        <p className="text-sm font-medium text-slate-700">
                          {selectedRecommendation.lowongan.kbji?.code} - {selectedRecommendation.lowongan.kbji?.name}
                        </p>
                      </div>
                      
                      <div className="bg-sky-50 border border-sky-100 rounded-lg p-4 mt-4 text-center">
                        <p className="text-sm text-sky-700 font-medium mb-1">Skor Kecocokan</p>
                        <div className="text-3xl font-bold text-sky-600">{selectedRecommendation.match_score}%</div>
                      </div>
                    </div>
                  </div>

                  {/* Kolom Kanan: Analisis Skill Gap */}
                  <div className="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                    <h3 className="font-semibold text-slate-800 border-b border-slate-100 pb-3 mb-4 flex items-center gap-2">
                      <Target className="w-5 h-5 text-emerald-500" /> Analisis Gap Skill
                    </h3>
                    
                    <div className="space-y-4">
                      <p className="text-sm text-slate-600">
                        Berikut adalah perbandingan antara skill yang disyaratkan oleh lowongan dengan skill yang dimiliki oleh pencari kerja.
                      </p>

                      <div className="space-y-3 max-h-[300px] overflow-y-auto pr-2 custom-scrollbar">
                        {(!selectedRecommendation.lowongan.skills || selectedRecommendation.lowongan.skills.length === 0) ? (
                          <div className="text-center py-6 bg-slate-50 rounded-lg border border-dashed border-slate-200">
                            <p className="text-sm text-slate-500">Lowongan ini tidak mensyaratkan skill spesifik.</p>
                          </div>
                        ) : (
                          selectedRecommendation.lowongan.skills.map((skill: any) => {
                            const matchedItem = pencakerSkills.find(ps => ps.id === skill.id || ps.esco_skill_id === skill.id);
                            const isMatch = !!matchedItem;
                            const rawSource = matchedItem?.source || matchedItem?.pivot?.source || (matchedItem?.pivot?.is_manual ? 'manual' : '');
                            
                            let sourceBadge = '';
                            if (rawSource.includes('experience')) sourceBadge = '💼 Pengalaman';
                            else if (rawSource.includes('sertifikasi')) sourceBadge = '📜 Sertifikasi';
                            else if (rawSource.includes('keahlian')) sourceBadge = '🎯 Keahlian';
                            else if (rawSource.includes('manual')) sourceBadge = '✍️ Input Manual';
                            
                            return (
                              <div key={skill.id} className={`p-3 rounded-lg border ${isMatch ? 'bg-emerald-50 border-emerald-100' : 'bg-rose-50 border-rose-100'}`}>
                                <div className="flex gap-3">
                                  <div className="mt-0.5">
                                    {isMatch ? (
                                      <CheckCircle2 className="w-5 h-5 text-emerald-500" />
                                    ) : (
                                      <AlertCircle className="w-5 h-5 text-rose-500" />
                                    )}
                                  </div>
                                  <div className="flex-1">
                                    <p className={`text-sm font-semibold ${isMatch ? 'text-emerald-800' : 'text-rose-800'}`}>
                                      {skill.title || skill.name}
                                    </p>
                                    <div className={`text-xs mt-1 flex flex-wrap items-center gap-1.5 ${isMatch ? 'text-emerald-700 font-medium' : 'text-rose-600'}`}>
                                      {isMatch ? (
                                        <>
                                          <span>✓ Cocok dengan kandidat</span>
                                          {sourceBadge && (
                                            <span className="text-[10px] px-1.5 py-0.5 rounded bg-white text-emerald-800 border border-emerald-200 shadow-2xs font-normal">
                                              Asal: {sourceBadge}
                                            </span>
                                          )}
                                        </>
                                      ) : (
                                        <span>✗ Belum ada di profil (Skill Gap)</span>
                                      )}
                                    </div>
                                  </div>
                                </div>
                              </div>
                            );
                          })
                        )}
                      </div>

                    </div>
                  </div>

                </div>
              </div>

            </div>
            
            {/* Tombol Rekomendasi Jika Skor > 70 */}
            {selectedRecommendation.match_score > 70 && (
              <div className="px-6 py-4 border-t border-slate-200 bg-slate-50 flex justify-end">
                <button 
                  onClick={() => alert("Berhasil direkomendasikan! (Dummy Action)")}
                  className="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-2.5 rounded-lg font-semibold transition-colors shadow-sm flex items-center gap-2"
                >
                  <CheckCircle2 className="w-5 h-5" />
                  Rekomendasikan Loker
                </button>
              </div>
            )}
          </div>
        )}

    </div>
  );
}
