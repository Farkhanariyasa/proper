'use client';

import React, { useState, useEffect, useMemo, useCallback, useRef, Suspense } from 'react';
import { useSearchParams } from 'next/navigation';
import { 
  Users, CheckCircle2, ChevronRight, Search, Briefcase, MapPin, Target,
  Loader2, ArrowRight, Save, Plus, X, Building2, AlertTriangle, AlertCircle,
  RefreshCw, Sparkles, Award, FileText, Check, LayoutList, Table as TableIcon,
  Maximize2, CheckCheck
} from 'lucide-react';
import { 
  getJobSeekers, getJobSeekerDetail, getJobSeekerSkills, 
  updateJobSeekerSkills, extractJobSeekerSkills, searchEscoSkills 
} from '@/services/job-seeker';
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
  
  // Kriteria Pencarian (By Judul Lowongan / Pekerjaan yang Diinginkan)
  const [jobTitleQuery, setJobTitleQuery] = useState('');
  
  const [selectedProvinsi, setSelectedProvinsi] = useState<string>('');
  const [selectedKabkota, setSelectedKabkota] = useState<string>('');
  const [filterPendidikan, setFilterPendidikan] = useState<boolean>(false);

  const [recommendations, setRecommendations] = useState<any[]>([]);
  const [matchMeta, setMatchMeta] = useState<{
    totalFound: number;
    totalPublished: number;
    keyword: string;
    provinsiName?: string;
    kabkotaName?: string;
  }>({
    totalFound: 0,
    totalPublished: 0,
    keyword: '',
  });

  // Search Esco Skill Manual
  const [skillQuery, setSkillQuery] = useState('');
  const [skillResults, setSkillResults] = useState<any[]>([]);

  // Pagination & Selection
  const [currentPage, setCurrentPage] = useState<number>(1);
  const [recommendedJobs, setRecommendedJobs] = useState<Record<string, boolean>>({});
  const [selectedRecommendation, setSelectedRecommendation] = useState<any>(null);
  const [isDetailModalOpen, setIsDetailModalOpen] = useState<boolean>(false);

  // Cache & Abort Controller untuk optimasi pencarian pencaker
  const searchCacheRef = useRef<Map<string, SearchableOption[]>>(new Map());
  const abortControllerRef = useRef<AbortController | null>(null);

  const handleSearchSeeker = useCallback(async (query: string) => {
    const q = query.trim().toLowerCase();
    if (q.length < 2) return;

    // 1. Cek in-memory cache (0 ms response jika kata kunci sudah pernah dicari)
    if (searchCacheRef.current.has(q)) {
      const cached = searchCacheRef.current.get(q)!;
      setPencakerList(prev => {
        const map = new Map<string, SearchableOption>();
        cached.forEach(item => map.set(item.value, item));
        prev.forEach(item => {
          if (!map.has(item.value)) map.set(item.value, item);
        });
        return Array.from(map.values());
      });
      return;
    }

    // 2. Batalkan request sebelumnya yang masih berjalan (anti race-condition & hemat resource)
    if (abortControllerRef.current) {
      abortControllerRef.current.abort();
    }
    const controller = new AbortController();
    abortControllerRef.current = controller;

    try {
      // 3. Request super-ringan (mode lite: hanya id, nama, profile_id tanpa eager join & withCount)
      const res = await getJobSeekers({ q, per_page: 25, lite: true }, controller.signal);
      if (res?.data) {
        const newOptions: SearchableOption[] = res.data.map((s: any) => ({
          value: String(s.id),
          label: s.name || s.full_name || 'Tanpa Nama',
          code: s.nik || s.profile_id,
        }));

        searchCacheRef.current.set(q, newOptions);

        setPencakerList(prev => {
          const map = new Map<string, SearchableOption>();
          newOptions.forEach(item => map.set(item.value, item));
          prev.forEach(item => {
            if (!map.has(item.value)) map.set(item.value, item);
          });
          return Array.from(map.values());
        });
      }
    } catch (err: any) {
      if (err.name !== 'AbortError') {
        console.error("Gagal mencari pencaker", err);
      }
    }
  }, []);

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
      alert("Gagal menyimpan data skill");
    } finally {
      setIsLoading(false);
    }
  };

  const handleMatch = async () => {
    if (!jobTitleQuery.trim()) {
      alert("Silakan ketik pekerjaan yang diinginkan terlebih dahulu.");
      return;
    }
    setIsLoading(true);
    try {
      const result: any = await recommendLowonganApi({
        pencaker_id: Number(selectedPencakerId),
        pekerjaan: jobTitleQuery.trim(),
        provinsi_id: selectedProvinsi || undefined,
        kabkota_id: selectedKabkota || undefined,
        skills: pencakerSkills.map(s => s.id),
        filter_pendidikan: true,
      });
      const list = Array.isArray(result) ? result : (result?.data || []);
      setRecommendations(list);
      const provObj = provinces.find(p => String(p.value) === String(selectedProvinsi));
      const kabObj = regencies.find(r => String(r.value) === String(selectedKabkota));
      setMatchMeta({
        totalFound: result?.total_found ?? list.length,
        totalPublished: result?.total_published_available ?? 0,
        keyword: jobTitleQuery.trim(),
        provinsiName: provObj?.label,
        kabkotaName: kabObj?.label,
      });
      setCurrentPage(1);
      if (list && list.length > 0) {
        setSelectedRecommendation(list[0]);
      } else {
        setSelectedRecommendation(null);
      }
      setStep(4);
    } catch (e: any) {
      alert(e?.message || "Gagal melakukan proses matching lowongan");
    } finally {
      setIsLoading(false);
    }
  };

  const handleRetryWithoutLocationFilter = async () => {
    setSelectedProvinsi('');
    setSelectedKabkota('');
    setIsLoading(true);
    try {
      const result: any = await recommendLowonganApi({
        pencaker_id: Number(selectedPencakerId),
        pekerjaan: jobTitleQuery.trim(),
        skills: pencakerSkills.map(s => s.id),
        filter_pendidikan: filterPendidikan ? true : undefined,
      });
      const list = Array.isArray(result) ? result : (result?.data || []);
      setRecommendations(list);
      setMatchMeta({
        totalFound: result?.total_found ?? list.length,
        totalPublished: result?.total_published_available ?? 0,
        keyword: jobTitleQuery.trim(),
        provinsiName: undefined,
        kabkotaName: undefined,
      });
      setCurrentPage(1);
      if (list && list.length > 0) {
        setSelectedRecommendation(list[0]);
      } else {
        setSelectedRecommendation(null);
      }
    } catch (e: any) {
      alert(e?.message || "Gagal melakukan proses matching lowongan");
    } finally {
      setIsLoading(false);
    }
  };

  const handleRecommendJob = (jobId: string | number) => {
    const key = String(jobId);
    setRecommendedJobs(prev => ({ ...prev, [key]: true }));
    alert("Kandidat berhasil direkomendasikan untuk lowongan ini! Notifikasi telah dikirimkan ke database penempatan.");
  };

  return (
    <div className={`mx-auto p-4 sm:p-6 lg:p-8 space-y-8 animate-in fade-in duration-500 transition-all ${
      step >= 3 ? 'max-w-7xl' : 'max-w-5xl'
    }`}>
      
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold tracking-tight text-slate-900">Rekomendasi Penempatan</h1>
          <p className="text-slate-500 mt-1">Cari lowongan paling cocok untuk pencari kerja.</p>
        </div>
      </div>

      {/* Stepper (4 Langkah) */}
      <div className="grid grid-cols-2 sm:grid-cols-4 gap-2 sm:gap-3 max-w-3xl mx-auto">
        {[
          { num: 1, title: '1. Pilih Pencaker', desc: 'Identitas kandidat' },
          { num: 2, title: '2. Validasi Skill', desc: 'Skill dari 3 pilar' },
          { num: 3, title: '3. Pilih Pekerjaan', desc: 'Judul loker & lokasi' },
          { num: 4, title: '4. Hasil Rekomendasi', desc: '10 ter-match' },
        ].map(s => {
          const isClickable = s.num < step || (s.num === 2 && Boolean(selectedPencakerId)) || (s.num === 3 && Boolean(selectedPencakerId) && pencakerSkills.length > 0);
          return (
            <button 
              key={s.num}
              type="button"
              disabled={!isClickable && s.num !== step}
              onClick={() => {
                if (isClickable) setStep(s.num);
              }}
              className={`flex items-center gap-2.5 p-2 rounded-xl border transition-all text-left ${
                step === s.num 
                  ? 'bg-sky-50/70 border-sky-300 shadow-2xs' 
                  : isClickable
                    ? 'bg-white border-slate-200 opacity-90 hover:bg-slate-50 cursor-pointer' 
                    : 'bg-white border-slate-100 opacity-50 cursor-not-allowed'
              }`}
            >
              <div className={`w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs border-2 transition-colors shrink-0 ${
                step === s.num ? 'border-sky-600 bg-sky-600 text-white shadow-md shadow-sky-200' :
                step > s.num ? 'border-emerald-600 bg-emerald-50 text-emerald-600' : 'border-slate-300 bg-white text-slate-400'
              }`}>
                {step > s.num ? <CheckCircle2 className="w-4 h-4" /> : s.num}
              </div>
              <div className="min-w-0">
                <p className={`text-xs font-bold truncate ${step === s.num ? 'text-sky-900' : 'text-slate-700'}`}>
                  {s.title}
                </p>
                <p className="text-[10px] text-slate-400 truncate hidden md:block">{s.desc}</p>
              </div>
            </button>
          );
        })}
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
                onSearch={handleSearchSeeker}
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
                className="w-full flex justify-center items-center gap-2 bg-sky-600 hover:bg-sky-700 text-white py-2.5 rounded-lg font-medium transition-all disabled:opacity-50 disabled:cursor-not-allowed shadow-sm hover:shadow"
              >
                {isLoading ? <Loader2 className="w-5 h-5 animate-spin" /> : <ArrowRight className="w-5 h-5" />}
                Lanjutkan Validasi Skill
              </button>
            </div>
          </div>
        )}

        {/* STEP 2: Validasi & Penyelarasan Skill */}
        {step === 2 && (
          <div className="p-6 sm:p-8 space-y-6 animate-in slide-in-from-right-8 duration-300">
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
                className="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold rounded-lg bg-sky-50 text-sky-700 hover:bg-sky-100 border border-sky-200 transition-all shadow-xs disabled:opacity-50 shrink-0 self-start sm:self-auto"
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
                    <Target className="w-3.5 h-3.5 text-sky-600" />
                    <span>Keahlian (Skill Set)</span>
                  </div>
                  <p className="text-slate-600 line-clamp-2" title={selectedSeeker.keahlian || '-'}>
                    {selectedSeeker.keahlian || <span className="text-slate-400 italic">Tidak dicantumkan</span>}
                  </p>
                </div>

                <div className="bg-white p-3 rounded-lg border border-slate-200 shadow-2xs space-y-1">
                  <div className="flex items-center gap-1.5 font-bold text-emerald-800">
                    <Briefcase className="w-3.5 h-3.5 text-emerald-600" />
                    <span>Pengalaman (Experience)</span>
                  </div>
                  <p className="text-slate-600 line-clamp-2" title={selectedSeeker.experience || '-'}>
                    {selectedSeeker.experience || <span className="text-slate-400 italic">Tidak ada riwayat</span>}
                  </p>
                </div>

                <div className="bg-white p-3 rounded-lg border border-slate-200 shadow-2xs space-y-1">
                  <div className="flex items-center gap-1.5 font-bold text-purple-800">
                    <Award className="w-3.5 h-3.5 text-purple-600" />
                    <span>Sertifikasi (Certifications)</span>
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
                    className="w-full pl-10 pr-4 py-2.5 border border-slate-300 rounded-lg text-sm focus:ring-sky-500 focus:border-sky-500 bg-white shadow-xs"
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

            <div className="flex justify-between items-center pt-5 border-t border-slate-200">
              <button 
                type="button"
                onClick={() => setStep(1)} 
                className="text-slate-600 hover:text-slate-900 text-sm font-medium px-4 py-2 rounded-lg hover:bg-slate-100 transition-colors"
              >
                ← Kembali ke Pilih Pencaker
              </button>
              <button 
                type="button"
                onClick={handleSaveSkills}
                disabled={isLoading}
                className="flex items-center gap-2 bg-sky-600 hover:bg-sky-700 text-white px-6 py-2.5 rounded-lg font-medium transition-all shadow-sm hover:shadow"
              >
                {isLoading ? <Loader2 className="w-5 h-5 animate-spin" /> : <Save className="w-5 h-5" />}
                Simpan & Lanjut ke Pilih Pekerjaan
              </button>
            </div>
          </div>
        )}

        {/* STEP 3: Pilih Pekerjaan (Preferensi Pencarian) */}
        {step === 3 && (
          <div className="p-6 sm:p-8 space-y-6 animate-in slide-in-from-right-8 duration-300">
            <div className="space-y-1">
              <h2 className="text-xl font-bold flex items-center gap-2 text-slate-900">
                <Briefcase className="w-5 h-5 text-sky-600" />
                Pilih Pekerjaan yang Diinginkan
              </h2>
              <p className="text-sm text-slate-500">
                Ketik nama pekerjaan yang diinginkan kandidat. Sistem akan mencari lowongan yang mengandung kata tersebut di judul lowongan dan menampilkan 10 paling cocok dengan skill kandidat.
              </p>
            </div>

            <div className="w-full space-y-6">
              {/* Box Pekerjaan yang Diinginkan */}
              <div className="bg-slate-50 border border-slate-200 rounded-xl p-5 space-y-4 shadow-2xs">
                <div className="space-y-2">
                  <label className="text-sm font-semibold text-slate-800 flex items-center justify-between">
                    <span>Pekerjaan yang Diinginkan (Judul Lowongan Kerja) <span className="text-rose-500">*</span></span>
                    <span className="text-xs font-normal text-slate-500">Pencarian judul lowongan</span>
                  </label>
                  <div className="relative">
                    <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                      <Search className="h-4 w-4 text-slate-400" />
                    </div>
                    <input 
                      type="text" 
                      value={jobTitleQuery}
                      onChange={e => setJobTitleQuery(e.target.value)}
                      placeholder="Ketik pekerjaan yang diinginkan (misal: Staff Administrasi, Kasir, IT Support, Front Office, Driver)..."
                      className="w-full pl-10 pr-10 py-3 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 bg-white shadow-xs font-medium text-slate-800 placeholder:text-slate-400"
                      autoFocus
                    />
                    {jobTitleQuery.length > 0 && (
                      <button 
                        type="button"
                        onClick={() => setJobTitleQuery('')}
                        className="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 transition-colors"
                        title="Hapus ketikan"
                      >
                        <X className="h-4 w-4" />
                      </button>
                    )}
                  </div>
                </div>

                {/* Filter Wilayah (Opsional) */}
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-3 border-t border-slate-200">
                  <div className="space-y-1.5">
                    <label className="text-xs font-semibold text-slate-700">Provinsi Penempatan (Opsional)</label>
                    <SearchableSelect 
                      options={provinces}
                      value={selectedProvinsi}
                      onChange={setSelectedProvinsi}
                      placeholder="Semua Provinsi..."
                    />
                  </div>
                  <div className="space-y-1.5">
                    <label className="text-xs font-semibold text-slate-700">Kabupaten/Kota (Opsional)</label>
                    <SearchableSelect 
                      options={regencies}
                      value={selectedKabkota}
                      onChange={setSelectedKabkota}
                      placeholder={selectedProvinsi ? "Semua Kab/Kota..." : "Pilih Provinsi dulu..."}
                      disabled={!selectedProvinsi}
                    />
                  </div>
                </div>
              </div>

              {/* Status Validasi Skill & Pendidikan Kandidat Ringkas */}
              <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-4 bg-sky-50/70 border border-sky-200 rounded-xl text-xs text-sky-900">
                <div className="flex items-center gap-3">
                  <CheckCircle2 className="w-5 h-5 text-sky-600 shrink-0" />
                  <div>
                    <p className="font-semibold">
                      Kandidat: {selectedSeeker?.name || selectedSeeker?.full_name || 'Kandidat'} • Pendidikan: <span className="font-bold text-sky-800">{selectedSeeker?.pendidikan || '-'}</span>
                    </p>
                    <p className="text-sky-700 mt-0.5">
                      {pencakerSkills.length} keahlian siap dicocokkan dengan formasi lowongan kerja.
                    </p>
                  </div>
                </div>

                <div className="inline-flex items-center gap-1.5 bg-emerald-50 px-3 py-1.5 rounded-lg border border-emerald-200 text-emerald-800 font-semibold shrink-0 shadow-2xs">
                  <Check className="w-3.5 h-3.5 text-emerald-600" />
                  <span>Kualifikasi Pendidikan: Wajib Memenuhi</span>
                </div>
              </div>
            </div>

            <div className="flex justify-between items-center pt-5 border-t border-slate-200">
              <button 
                type="button"
                onClick={() => setStep(2)} 
                className="text-slate-600 hover:text-slate-900 text-sm font-medium px-4 py-2 rounded-lg hover:bg-slate-100 transition-colors"
              >
                ← Kembali ke Validasi Skill
              </button>
              <button 
                type="button"
                onClick={handleMatch}
                disabled={isLoading || !jobTitleQuery.trim()}
                className="flex items-center gap-2 bg-[#0E385E] hover:bg-[#154a79] text-white px-7 py-3 rounded-xl font-semibold text-sm transition-all shadow-md hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {isLoading ? <Loader2 className="w-5 h-5 animate-spin" /> : <Target className="w-5 h-5" />}
                Mulai Matching (Tampilkan 10 Ter-Match)
              </button>
            </div>
          </div>
        )}

        {/* STEP 4: Hasil Rekomendasi (Tampilan Tabel Langsung 5x2 Pagination) */}
        {step === 4 && (
          <div className="p-6 sm:p-8 space-y-6 animate-in slide-in-from-right-8 duration-300 bg-slate-50/60">
            {/* Header Rekomendasi */}
            <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-4 border-b border-slate-200">
              <div className="space-y-1">
                <div className="flex items-center gap-2 flex-wrap">
                  <span className={`px-2.5 py-0.5 rounded-full text-xs font-bold shadow-2xs ${
                    recommendations.length > 0 ? 'bg-[#0E385E] text-white' : 'bg-amber-100 text-amber-800 border border-amber-300'
                  }`}>
                    {recommendations.length > 0 ? `Top ${recommendations.length} Rekomendasi Ter-match` : '0 Lowongan Ter-match'}
                  </span>
                  <span className="text-xs text-slate-400">•</span>
                  <span className="text-xs text-slate-600 font-medium">
                    Kandidat: <strong className="text-slate-900">{selectedSeeker?.name || selectedSeeker?.full_name || 'Kandidat'}</strong>
                  </span>
                  <span className="text-xs text-slate-400">•</span>
                  <span className="text-xs text-slate-600">
                    Kata Kunci: <strong className="text-slate-900">&quot;{jobTitleQuery}&quot;</strong>
                  </span>
                </div>
                <h2 className="text-2xl font-bold text-slate-900">Hasil Rekomendasi Lowongan Kerja</h2>
                <p className="text-xs text-slate-500">
                  {recommendations.length > 0 
                    ? 'Daftar 10 lowongan pekerjaan paling cocok berdasarkan kesesuaian skill kandidat dan kata kunci jabatan.'
                    : 'Belum ada lowongan aktif yang cocok dengan kriteria pencarian saat ini.'}
                </p>
              </div>

              <div className="flex flex-wrap items-center gap-2.5 shrink-0">
                <button 
                  onClick={() => setStep(3)} 
                  className="px-3.5 py-1.5 text-xs font-semibold rounded-lg bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 transition-colors shadow-2xs"
                >
                  Ubah Kriteria
                </button>
                <button 
                  onClick={() => {
                    setSelectedPencakerId('');
                    setSelectedSeeker(null);
                    setJobTitleQuery('');
                    setRecommendations([]);
                    setSelectedRecommendation(null);
                    setCurrentPage(1);
                    setStep(1);
                  }} 
                  className="px-3.5 py-1.5 text-xs font-semibold rounded-lg bg-sky-50 border border-sky-200 text-sky-700 hover:bg-sky-100 transition-colors"
                >
                  Pencaker Baru
                </button>
              </div>
            </div>

            {/* Empty State: Informasi Jelas Lowongan Published Tidak Ditemukan */}
            {recommendations.length === 0 ? (
              <div className="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-sm max-w-2xl mx-auto space-y-5">
                <div className="flex flex-col items-center text-center space-y-2.5">
                  <div className="w-12 h-12 rounded-xl bg-amber-50 border border-amber-200 flex items-center justify-center text-amber-600 shadow-2xs">
                    <AlertCircle className="w-6 h-6" />
                  </div>
                  
                  <div className="space-y-1">
                    <div className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-emerald-50 border border-emerald-200 text-[11px] font-medium text-emerald-700 mb-1">
                      <span className="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                      Hanya lowongan aktif (tayang)
                    </div>
                    <h3 className="text-lg font-bold text-slate-900">
                      Tidak Ada Lowongan yang Cocok
                    </h3>
                    <p className="text-xs sm:text-sm text-slate-500 max-w-md mx-auto">
                      Tidak ditemukan lowongan aktif untuk kata kunci <strong className="text-slate-800">&quot;{jobTitleQuery}&quot;</strong>
                      {matchMeta.provinsiName ? (
                        <> di wilayah <strong className="text-slate-800">{matchMeta.provinsiName}{matchMeta.kabkotaName ? `, ${matchMeta.kabkotaName}` : ''}</strong>.</>
                      ) : '.'}
                    </p>
                  </div>
                </div>

                {/* Ringkasan Parameter Pencarian yang Digunakan */}
                <div className="bg-slate-50 border border-slate-200 rounded-xl p-3.5 sm:p-4">
                  <h4 className="text-xs font-semibold text-slate-500 mb-2.5 flex items-center gap-1.5">
                    <Search className="w-3.5 h-3.5 text-slate-400" /> Filter yang Digunakan
                  </h4>
                  <div className="grid grid-cols-2 sm:grid-cols-4 gap-2.5 text-xs">
                    <div className="bg-white p-2.5 rounded-lg border border-slate-200/80 shadow-2xs">
                      <p className="text-slate-400 text-[11px]">Kata Kunci</p>
                      <p className="font-semibold text-slate-800 truncate mt-0.5">&quot;{jobTitleQuery}&quot;</p>
                    </div>
                    <div className="bg-white p-2.5 rounded-lg border border-slate-200/80 shadow-2xs">
                      <p className="text-slate-400 text-[11px]">Status</p>
                      <p className="font-semibold text-emerald-700 mt-0.5 flex items-center gap-1">
                        <span className="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Tayang
                      </p>
                    </div>
                    <div className="bg-white p-2.5 rounded-lg border border-slate-200/80 shadow-2xs">
                      <p className="text-slate-400 text-[11px]">Wilayah</p>
                      <p className="font-semibold text-slate-800 truncate mt-0.5" title={matchMeta.provinsiName || 'Semua Wilayah (Nasional)'}>
                        {matchMeta.provinsiName ? `${matchMeta.provinsiName}${matchMeta.kabkotaName ? ` (${matchMeta.kabkotaName})` : ''}` : 'Semua Wilayah'}
                      </p>
                    </div>
                    <div className="bg-white p-2.5 rounded-lg border border-slate-200/80 shadow-2xs">
                      <p className="text-slate-400 text-[11px]">Skill Kandidat</p>
                      <p className="font-semibold text-sky-700 mt-0.5">
                        {pencakerSkills.length} Keahlian
                      </p>
                    </div>
                  </div>
                </div>

                {/* Informasi Penyebab & Saran Solusi */}
                <div className="border border-amber-200/70 bg-amber-50/40 rounded-xl p-3.5 sm:p-4 text-xs text-slate-700 space-y-1.5">
                  <p className="font-semibold text-amber-900 flex items-center gap-1.5">
                    <AlertTriangle className="w-3.5 h-3.5 text-amber-600 shrink-0" />
                    Saran Pencarian:
                  </p>
                  <ul className="space-y-1 text-slate-600 pl-5 list-disc">
                    <li>Gunakan kata kunci posisi yang lebih umum (misal: <em>Staff, Admin, Teknisi, Operator</em>).</li>
                    <li>Pastikan lowongan sudah dipublikasikan dan masa berlakunya masih aktif.</li>
                    {matchMeta.provinsiName && (
                      <li>Coba hapus filter wilayah untuk memperluas pencarian ke cakupan nasional.</li>
                    )}
                  </ul>
                </div>

                {/* Tombol Aksi */}
                <div className="flex flex-col sm:flex-row items-center justify-center gap-2.5 pt-1">
                  <button 
                    type="button"
                    onClick={() => setStep(3)} 
                    className="w-full sm:w-auto px-5 py-2.5 bg-sky-600 text-white rounded-xl text-xs font-semibold hover:bg-sky-700 transition-all shadow-sm flex items-center justify-center gap-2"
                  >
                    ← Ubah Kriteria Pencarian
                  </button>
                  
                  {Boolean(selectedProvinsi || selectedKabkota) && (
                    <button 
                      type="button"
                      onClick={handleRetryWithoutLocationFilter}
                      disabled={isLoading}
                      className="w-full sm:w-auto px-4 py-2.5 bg-white border border-slate-300 text-slate-700 rounded-xl text-xs font-semibold hover:bg-slate-50 hover:border-slate-400 transition-all flex items-center justify-center gap-2 shadow-2xs"
                    >
                      {isLoading ? <Loader2 className="w-4 h-4 animate-spin text-sky-600" /> : <RefreshCw className="w-3.5 h-3.5 text-slate-500" />}
                      Cari Tanpa Filter Wilayah
                    </button>
                  )}

                  <button 
                    type="button"
                    onClick={() => {
                      setSelectedPencakerId('');
                      setSelectedSeeker(null);
                      setJobTitleQuery('');
                      setRecommendations([]);
                      setSelectedRecommendation(null);
                      setCurrentPage(1);
                      setStep(1);
                    }} 
                    className="w-full sm:w-auto px-4 py-2.5 text-slate-500 hover:text-slate-800 rounded-xl text-xs font-medium hover:bg-slate-100 transition-colors"
                  >
                    Pilih Pencaker Lain
                  </button>
                </div>
              </div>
            ) : (
              /* TABEL LANGSUNG DENGAN PAGINATION 5 x 2 */
              <div className="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs space-y-0">
                <div className="overflow-x-auto">
                  <table className="w-full text-left text-xs text-slate-600">
                    <thead className="bg-slate-50 border-b border-slate-200 text-slate-700 font-bold uppercase text-[11px] tracking-wider">
                      <tr>
                        <th className="py-3.5 px-4 w-14 text-center">Rank</th>
                        <th className="py-3.5 px-4">Posisi Lowongan & Perusahaan</th>
                        <th className="py-3.5 px-4">Pendidikan</th>
                        <th className="py-3.5 px-4">Lokasi Penempatan</th>
                        <th className="py-3.5 px-4">Kode KBJI</th>
                        <th className="py-3.5 px-4 text-center">Skor Match</th>
                        <th className="py-3.5 px-4 text-center">Skill Cocok</th>
                        <th className="py-3.5 px-4 text-center">Status</th>
                        <th className="py-3.5 px-4 text-right">Aksi</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                      {recommendations
                        .slice((currentPage - 1) * 5, currentPage * 5)
                        .map((rec, idx) => {
                          const globalRank = (currentPage - 1) * 5 + idx + 1;
                          const totalLokerSkills = rec.lowongan?.skills?.length || 0;
                          const isRecommended = recommendedJobs[String(rec.lowongan?.id)];
                          
                          let scoreBadgeClass = "bg-emerald-50 text-emerald-700 border-emerald-200";
                          if (rec.match_score < 70) scoreBadgeClass = "bg-sky-50 text-sky-700 border-sky-200";
                          if (rec.match_score < 40) scoreBadgeClass = "bg-amber-50 text-amber-700 border-amber-200";
                          if (rec.match_score === 0) scoreBadgeClass = "bg-slate-100 text-slate-600 border-slate-200";

                          return (
                            <tr key={rec.lowongan?.id || idx} className="hover:bg-slate-50/80 transition-colors">
                              <td className="py-4 px-4 text-center">
                                <span className={`inline-flex items-center justify-center w-7 h-7 rounded-lg text-xs font-bold ${
                                  globalRank === 1 
                                    ? 'bg-amber-100 text-amber-900 border border-amber-300 font-extrabold' 
                                    : globalRank === 2
                                      ? 'bg-slate-100 text-slate-800 border border-slate-300'
                                      : globalRank === 3
                                        ? 'bg-amber-50 text-amber-800 border border-amber-200'
                                        : 'bg-slate-50 text-slate-600'
                                }`}>
                                  #{globalRank}
                                </span>
                              </td>
                              <td className="py-4 px-4">
                                <p className="font-bold text-slate-900 text-sm">{rec.lowongan.judul_pekerjaan || rec.lowongan.judul_lowongan}</p>
                                <p className="text-slate-500 flex items-center gap-1.5 mt-1 text-xs">
                                  <Building2 className="w-3.5 h-3.5 text-slate-400 shrink-0" />
                                  <span className="truncate">{rec.lowongan.nama_perusahaan}</span>
                                </p>
                              </td>
                              <td className="py-4 px-4">
                                <span className="font-semibold text-slate-800 text-xs block">
                                  {rec.lowongan?.education_level?.name || rec.education_match?.required_level_name || 'Semua Jenjang'}
                                </span>
                                {rec.education_match && (
                                  rec.education_match.is_matched ? (
                                    <span className="inline-flex items-center gap-1 text-[10px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200 mt-1">
                                      <Check className="w-2.5 h-2.5 text-emerald-600 shrink-0" />
                                      {rec.education_match.status === 'melebihi' ? 'Memenuhi (>)' : 'Sesuai'} ({selectedSeeker?.pendidikan || rec.education_match.candidate_level_name})
                                    </span>
                                  ) : (
                                    <span className="inline-flex items-center gap-1 text-[10px] font-semibold text-rose-700 bg-rose-50 px-2 py-0.5 rounded-full border border-rose-200 mt-1" title={rec.education_match.label}>
                                      <AlertCircle className="w-2.5 h-2.5 text-rose-500 shrink-0" />
                                      Di Bawah Syarat ({selectedSeeker?.pendidikan || rec.education_match.candidate_level_name})
                                    </span>
                                  )
                                )}
                              </td>
                              <td className="py-4 px-4 text-slate-600">
                                <div className="flex items-center gap-1.5">
                                  <MapPin className="w-3.5 h-3.5 text-slate-400 shrink-0" />
                                  <span>{rec.lowongan.regency?.name || '-'}, {rec.lowongan.province?.name || '-'}</span>
                                </div>
                              </td>
                              <td className="py-4 px-4 text-slate-600 font-mono text-[11px]">
                                {rec.lowongan.kbji?.code ? (
                                  <span title={rec.lowongan.kbji?.name}>
                                    {rec.lowongan.kbji?.code}
                                  </span>
                                ) : (
                                  <span className="text-slate-400 italic">-</span>
                                )}
                              </td>
                              <td className="py-4 px-4 text-center">
                                <span className={`inline-block px-2.5 py-1 rounded-full font-extrabold text-xs border ${scoreBadgeClass}`}>
                                  {rec.match_score}%
                                </span>
                              </td>
                              <td className="py-4 px-4 text-center font-medium text-slate-700">
                                <span className={rec.matched_skills_count > 0 ? 'text-emerald-700 font-bold' : 'text-slate-500'}>
                                  {rec.matched_skills_count}
                                </span>
                                <span className="text-slate-400"> / {totalLokerSkills} skill</span>
                              </td>
                              <td className="py-4 px-4 text-center">
                                {isRecommended ? (
                                  <span className="inline-flex items-center gap-1 text-emerald-700 font-bold bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200 text-[10px]">
                                    <Check className="w-3 h-3 text-emerald-600" />
                                    Direkomendasikan
                                  </span>
                                ) : rec.match_score >= 70 ? (
                                  <span className="inline-flex items-center gap-1 text-emerald-700 font-medium bg-emerald-50/60 px-2.5 py-0.5 rounded-full text-[10px] border border-emerald-100">
                                    Sangat Cocok
                                  </span>
                                ) : rec.match_score >= 40 ? (
                                  <span className="inline-flex items-center gap-1 text-sky-700 font-medium bg-sky-50 px-2.5 py-0.5 rounded-full text-[10px] border border-sky-100">
                                    Cukup Sesuai
                                  </span>
                                ) : (
                                  <span className="inline-flex items-center gap-1 text-slate-500 bg-slate-100 px-2.5 py-0.5 rounded-full text-[10px]">
                                    Perlu Pelatihan
                                  </span>
                                )}
                              </td>
                              <td className="py-4 px-4 text-right">
                                <button
                                  type="button"
                                  onClick={() => {
                                    setSelectedRecommendation(rec);
                                    setIsDetailModalOpen(true);
                                  }}
                                  className="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-sky-50 text-sky-700 hover:bg-sky-100 border border-sky-200 transition-colors shadow-2xs"
                                >
                                  <Target className="w-3.5 h-3.5 text-sky-600" />
                                  <span>Lihat Analisis & Detail</span>
                                </button>
                              </td>
                            </tr>
                          );
                        })}
                    </tbody>
                  </table>
                </div>

                {/* Pagination Controls (5 baris x 2 halaman) */}
                <div className="p-4 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-600">
                  <div>
                    Menampilkan{' '}
                    <strong className="text-slate-900">
                      {(currentPage - 1) * 5 + 1}
                    </strong>{' '}
                    -{' '}
                    <strong className="text-slate-900">
                      {Math.min(currentPage * 5, recommendations.length)}
                    </strong>{' '}
                    dari <strong className="text-slate-900">{recommendations.length}</strong> lowongan kerja
                  </div>

                  <div className="flex items-center gap-1.5">
                    <button
                      type="button"
                      disabled={currentPage === 1}
                      onClick={() => setCurrentPage(prev => Math.max(prev - 1, 1))}
                      className="px-3 py-1.5 rounded-lg border border-slate-200 bg-white font-medium hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                    >
                      Sebelumnya
                    </button>

                    {Array.from({ length: Math.ceil(recommendations.length / 5) || 1 }, (_, i) => i + 1).map(pageNum => (
                      <button
                        key={pageNum}
                        type="button"
                        onClick={() => setCurrentPage(pageNum)}
                        className={`w-8 h-8 rounded-lg font-bold text-xs transition-all ${
                          currentPage === pageNum
                            ? 'bg-[#0E385E] text-white shadow-2xs'
                            : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-100'
                        }`}
                      >
                        {pageNum}
                      </button>
                    ))}

                    <button
                      type="button"
                      disabled={currentPage >= Math.ceil(recommendations.length / 5)}
                      onClick={() => setCurrentPage(prev => Math.min(prev + 1, Math.ceil(recommendations.length / 5)))}
                      className="px-3 py-1.5 rounded-lg border border-slate-200 bg-white font-medium hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                    >
                      Selanjutnya
                    </button>
                  </div>
                </div>
              </div>
            )}

          </div>
        )}
      </div>

      {/* Modal Detail Rekomendasi & Analisis Gap Skill (Bebas Emoticon) */}
      {isDetailModalOpen && selectedRecommendation && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
          <div className="bg-white rounded-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col shadow-2xl relative">
            
            <div className="flex justify-between items-center px-6 py-4 border-b border-slate-100">
              <div>
                <h2 className="text-xl font-bold text-slate-800">Detail Rekomendasi Lowongan</h2>
                <p className="text-xs text-slate-500">Analisis kecocokan profil kandidat dengan kualifikasi formasi lowongan</p>
              </div>
              <button 
                onClick={() => setIsDetailModalOpen(false)}
                className="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-colors"
              >
                <X className="w-5 h-5" />
              </button>
            </div>

            <div className="p-6 overflow-y-auto bg-slate-50 flex-1 space-y-6">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                
                {/* Kolom Kiri: Profil Loker */}
                <div className="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                  <h3 className="font-semibold text-slate-800 border-b border-slate-100 pb-3 mb-4 flex items-center gap-2">
                    <Briefcase className="w-5 h-5 text-sky-500" /> Profil Lowongan
                  </h3>
                  <div className="space-y-4">
                    <div>
                      <p className="text-xs text-slate-500 mb-1 font-medium">Posisi / Jabatan</p>
                      <p className="font-semibold text-slate-800 text-base">
                        {selectedRecommendation.lowongan.judul_pekerjaan || selectedRecommendation.lowongan.judul_lowongan}
                      </p>
                    </div>
                    <div>
                      <p className="text-xs text-slate-500 mb-1 font-medium">Perusahaan</p>
                      <div className="flex items-center gap-2">
                        <Building2 className="w-4 h-4 text-slate-400 shrink-0" />
                        <p className="text-sm font-medium text-slate-700">{selectedRecommendation.lowongan.nama_perusahaan}</p>
                      </div>
                    </div>
                    <div>
                      <p className="text-xs text-slate-500 mb-1 font-medium">Lokasi</p>
                      <div className="flex items-center gap-2">
                        <MapPin className="w-4 h-4 text-slate-400 shrink-0" />
                        <p className="text-sm font-medium text-slate-700">
                          {selectedRecommendation.lowongan.regency?.name}, {selectedRecommendation.lowongan.province?.name}
                        </p>
                      </div>
                    </div>
                    <div>
                      <p className="text-xs text-slate-500 mb-1 font-medium">Kode KBJI</p>
                      <p className="text-sm font-medium text-slate-700">
                        {selectedRecommendation.lowongan.kbji?.code} - {selectedRecommendation.lowongan.kbji?.name}
                      </p>
                    </div>

                    <div>
                      <p className="text-xs text-slate-500 mb-1 font-medium">Syarat Pendidikan Lowongan</p>
                      <div className="flex items-center gap-2">
                        <Award className="w-4 h-4 text-slate-400 shrink-0" />
                        <p className="text-sm font-semibold text-slate-800">
                          {selectedRecommendation.lowongan.education_level?.name || selectedRecommendation.education_match?.required_level_name || 'Semua Jenjang'}
                        </p>
                      </div>
                    </div>

                    {/* Evaluasi Kesesuaian Pendidikan */}
                    {selectedRecommendation.education_match && (
                      <div className={`p-3 rounded-lg border text-xs ${
                        selectedRecommendation.education_match.is_matched 
                          ? 'bg-emerald-50/80 border-emerald-200 text-emerald-900' 
                          : 'bg-rose-50/80 border-rose-200 text-rose-900'
                      }`}>
                        <div className="flex items-start gap-2">
                          {selectedRecommendation.education_match.is_matched ? (
                            <CheckCircle2 className="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" />
                          ) : (
                            <AlertCircle className="w-4 h-4 text-rose-600 shrink-0 mt-0.5" />
                          )}
                          <div className="space-y-0.5">
                            <p className="font-bold">
                              {selectedRecommendation.education_match.is_matched ? 'Kualifikasi Pendidikan Terpenuhi' : 'Pendidikan Di Bawah Syarat'}
                            </p>
                            <p className="text-[11px] opacity-90">
                              Kandidat: <strong>{selectedSeeker?.pendidikan || selectedRecommendation.education_match.candidate_level_name}</strong> • Lowongan: <strong>{selectedRecommendation.education_match.required_level_name}</strong>
                            </p>
                            <p className="text-[11px] font-medium pt-0.5">
                              {selectedRecommendation.education_match.label}
                            </p>
                          </div>
                        </div>
                      </div>
                    )}
                    
                    <div className="bg-sky-50 border border-sky-100 rounded-lg p-4 mt-4 text-center">
                      <p className="text-xs text-sky-700 font-bold uppercase tracking-wider mb-1">Skor Kecocokan Skill</p>
                      <div className="text-3xl font-extrabold text-[#0E385E]">{selectedRecommendation.match_score}%</div>
                      <p className="text-xs text-slate-500 mt-1">
                        {selectedRecommendation.matched_skills_count} dari {selectedRecommendation.lowongan.skills?.length || 0} skill lowongan terpenuhi
                      </p>
                    </div>
                  </div>
                </div>

                {/* Kolom Kanan: Analisis Skill Gap */}
                <div className="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                  <h3 className="font-semibold text-slate-800 border-b border-slate-100 pb-3 mb-4 flex items-center gap-2">
                    <Target className="w-5 h-5 text-emerald-500" /> Analisis Gap Skill
                  </h3>
                  
                  <div className="space-y-4">
                    <p className="text-xs text-slate-600">
                      Perbandingan skill yang disyaratkan oleh lowongan dengan profil keahlian {selectedSeeker?.name || 'kandidat'}.
                    </p>

                    <div className="space-y-3 max-h-[340px] overflow-y-auto pr-2 custom-scrollbar">
                      {(!selectedRecommendation.lowongan.skills || selectedRecommendation.lowongan.skills.length === 0) ? (
                        <div className="text-center py-6 bg-slate-50 rounded-lg border border-dashed border-slate-200">
                          <p className="text-xs text-slate-500">Lowongan ini tidak mensyaratkan taksonomi skill spesifik di sistem.</p>
                        </div>
                      ) : (
                        selectedRecommendation.lowongan.skills.map((skill: any) => {
                          const matchedItem = pencakerSkills.find(ps => ps.id === skill.id || ps.esco_skill_id === skill.id);
                          const isMatch = !!matchedItem;
                          const rawSource = matchedItem?.source || matchedItem?.pivot?.source || (matchedItem?.pivot?.is_manual ? 'manual' : '');
                          
                          let sourceLabel = '';
                          let sourceIcon = null;
                          if (rawSource.includes('experience')) {
                            sourceLabel = 'Pengalaman';
                            sourceIcon = <Briefcase className="w-3 h-3 text-emerald-600 inline mr-1" />;
                          } else if (rawSource.includes('sertifikasi')) {
                            sourceLabel = 'Sertifikasi';
                            sourceIcon = <Award className="w-3 h-3 text-purple-600 inline mr-1" />;
                          } else if (rawSource.includes('keahlian')) {
                            sourceLabel = 'Keahlian';
                            sourceIcon = <Target className="w-3 h-3 text-sky-600 inline mr-1" />;
                          } else if (rawSource.includes('manual')) {
                            sourceLabel = 'Manual';
                            sourceIcon = <FileText className="w-3 h-3 text-amber-600 inline mr-1" />;
                          }
                          
                          return (
                            <div key={skill.id} className={`p-3 rounded-lg border ${isMatch ? 'bg-emerald-50/70 border-emerald-200' : 'bg-rose-50/60 border-rose-200'}`}>
                              <div className="flex gap-3">
                                <div className="mt-0.5">
                                  {isMatch ? (
                                    <CheckCircle2 className="w-5 h-5 text-emerald-600" />
                                  ) : (
                                    <AlertCircle className="w-5 h-5 text-rose-500" />
                                  )}
                                </div>
                                <div className="flex-1 min-w-0">
                                  <p className={`text-xs font-semibold ${isMatch ? 'text-emerald-900' : 'text-rose-900'}`}>
                                    {skill.title || skill.name}
                                  </p>
                                  <div className="text-[11px] mt-1 flex flex-wrap items-center gap-1.5">
                                    {isMatch ? (
                                      <>
                                        <span className="text-emerald-700 font-medium">Cocok dengan profil kandidat</span>
                                        {sourceLabel && (
                                          <span className="text-[10px] px-2 py-0.5 rounded-full bg-white text-emerald-800 border border-emerald-200 shadow-2xs font-normal inline-flex items-center">
                                            {sourceIcon}
                                            Sumber: {sourceLabel}
                                          </span>
                                        )}
                                      </>
                                    ) : (
                                      <span className="text-rose-600 font-medium">Belum ada di profil (Area Pelatihan/Gap)</span>
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

              {/* Deskripsi & Kualifikasi Formasi */}
              {(selectedRecommendation.lowongan.deskripsi_pekerjaan || selectedRecommendation.lowongan.kualifikasi) && (
                <div className="bg-white p-5 rounded-xl border border-slate-200 shadow-sm space-y-3">
                  <h4 className="text-xs font-bold text-slate-700 uppercase tracking-wider">
                    Deskripsi & Persyaratan Formasi Pekerjaan
                  </h4>

                  {selectedRecommendation.lowongan.deskripsi_pekerjaan && (
                    <div>
                      {selectedRecommendation.lowongan.kualifikasi && selectedRecommendation.lowongan.kualifikasi !== selectedRecommendation.lowongan.deskripsi_pekerjaan && (
                        <p className="text-xs font-semibold text-slate-800 mb-1.5">Deskripsi Pekerjaan:</p>
                      )}
                      {/<[a-z][\s\S]*>/i.test(selectedRecommendation.lowongan.deskripsi_pekerjaan) ? (
                        <div
                          className="p-4 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 leading-relaxed text-xs space-y-2 [&_ol]:list-decimal [&_ol]:pl-5 [&_ol]:space-y-1.5 [&_ul]:list-disc [&_ul]:pl-5 [&_ul]:space-y-1.5 [&_li]:pl-1 [&_p]:mb-2 [&_p:last-child]:mb-0 [&_strong]:font-semibold [&_b]:font-semibold [&_a]:text-blue-600 [&_a]:underline"
                          dangerouslySetInnerHTML={{ __html: selectedRecommendation.lowongan.deskripsi_pekerjaan }}
                        />
                      ) : (
                        <div className="p-4 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 whitespace-pre-line leading-relaxed text-xs">
                          {selectedRecommendation.lowongan.deskripsi_pekerjaan}
                        </div>
                      )}
                    </div>
                  )}

                  {selectedRecommendation.lowongan.kualifikasi && selectedRecommendation.lowongan.kualifikasi !== selectedRecommendation.lowongan.deskripsi_pekerjaan && (
                    <div>
                      {selectedRecommendation.lowongan.deskripsi_pekerjaan && (
                        <p className="text-xs font-semibold text-slate-800 mb-1.5 mt-3">Kualifikasi / Persyaratan:</p>
                      )}
                      {/<[a-z][\s\S]*>/i.test(selectedRecommendation.lowongan.kualifikasi) ? (
                        <div
                          className="p-4 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 leading-relaxed text-xs space-y-2 [&_ol]:list-decimal [&_ol]:pl-5 [&_ol]:space-y-1.5 [&_ul]:list-disc [&_ul]:pl-5 [&_ul]:space-y-1.5 [&_li]:pl-1 [&_p]:mb-2 [&_p:last-child]:mb-0 [&_strong]:font-semibold [&_b]:font-semibold [&_a]:text-blue-600 [&_a]:underline"
                          dangerouslySetInnerHTML={{ __html: selectedRecommendation.lowongan.kualifikasi }}
                        />
                      ) : (
                        <div className="p-4 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 whitespace-pre-line leading-relaxed text-xs">
                          {selectedRecommendation.lowongan.kualifikasi}
                        </div>
                      )}
                    </div>
                  )}
                </div>
              )}
            </div>

            {/* Footer Modal */}
            <div className="px-6 py-4 border-t border-slate-200 bg-slate-50 flex justify-between items-center">
              <button
                type="button"
                onClick={() => setIsDetailModalOpen(false)}
                className="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-900 rounded-lg hover:bg-slate-200/60 transition-colors"
              >
                Tutup
              </button>
              
              {recommendedJobs[String(selectedRecommendation.lowongan?.id)] ? (
                <div className="inline-flex items-center gap-2 px-4 py-2 bg-emerald-50 text-emerald-800 rounded-xl text-xs font-bold border border-emerald-200">
                  <CheckCheck className="w-4 h-4 text-emerald-600" />
                  <span>Telah Direkomendasikan</span>
                </div>
              ) : selectedRecommendation.match_score >= 70 ? (
                <button 
                  onClick={() => handleRecommendJob(selectedRecommendation.lowongan?.id)}
                  className="bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2.5 rounded-xl text-xs font-semibold transition-colors shadow-sm flex items-center gap-2"
                >
                  <CheckCircle2 className="w-4 h-4" />
                  <span>Rekomendasikan Kandidat ke Lowongan Ini</span>
                </button>
              ) : (
                <span className="text-[11px] text-amber-700 bg-amber-50 px-3 py-1.5 rounded-lg border border-amber-200 font-medium">
                  Skor di bawah 70% (Disarankan pelatihan kompetensi terlebih dahulu)
                </span>
              )}
            </div>

          </div>
        </div>
      )}

    </div>
  );
}
