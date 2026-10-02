'use client';

import React, { useState } from 'react';
import { ChevronRight, ChevronDown, Loader2, Briefcase, FolderOpen, Circle } from 'lucide-react';
import { KbjiNode } from '@/types/kbji';
import { getKbjiChildren } from '@/services/kbji';
import { KBJI_LEVELS } from '@/constants/kbji';

interface KbjiTreeNodeProps {
  node: KbjiNode;
  level?: number;
  selectedCode: string | null;
  onSelectNode: (code: string) => void;
}

export default function KbjiTreeNode({
  node,
  level = 0,
  selectedCode,
  onSelectNode,
}: KbjiTreeNodeProps) {
  const [isOpen, setIsOpen] = useState(false);
  const [isLoading, setIsLoading] = useState(false);
  const [children, setChildren] = useState<KbjiNode[]>([]);
  const [hasLoaded, setHasLoaded] = useState(false);

  const isSelected = selectedCode === node.code;
  const isOccupation = node.level === 'occupation';
  const levelInfo = KBJI_LEVELS[node.level];

  const handleToggle = async (e: React.MouseEvent) => {
    e.stopPropagation();

    if (!node.hasChildren) {
      onSelectNode(node.code);
      return;
    }

    if (!isOpen && !hasLoaded) {
      setIsLoading(true);
      try {
        const res = await getKbjiChildren(node.code);
        setChildren(res.children);
        setHasLoaded(true);
      } catch (err) {
        console.error('Gagal mengambil turunan KBJI:', err);
      } finally {
        setIsLoading(false);
      }
    }

    setIsOpen(!isOpen);
  };

  return (
    <div className="select-none">
      {/* Baris Node Item */}
      <div
        onClick={() => onSelectNode(node.code)}
        className={`group flex items-center justify-between gap-2 rounded-lg py-1.5 px-2 text-xs transition-colors cursor-pointer border ${
          isSelected
            ? 'bg-blue-50 border-blue-300 text-blue-900 font-semibold shadow-xs'
            : 'border-transparent text-slate-700 hover:bg-slate-100/80 hover:text-slate-900'
        }`}
        style={{ paddingLeft: `${Math.max(8, level * 18 + 8)}px` }}
      >
        <div className="flex items-center gap-2 min-w-0 flex-1">
          {/* Tombol Expand / Collapse atau Bullet Daun */}
          {node.hasChildren ? (
            <button
              type="button"
              onClick={handleToggle}
              className="flex h-5 w-5 shrink-0 items-center justify-center rounded hover:bg-slate-200/70 text-slate-500 hover:text-slate-800 transition-colors cursor-pointer"
              title={isOpen ? 'Tutup cabang' : 'Buka cabang'}
            >
              {isLoading ? (
                <Loader2 className="h-3.5 w-3.5 text-blue-600 animate-spin" />
              ) : isOpen ? (
                <ChevronDown className="h-3.5 w-3.5" />
              ) : (
                <ChevronRight className="h-3.5 w-3.5" />
              )}
            </button>
          ) : (
            <div className="flex h-5 w-5 shrink-0 items-center justify-center">
              <Circle className="h-1.5 w-1.5 text-slate-400" />
            </div>
          )}

          {/* Ikon Tipe (Jabatan vs Golongan) */}
          <div className="shrink-0">
            {isOccupation ? (
              <Briefcase className="h-3.5 w-3.5 text-amber-600" />
            ) : (
              <FolderOpen className="h-3.5 w-3.5 text-blue-600" />
            )}
          </div>

          {/* Badge Kode KBJI */}
          <span
            className={`shrink-0 rounded px-1.5 py-0.5 font-mono text-[10px] font-bold ${
              isSelected
                ? 'bg-blue-600 text-white'
                : 'bg-slate-100 text-slate-700 border border-slate-200 group-hover:bg-slate-200'
            }`}
          >
            {node.code}
          </span>

          {/* Judul Golongan / Jabatan */}
          <span className="truncate">{node.title}</span>
        </div>

        {/* Badge Level di Ujung Kanan */}
        <span
          className={`hidden sm:inline shrink-0 rounded border px-1.5 py-0.2 text-[9px] font-bold uppercase tracking-wider ${levelInfo.badgeClass}`}
        >
          {levelInfo.label}
        </span>
      </div>

      {/* Anak-anak Node (Recursive) */}
      {isOpen && (
        <div className="relative mt-0.5">
          {/* Garis vertikal penghubung hierarki */}
          <div
            className="absolute bottom-2 top-0 border-l border-slate-200"
            style={{ left: `${level * 18 + 17}px` }}
          />

          {children.length === 0 && !isLoading && (
            <div
              className="py-1 text-[11px] text-slate-400 italic"
              style={{ paddingLeft: `${(level + 1) * 18 + 12}px` }}
            >
              Tidak ada sub-elemen.
            </div>
          )}

          {children.map((child) => (
            <KbjiTreeNode
              key={child.code}
              node={child}
              level={level + 1}
              selectedCode={selectedCode}
              onSelectNode={onSelectNode}
            />
          ))}
        </div>
      )}
    </div>
  );
}
