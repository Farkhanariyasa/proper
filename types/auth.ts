export interface Permission {
  id: number;
  name: string;
  display_name: string;
  group: string;
  created_at?: string;
  updated_at?: string;
}

export interface Role {
  id: number;
  name: string;
  display_name: string;
  description: string | null;
  users_count?: number;
  permissions?: Permission[];
  created_at?: string;
  updated_at?: string;
}

export interface AuthUser {
  id: number;
  name: string;
  username: string;
  email: string | null;
  is_active: boolean;
  roles: string[];
  role_names: string[];
  permissions: string[];
  last_login_at?: string | null;
}

export interface LoginResponse {
  status: string;
  message?: string;
  data: {
    token: string;
    user: AuthUser;
  };
}

export interface UserItem {
  id: number;
  name: string;
  username: string;
  email: string | null;
  is_active: boolean;
  last_login_at: string | null;
  created_by: number | null;
  created_at: string;
  updated_at: string;
  roles: {
    id: number;
    name: string;
    display_name: string;
  }[];
}

export interface PaginatedUsersResponse {
  status: string;
  data: {
    current_page: number;
    data: UserItem[];
    first_page_url: string;
    from: number | null;
    last_page: number;
    last_page_url: string;
    next_page_url: string | null;
    path: string;
    per_page: number;
    prev_page_url: string | null;
    to: number | null;
    total: number;
  };
}

export interface PermissionsGroupedResponse {
  status: string;
  data: {
    list: Permission[];
    grouped: Record<string, Permission[]>;
  };
}
