import axios, { AxiosError } from "axios"
import { toast } from "@/components/ui/toaster"

export const API_BASE = "http://127.0.0.1:8000/api/v1"

export const api = axios.create({
  baseURL: API_BASE,
  headers: {
    Accept: "application/json",
    "Content-Type": "application/json",
  },
})

api.interceptors.request.use((config) => {
  const token = localStorage.getItem("auth_token")
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

api.interceptors.response.use(
  (response) => response,
  (error: AxiosError<{ message?: string; errors?: Record<string, string[]> }>) => {
    if (error.response?.status === 401) {
      localStorage.removeItem("auth_token")
      localStorage.removeItem("auth_user")
      if (!window.location.pathname.startsWith("/login")) {
        window.location.href = "/login"
      }
    } else if (error.response?.status === 403) {
      toast.error("Akses ditolak", error.response.data?.message ?? "Anda tidak punya akses.")
    } else if (error.response?.status === 422) {
      const errs = error.response.data?.errors
      const firstErr = errs ? Object.values(errs).flat()[0] : null
      toast.error("Validasi gagal", firstErr ?? error.response.data?.message ?? "Periksa input Anda.")
    } else if (error.response?.status === 429) {
      toast.error("Terlalu cepat", error.response.data?.message ?? "Tunggu beberapa saat.")
    } else if ((error.response?.status ?? 0) >= 500) {
      toast.error("Server error", error.response?.data?.message ?? "Terjadi kesalahan server.")
    }
    return Promise.reject(error)
  }
)
