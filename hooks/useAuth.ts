'use client';

import { useState, useEffect, useCallback } from 'react';
import { useRouter } from 'next/navigation';
import { AuthUser } from '@/types/auth';
import {
  getStoredToken,
  getStoredUser,
  hasUserPermission,
  hasUserRole,
} from '@/lib/auth';
import { getMeApi, loginApi, logoutApi } from '@/services/auth';

export function useAuth() {
  const router = useRouter();
  const [user, setUser] = useState<AuthUser | null>(null);
  const [loading, setLoading] = useState(true);

  // Inisialisasi dari storage lokal lalu sinkronkan dengan server
  useEffect(() => {
    const token = getStoredToken();
    const storedUser = getStoredUser();

    if (!token) {
      setUser(null);
      setLoading(false);
      return;
    }

    if (storedUser) {
      setUser(storedUser);
      setLoading(false);
    }

    // Refresh data pengguna dari API
    getMeApi()
      .then((freshUser) => {
        setUser(freshUser);
      })
      .catch(() => {
        setUser(null);
      })
      .finally(() => {
        setLoading(false);
      });
  }, []);

  const login = useCallback(
    async (username: string, password: string): Promise<AuthUser> => {
      setLoading(true);
      try {
        const data = await loginApi(username, password);
        setUser(data.user);
        return data.user;
      } finally {
        setLoading(false);
      }
    },
    []
  );

  const logout = useCallback(async () => {
    setLoading(true);
    try {
      await logoutApi();
    } finally {
      setUser(null);
      setLoading(false);
      router.push('/login');
    }
  }, [router]);

  const hasPermission = useCallback(
    (permission: string): boolean => {
      return hasUserPermission(user, permission);
    },
    [user]
  );

  const hasRole = useCallback(
    (role: string): boolean => {
      return hasUserRole(user, role);
    },
    [user]
  );

  return {
    user,
    loading,
    isAuthenticated: Boolean(user),
    login,
    logout,
    hasPermission,
    hasRole,
  };
}
