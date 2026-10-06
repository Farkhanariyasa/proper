'use client';

import React, { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '@/hooks/useAuth';
import {
  Shield,
  Lock,
  User,
  Eye,
  EyeOff,
  AlertCircle,
  Loader2,
  Sparkles,
} from 'lucide-react';

export default function LoginPage() {
  const router = useRouter();
  const { isAuthenticated, login, loading: authLoading } = useAuth();

  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);

  // Jika sudah terotentikasi, alihkan ke profil-skill / dashboard
  useEffect(() => {
    if (!authLoading && isAuthenticated) {
      router.push('/profil-skill');
    }
  }, [authLoading, isAuthenticated, router]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!username.trim() || !password.trim()) {
      setErrorMessage('Harap masukkan username dan kata sandi.');
      return;
    }

    setSubmitting(true);
    setErrorMessage(null);

    try {
      const user = await login(username.trim(), password);
      // Arahkan sesuai role utama
      if (user.roles.includes('superadmin') || user.roles.includes('operator')) {
        router.push('/profil-skill');
      } else if (user.roles.includes('pimpinan')) {
        router.push('/dashboard');
      } else {
        router.push('/profil-skill');
      }
    } catch (err: unknown) {
      if (err instanceof Error) {
        setErrorMessage(err.message);
      } else {
        setErrorMessage('Terjadi kesalahan saat masuk. Silakan coba kembali.');
      }
    } finally {
      setSubmitting(false);
    }
  };

  const fillQuickAccount = (userVal: string, passVal: string) => {
    setUsername(userVal);
    setPassword(passVal);
    setErrorMessage(null);
  };

  return (
    <div className="min-h-screen bg-slate-100 flex flex-col justify-center py-12 px-4 sm:px-6 lg:px-8">
      <div className="sm:mx-auto sm:w-full sm:max-w-md">
        {/* Logo & Header */}
        <div className="text-center">
          <div className="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-[#0E385E] text-amber-300 shadow-md mb-4 border border-[#1F5A88]">
            <Shield className="h-8 w-8" />
          </div>
          <h2 className="text-2xl font-extrabold tracking-tight text-slate-900">
            e-Pengantar Kerja
          </h2>
          <p className="mt-1 text-xs text-slate-600">
            Platform Taksonomi Keahlian & Penempatan Tenaga Kerja
          </p>
        </div>

        {/* Card Form */}
        <div className="mt-8 bg-white py-8 px-6 shadow-sm border border-slate-200/80 rounded-2xl sm:px-10">
          <form className="space-y-5" onSubmit={handleSubmit}>
            {errorMessage && (
              <div
                role="alert"
                className="p-3 rounded-lg bg-red-50 border border-red-200 flex items-start gap-2.5 text-xs text-red-800"
              >
                <AlertCircle className="w-4 h-4 text-red-600 shrink-0 mt-0.5" />
                <span className="leading-relaxed">{errorMessage}</span>
              </div>
            )}

            {/* Username Field */}
            <div>
              <label
                htmlFor="username"
                className="block text-xs font-semibold uppercase tracking-wider text-slate-700"
              >
                Nama Pengguna / Email
              </label>
              <div className="mt-1.5 relative rounded-lg shadow-2xs">
                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                  <User className="h-4 w-4" />
                </div>
                <input
                  id="username"
                  name="username"
                  type="text"
                  autoComplete="username"
                  required
                  value={username}
                  onChange={(e) => setUsername(e.target.value)}
                  placeholder="Contoh: superadmin atau operator1"
                  className="block w-full pl-9 pr-3 py-2 text-sm border border-slate-300 rounded-lg text-slate-900 placeholder:text-slate-400 focus:outline-hidden focus:ring-2 focus:ring-[#0E385E] focus:border-[#0E385E]"
                />
              </div>
            </div>

            {/* Password Field */}
            <div>
              <label
                htmlFor="password"
                className="block text-xs font-semibold uppercase tracking-wider text-slate-700"
              >
                Kata Sandi
              </label>
              <div className="mt-1.5 relative rounded-lg shadow-2xs">
                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                  <Lock className="h-4 w-4" />
                </div>
                <input
                  id="password"
                  name="password"
                  type={showPassword ? 'text' : 'password'}
                  autoComplete="current-password"
                  required
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  placeholder="Masukkan kata sandi"
                  className="block w-full pl-9 pr-10 py-2 text-sm border border-slate-300 rounded-lg text-slate-900 placeholder:text-slate-400 focus:outline-hidden focus:ring-2 focus:ring-[#0E385E] focus:border-[#0E385E]"
                />
                <button
                  type="button"
                  onClick={() => setShowPassword(!showPassword)}
                  className="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 cursor-pointer"
                  tabIndex={-1}
                  aria-label={showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'}
                >
                  {showPassword ? (
                    <EyeOff className="h-4 w-4" />
                  ) : (
                    <Eye className="h-4 w-4" />
                  )}
                </button>
              </div>
            </div>

            {/* Tombol Submit */}
            <div className="pt-2">
              <button
                type="submit"
                disabled={submitting}
                className="w-full flex justify-center items-center gap-2 py-2.5 px-4 border border-transparent rounded-lg shadow-xs text-sm font-semibold text-white bg-[#0E385E] hover:bg-[#163A5F] focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-[#0E385E] disabled:opacity-60 transition-colors cursor-pointer"
              >
                {submitting ? (
                  <>
                    <Loader2 className="w-4 h-4 animate-spin" />
                    <span>Sedang memverifikasi...</span>
                  </>
                ) : (
                  <span>Masuk ke Sistem</span>
                )}
              </button>
            </div>
          </form>

          {/* Quick Demo Accounts Helper */}
          <div className="mt-8 pt-6 border-t border-slate-200">
            <div className="flex items-center gap-1.5 text-xs font-semibold text-slate-700 mb-3">
              <span>Pilih Akun :</span>
            </div>
            <div className="grid grid-cols-3 gap-2">
              <button
                type="button"
                onClick={() => fillQuickAccount('superadmin', 'superadmin123')}
                className="p-2 text-left rounded-lg bg-blue-50/70 hover:bg-blue-100/70 border border-blue-200 transition-colors cursor-pointer"
              >
                <div className="text-[11px] font-bold text-blue-900 truncate">Superadmin</div>
                <div className="text-[10px] text-blue-700 truncate">superadmin</div>
              </button>

              <button
                type="button"
                onClick={() => fillQuickAccount('operator1', 'operator123')}
                className="p-2 text-left rounded-lg bg-emerald-50/70 hover:bg-emerald-100/70 border border-emerald-200 transition-colors cursor-pointer"
              >
                <div className="text-[11px] font-bold text-emerald-900 truncate">Operator</div>
                <div className="text-[10px] text-emerald-700 truncate">operator1</div>
              </button>

              <button
                type="button"
                onClick={() => fillQuickAccount('pimpinan1', 'pimpinan123')}
                className="p-2 text-left rounded-lg bg-purple-50/70 hover:bg-purple-100/70 border border-purple-200 transition-colors cursor-pointer"
              >
                <div className="text-[11px] font-bold text-purple-900 truncate">Pimpinan</div>
                <div className="text-[10px] text-purple-700 truncate">pimpinan1</div>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
