import { AuthUser } from '@/types/auth';

const TOKEN_KEY = 'proper_auth_token';
const USER_KEY = 'proper_auth_user';
const ACTIVE_ROLE_KEY = 'proper_active_role';

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

export const getStoredActiveRole = (): string | null => {
  if (typeof window === 'undefined') return null;
  return localStorage.getItem(ACTIVE_ROLE_KEY);
};

export const setStoredActiveRole = (role: string): void => {
  if (typeof window === 'undefined') return;
  localStorage.setItem(ACTIVE_ROLE_KEY, role);
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
  localStorage.removeItem(ACTIVE_ROLE_KEY);

  // Hapus cookie
  document.cookie = `${TOKEN_KEY}=; path=/; max-age=0; SameSite=Lax`;
};

/**
 * Validasi dan tentukan peran aktif yang sah bagi akun pengguna
 */
export const resolveActiveRole = (
  user: AuthUser | null,
  preferredRole?: string | null
): string => {
  if (!user) return 'operator';

  // Akun superadmin berhak beralih ke superadmin, operator, atau pimpinan
  const isSuperadminAccount = user.roles.includes('superadmin');
  if (isSuperadminAccount) {
    if (preferredRole && ['superadmin', 'operator', 'pimpinan'].includes(preferredRole)) {
      return preferredRole;
    }
    return 'superadmin';
  }

  // Pengguna biasa hanya boleh memilih peran yang terdaftar di user.roles
  if (preferredRole && user.roles.includes(preferredRole)) {
    return preferredRole;
  }

  return user.roles[0] || 'operator';
};

/**
 * Cek apakah peran aktif user memiliki permission tertentu
 */
export const hasUserPermission = (
  user: AuthUser | null,
  permission: string,
  activeRole?: string | null
): boolean => {
  if (!user) return false;

  const currentRole = activeRole || resolveActiveRole(user);

  // Jika peran aktif adalah superadmin, beri akses penuh tanpa batas
  if (currentRole === 'superadmin') {
    return true;
  }

  // Jika tersedia pemetaan hak akses per peran dari backend
  if (user.role_permissions && user.role_permissions[currentRole]) {
    return user.role_permissions[currentRole].includes(permission);
  }

  // Fallback jika pemetaan per peran tidak ditemukan
  if (user.roles.includes(currentRole)) {
    return user.permissions.includes(permission);
  }

  return false;
};

/**
 * Cek apakah peran aktif user saat ini sesuai dengan target peran
 */
export const hasUserRole = (
  user: AuthUser | null,
  role: string,
  activeRole?: string | null
): boolean => {
  if (!user) return false;
  const currentRole = activeRole || resolveActiveRole(user);
  return currentRole === role;
};
