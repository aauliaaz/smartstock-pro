import { useEffect, useState } from "react"
import { AlertCircle, ChevronDown, ChevronUp } from "lucide-react"
import { Card, CardHeader, CardTitle, CardContent } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import client from "@/api/client"
import { formatDate } from "@/lib/utils"

export default function ErrorLogsPage() {
  const [logs, setLogs] = useState<any[]>([])
  const [loading, setLoading] = useState(true)
  const [expanded, setExpanded] = useState<number | null>(null)

  useEffect(() => {
    client.get("/api/error-logs").then(({ data }) => {
      setLogs(data)
      setLoading(false)
    })
  }, [])

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold tracking-tight">Error Log</h1>
        <p className="text-muted-foreground">Log kesalahan sistem untuk debugging</p>
      </div>

      <div className="space-y-4">
        {loading ? (
          <p className="text-center py-8 text-muted-foreground">Memuat...</p>
        ) : logs.length === 0 ? (
          <Card className="p-8 text-center text-muted-foreground">
            <AlertCircle className="h-12 w-12 mx-auto mb-4 opacity-20" />
            Tidak ada error log tercatat.
          </Card>
        ) : logs.map((log) => (
          <Card key={log.id} className="overflow-hidden">
            <CardHeader className="p-4 cursor-pointer hover:bg-muted/50 transition-colors" onClick={() => setExpanded(expanded === log.id ? null : log.id)}>
              <div className="flex items-center justify-between gap-4">
                <div className="flex items-center gap-3">
                  <Badge variant={log.severity === 'CRITICAL' ? 'destructive' : 'warning'}>{log.severity}</Badge>
                  <div>
                    <CardTitle className="text-sm font-semibold">{log.message}</CardTitle>
                    <p className="text-xs text-muted-foreground mt-1">{log.method} {log.url} • {formatDate(log.created_at, true)}</p>
                  </div>
                </div>
                {expanded === log.id ? <ChevronUp className="h-4 w-4" /> : <ChevronDown className="h-4 w-4" />}
              </div>
            </CardHeader>
            {expanded === log.id && (
              <CardContent className="p-4 bg-slate-950 text-slate-50 font-mono text-xs overflow-x-auto border-t border-slate-800">
                <p className="mb-2 text-slate-400"># STACK TRACE:</p>
                <pre className="whitespace-pre-wrap">{log.stack_trace}</pre>
                {log.payload && (
                  <>
                    <p className="mt-4 mb-2 text-slate-400"># PAYLOAD:</p>
                    <pre>{JSON.stringify(log.payload, null, 2)}</pre>
                  </>
                )}
              </CardContent>
            )}
          </Card>
        ))}
      </div>
    </div>
  )
}
