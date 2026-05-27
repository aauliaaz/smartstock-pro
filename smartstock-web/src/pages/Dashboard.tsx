import { useEffect, useState } from "react"
import {
  Package, Boxes, AlertTriangle, TrendingUp,
} from "lucide-react"
import {
  BarChart, Bar, ResponsiveContainer,
  XAxis, YAxis, CartesianGrid, Tooltip, PieChart, Pie, Cell
} from "recharts"
import { MapContainer, TileLayer, Marker, Popup } from "react-leaflet"
import L from "leaflet"
import "leaflet/dist/leaflet.css"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import client from "@/api/client"
import { formatCurrency, formatNumber, formatDate } from "@/lib/utils"

// Fix Leaflet default icon path issue
L.Icon.Default.mergeOptions({
  iconRetinaUrl: "https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png",
  iconUrl: "https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png",
  shadowUrl: "https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png",
})

const PIE_COLORS = ["#3b82f6", "#10b981", "#f59e0b", "#ef4444", "#8b5cf6"]

export default function DashboardPage() {
  const [data, setData] = useState<any>(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    const load = async () => {
      try {
        const response = await client.get("/api/dashboard")
        setData(response.data)
      } catch (error) {
        console.error("Failed to fetch dashboard data", error)
      } finally {
        setLoading(false)
      }
    }
    load()
    const interval = setInterval(load, 60000)
    return () => clearInterval(interval)
  }, [])

  if (loading) return <div>Loading...</div>
  if (!data) return <div>Error loading data</div>

  const { summary, low_stock_alerts, recent_movements, warehouse_distribution } = data

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold tracking-tight">Dashboard</h1>
        <p className="text-muted-foreground">Ringkasan kondisi inventaris real-time</p>
      </div>

      {/* KPI Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2 space-y-0">
            <CardTitle className="text-sm font-medium">Total Produk</CardTitle>
            <Package className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{formatNumber(summary.total_products)}</div>
            <p className="text-xs text-muted-foreground">Aktif di seluruh sistem</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2 space-y-0">
            <CardTitle className="text-sm font-medium">Total Stok</CardTitle>
            <Boxes className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{formatNumber(summary.total_stock)}</div>
            <p className="text-xs text-muted-foreground">Unit di {summary.total_warehouses} gudang</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2 space-y-0">
            <CardTitle className="text-sm font-medium">Nilai Inventaris</CardTitle>
            <TrendingUp className="h-4 w-4 text-green-600" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{formatCurrency(summary.total_value)}</div>
            <p className="text-xs text-muted-foreground">Berdasarkan harga unit</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2 space-y-0">
            <CardTitle className="text-sm font-medium">Stok Menipis</CardTitle>
            <AlertTriangle className="h-4 w-4 text-yellow-600" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{formatNumber(low_stock_alerts.length)}</div>
            <p className="text-xs text-muted-foreground">Perlu restock segera</p>
          </CardContent>
        </Card>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <Card className="lg:col-span-2">
          <CardHeader>
            <CardTitle>Distribusi Stok per Gudang</CardTitle>
            <CardDescription>Pembagian kuantitas stok di setiap lokasi</CardDescription>
          </CardHeader>
          <CardContent>
            <ResponsiveContainer width="100%" height={280}>
              <BarChart data={warehouse_distribution}>
                <CartesianGrid strokeDasharray="3 3" />
                <XAxis dataKey="name" />
                <YAxis />
                <Tooltip />
                <Bar dataKey="stock" fill="#3b82f6" name="Stok" />
              </BarChart>
            </ResponsiveContainer>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Proporsi Stok</CardTitle>
            <CardDescription>Persentase stok per gudang</CardDescription>
          </CardHeader>
          <CardContent>
            <ResponsiveContainer width="100%" height={280}>
              <PieChart>
                <Pie data={warehouse_distribution} dataKey="stock" nameKey="name" cx="50%" cy="50%" outerRadius={80} label>
                  {warehouse_distribution.map((_: any, i: number) => <Cell key={i} fill={PIE_COLORS[i % PIE_COLORS.length]} />)}
                </Pie>
                <Tooltip />
              </PieChart>
            </ResponsiveContainer>
          </CardContent>
        </Card>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <Card>
          <CardHeader>
            <CardTitle>Transaksi Terbaru</CardTitle>
            <CardDescription>10 mutasi stok terakhir</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="space-y-4">
              {recent_movements.map((m: any) => (
                <div key={m.id} className="flex items-center justify-between border-b pb-2 last:border-0 last:pb-0">
                  <div>
                    <p className="text-sm font-medium">{m.product?.name}</p>
                    <p className="text-xs text-muted-foreground">{m.warehouse?.name} • {formatDate(m.created_at, true)}</p>
                  </div>
                  <div className="text-right">
                    <Badge variant={m.type === 'IN' ? 'success' : 'destructive'}>
                      {m.type === 'IN' ? '+' : '-'}{m.quantity}
                    </Badge>
                  </div>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Peta Lokasi Gudang</CardTitle>
            <CardDescription>Lokasi geografis fasilitas penyimpanan</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="h-[280px] rounded-md overflow-hidden border">
              <MapContainer center={[-2.5, 118]} zoom={4} style={{ height: "100%", width: "100%" }}>
                <TileLayer
                  attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
                  url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
                />
                {warehouse_distribution.map((w: any) => (
                  <Marker key={w.id} position={[w.latitude, w.longitude]}>
                    <Popup>
                      <div className="text-sm">
                        <p className="font-semibold">{w.name}</p>
                        <p>📍 {w.city}</p>
                        <p>📦 {formatNumber(w.stock)} unit</p>
                      </div>
                    </Popup>
                  </Marker>
                ))}
              </MapContainer>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Alert table */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <AlertTriangle className="h-5 w-5 text-yellow-600" /> Stok Menipis
          </CardTitle>
          <CardDescription>Produk yang mendekati atau di bawah ambang batas minimum</CardDescription>
        </CardHeader>
        <CardContent>
          {low_stock_alerts.length === 0 ? (
            <p className="text-sm text-muted-foreground py-4 text-center">✅ Tidak ada alert stok kritis.</p>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b">
                    <th className="text-left py-2 px-3 font-medium text-muted-foreground">SKU</th>
                    <th className="text-left py-2 px-3 font-medium text-muted-foreground">Produk</th>
                    <th className="text-right py-2 px-3 font-medium text-muted-foreground">Threshold</th>
                    <th className="text-center py-2 px-3 font-medium text-muted-foreground">Status</th>
                  </tr>
                </thead>
                <tbody>
                  {low_stock_alerts.map((p: any) => (
                    <tr key={p.id} className="border-b hover:bg-muted/50">
                      <td className="py-2 px-3 font-mono text-xs">{p.sku}</td>
                      <td className="py-2 px-3">{p.name}</td>
                      <td className="py-2 px-3 text-right">{p.min_threshold}</td>
                      <td className="py-2 px-3 text-center">
                        <Badge variant="warning">MENIPIS</Badge>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  )
}
