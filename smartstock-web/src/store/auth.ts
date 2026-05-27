import { create } from "zustand"
import { api } from "@/lib/api"
import type { User } from "@/types"

interface AuthState {
  user: User | null
  token: string | null
  loading: boolean
  login: (email: string, password: string) => Promise<void>
  logout: () => Promise<void>
  fetchMe: () => Promise<void>
  hasRole: (roles: string | string[]) => boolean
  init: () => void
}

export const useAuth = create<AuthState>((set, get) => ({
  user: null,
  token: localStorage.getItem("auth_token"),
  loading: false,

  init: () => {
    const token = localStorage.getItem("auth_token")
    const userJson = localStorage.getItem("auth_user")
    if (token && userJson) {
      try {
        set({ token, user: JSON.parse(userJson) })
      } catch {
        localStorage.removeItem("auth_user")
      }
    }
  },

  login: async (email, password) => {
    set({ loading: true })
    try {
      const { data } = await api.post("/auth/login", { email, password })
      const { token, user } = data.data
      localStorage.setItem("auth_token", token)
      localStorage.setItem("auth_user", JSON.stringify(user))
      set({ token, user, loading: false })
    } catch (e) {
      set({ loading: false })
      throw e
    }
  },

  logout: async () => {
    try {
      await api.post("/auth/logout")
    } catch {}
    localStorage.removeItem("auth_token")
    localStorage.removeItem("auth_user")
    set({ user: null, token: null })
  },

  fetchMe: async () => {
    try {
      const { data } = await api.get("/auth/me")
      localStorage.setItem("auth_user", JSON.stringify(data.data))
      set({ user: data.data })
    } catch {
      // ignore
    }
  },

  hasRole: (roles) => {
    const user = get().user
    if (!user?.role) return false
    const list = Array.isArray(roles) ? roles : [roles]
    return list.includes(user.role.code)
  },
}))
