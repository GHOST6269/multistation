export type UserRole = 'ROLE_SUPER_ADMIN' | 'ROLE_GERANT' | 'ROLE_QUALITY_MARSHALL' | 'ROLE_ASSISTANT';

export interface AppUser {
  id: number;
  email: string;
  firstName: string;
  lastName: string | null;
  contact: string | null;
  role: UserRole;
  roleLabel: string;
  roles: UserRole[];
  stationIds: number[];
  stationNames?: string[];
  isActive: boolean;
  canViewCustomers: boolean;
  lastLogin: string | null;
}

export interface ProfileInput {
  email?: string;
  firstName: string;
  lastName: string;
  contact: string;
  role?: UserRole;
  stationIds?: number[];
  canViewCustomers?: boolean;
  password?: string;
}

export interface RoleOption {
  value: UserRole;
  label: string;
}

export interface UserInput {
  email: string;
  firstName: string;
  lastName: string;
  contact: string;
  role: UserRole;
  stationIds: number[];
  canViewCustomers: boolean;
  password?: string;
  isActive: boolean;
}
