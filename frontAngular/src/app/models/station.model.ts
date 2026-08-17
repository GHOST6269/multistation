export interface Station {
  id: number;
  code: string | null;
  name: string;
  address: string | null;
  city: string | null;
  contact: string | null;
  email: string | null;
  status: string;
  manager: string | null;
  usersCount: number;
  articlesCount: number;
  createdAt: string;
}

export type StationInput = Pick<Station, 'name'> & Partial<Pick<Station, 'code' | 'address' | 'city' | 'contact' | 'email' | 'status' | 'manager'>>;
