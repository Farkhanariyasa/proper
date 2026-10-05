'use client';

import React, { useState, useEffect, useCallback, useMemo } from 'react';
import AuthGuard from '@/components/auth/AuthGuard';
import { useAuth } from '@/hooks/useAuth';
import {
  getPermissionsApi,
  getRolesApi,
  syncRolePermissionsApi,
} from '@/services/admin';
import { Permission, Role } from '@/types/auth';
import {
  Shield,
  ShieldCheck,
  Save,
  CheckCircle2,
  AlertCircle,
  Loader2,
  Search,
  RotateCcw,
  CheckSquare,
  Square,
  Lock,
  Layers,
} from 'lucide-react';

export default function RolesAndPermissionsMatrixPage() {
  const { hasPermission } = useAuth();

  const [roles, setRoles] = useState<Role[]>([]);
  const [permissionsGrouped, setPermissionsGrouped] = useState<Record<string, Permission[]>>({});
  const [permissionsList, setPermissionsList] = useState<Permission[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  // Map roleId -> Array of permission IDs
  const [matrixState, setMatrixState] = useState<Record<number, number[]>>({});
  const [initialMatrixState, setInitialMatrixState] = useState<Record<number, number[]>>({});

  const [searchQuery, setSearchQuery] = useState('');
  const [saving, setSaving] = useState(false);
  const [saveSuccess, setSaveSuccess] = useState<string | null>(null);

  // Muat data awal: roles dan permissions
  const fetchData = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const [rolesData, permsData] = await Promise.all([
        getRolesApi(),
        getPermissionsApi(),
      ]);

      setRoles(rolesData);
      setPermissionsGrouped(permsData.grouped || {});
      setPermissionsList(permsData.list || []);

      // Inisialisasi state matriks
      const initialMap: Record<number, number[]> = {};
      rolesData.forEach((role) => {
        initialMap[role.id] = role.permissions?.map((p) => p.id) || [];
      });

      setMatrixState(initialMap);
      setInitialMatrixState(initialMap);
    } catch (err: unknown) {
      if (err instanceof Error) {
        setError(err.message);
      } else {
        setError('Gagal memuat matriks perizinan.');
      }
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchData();
  }, [fetchData]);

  // Cek apakah ada perubahan belum disimpan (isDirty)
  const isDirty = useMemo(() => {
    for (const role of roles) {
      const current = matrixState[role.id] || [];
      const initial = initialMatrixState[role.id] || [];
      if (current.length !== initial.length) return true;
      const set = new Set(initial);
      for (const id of current) {
        if (!set.has(id)) return true;
      }
    }
    return false;
  }, [roles, matrixState, initialMatrixState]);

  // Toggle satu checkbox permission untuk role tertentu
  const handleToggleCell = (roleId: number, permissionId: number) => {
    setSaveSuccess(null);
    setMatrixState((prev) => {
      const current = prev[roleId] || [];
      const updated = current.includes(permissionId)
        ? current.filter((id) => id !== permissionId)
        : [...current, permissionId];
      return { ...prev, [roleId]: updated };
    });
  };

  // Toggle seluruh kolom role (Pilih Semua / Kosongkan)
  const handleToggleRoleColumn = (roleId: number, selectAll: boolean) => {
    setSaveSuccess(null);
    setMatrixState((prev) => ({
      ...prev,
      [roleId]: selectAll ? permissionsList.map((p) => p.id) : [],
    }));
  };

  // Toggle seluruh grup untuk role tertentu
  const handleToggleRoleGroup = (roleId: number, groupPerms: Permission[], selectAll: boolean) => {
    setSaveSuccess(null);
    const groupIds = groupPerms.map((p) => p.id);
    setMatrixState((prev) => {
      const current = prev[roleId] || [];
      if (selectAll) {
        const set = new Set([...current, ...groupIds]);
        return { ...prev, [roleId]: Array.from(set) };
      } else {
        return { ...prev, [roleId]: current.filter((id) => !groupIds.includes(id)) };
      }
    });
  };

  // Reset perubahan ke state awal
  const handleReset = () => {
    setMatrixState(initialMatrixState);
    setSaveSuccess(null);
  };

  // Simpan seluruh perubahan matriks ke backend
  const handleSaveMatrix = async () => {
    setSaving(true);
    setSaveSuccess(null);

    try {
      // Jalankan sinkronisasi untuk role yang mengalami perubahan
      const promises: Promise<Role>[] = [];

      roles.forEach((role) => {
        const current = matrixState[role.id] || [];
        const initial = initialMatrixState[role.id] || [];
        const changed =
          current.length !== initial.length ||
          current.some((id) => !initial.includes(id));

        if (changed) {
          promises.push(syncRolePermissionsApi(role.id, current));
        }
      });

      if (promises.length > 0) {
        await Promise.all(promises);
      }

      setInitialMatrixState(matrixState);
      setSaveSuccess('Matriks hak akses peran berhasil disimpan.');
    } catch (err: unknown) {
      if (err instanceof Error) {
        alert(err.message);
      } else {
        alert('Gagal menyimpan perubahan matriks.');
      }
    } finally {
      setSaving(false);
    }
  };

  // Filter group & permission berdasarkan search query
  const filteredGroups = useMemo(() => {
    if (!searchQuery.trim()) return permissionsGrouped;

    const q = searchQuery.toLowerCase();
    const result: Record<string, Permission[]> = {};

    Object.entries(permissionsGrouped).forEach(([group, perms]) => {
      const matched = perms.filter(
        (p) =>
          p.display_name.toLowerCase().includes(q) ||
          p.name.toLowerCase().includes(q) ||
          group.toLowerCase().includes(q)
      );
      if (matched.length > 0) {
        result[group] = matched;
      }
    });

    return result;
  }, [permissionsGrouped, searchQuery]);

  return (
    <AuthGuard requiredPermission="roles.view">
      <div className="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto space-y-6">
        {/* Header Title & Actions */}
        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <div className="flex items-center gap-2">
              <h1 className="text-xl sm:text-2xl font-bold text-slate-900">
                Matriks Peran & Hak Akses
              </h1>
              <span className="px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                {roles.length} Peran
              </span>
            </div>
            <p className="text-xs sm:text-sm text-slate-600 mt-1">
              Atur hak akses secara terpadu melalui tabel matriks role versus izin tindakan.
            </p>
          </div>

          {/* Action Save Bar */}
          {hasPermission('permissions.assign') && (
            <div className="flex items-center gap-2">
              {isDirty && (
                <button
                  type="button"
                  onClick={handleReset}
                  disabled={saving}
                  className="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-100 transition-colors cursor-pointer"
                >
                  <RotateCcw className="w-3.5 h-3.5" />
                  <span>Batal</span>
                </button>
              )}

              <button
                type="button"
                onClick={handleSaveMatrix}
                disabled={saving || !isDirty}
                className="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-[#0E385E] hover:bg-[#163A5F] text-white text-xs sm:text-sm font-semibold shadow-xs transition-colors disabled:opacity-50 cursor-pointer"
              >
                {saving ? (
                  <Loader2 className="w-4 h-4 animate-spin" />
                ) : (
                  <Save className="w-4 h-4" />
                )}
                <span>Simpan Matriks</span>
              </button>
            </div>
          )}
        </div>

        {/* Feedback Alert Unsaved Changes & Success */}
        {saveSuccess && (
          <div
            role="status"
            className="p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 flex items-center gap-2.5 text-xs text-emerald-900 shadow-2xs animate-in fade-in duration-200"
          >
            <CheckCircle2 className="w-4 h-4 text-emerald-600 shrink-0" />
            <span className="font-medium">{saveSuccess}</span>
          </div>
        )}

        {isDirty && !saveSuccess && (
          <div
            role="alert"
            className="p-3.5 rounded-xl bg-amber-50 border border-amber-200 flex items-center justify-between text-xs text-amber-900 shadow-2xs"
          >
            <div className="flex items-center gap-2">
              <AlertCircle className="w-4 h-4 text-amber-600 shrink-0" />
              <span>
                Terdapat perubahan hak akses yang belum disimpan ke database.
              </span>
            </div>
            <span className="text-[11px] font-semibold text-amber-800">
              Klik &quot;Simpan Matriks&quot; untuk menerapkan.
            </span>
          </div>
        )}

        {/* Search Filter Bar */}
        <div className="bg-white p-3.5 rounded-xl border border-slate-200/80 shadow-2xs flex flex-col sm:flex-row items-center justify-between gap-3">
          <div className="relative w-full sm:w-80">
            <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
              <Search className="h-4 w-4" />
            </div>
            <input
              type="text"
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              placeholder="Cari nama izin atau nama modul..."
              className="block w-full pl-9 pr-3 py-1.5 text-xs sm:text-sm border border-slate-300 rounded-lg text-slate-900 placeholder:text-slate-400 focus:outline-hidden focus:ring-2 focus:ring-[#0E385E]"
            />
          </div>

          <div className="text-xs text-slate-500 font-medium">
            Total {permissionsList.length} Izin Sistem Terdaftar
          </div>
        </div>

        {/* Tabel Matriks */}
        {loading ? (
          <div className="bg-white p-16 rounded-xl border border-slate-200/80 shadow-2xs flex flex-col items-center justify-center gap-3 text-slate-500">
            <Loader2 className="w-8 h-8 animate-spin text-[#0E385E]" />
            <p className="text-sm font-medium">Memuat tabel matriks perizinan...</p>
          </div>
        ) : error ? (
          <div className="bg-white p-8 rounded-xl border border-red-200 shadow-2xs text-center space-y-3">
            <AlertCircle className="w-8 h-8 text-red-600 mx-auto" />
            <h3 className="text-sm font-bold text-slate-900">Gagal Memuat Matriks</h3>
            <p className="text-xs text-slate-600">{error}</p>
            <button
              type="button"
              onClick={fetchData}
              className="px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-xs font-semibold text-slate-800 transition-colors cursor-pointer"
            >
              Muat Ulang
            </button>
          </div>
        ) : (
          <div className="bg-white rounded-xl border border-slate-200/80 shadow-2xs overflow-hidden">
            <div className="overflow-x-auto">
              <table className="w-full text-left border-collapse text-xs sm:text-sm">
                <thead>
                  <tr className="bg-[#0E385E] text-white border-b border-[#1F5A88]">
                    {/* Kolom Nama Izin */}
                    <th className="py-4 px-5 font-bold uppercase tracking-wider text-[11px] min-w-[280px] w-1/3">
                      Modul & Hak Akses
                    </th>

                    {/* Kolom-kolom Role */}
                    {roles.map((role) => {
                      const assignedCount = matrixState[role.id]?.length || 0;
                      const isSuperadmin = role.name === 'superadmin';

                      return (
                        <th
                          key={role.id}
                          className="py-4 px-4 text-center border-l border-blue-400/20 min-w-[170px]"
                        >
                          <div className="flex flex-col items-center gap-1">
                            <div className="flex items-center gap-1.5">
                              <span className="font-bold text-xs sm:text-sm">
                                {role.display_name}
                              </span>
                              {isSuperadmin && (
                                <span title="Akses Penuh Otomatis" className="inline-flex">
                                  <Lock className="w-3.5 h-3.5 text-amber-300" />
                                </span>
                              )}
                            </div>

                            <span className="text-[10px] text-blue-200 font-mono">
                              @{role.name}
                            </span>

                            {/* Badge Counter */}
                            <span className="mt-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-[#1F5A88] text-amber-300 border border-blue-300/30">
                              {isSuperadmin
                                ? 'Semua Hak Akses'
                                : `${assignedCount} / ${permissionsList.length}`}
                            </span>

                            {/* Tombol Kolom Cepat (Pilih Semua / Kosongkan) */}
                            {hasPermission('permissions.assign') && !isSuperadmin && (
                              <div className="flex items-center gap-2 mt-1.5 text-[10px] text-blue-200">
                                <button
                                  type="button"
                                  onClick={() => handleToggleRoleColumn(role.id, true)}
                                  className="hover:text-amber-300 transition-colors cursor-pointer"
                                >
                                  Pilih Semua
                                </button>
                                <span>•</span>
                                <button
                                  type="button"
                                  onClick={() => handleToggleRoleColumn(role.id, false)}
                                  className="hover:text-amber-300 transition-colors cursor-pointer"
                                >
                                  Kosongkan
                                </button>
                              </div>
                            )}
                          </div>
                        </th>
                      );
                    })}
                  </tr>
                </thead>

                <tbody className="divide-y divide-slate-100">
                  {Object.entries(filteredGroups).map(([groupName, groupPerms]) => (
                    <React.Fragment key={groupName}>
                      {/* Baris Kategori Grup */}
                      <tr className="bg-slate-100/90 font-bold text-slate-800 border-t border-b border-slate-200">
                        <td className="py-2.5 px-5">
                          <div className="flex items-center gap-2">
                            <Layers className="w-3.5 h-3.5 text-[#0E385E]" />
                            <span className="uppercase tracking-wider text-[11px]">
                              {groupName}
                            </span>
                            <span className="text-[10px] text-slate-500 font-normal">
                              ({groupPerms.length} izin)
                            </span>
                          </div>
                        </td>

                        {/* Quick Action Grup per Role */}
                        {roles.map((role) => {
                          const isSuperadmin = role.name === 'superadmin';
                          const assignedInGroup = groupPerms.filter((p) =>
                            matrixState[role.id]?.includes(p.id)
                          ).length;

                          return (
                            <td
                              key={role.id}
                              className="py-2.5 px-4 text-center border-l border-slate-200/60 bg-slate-100/60"
                            >
                              {!isSuperadmin && hasPermission('permissions.assign') ? (
                                <div className="flex items-center justify-center gap-2 text-[10px] text-slate-600">
                                  <button
                                    type="button"
                                    onClick={() => handleToggleRoleGroup(role.id, groupPerms, true)}
                                    className="hover:text-[#0E385E] font-semibold transition-colors cursor-pointer"
                                  >
                                    Semua
                                  </button>
                                  <span className="text-slate-300">/</span>
                                  <button
                                    type="button"
                                    onClick={() => handleToggleRoleGroup(role.id, groupPerms, false)}
                                    className="hover:text-red-700 font-semibold transition-colors cursor-pointer"
                                  >
                                    Hapus
                                  </button>
                                </div>
                              ) : (
                                <span className="text-[10px] text-slate-500 font-mono">
                                  {isSuperadmin ? 'Penuh' : `${assignedInGroup}/${groupPerms.length}`}
                                </span>
                              )}
                            </td>
                          );
                        })}
                      </tr>

                      {/* Baris Tiap Permission dalam Grup */}
                      {groupPerms.map((perm) => (
                        <tr
                          key={perm.id}
                          className="hover:bg-blue-50/40 transition-colors"
                        >
                          {/* Nama Permission & Resource Code */}
                          <td className="py-3 px-5">
                            <div>
                              <div className="font-semibold text-slate-900 leading-tight">
                                {perm.display_name}
                              </div>
                              <div className="text-[10px] text-slate-500 font-mono mt-0.5">
                                {perm.name}
                              </div>
                            </div>
                          </td>

                          {/* Checkbox per Role Column */}
                          {roles.map((role) => {
                            const isSuperadmin = role.name === 'superadmin';
                            const isChecked = isSuperadmin
                              ? true
                              : matrixState[role.id]?.includes(perm.id);

                            return (
                              <td
                                key={role.id}
                                className="py-3 px-4 text-center border-l border-slate-100 align-middle"
                              >
                                {isSuperadmin ? (
                                  <div
                                    className="inline-flex items-center justify-center text-emerald-600"
                                    title="Superadmin memiliki akses penuh ke izin ini"
                                  >
                                    <CheckCircle2 className="w-5 h-5" />
                                  </div>
                                ) : (
                                  <label className="inline-flex items-center justify-center p-1.5 rounded-lg hover:bg-slate-100 cursor-pointer">
                                    <input
                                      type="checkbox"
                                      checked={Boolean(isChecked)}
                                      onChange={() => handleToggleCell(role.id, perm.id)}
                                      disabled={!hasPermission('permissions.assign')}
                                      className="w-4 h-4 rounded text-[#0E385E] focus:ring-[#0E385E] cursor-pointer"
                                    />
                                  </label>
                                )}
                              </td>
                            );
                          })}
                        </tr>
                      ))}
                    </React.Fragment>
                  ))}
                </tbody>
              </table>
            </div>

            {/* Bottom Footer Bar */}
            <div className="p-4 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-600">
              <div className="flex items-center gap-2">
                <Shield className="w-4 h-4 text-[#0E385E]" />
                <span>
                  Perubahan pada matriks akan langsung berlaku pada sesi login berikutnya atau saat token disegarkan.
                </span>
              </div>

              {hasPermission('permissions.assign') && (
                <button
                  type="button"
                  onClick={handleSaveMatrix}
                  disabled={saving || !isDirty}
                  className="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg bg-[#0E385E] hover:bg-[#163A5F] text-white font-semibold shadow-xs transition-colors disabled:opacity-50 cursor-pointer shrink-0"
                >
                  {saving ? (
                    <Loader2 className="w-4 h-4 animate-spin" />
                  ) : (
                    <Save className="w-4 h-4" />
                  )}
                  <span>Simpan Matriks Hak Akses</span>
                </button>
              )}
            </div>
          </div>
        )}
      </div>
    </AuthGuard>
  );
}
