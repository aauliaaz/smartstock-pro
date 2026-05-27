import React, { createContext, useContext, useState, useEffect } from 'react';
import client from '@/api/client';
import { RoleCode, User } from '@/types';

interface AuthContextType {
  user: User | null;
  loading: boolean;
  login: (credentials: any) => Promise<void>;
  logout: () => Promise<void>;
  isAuthenticated: boolean;
  hasRole: (roles: string[]) => boolean;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

const ROLE_SLUGS: Record<string, RoleCode> = {
  adm: 'admin',
  mgr: 'manager',
  stf: 'staff',
  vwr: 'viewer',
  admin: 'admin',
  manager: 'manager',
  staff: 'staff',
  viewer: 'viewer',
};

const normalizeUser = (value: any): User | null => {
  if (!value) return null;

  const role = value.role
    ? {
        ...value.role,
        slug: ROLE_SLUGS[String(value.role.slug ?? value.role.code ?? '').toLowerCase()] ?? 'viewer',
      }
    : null;

  return { ...value, role };
};

const getAuthPayload = (responseData: any) => {
  const payload = responseData?.data ?? responseData;
  return {
    token: payload?.token as string | undefined,
    user: normalizeUser(payload?.user ?? payload),
  };
};

const persistAuth = (user: User, token?: string) => {
  localStorage.setItem('auth_user', JSON.stringify(user));
  if (token) localStorage.setItem('auth_token', token);
};

const clearPersistedAuth = () => {
  localStorage.removeItem('auth_token');
  localStorage.removeItem('auth_user');
};

export const AuthProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);

  const checkAuth = async () => {
    try {
      const cachedUser = localStorage.getItem('auth_user');
      if (cachedUser) {
        setUser(normalizeUser(JSON.parse(cachedUser)));
      }

      const hasToken = Boolean(localStorage.getItem('auth_token'));
      const response = await client.get(hasToken ? '/api/v1/auth/me' : '/api/me');
      const { user } = getAuthPayload(response.data);
      if (user) {
        persistAuth(user);
        setUser(user);
      } else {
        clearPersistedAuth();
        setUser(null);
      }
    } catch {
      clearPersistedAuth();
      setUser(null);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    checkAuth();
  }, []);

  const login = async (credentials: any) => {
    try {
      await client.get('/sanctum/csrf-cookie').catch(() => undefined);

      let response;
      try {
        response = await client.post('/api/login', credentials);
      } catch (sessionError) {
        try {
          response = await client.post('/api/v1/auth/login', credentials);
        } catch (tokenError) {
          throw tokenError ?? sessionError;
        }
      }

      const { token, user } = getAuthPayload(response.data);
      if (!user) throw new Error("Invalid response format from server");

      persistAuth(user, token);
      setUser(user);
    } catch (error) {
      console.error("Login error:", error);
      throw error;
    }
  };

  const logout = async () => {
    const hasToken = Boolean(localStorage.getItem('auth_token'));
    await client.post(hasToken ? '/api/v1/auth/logout' : '/api/logout').catch(() => undefined);
    clearPersistedAuth();
    setUser(null);
    window.location.href = '/login';
  };

  const hasRole = (roles: string[]) => {
    if (!user || !user.role) return false;
    return roles.includes(user.role.slug);
  };

  return (
    <AuthContext.Provider value={{ user, loading, login, logout, isAuthenticated: !!user, hasRole }}>
      {children}
    </AuthContext.Provider>
  );
};

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (context === undefined) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
};
