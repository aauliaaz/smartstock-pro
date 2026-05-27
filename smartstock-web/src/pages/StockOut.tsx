import { useEffect, useState } from "react"
import { Plus, Search, ArrowUpFromLine } from "lucide-react"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Card } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog"
import { Label } from "@/components/ui/label"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import client from "@/api/client"
import { formatNumber, formatDate } from "@/lib/utils"
import { toast } from "react-hot-toast"
import { useAuth } from "@/context/AuthContext"

export default function StockOutPage() {
  const { hasRole } = useAuth()
  const canEdit = hasRole(["admin", "manager", "staff"])

  const [movements, setMovements] = useState<any[]>([])
  const [products, setProducts] = useState<any[]>([])
  const [warehouses, setWarehouses] = useState<any[]>([])
  const [loading, setLoading] = useState(true)
  const [showForm, setShowForm] = useState(false)
  const [meta, setMeta] = useState<any>(null)
  const [page, setPage] = useState(1)
  const [form, setForm] = useState({
    product_id: "",
    warehouse_id: "",
    quantity: "1",
    reference: "",
    notes: ""
  })

  const load = async () => {
    setLoading(true)
    try {
      const { data } = await client.get("/api/stock-movements", { params: { type: "OUT", page } })
      setMovements(data.data)
      setMeta({ current_page: data.current_page, last_page: data.last_page, total: data.total })
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    load()
    client.get("/api/products").then(({ data }) => setProducts(data.data || data))
    client.get("/api/warehouses").then(({ data }) => setWarehouses(data))
  }, [page])

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    try {
      await client.post("/api/stock-movements", {
        ...form,
        type: "OUT",
        product_id: parseInt(form.product_id),
        warehouse_id: parseInt(form.warehouse_id),
        quantity: parseInt(form.quantity)
      })
      toast.success("Barang keluar berhasil dicatat")
      setShowForm(false)
      setForm({ product_id: "", warehouse_id: "", quantity: "1", reference: "", notes: "" })
      load()
    } catch (err: any) {
      toast.error(err.response?.data?.message || "Terjadi kesalahan (Cek stok di gudang)")
    }
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Barang Keluar (Stock Out)</h1>
          <p className="text-muted-foreground">Catat pengurangan stok barang dari gudang</p>
        </div>
        {canEdit && (
          <Button onClick={() => setShowForm(true)} variant="destructive">
            <Plus className="h-4 w-4 mr-2" /> Catat Barang Keluar
          </Button>
        )}
      </div>

      <Card>
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Tanggal</TableHead>
              <TableHead>Produk</TableHead>
              <TableHead>Gudang</TableHead>
              <TableHead className="text-right">Jumlah</TableHead>
              <TableHead>Referensi</TableHead>
              <TableHead>Catatan</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {loading ? (
              <TableRow><TableCell colSpan={6} className="text-center py-8 text-muted-foreground">Memuat...</TableCell></TableRow>
            ) : movements.length === 0 ? (
              <TableRow><TableCell colSpan={6} className="text-center py-8 text-muted-foreground">Belum ada data barang keluar.</TableCell></TableRow>
            ) : movements.map((m) => (
              <TableRow key={m.id}>
                <TableCell className="text-xs">{formatDate(m.created_at, true)}</TableCell>
                <TableCell className="font-medium">{m.product?.name}</TableCell>
                <TableCell><Badge variant="outline">{m.warehouse?.name}</Badge></TableCell>
                <TableCell className="text-right font-bold text-red-600">-{formatNumber(m.quantity)}</TableCell>
                <TableCell className="text-xs text-muted-foreground">{m.reference || "-"}</TableCell>
                <TableCell className="text-xs text-muted-foreground">{m.notes || "-"}</TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
        
        {meta && meta.last_page > 1 && (
          <div className="flex items-center justify-between p-4 border-t">
            <p className="text-sm text-muted-foreground">
              Halaman {meta.current_page} dari {meta.last_page}
            </p>
            <div className="flex gap-2">
              <Button variant="outline" size="sm" onClick={() => setPage(p => p - 1)} disabled={meta.current_page === 1}>‹ Prev</Button>
              <Button variant="outline" size="sm" onClick={() => setPage(p => p + 1)} disabled={meta.current_page === meta.last_page}>Next ›</Button>
            </div>
          </div>
        )}
      </Card>

      <Dialog open={showForm} onOpenChange={setShowForm}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Form Barang Keluar</DialogTitle>
          </DialogHeader>
          <form onSubmit={handleSubmit} className="space-y-4">
            <div className="space-y-2">
              <Label>Produk</Label>
              <Select value={form.product_id} onValueChange={v => setForm({...form, product_id: v})}>
                <SelectTrigger><SelectValue placeholder="Pilih produk" /></SelectTrigger>
                <SelectContent>
                  {products.map(p => <SelectItem key={p.id} value={String(p.id)}>{p.sku} - {p.name}</SelectItem>)}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-2">
              <Label>Gudang Asal</Label>
              <Select value={form.warehouse_id} onValueChange={v => setForm({...form, warehouse_id: v})}>
                <SelectTrigger><SelectValue placeholder="Pilih gudang" /></SelectTrigger>
                <SelectContent>
                  {warehouses.map(w => <SelectItem key={w.id} value={String(w.id)}>{w.name}</SelectItem>)}
                </SelectContent>
              </Select>
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label htmlFor="qty">Jumlah</Label>
                <Input id="qty" type="number" value={form.quantity} onChange={e => setForm({...form, quantity: e.target.value})} required />
              </div>
              <div className="space-y-2">
                <Label htmlFor="ref">No. Referensi / Invoice</Label>
                <Input id="ref" value={form.reference} onChange={e => setForm({...form, reference: e.target.value})} placeholder="INV-12345" />
              </div>
            </div>
            <div className="space-y-2">
              <Label htmlFor="notes">Catatan / Tujuan</Label>
              <Input id="notes" value={form.notes} onChange={e => setForm({...form, notes: e.target.value})} />
            </div>
            <DialogFooter>
              <Button type="button" variant="outline" onClick={() => setShowForm(false)}>Batal</Button>
              <Button type="submit" variant="destructive">Simpan Stok Keluar</Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </div>
  )
}
