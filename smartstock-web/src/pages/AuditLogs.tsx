import { useEffect, useState } from "react"
import { ClipboardList, User as UserIcon, Activity } from "lucide-react"
import { Card } from "@/components/ui/card"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import client from "@/api/client"
import { formatDate } from "@/lib/utils"
import { Badge } from "@/components/ui/badge"

export default function AuditLogsPage() {
  const [logs, setLogs] = useState<any[]>([])
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    client.get("/api/audit-logs").then(({ data }) => {
      setLogs(data)
      setLoading(false)
    })
  }, [])

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold tracking-tight">Audit Log</h1>
        <p className="text-muted-foreground">Catatan aktivitas user dalam sistem</p>
      </div>

      <Card>
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Waktu</TableHead>
              <TableHead>User</TableHead>
              <TableHead>Aksi</TableHead>
              <TableHead>IP Address</TableHead>
              <TableHead>User Agent</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {loading ? (
              <TableRow><TableCell colSpan={5} className="text-center py-8 text-muted-foreground">Memuat...</TableCell></TableRow>
            ) : logs.length === 0 ? (
              <TableRow><TableCell colSpan={5} className="text-center py-8 text-muted-foreground">Belum ada log aktivitas.</TableCell></TableRow>
            ) : logs.map((log) => (
              <TableRow key={log.id}>
                <TableCell className="text-xs">{formatDate(log.created_at, true)}</TableCell>
                <TableCell>
                  <div className="flex items-center gap-2">
                    <UserIcon className="h-3 w-3 text-muted-foreground" />
                    <span className="text-sm font-medium">{log.user?.name || "System"}</span>
                  </div>
                </TableCell>
                <TableCell>
                  <Badge variant="outline">{log.action}</Badge>
                </TableCell>
                <TableCell className="text-xs font-mono">{log.ip_address}</TableCell>
                <TableCell className="text-xs text-muted-foreground max-w-[200px] truncate">{log.user_agent}</TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </Card>
    </div>
  )
}
