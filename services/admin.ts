import {
  PaginatedUsersResponse,
  Permission,
  PermissionsGroupedResponse,
  Role,
  UserItem,
} from '@/types/auth';
import { getAuthHeaders } from './auth';

const getApiBaseUrl = (): string => {
  if (typeof window !== 'undefined') {
    return process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000';
  }
  return process.env.API_URL || process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000';
};

/* ========================================================
   USER MANAGEMENT
======================================================== */

export interface GetUsersParams {
  search?: string;
  role?: string;
  is_active?: boolean;
  page?: number;
  per_page?: number;
}

export async function getUsersApi(params: GetUsersParams = {}): Promise<PaginatedUsersResponse['data']> {
  const baseUrl = getApiBaseUrl();
  const query = new URLSearchParams();

  if (params.search) query.append('search', params.search);
  if (params.role) query.append('role', params.role);
  if (params.is_active !== undefined) query.append('is_active', String(params.is_active));
  if (params.page) query.append('page', String(params.page));
  if (params.per_page) query.append('per_page', String(params.per_page));

  const res = await fetch(`${baseUrl}/api/admin/users?${query.toString()}`, {
    headers: getAuthHeaders(),
    cache: 'no-store',
  });

  if (!res.ok) {
    const json = await res.json().catch(() => ({}));
    throw new Error(json.message || `Gagal memuat daftar pengguna (${res.status})`);
  }

  const json = await res.json();
  return json.data;
}

export interface CreateUserPayload {
  name: string;
  username: string;
  email?: string;
  password: string;
  is_active?: boolean;
  role_ids: number[];
}

export async function createUserApi(payload: CreateUserPayload): Promise<UserItem> {
  const baseUrl = getApiBaseUrl();
  const res = await fetch(`${baseUrl}/api/admin/users`, {
    method: 'POST',
    headers: getAuthHeaders(),
    body: JSON.stringify(payload),
  });

  const json = await res.json();
  if (!res.ok) {
    const errorMsg =
      json.errors?.username?.[0] ||
      json.errors?.email?.[0] ||
      json.errors?.password?.[0] ||
      json.message ||
      'Gagal menambahkan pengguna.';
    throw new Error(errorMsg);
  }

  return json.data;
}

export interface UpdateUserPayload {
  name?: string;
  username?: string;
  email?: string | null;
  password?: string;
  is_active?: boolean;
  role_ids?: number[];
}

export async function updateUserApi(id: number, payload: UpdateUserPayload): Promise<UserItem> {
  const baseUrl = getApiBaseUrl();
  const res = await fetch(`${baseUrl}/api/admin/users/${id}`, {
    method: 'PUT',
    headers: getAuthHeaders(),
    body: JSON.stringify(payload),
  });

  const json = await res.json();
  if (!res.ok) {
    const errorMsg =
      json.errors?.username?.[0] ||
      json.errors?.email?.[0] ||
      json.errors?.password?.[0] ||
      json.message ||
      'Gagal memperbarui pengguna.';
    throw new Error(errorMsg);
  }

  return json.data;
}

export async function deleteUserApi(id: number): Promise<void> {
  const baseUrl = getApiBaseUrl();
  const res = await fetch(`${baseUrl}/api/admin/users/${id}`, {
    method: 'DELETE',
    headers: getAuthHeaders(),
  });

  if (!res.ok) {
    const json = await res.json().catch(() => ({}));
    throw new Error(json.message || 'Gagal menghapus pengguna.');
  }
}

export async function toggleUserActiveApi(id: number): Promise<{ id: number; is_active: boolean }> {
  const baseUrl = getApiBaseUrl();
  const res = await fetch(`${baseUrl}/api/admin/users/${id}/toggle-active`, {
    method: 'POST',
    headers: getAuthHeaders(),
  });

  const json = await res.json();
  if (!res.ok) {
    throw new Error(json.message || 'Gagal mengubah status aktif pengguna.');
  }

  return json.data;
}

/* ========================================================
   ROLE & PERMISSION MANAGEMENT
======================================================== */

export async function getRolesApi(): Promise<Role[]> {
  const baseUrl = getApiBaseUrl();
  const res = await fetch(`${baseUrl}/api/admin/roles`, {
    headers: getAuthHeaders(),
    cache: 'no-store',
  });

  if (!res.ok) {
    const json = await res.json().catch(() => ({}));
    throw new Error(json.message || `Gagal memuat role (${res.status})`);
  }

  const json = await res.json();
  return json.data;
}

export async function getRoleDetailApi(id: number): Promise<Role> {
  const baseUrl = getApiBaseUrl();
  const res = await fetch(`${baseUrl}/api/admin/roles/${id}`, {
    headers: getAuthHeaders(),
    cache: 'no-store',
  });

  if (!res.ok) {
    const json = await res.json().catch(() => ({}));
    throw new Error(json.message || `Gagal memuat detail role (${res.status})`);
  }

  const json = await res.json();
  return json.data;
}

export interface CreateRolePayload {
  name: string;
  display_name: string;
  description?: string;
  permission_ids?: number[];
}

export async function createRoleApi(payload: CreateRolePayload): Promise<Role> {
  const baseUrl = getApiBaseUrl();
  const res = await fetch(`${baseUrl}/api/admin/roles`, {
    method: 'POST',
    headers: getAuthHeaders(),
    body: JSON.stringify(payload),
  });

  const json = await res.json();
  if (!res.ok) {
    throw new Error(json.message || 'Gagal membuat role.');
  }

  return json.data;
}

export async function updateRoleApi(
  id: number,
  payload: { display_name?: string; description?: string; permission_ids?: number[] }
): Promise<Role> {
  const baseUrl = getApiBaseUrl();
  const res = await fetch(`${baseUrl}/api/admin/roles/${id}`, {
    method: 'PUT',
    headers: getAuthHeaders(),
    body: JSON.stringify(payload),
  });

  const json = await res.json();
  if (!res.ok) {
    throw new Error(json.message || 'Gagal memperbarui role.');
  }

  return json.data;
}

export async function deleteRoleApi(id: number): Promise<void> {
  const baseUrl = getApiBaseUrl();
  const res = await fetch(`${baseUrl}/api/admin/roles/${id}`, {
    method: 'DELETE',
    headers: getAuthHeaders(),
  });

  if (!res.ok) {
    const json = await res.json().catch(() => ({}));
    throw new Error(json.message || 'Gagal menghapus role.');
  }
}

export async function syncRolePermissionsApi(roleId: number, permissionIds: number[]): Promise<Role> {
  const baseUrl = getApiBaseUrl();
  const res = await fetch(`${baseUrl}/api/admin/roles/${roleId}/permissions`, {
    method: 'PUT',
    headers: getAuthHeaders(),
    body: JSON.stringify({ permission_ids: permissionIds }),
  });

  const json = await res.json();
  if (!res.ok) {
    throw new Error(json.message || 'Gagal menyinkronkan permission role.');
  }

  return json.data;
}

export async function getPermissionsApi(): Promise<PermissionsGroupedResponse['data']> {
  const baseUrl = getApiBaseUrl();
  const res = await fetch(`${baseUrl}/api/admin/permissions`, {
    headers: getAuthHeaders(),
    cache: 'no-store',
  });

  if (!res.ok) {
    const json = await res.json().catch(() => ({}));
    throw new Error(json.message || `Gagal memuat daftar permission (${res.status})`);
  }

  const json = await res.json();
  return json.data;
}

/* ========================================================
   KBJI ALIAS CURATION
======================================================== */

export interface GetKbjiAliasesParams {
  status?: string;
  method?: string;
  search?: string;
  page?: number;
}

export async function getKbjiAliasesApi(params: GetKbjiAliasesParams = {}) {
  const baseUrl = getApiBaseUrl();
  const query = new URLSearchParams();

  if (params.status) query.append('status', params.status);
  if (params.method) query.append('method', params.method);
  if (params.search) query.append('search', params.search);
  if (params.page) query.append('page', String(params.page));

  const res = await fetch(`${baseUrl}/api/admin/kbji-aliases?${query.toString()}`, {
    headers: getAuthHeaders(),
    cache: 'no-store',
  });

  if (!res.ok) {
    throw new Error(`Gagal memuat alias KBJI (${res.status})`);
  }

  return res.json();
}

export async function updateKbjiAliasApi(id: number, kbjiId: number) {
  const baseUrl = getApiBaseUrl();
  const res = await fetch(`${baseUrl}/api/admin/kbji-aliases/${id}`, {
    method: 'PUT',
    headers: getAuthHeaders(),
    body: JSON.stringify({ kbji_id: kbjiId }),
  });

  const json = await res.json();
  if (!res.ok) {
    throw new Error(json.message || 'Gagal memverifikasi alias KBJI.');
  }

  return json;
}
