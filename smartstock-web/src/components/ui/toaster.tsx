import * as React from "react"
import { create } from "zustand"
import { X, CheckCircle, AlertCircle, Info, AlertTriangle } from "lucide-react"
import { cn } from "@/lib/utils"

type ToastType = "success" | "error" | "info" | "warning"

interface Toast {
  id: number
  title?: string
  description?: string
  type: ToastType
}

interface ToastStore {
  toasts: Toast[]
  add: (toast: Omit<Toast, "id">) => void
  remove: (id: number) => void
}

let counter = 0
export const useToastStore = create<ToastStore>((set) => ({
  toasts: [],
  add: (toast) => {
    const id = ++counter
    set((s) => ({ toasts: [...s.toasts, { id, ...toast }] }))
    setTimeout(() => set((s) => ({ toasts: s.toasts.filter((t) => t.id !== id) })), 4000)
  },
  remove: (id) => set((s) => ({ toasts: s.toasts.filter((t) => t.id !== id) })),
}))

export const toast = {
  success: (title: string, description?: string) => useToastStore.getState().add({ title, description, type: "success" }),
  error: (title: string, description?: string) => useToastStore.getState().add({ title, description, type: "error" }),
  info: (title: string, description?: string) => useToastStore.getState().add({ title, description, type: "info" }),
  warning: (title: string, description?: string) => useToastStore.getState().add({ title, description, type: "warning" }),
}

const icons = {
  success: <CheckCircle className="h-5 w-5 text-green-600" />,
  error: <AlertCircle className="h-5 w-5 text-red-600" />,
  info: <Info className="h-5 w-5 text-blue-600" />,
  warning: <AlertTriangle className="h-5 w-5 text-yellow-600" />,
}

const borderColors = {
  success: "border-l-green-500",
  error: "border-l-red-500",
  info: "border-l-blue-500",
  warning: "border-l-yellow-500",
}

export function Toaster() {
  const toasts = useToastStore((s) => s.toasts)
  const remove = useToastStore((s) => s.remove)

  return (
    <div className="fixed top-4 right-4 z-[100] flex flex-col gap-2 max-w-sm">
      {toasts.map((t) => (
        <div
          key={t.id}
          className={cn(
            "bg-background border border-l-4 rounded-md shadow-lg p-4 flex items-start gap-3 min-w-[300px]",
            borderColors[t.type]
          )}
        >
          {icons[t.type]}
          <div className="flex-1">
            {t.title && <p className="font-semibold text-sm">{t.title}</p>}
            {t.description && <p className="text-sm text-muted-foreground mt-1">{t.description}</p>}
          </div>
          <button onClick={() => remove(t.id)} className="text-muted-foreground hover:text-foreground">
            <X className="h-4 w-4" />
          </button>
        </div>
      ))}
    </div>
  )
}
