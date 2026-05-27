import { type ClassValue, clsx } from "clsx"
import { twMerge } from "tailwind-merge"

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs))
}

export function formatCurrency(value: number | string | null | undefined): string {
  const n = Number(value ?? 0)
  return new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    minimumFractionDigits: 0,
  }).format(n)
}

export function formatNumber(value: number | string | null | undefined): string {
  const n = Number(value ?? 0)
  return new Intl.NumberFormat("id-ID").format(n)
}

export function formatDate(value: string | Date | null | undefined, includeTime = false): string {
  if (!value) return "-"
  const d = typeof value === "string" ? new Date(value) : value
  if (isNaN(d.getTime())) return "-"
  return new Intl.DateTimeFormat("id-ID", {
    day: "2-digit",
    month: "short",
    year: "numeric",
    ...(includeTime ? { hour: "2-digit", minute: "2-digit" } : {}),
  }).format(d)
}
