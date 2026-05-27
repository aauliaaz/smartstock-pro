import { useEffect, useState } from "react"
import { Bell, CheckCircle, Clock } from "lucide-react"
import { Button } from "@/components/ui/button"
import { Card } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import client from "@/api/client"
import { formatDate } from "@/lib/utils"
import { toast } from "react-hot-toast"

export default function NotificationsPage() {
  const [notifications, setNotifications] = useState<any[]>([])
  const [loading, setLoading] = useState(true)

  const load = async () => {
    setLoading(true)
    try {
      const { data } = await client.get("/api/notifications")
      setNotifications(data)
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => { load() }, [])

  const markRead = async (id: number) => {
    try {
      await client.patch(`/api/notifications/${id}/read`)
      load()
    } catch {}
  }

  const markAllRead = async () => {
    try {
      await client.post("/api/notifications/read-all")
      toast.success("Semua notifikasi ditandai dibaca")
      load()
    } catch {}
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Notifikasi</h1>
          <p className="text-muted-foreground">Pemberitahuan sistem dan alert stok</p>
        </div>
        <Button variant="outline" onClick={markAllRead} disabled={notifications.every(n => n.read_at)}>
          Tandai Semua Dibaca
        </Button>
      </div>

      <div className="space-y-4">
        {loading ? (
          <p className="text-center py-8 text-muted-foreground">Memuat...</p>
        ) : notifications.length === 0 ? (
          <Card className="p-8 text-center text-muted-foreground">
            <Bell className="h-12 w-12 mx-auto mb-4 opacity-20" />
            Tidak ada notifikasi baru.
          </Card>
        ) : notifications.map((n) => (
          <Card key={n.id} className={cn("p-4 transition-colors", !n.read_at && "bg-primary/5 border-primary/20")}>
            <div className="flex items-start gap-4">
              <div className={cn("mt-1 p-2 rounded-full", !n.read_at ? "bg-primary text-primary-foreground" : "bg-muted text-muted-foreground")}>
                <Bell className="h-4 w-4" />
              </div>
              <div className="flex-1">
                <div className="flex items-center justify-between gap-2">
                  <p className={cn("text-sm font-semibold", !n.read_at ? "text-primary" : "text-foreground")}>
                    {n.title || "Pemberitahuan Sistem"}
                  </p>
                  <span className="text-xs text-muted-foreground">{formatDate(n.created_at, true)}</span>
                </div>
                <p className="text-sm mt-1">{n.message}</p>
                {!n.read_at && (
                  <Button variant="link" size="sm" className="h-auto p-0 mt-2" onClick={() => markRead(n.id)}>
                    Tandai dibaca
                  </Button>
                )}
              </div>
            </div>
          </Card>
        ))}
      </div>
    </div>
  )
}

function cn(...classes: any[]) {
  return classes.filter(Boolean).join(" ")
}
