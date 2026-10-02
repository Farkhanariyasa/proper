export type KbjiLevel =
  | 'major_group'
  | 'sub_major_group'
  | 'minor_group'
  | 'unit_group'
  | 'occupation';

export interface KbjiNode {
  code: string;
  title: string;
  level: KbjiLevel;
  iscoCode: string | null;
  hasChildren: boolean;
}

export interface KbjiAncestor {
  code: string;
  title: string;
  level: KbjiLevel;
}

export interface KbjiChildrenResponse {
  status: string;
  parent: KbjiAncestor;
  totalChildren: number;
  children: KbjiNode[];
}

export interface KbjiDetail {
  code: string;
  title: string;
  level: KbjiLevel;
  parentCode: string | null;
  description: string | null;
  iscoCode: string | null;
  ancestors: KbjiAncestor[];
  children: KbjiNode[];
}

export interface KbjiSearchResponse {
  status: string;
  query: string;
  total: number;
  page: number;
  limit: number;
  data: KbjiNode[];
}

export interface KbjiStats {
  status: string;
  version: string;
  total: number;
  byLevel: Record<KbjiLevel, number>;
}
