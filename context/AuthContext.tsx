'use client';

import React, {
  createContext,
  useContext,
  useState,
  useEffect,
  useCallback,
  useMemo,
} from 'react';
import { useRouter } from 'next/navigation';
import { AuthUser } from '@/types/auth';
import {
  getStoredToken,
  getStoredUser,
  getStoredActiveRole,
  setStoredActiveRole,
  clearStoredAuth,
  resolveActiveRole,
  hasUserPermission,
  hasUserRole,
} from '@/lib/auth';
import { getMeApi, loginApi, logoutApi } from '@/services/auth';

interface AuthContextType {
  user: AuthUser | null;
  loading: boolean;
  isAuthenticated: boolean;
  activeRole: string;
  setActiveRole: (role: string) => void;
  login: (username: string, password: string) => Promise<AuthUser>;
  logout: () => Promise<void>;
  hasPermission: (permission: string) => boolean;
  hasRole: (role: string) => boolean;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const router = useRouter();
  const [user, setUser] = useState<AuthUser | null>(null);
  const [loading, setLoading] = useState(true);
  const [activeRole, setActiveRoleState] = useState<string>('operator');

  // Sinkronisasi inisial dari storage lokal
  useEffect(() => {
    const token = getStoredToken();
    const storedUser = getStoredUser();
    const storedRole = getStoredActiveRole();

    if (!token) {
      setUser(null);
      setLoading(false);
      return;
    }

    if (storedUser) {
      setUser(storedUser);
      setActiveRoleState(resolveActiveRole(storedUser, storedRole));
      setLoading(false);
    }

    // Refresh data user terbaru dari API backend
    getMeApi()
      .then((freshUser) => {
        setUser(freshUser);
        setActiveRoleState(resolveActiveRole(freshUser, storedRole));
      })
      .catch(() => {
        setUser(null);
      })
      .finally(() => {
        setLoading(false);
      });
  }, []);

  const setActiveRole = useCallback(
    (newRole: string) => {
      const validRole = resolveActiveRole(user, newRole);
      setStoredActiveRole(validRole);
      setActiveRoleState(validRole);
    },
    [user]
  );

  const login = useCallback(
    async (username: string, password: string): Promise<AuthUser> => {
      setLoading(true);
      try {
        const data = await loginApi(username, password);
        const resolvedRole = resolveActiveRole(data.user, null);
        setUser(data.user);
        setActiveRoleState(resolvedRole);
        setStoredActiveRole(resolvedRole);
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
      clearStoredAuth();
      setUser(null);
      setActiveRoleState('operator');
      setLoading(false);
      router.push('/login');
    }
  }, [router]);

  const hasPermission = useCallback(
    (permission: string): boolean => {
      return hasUserPermission(user, permission, activeRole);
    },
    [user, activeRole]
  );

  const hasRole = useCallback(
    (role: string): boolean => {
      return hasUserRole(user, role, activeRole);
    },
    [user, activeRole]
  );

  const contextValue = useMemo<AuthContextType>(
    () => ({
      user,
      loading,
      isAuthenticated: Boolean(user),
      activeRole,
      setActiveRole,
      login,
      logout,
      hasPermission,
      hasRole,
    }),
    [user, loading, activeRole, setActiveRole, login, logout, hasPermission, hasRole]
  );

  return (
    <AuthContext.Provider value={contextValue}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
}
