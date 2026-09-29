export interface TaxonomyCategory {
  id: number;
  code: string;
  title: string;
  titleEn: string;
  type: 'concept' | 'skill';
  isLayer1: boolean;
  hasChildren: boolean;
}

export interface TaxonomyChild {
  id: number;
  code: string | null;
  title: string;
  titleEn: string;
  type: 'concept' | 'skill';
  hasChildren: boolean;
}

export interface TaxonomyParentSummary {
  id: number;
  code: string | null;
  title: string;
  titleEn?: string;
}

export interface CategoryChildrenResponse {
  status: string;
  parent: TaxonomyParentSummary;
  totalChildren: number;
  children: TaxonomyChild[];
}

export interface TaxonomyNodeDetail {
  id: number;
  code: string | null;
  title: string;
  titleEn: string;
  type: 'concept' | 'skill';
  isLayer1: boolean;
  description: string | null;
  descriptionEn: string | null;
  altLabels: string[];
  altLabelsEn: string[];
  parents: TaxonomyParentSummary[];
  children: Array<{
    id: number;
    code: string | null;
    title: string;
    hasChildren: boolean;
  }>;
}

export interface SkillSearchResult {
  id: number;
  title: string;
  titleEn: string;
  type: 'concept' | 'skill';
}

export interface SkillSearchResponse {
  status: string;
  query: string;
  total: number;
  page: number;
  limit: number;
  data: SkillSearchResult[];
}

export interface TaxonomyStats {
  status: string;
  database: string;
  version: string;
  totalCategories: number;
  totalSkills: number;
  totalRelations: number;
}
