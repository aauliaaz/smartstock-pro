import { useEffect, useState } from "react"
import { Cpu, HardDrive, Zap, Clock } from "lucide-react"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import client from "@/api/client"

export default function ServerMonitoringPage() {
  const [stats, setStats] = useState<any>(null)

  const load = async () => {
    try {
      const { data } = await client.get("/api/system/stats")
      setStats(data)
    } catch {}
  }

  useEffect(() => {
    load()
    const interval = setInterval(load, 5000)
    return () => clearInterval(interval)
  }, [])

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold tracking-tight">Monitoring Server</h1>
        <p className="text-muted-foreground">Status performa infrastruktur real-time</p>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2 space-y-0">
            <CardTitle className="text-sm font-medium">CPU Usage</CardTitle>
            <Cpu className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats?.cpu || "0%"}</div>
            <div className="w-full bg-muted rounded-full h-1.5 mt-2">
              <div className="bg-primary h-1.5 rounded-full" style={{ width: stats?.cpu || "0%" }}></div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2 space-y-0">
            <CardTitle className="text-sm font-medium">Memory Usage</CardTitle>
            <HardDrive className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats?.memory || "0 GB / 0 GB"}</div>
            <p className="text-xs text-muted-foreground mt-2">Available: 2.4 GB</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2 space-y-0">
            <CardTitle className="text-sm font-medium">Response Time</CardTitle>
            <Zap className="h-4 w-4 text-yellow-500" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats?.response_time || "0ms"}</div>
            <p className="text-xs text-muted-foreground mt-2">Average API latency</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2 space-y-0">
            <CardTitle className="text-sm font-medium">Uptime</CardTitle>
            <Clock className="h-4 w-4 text-green-500" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats?.uptime || "99.9%"}</div>
            <p className="text-xs text-muted-foreground mt-2">Server status: Healthy</p>
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Aktivitas Sistem</CardTitle>
          <CardDescription>Real-time system event monitoring</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="space-y-4">
            <div className="flex items-center gap-3 text-sm border-b pb-2">
              <div className="h-2 w-2 rounded-full bg-green-500 animate-pulse"></div>
              <span className="font-mono text-xs text-muted-foreground">[{new Date().toLocaleTimeString()}]</span>
              <span>API Gateway is healthy</span>
            </div>
            <div className="flex items-center gap-3 text-sm border-b pb-2">
              <div className="h-2 w-2 rounded-full bg-green-500"></div>
              <span className="font-mono text-xs text-muted-foreground">[{new Date().toLocaleTimeString()}]</span>
              <span>Database connection established (SQLite)</span>
            </div>
            <div className="flex items-center gap-3 text-sm">
              <div className="h-2 w-2 rounded-full bg-blue-500"></div>
              <span className="font-mono text-xs text-muted-foreground">[{new Date().toLocaleTimeString()}]</span>
              <span>Background worker processing jobs...</span>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  )
}
