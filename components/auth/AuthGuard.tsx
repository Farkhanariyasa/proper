'use client';

import React, { useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '@/hooks/useAuth';
import { ShieldAlert, Loader2 } from 'lucide-react';

interface AuthGuardProps {
  children: React.ReactNode;
  requiredPermission?: string;
  requiredRole?: string;
}

export default function AuthGuard({
  children,
  requiredPermission,
  requiredRole,
}: AuthGuardProps) {
  const { user, loading, isAuthenticated, hasPermission, hasRole } = useAuth();
  const router = useRouter();

  useEffect(() => {
    if (!loading && !isAuthenticated) {
      router.push('/login');
    }
  }, [loading, isAuthenticated, router]);

  if (loading) {
    return (
      <div className="min-h-[50vh] flex flex-col items-center justify-center gap-3 p-8 text-slate-500">
        <Loader2 className="w-8 h-8 animate-spin text-[#0E385E]" />
        <p className="text-sm font-medium">Memverifikasi sesi pengguna...</p>
      </div>
    );
  }

  if (!isAuthenticated) {
    return null;
  }

  if (requiredPermission && !hasPermission(requiredPermission)) {
    return (
      <div className="max-w-2xl mx-auto my-12 p-8 bg-white border border-red-200 rounded-xl shadow-xs text-center space-y-4">
        <div className="w-14 h-14 bg-red-50 text-red-600 rounded-full flex items-center justify-center mx-auto">
          <ShieldAlert className="w-7 h-7" />
        </div>
        <h2 className="text-lg font-bold text-slate-900">Akses Terbatas</h2>
        <p className="text-sm text-slate-600">
          Akun Anda ({user?.username}) tidak memiliki izin akses untuk fitur ini ({requiredPermission}). Silakan hubungi Superadmin jika Anda memerlukan wewenang ini.
        </p>
      </div>
    );
  }

  if (requiredRole && !hasRole(requiredRole)) {
    return (
      <div className="max-w-2xl mx-auto my-12 p-8 bg-white border border-red-200 rounded-xl shadow-xs text-center space-y-4">
        <div className="w-14 h-14 bg-red-50 text-red-600 rounded-full flex items-center justify-center mx-auto">
          <ShieldAlert className="w-7 h-7" />
        </div>
        <h2 className="text-lg font-bold text-slate-900">Peran Tidak Sesuai</h2>
        <p className="text-sm text-slate-600">
          Halaman ini khusus untuk peran {requiredRole}.
        </p>
      </div>
    );
  }

  return <>{children}</>;
}
