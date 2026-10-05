import { AuthUser, LoginResponse } from '@/types/auth';
import { clearStoredAuth, getStoredToken, setStoredAuth } from '@/lib/auth';

const getApiBaseUrl = (): string => {
  if (typeof window !== 'undefined') {
    return process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000';
  }
  return process.env.API_URL || process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000';
};

export const getAuthHeaders = (): HeadersInit => {
  const headers: Record<string, string> = {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  };

  const token = getStoredToken();
  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  }

  return headers;
};

/**
 * Login dengan username/email dan password
 */
export async function loginApi(username: string, password: string): Promise<LoginResponse['data']> {
  const baseUrl = getApiBaseUrl();
  const res = await fetch(`${baseUrl}/api/auth/login`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
    },
    body: JSON.stringify({ username, password }),
  });

  const json = await res.json();

  if (!res.ok) {
    const errorMsg =
      json.errors?.username?.[0] ||
      json.errors?.password?.[0] ||
      json.message ||
      'Gagal melakukan login. Silakan cek kredensial Anda.';
    throw new Error(errorMsg);
  }

  // Simpan data login ke storage
  setStoredAuth(json.data.token, json.data.user);

  return json.data;
}

/**
 * Logout dari sistem
 */
export async function logoutApi(): Promise<void> {
  const baseUrl = getApiBaseUrl();
  try {
    await fetch(`${baseUrl}/api/auth/logout`, {
      method: 'POST',
      headers: getAuthHeaders(),
    });
  } catch {
    // Abaikan jika network error saat logout, tetap bersihkan storage lokal
  } finally {
    clearStoredAuth();
  }
}

/**
 * Verifikasi profil user aktif dari server
 */
export async function getMeApi(): Promise<AuthUser> {
  const baseUrl = getApiBaseUrl();
  const res = await fetch(`${baseUrl}/api/auth/me`, {
    method: 'GET',
    headers: getAuthHeaders(),
  });

  if (!res.ok) {
    clearStoredAuth();
    throw new Error('Sesi autentikasi telah berakhir.');
  }

  const json = await res.json();
  return json.data;
}
