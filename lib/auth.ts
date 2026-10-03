import { AuthUser } from '@/types/auth';

const TOKEN_KEY = 'proper_auth_token';
const USER_KEY = 'proper_auth_user';

export const getStoredToken = (): string | null => {
  if (typeof window === 'undefined') return null;
  return localStorage.getItem(TOKEN_KEY);
};

export const getStoredUser = (): AuthUser | null => {
  if (typeof window === 'undefined') return null;
  const raw = localStorage.getItem(USER_KEY);
  if (!raw) return null;
  try {
    return JSON.parse(raw) as AuthUser;
  } catch {
    return null;
  }
};

export const setStoredAuth = (token: string, user: AuthUser): void => {
  if (typeof window === 'undefined') return;
  localStorage.setItem(TOKEN_KEY, token);
  localStorage.setItem(USER_KEY, JSON.stringify(user));

  // Set cookie sederhana untuk middleware/SSR jika dibutuhkan
  document.cookie = `${TOKEN_KEY}=${encodeURIComponent(token)}; path=/; max-age=604800; SameSite=Lax`;
};

export const clearStoredAuth = (): void => {
  if (typeof window === 'undefined') return;
  localStorage.removeItem(TOKEN_KEY);
  localStorage.removeItem(USER_KEY);

  // Hapus cookie
  document.cookie = `${TOKEN_KEY}=; path=/; max-age=0; SameSite=Lax`;
};

export const hasUserPermission = (user: AuthUser | null, permission: string): boolean => {
  if (!user) return false;
  if (user.roles.includes('superadmin')) return true;
  return user.permissions.includes(permission);
};

export const hasUserRole = (user: AuthUser | null, role: string): boolean => {
  if (!user) return false;
  return user.roles.includes(role);
};
