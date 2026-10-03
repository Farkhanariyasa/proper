'use client';

import React, { useState, useEffect, useCallback, useMemo } from 'react';
import AuthGuard from '@/components/auth/AuthGuard';
import { useAuth } from '@/hooks/useAuth';
import {
  createUserApi,
  deleteUserApi,
  getRolesApi,
  getUsersApi,
  toggleUserActiveApi,
  updateUserApi,
} from '@/services/admin';
import { Role, UserItem } from '@/types/auth';
import {
  UserPlus,
  Search,
  Filter,
  MoreVertical,
  CheckCircle2,
  XCircle,
  Edit2,
  Trash2,
  Shield,
  Loader2,
  AlertCircle,
  Users,
  X,
  Lock,
  ChevronLeft,
  ChevronRight,
} from 'lucide-react';

export default function UsersManagementPage() {
  const { user: currentUser, hasPermission } = useAuth();

  // State Data
  const [users, setUsers] = useState<UserItem[]>([]);
  const [roles, setRoles] = useState<Role[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  // State Filter
  const [search, setSearch] = useState('');
  const [selectedRole, setSelectedRole] = useState('');
  const [selectedStatus, setSelectedStatus] = useState('');

  // Pagination State
  const [currentPage, setCurrentPage] = useState(1);
  const [itemsPerPage, setItemsPerPage] = useState(10);

  // State Modal Form (Create / Edit)
  const [modalOpen, setModalOpen] = useState(false);
  const [modalMode, setModalMode] = useState<'create' | 'edit'>('create');
  const [editingUserId, setEditingUserId] = useState<number | null>(null);
  const [formSubmitting, setFormSubmitting] = useState(false);
  const [formError, setFormError] = useState<string | null>(null);

  const [formName, setFormName] = useState('');
  const [formUsername, setFormUsername] = useState('');
  const [formEmail, setFormEmail] = useState('');
  const [formPassword, setFormPassword] = useState('');
  const [formIsActive, setFormIsActive] = useState(true);
  const [formRoleIds, setFormRoleIds] = useState<number[]>([]);

  // State Modal Konfirmasi Delete
  const [deleteModalOpen, setDeleteModalOpen] = useState(false);
  const [deletingUser, setDeletingUser] = useState<UserItem | null>(null);
  const [deleteSubmitting, setDeleteSubmitting] = useState(false);

  // Fetch roles list untuk dropdown/checkbox
  const fetchRoles = useCallback(async () => {
    try {
      const data = await getRolesApi();
      setRoles(data);
    } catch {
      // ignore
    }
  }, []);

  // Fetch users data
  const fetchUsers = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const params: { search?: string; role?: string; is_active?: boolean } = {};
      if (search.trim()) params.search = search.trim();
      if (selectedRole) params.role = selectedRole;
      if (selectedStatus !== '') params.is_active = selectedStatus === 'true';

      const data = await getUsersApi(params);
      setUsers(data.data || []);
    } catch (err: unknown) {
      if (err instanceof Error) {
        setError(err.message);
      } else {
        setError('Gagal memuat daftar pengguna.');
      }
    } finally {
      setLoading(false);
    }
  }, [search, selectedRole, selectedStatus]);

  useEffect(() => {
    setCurrentPage(1);
  }, [search, selectedRole, selectedStatus]);

  const totalItems = users.length;
  const totalPages = Math.max(1, Math.ceil(totalItems / itemsPerPage));
  const startIndex = (currentPage - 1) * itemsPerPage;
  const paginatedUsers = useMemo<UserItem[]>(() => {
    return users.slice(startIndex, startIndex + itemsPerPage);
  }, [users, startIndex, itemsPerPage]);

  useEffect(() => {
    fetchRoles();
  }, [fetchRoles]);

  useEffect(() => {
    fetchUsers();
  }, [fetchUsers]);

  // Buka Modal Tambah User
  const handleOpenCreateModal = () => {
    setModalMode('create');
    setEditingUserId(null);
    setFormName('');
    setFormUsername('');
    setFormEmail('');
    setFormPassword('');
    setFormIsActive(true);
    // Default centang role operator jika ada
    const defaultRole = roles.find((r) => r.name === 'operator') || roles[0];
    setFormRoleIds(defaultRole ? [defaultRole.id] : []);
    setFormError(null);
    setModalOpen(true);
  };

  // Buka Modal Edit User
  const handleOpenEditModal = (targetUser: UserItem) => {
    setModalMode('edit');
    setEditingUserId(targetUser.id);
    setFormName(targetUser.name);
    setFormUsername(targetUser.username);
    setFormEmail(targetUser.email || '');
    setFormPassword(''); // Password kosong jika tidak diubah
    setFormIsActive(targetUser.is_active);
    setFormRoleIds(targetUser.roles.map((r) => r.id));
    setFormError(null);
    setModalOpen(true);
  };

  // Toggle role checkbox di modal
  const handleToggleRoleId = (id: number) => {
    setFormRoleIds((prev) =>
      prev.includes(id) ? prev.filter((rId) => rId !== id) : [...prev, id]
    );
  };

  // Submit Form Tambah / Edit
  const handleFormSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!formName.trim() || !formUsername.trim()) {
      setFormError('Nama lengkap dan nama pengguna wajib diisi.');
      return;
    }

    if (modalMode === 'create' && !formPassword.trim()) {
      setFormError('Kata sandi wajib diisi untuk pengguna baru.');
      return;
    }

    if (formRoleIds.length === 0) {
      setFormError('Pilih minimal satu peran (role) untuk pengguna ini.');
      return;
    }

    setFormSubmitting(true);
    setFormError(null);

    try {
      if (modalMode === 'create') {
        await createUserApi({
          name: formName.trim(),
          username: formUsername.trim(),
          email: formEmail.trim() || undefined,
          password: formPassword,
          is_active: formIsActive,
          role_ids: formRoleIds,
        });
      } else if (editingUserId) {
        await updateUserApi(editingUserId, {
          name: formName.trim(),
          username: formUsername.trim(),
          email: formEmail.trim() || null,
          password: formPassword.trim() ? formPassword : undefined,
          is_active: formIsActive,
          role_ids: formRoleIds,
        });
      }

      setModalOpen(false);
      fetchUsers();
    } catch (err: unknown) {
      if (err instanceof Error) {
        setFormError(err.message);
      } else {
        setFormError('Terjadi kesalahan saat menyimpan data pengguna.');
      }
    } finally {
      setFormSubmitting(false);
    }
  };

  // Toggle status aktif/nonaktif langsung dari tabel
  const handleToggleActive = async (targetUser: UserItem) => {
    if (targetUser.id === currentUser?.id) {
      alert('Anda tidak dapat menonaktifkan akun Anda sendiri.');
      return;
    }

    try {
      await toggleUserActiveApi(targetUser.id);
      fetchUsers();
    } catch (err: unknown) {
      if (err instanceof Error) {
        alert(err.message);
      }
    }
  };

  // Buka konfirmasi delete
  const handleOpenDelete = (targetUser: UserItem) => {
    if (targetUser.id === currentUser?.id) {
      alert('Anda tidak dapat menghapus akun Anda sendiri.');
      return;
    }
    setDeletingUser(targetUser);
    setDeleteModalOpen(true);
  };

  // Eksekusi delete user
  const handleConfirmDelete = async () => {
    if (!deletingUser) return;
    setDeleteSubmitting(true);
    try {
      await deleteUserApi(deletingUser.id);
      setDeleteModalOpen(false);
      setDeletingUser(null);
      fetchUsers();
    } catch (err: unknown) {
      if (err instanceof Error) {
        alert(err.message);
      }
    } finally {
      setDeleteSubmitting(false);
    }
  };

  const getRoleBadgeClass = (roleName: string) => {
    if (roleName === 'superadmin') {
      return 'bg-blue-100 text-blue-800 border-blue-200';
    }
    if (roleName === 'operator') {
      return 'bg-emerald-100 text-emerald-800 border-emerald-200';
    }
    if (roleName === 'pimpinan') {
      return 'bg-purple-100 text-purple-800 border-purple-200';
    }
    return 'bg-slate-100 text-slate-700 border-slate-200';
  };

  return (
    <AuthGuard requiredPermission="users.view">
      <div className="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto space-y-6">
        {/* Header Title & Action Button */}
        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <div className="flex items-center gap-2">
              <h1 className="text-xl sm:text-2xl font-bold text-slate-900">
                Manajemen Pengguna
              </h1>
              <span className="px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                {users.length} Akun
              </span>
            </div>
            <p className="text-xs sm:text-sm text-slate-600 mt-1">
              Kelola daftar akun pengguna, peran akses, dan status operasional sistem.
            </p>
          </div>

          {hasPermission('users.create') && (
            <button
              type="button"
              onClick={handleOpenCreateModal}
              className="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-[#0E385E] hover:bg-[#163A5F] text-white text-xs sm:text-sm font-semibold shadow-xs transition-colors cursor-pointer"
            >
              <UserPlus className="w-4 h-4" />
              <span>Tambah Pengguna</span>
            </button>
          )}
        </div>

        {/* Filter Bar */}
        <div className="bg-white p-4 rounded-xl border border-slate-200/80 shadow-2xs grid grid-cols-1 sm:grid-cols-3 gap-3">
          {/* Input Search */}
          <div className="relative">
            <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
              <Search className="h-4 w-4" />
            </div>
            <input
              type="text"
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              placeholder="Cari nama, username, atau email..."
              className="block w-full pl-9 pr-3 py-2 text-xs sm:text-sm border border-slate-300 rounded-lg text-slate-900 placeholder:text-slate-400 focus:outline-hidden focus:ring-2 focus:ring-[#0E385E]"
            />
          </div>

          {/* Filter Role */}
          <div className="relative">
            <select
              value={selectedRole}
              onChange={(e) => setSelectedRole(e.target.value)}
              className="block w-full px-3 py-2 text-xs sm:text-sm border border-slate-300 rounded-lg text-slate-900 bg-white focus:outline-hidden focus:ring-2 focus:ring-[#0E385E]"
            >
              <option value="">Semua Peran (Role)</option>
              {roles.map((r) => (
                <option key={r.id} value={r.name}>
                  {r.display_name}
                </option>
              ))}
            </select>
          </div>

          {/* Filter Status */}
          <div className="relative">
            <select
              value={selectedStatus}
              onChange={(e) => setSelectedStatus(e.target.value)}
              className="block w-full px-3 py-2 text-xs sm:text-sm border border-slate-300 rounded-lg text-slate-900 bg-white focus:outline-hidden focus:ring-2 focus:ring-[#0E385E]"
            >
              <option value="">Semua Status Akun</option>
              <option value="true">Aktif</option>
              <option value="false">Nonaktif</option>
            </select>
          </div>
        </div>

        {/* Tabel Data Pengguna */}
        <div className="bg-white rounded-xl border border-slate-200/80 shadow-2xs overflow-hidden">
          {loading ? (
            <div className="p-12 flex flex-col items-center justify-center gap-3 text-slate-500">
              <Loader2 className="w-7 h-7 animate-spin text-[#0E385E]" />
              <p className="text-xs sm:text-sm font-medium">Memuat data pengguna...</p>
            </div>
          ) : error ? (
            <div className="p-8 text-center space-y-2">
              <div className="inline-flex p-3 rounded-full bg-red-50 text-red-600 mb-2">
                <AlertCircle className="w-6 h-6" />
              </div>
              <h3 className="text-sm font-bold text-slate-900">Gagal Memuat Data</h3>
              <p className="text-xs text-slate-600">{error}</p>
              <button
                type="button"
                onClick={fetchUsers}
                className="mt-3 px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-xs font-semibold text-slate-800 transition-colors cursor-pointer"
              >
                Coba Lagi
              </button>
            </div>
          ) : users.length === 0 ? (
            <div className="p-12 text-center space-y-3">
              <div className="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto">
                <Users className="w-6 h-6" />
              </div>
              <h3 className="text-sm font-bold text-slate-900">Tidak ada pengguna ditemukan</h3>
              <p className="text-xs text-slate-500 max-w-sm mx-auto">
                Tidak ada data yang cocok dengan kriteria pencarian atau filter yang Anda tentukan.
              </p>
            </div>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-left border-collapse text-xs sm:text-sm">
                <thead>
                  <tr className="bg-slate-50/80 border-b border-slate-200 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                    <th className="py-3 px-4">Pengguna</th>
                    <th className="py-3 px-4">Kontak Email</th>
                    <th className="py-3 px-4">Peran (Role)</th>
                    <th className="py-3 px-4 text-center">Status</th>
                    <th className="py-3 px-4">Login Terakhir</th>
                    <th className="py-3 px-4 text-right">Aksi</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100 text-slate-800">
                  {paginatedUsers.map((u) => {
                    const isSelf = u.id === currentUser?.id;

                    return (
                      <tr key={u.id} className="hover:bg-slate-50/60 transition-colors">
                        {/* Nama & Username */}
                        <td className="py-3.5 px-4">
                          <div className="flex items-center gap-3">
                            <div className="w-9 h-9 rounded-full bg-[#0E385E] text-white flex items-center justify-center font-bold text-xs shrink-0 border border-blue-300/30">
                              {u.name.charAt(0).toUpperCase()}
                            </div>
                            <div>
                              <div className="font-semibold text-slate-900 flex items-center gap-1.5">
                                <span>{u.name}</span>
                                {isSelf && (
                                  <span className="text-[10px] font-bold px-1.5 py-0.2 rounded bg-amber-100 text-amber-900 border border-amber-300">
                                    Anda
                                  </span>
                                )}
                              </div>
                              <div className="text-xs text-slate-500 font-mono">
                                @{u.username}
                              </div>
                            </div>
                          </div>
                        </td>

                        {/* Email */}
                        <td className="py-3.5 px-4 text-slate-600">
                          {u.email ? u.email : <span className="text-slate-400 italic">Tidak ada email</span>}
                        </td>

                        {/* Roles */}
                        <td className="py-3.5 px-4">
                          <div className="flex flex-wrap gap-1.5">
                            {u.roles.map((r) => (
                              <span
                                key={r.id}
                                className={`px-2 py-0.5 rounded-md text-[11px] font-semibold border ${getRoleBadgeClass(
                                  r.name
                                )}`}
                              >
                                {r.display_name}
                              </span>
                            ))}
                          </div>
                        </td>

                        {/* Status Aktif */}
                        <td className="py-3.5 px-4 text-center">
                          {hasPermission('users.edit') && !isSelf ? (
                            <button
                              type="button"
                              onClick={() => handleToggleActive(u)}
                              title={u.is_active ? 'Klik untuk nonaktifkan' : 'Klik untuk aktifkan'}
                              className={`inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold cursor-pointer transition-colors border ${
                                u.is_active
                                  ? 'bg-emerald-50 text-emerald-800 border-emerald-300 hover:bg-emerald-100'
                                  : 'bg-slate-100 text-slate-600 border-slate-300 hover:bg-slate-200'
                              }`}
                            >
                              {u.is_active ? (
                                <>
                                  <CheckCircle2 className="w-3.5 h-3.5 text-emerald-600" />
                                  <span>Aktif</span>
                                </>
                              ) : (
                                <>
                                  <XCircle className="w-3.5 h-3.5 text-slate-500" />
                                  <span>Nonaktif</span>
                                </>
                              )}
                            </button>
                          ) : (
                            <span
                              className={`inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold border ${
                                u.is_active
                                  ? 'bg-emerald-50 text-emerald-800 border-emerald-200'
                                  : 'bg-slate-100 text-slate-600 border-slate-200'
                              }`}
                            >
                              {u.is_active ? 'Aktif' : 'Nonaktif'}
                            </span>
                          )}
                        </td>

                        {/* Login Terakhir */}
                        <td className="py-3.5 px-4 text-slate-500 text-xs">
                          {u.last_login_at
                            ? new Date(u.last_login_at).toLocaleString('id-ID', {
                                day: '2-digit',
                                month: 'short',
                                year: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit',
                              })
                            : 'Belum pernah login'}
                        </td>

                        {/* Aksi */}
                        <td className="py-3.5 px-4 text-right">
                          <div className="flex items-center justify-end gap-1">
                            {hasPermission('users.edit') && (
                              <button
                                type="button"
                                onClick={() => handleOpenEditModal(u)}
                                className="p-1.5 rounded-lg text-slate-600 hover:text-blue-700 hover:bg-blue-50 transition-colors cursor-pointer"
                                title="Edit Pengguna"
                              >
                                <Edit2 className="w-4 h-4" />
                              </button>
                            )}

                            {hasPermission('users.delete') && !isSelf && (
                              <button
                                type="button"
                                onClick={() => handleOpenDelete(u)}
                                className="p-1.5 rounded-lg text-slate-600 hover:text-red-700 hover:bg-red-50 transition-colors cursor-pointer"
                                title="Hapus Pengguna"
                              >
                                <Trash2 className="w-4 h-4" />
                              </button>
                            )}
                          </div>
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
          )}

          {/* Pagination Footer */}
          {totalItems > 0 && (
            <div className="px-4 py-3 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-600">
              <div>
                Menampilkan{' '}
                <span className="font-semibold text-slate-800">
                  {startIndex + 1} - {Math.min(startIndex + itemsPerPage, totalItems)}
                </span>{' '}
                dari <span className="font-semibold text-slate-800">{totalItems}</span> total pengguna
              </div>

              <div className="flex items-center gap-1">
                <button
                  type="button"
                  disabled={currentPage <= 1}
                  onClick={() => setCurrentPage((p) => Math.max(1, p - 1))}
                  className="inline-flex items-center gap-1 px-3 py-1.5 rounded-md border border-slate-300 bg-white hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors font-medium shadow-2xs"
                >
                  <ChevronLeft className="w-3.5 h-3.5" />
                  <span>Sebelumnya</span>
                </button>

                <div className="px-3 py-1.5 font-semibold text-slate-800 bg-white rounded-md border border-slate-200">
                  {currentPage} / {totalPages}
                </div>

                <button
                  type="button"
                  disabled={currentPage >= totalPages}
                  onClick={() => setCurrentPage((p) => Math.min(totalPages, p + 1))}
                  className="inline-flex items-center gap-1 px-3 py-1.5 rounded-md border border-slate-300 bg-white hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors font-medium shadow-2xs"
                >
                  <span>Selanjutnya</span>
                  <ChevronRight className="w-3.5 h-3.5" />
                </button>
              </div>
            </div>
          )}
        </div>

        {/* Modal Tambah / Edit Pengguna */}
        {modalOpen && (
          <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-2xs">
            <div className="bg-white rounded-2xl border border-slate-200 shadow-xl w-full max-w-lg overflow-hidden animate-in fade-in zoom-in-95 duration-150">
              {/* Header Modal */}
              <div className="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50/70">
                <h3 className="text-base font-bold text-slate-900">
                  {modalMode === 'create' ? 'Tambah Pengguna Baru' : 'Perbarui Data Pengguna'}
                </h3>
                <button
                  type="button"
                  onClick={() => setModalOpen(false)}
                  className="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-200 transition-colors cursor-pointer"
                >
                  <X className="w-5 h-5" />
                </button>
              </div>

              {/* Form Modal */}
              <form onSubmit={handleFormSubmit} className="p-6 space-y-4">
                {formError && (
                  <div className="p-3 rounded-lg bg-red-50 border border-red-200 flex items-start gap-2 text-xs text-red-800">
                    <AlertCircle className="w-4 h-4 text-red-600 shrink-0 mt-0.5" />
                    <span>{formError}</span>
                  </div>
                )}

                {/* Nama Lengkap */}
                <div>
                  <label className="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">
                    Nama Lengkap <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="text"
                    required
                    value={formName}
                    onChange={(e) => setFormName(e.target.value)}
                    placeholder="Contoh: Budi Santoso"
                    className="block w-full px-3 py-2 text-sm border border-slate-300 rounded-lg text-slate-900 focus:outline-hidden focus:ring-2 focus:ring-[#0E385E]"
                  />
                </div>

                {/* Username */}
                <div>
                  <label className="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">
                    Nama Pengguna (Username) <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="text"
                    required
                    value={formUsername}
                    onChange={(e) => setFormUsername(e.target.value)}
                    placeholder="Contoh: budi_santoso"
                    className="block w-full px-3 py-2 text-sm border border-slate-300 rounded-lg text-slate-900 focus:outline-hidden focus:ring-2 focus:ring-[#0E385E]"
                  />
                </div>

                {/* Email */}
                <div>
                  <label className="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">
                    Alamat Email (Opsional)
                  </label>
                  <input
                    type="email"
                    value={formEmail}
                    onChange={(e) => setFormEmail(e.target.value)}
                    placeholder="Contoh: budi@kemnaker.go.id"
                    className="block w-full px-3 py-2 text-sm border border-slate-300 rounded-lg text-slate-900 focus:outline-hidden focus:ring-2 focus:ring-[#0E385E]"
                  />
                </div>

                {/* Password */}
                <div>
                  <label className="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">
                    {modalMode === 'create' ? 'Kata Sandi' : 'Ganti Kata Sandi (Kosongkan bila tidak diubah)'}
                    {modalMode === 'create' && <span className="text-red-500"> *</span>}
                  </label>
                  <input
                    type="password"
                    required={modalMode === 'create'}
                    value={formPassword}
                    onChange={(e) => setFormPassword(e.target.value)}
                    placeholder={modalMode === 'create' ? 'Minimal 6 karakter' : 'Masukkan kata sandi baru'}
                    className="block w-full px-3 py-2 text-sm border border-slate-300 rounded-lg text-slate-900 focus:outline-hidden focus:ring-2 focus:ring-[#0E385E]"
                  />
                </div>

                {/* Roles Checkboxes */}
                <div>
                  <label className="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-2">
                    Peran Pengguna (Role) <span className="text-red-500">*</span>
                  </label>
                  <div className="space-y-2 border border-slate-200 rounded-lg p-3 bg-slate-50/50">
                    {roles.map((r) => {
                      const checked = formRoleIds.includes(r.id);
                      return (
                        <label
                          key={r.id}
                          className="flex items-start gap-2.5 p-1.5 rounded-lg hover:bg-slate-100/70 cursor-pointer"
                        >
                          <input
                            type="checkbox"
                            checked={checked}
                            onChange={() => handleToggleRoleId(r.id)}
                            className="mt-0.5 rounded text-[#0E385E] focus:ring-[#0E385E]"
                          />
                          <div>
                            <div className="text-xs font-bold text-slate-900">
                              {r.display_name}
                            </div>
                            {r.description && (
                              <div className="text-[11px] text-slate-500">
                                {r.description}
                              </div>
                            )}
                          </div>
                        </label>
                      );
                    })}
                  </div>
                </div>

                {/* Status Aktif Switch */}
                <div className="flex items-center justify-between pt-2">
                  <span className="text-xs font-semibold uppercase tracking-wider text-slate-700">
                    Status Akun Aktif
                  </span>
                  <input
                    type="checkbox"
                    checked={formIsActive}
                    onChange={(e) => setFormIsActive(e.target.checked)}
                    className="w-4 h-4 rounded text-[#0E385E] focus:ring-[#0E385E]"
                  />
                </div>

                {/* Buttons */}
                <div className="pt-4 border-t border-slate-200 flex items-center justify-end gap-2">
                  <button
                    type="button"
                    onClick={() => setModalOpen(false)}
                    className="px-4 py-2 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50 cursor-pointer"
                  >
                    Batal
                  </button>
                  <button
                    type="submit"
                    disabled={formSubmitting}
                    className="px-4 py-2 rounded-lg bg-[#0E385E] hover:bg-[#163A5F] text-xs font-semibold text-white flex items-center gap-1.5 disabled:opacity-60 cursor-pointer"
                  >
                    {formSubmitting && <Loader2 className="w-3.5 h-3.5 animate-spin" />}
                    <span>{modalMode === 'create' ? 'Simpan Pengguna' : 'Perbarui'}</span>
                  </button>
                </div>
              </form>
            </div>
          </div>
        )}

        {/* Modal Konfirmasi Hapus */}
        {deleteModalOpen && deletingUser && (
          <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-2xs">
            <div className="bg-white rounded-2xl border border-slate-200 shadow-xl w-full max-w-sm p-6 space-y-4">
              <div className="w-12 h-12 rounded-full bg-red-100 text-red-600 flex items-center justify-center mx-auto">
                <Trash2 className="w-6 h-6" />
              </div>
              <div className="text-center space-y-1">
                <h3 className="text-base font-bold text-slate-900">Hapus Pengguna</h3>
                <p className="text-xs text-slate-600 leading-relaxed">
                  Apakah Anda yakin ingin menghapus akun <strong>{deletingUser.name}</strong> (@{deletingUser.username})? Tindakan ini dapat dibatalkan melalui sistem database soft-delete.
                </p>
              </div>
              <div className="flex items-center justify-center gap-2 pt-2">
                <button
                  type="button"
                  onClick={() => setDeleteModalOpen(false)}
                  disabled={deleteSubmitting}
                  className="px-4 py-2 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50 cursor-pointer"
                >
                  Batal
                </button>
                <button
                  type="button"
                  onClick={handleConfirmDelete}
                  disabled={deleteSubmitting}
                  className="px-4 py-2 rounded-lg bg-red-600 hover:bg-red-700 text-xs font-semibold text-white flex items-center gap-1.5 disabled:opacity-60 cursor-pointer"
                >
                  {deleteSubmitting && <Loader2 className="w-3.5 h-3.5 animate-spin" />}
                  <span>Ya, Hapus</span>
                </button>
              </div>
            </div>
          </div>
        )}
      </div>
    </AuthGuard>
  );
}
