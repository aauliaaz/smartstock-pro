import { useEffect, useState } from "react"
import { Plus, Trash2, CheckCircle, Clock } from "lucide-react"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Card } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog"
import { Label } from "@/components/ui/label"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import client from "@/api/client"
import { formatDate } from "@/lib/utils"
import { toast } from "react-hot-toast"
import { useAuth } from "@/context/AuthContext"

export default function StockTransferPage() {
  const { hasRole } = useAuth()
  const canApprove = hasRole(["admin", "manager"])
  const canRequest = hasRole(["admin", "manager", "staff"])

  const [transfers, setTransfers] = useState<any[]>([])
  const [products, setProducts] = useState<any[]>([])
  const [warehouses, setWarehouses] = useState<any[]>([])
  const [loading, setLoading] = useState(true)
  const [showForm, setShowForm] = useState(false)
  const [meta, setMeta] = useState<any>(null)
  const [page, setPage] = useState(1)
  
  const [form, setForm] = useState({
    from_warehouse_id: "",
    to_warehouse_id: "",
    notes: "",
    items: [{ product_id: "", quantity: "1" }]
  })

  const load = async () => {
    setLoading(true)
    try {
      const { data } = await client.get("/api/transfers", { params: { page } })
      setTransfers(data.data)
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

  const addItem = () => {
    setForm({ ...form, items: [...form.items, { product_id: "", quantity: "1" }] })
  }

  const removeItem = (index: number) => {
    const newItems = [...form.items]
    newItems.splice(index, 1)
    setForm({ ...form, items: newItems })
  }

  const updateItem = (index: number, field: string, value: string) => {
    const newItems = [...form.items]
    newItems[index] = { ...newItems[index], [field]: value }
    setForm({ ...form, items: newItems })
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    if (form.from_warehouse_id === form.to_warehouse_id) {
      toast.error("Gudang asal dan tujuan tidak boleh sama")
      return
    }
    try {
      await client.post("/api/transfers", {
        ...form,
        from_warehouse_id: parseInt(form.from_warehouse_id),
        to_warehouse_id: parseInt(form.to_warehouse_id),
        items: form.items.map(i => ({
          product_id: parseInt(i.product_id),
          quantity: parseInt(i.quantity)
        }))
      })
      toast.success("Permintaan transfer berhasil dikirim")
      setShowForm(false)
      setForm({ from_warehouse_id: "", to_warehouse_id: "", notes: "", items: [{ product_id: "", quantity: "1" }] })
      load()
    } catch (err: any) {
      toast.error(err.response?.data?.message || "Terjadi kesalahan")
    }
  }

  const handleApprove = async (id: number) => {
    try {
      await client.patch(`/api/transfers/${id}/approve`)
      toast.success("Transfer disetujui")
      load()
    } catch (err: any) {
      toast.error(err.response?.data?.message || "Terjadi kesalahan")
    }
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Transfer Gudang</h1>
          <p className="text-muted-foreground">Pindahkan stok antar lokasi gudang</p>
        </div>
        {canRequest && (
          <Button onClick={() => setShowForm(true)}>
            <Plus className="h-4 w-4 mr-2" /> Request Transfer
          </Button>
        )}
      </div>

      <Card>
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>No. Transfer</TableHead>
              <TableHead>Dari</TableHead>
              <TableHead>Ke</TableHead>
              <TableHead>Status</TableHead>
              <TableHead>User</TableHead>
              <TableHead>Tanggal</TableHead>
              {canApprove && <TableHead className="text-right">Aksi</TableHead>}
            </TableRow>
          </TableHeader>
          <TableBody>
            {loading ? (
              <TableRow><TableCell colSpan={7} className="text-center py-8 text-muted-foreground">Memuat...</TableCell></TableRow>
            ) : transfers.length === 0 ? (
              <TableRow><TableCell colSpan={7} className="text-center py-8 text-muted-foreground">Belum ada data transfer.</TableCell></TableRow>
            ) : transfers.map((t) => (
              <TableRow key={t.id}>
                <TableCell className="font-mono text-xs">{t.transfer_number}</TableCell>
                <TableCell>{t.from_warehouse?.name}</TableCell>
                <TableCell>{t.to_warehouse?.name}</TableCell>
                <TableCell>
                  {t.status === 'APPROVED' ? (
                    <Badge variant="success" className="gap-1"><CheckCircle className="h-3 w-3" /> Selesai</Badge>
                  ) : (
                    <Badge variant="warning" className="gap-1"><Clock className="h-3 w-3" /> Menunggu</Badge>
                  )}
                </TableCell>
                <TableCell className="text-xs">{t.user?.name}</TableCell>
                <TableCell className="text-xs">{formatDate(t.created_at, true)}</TableCell>
                {canApprove && (
                  <TableCell className="text-right">
                    {t.status === 'PENDING' && (
                      <Button size="sm" onClick={() => handleApprove(t.id)}>Setujui</Button>
                    )}
                  </TableCell>
                )}
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
        <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
          <DialogHeader>
            <DialogTitle>Permintaan Transfer Stok</DialogTitle>
          </DialogHeader>
          <form onSubmit={handleSubmit} className="space-y-4">
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label>Gudang Asal</Label>
                <Select value={form.from_warehouse_id} onValueChange={v => setForm({...form, from_warehouse_id: v})}>
                  <SelectTrigger><SelectValue placeholder="Pilih gudang asal" /></SelectTrigger>
                  <SelectContent>
                    {warehouses.map(w => <SelectItem key={w.id} value={String(w.id)}>{w.name}</SelectItem>)}
                  </SelectContent>
                </Select>
              </div>
              <div className="space-y-2">
                <Label>Gudang Tujuan</Label>
                <Select value={form.to_warehouse_id} onValueChange={v => setForm({...form, to_warehouse_id: v})}>
                  <SelectTrigger><SelectValue placeholder="Pilih gudang tujuan" /></SelectTrigger>
                  <SelectContent>
                    {warehouses.map(w => <SelectItem key={w.id} value={String(w.id)}>{w.name}</SelectItem>)}
                  </SelectContent>
                </Select>
              </div>
            </div>

            <div className="space-y-2">
              <div className="flex items-center justify-between">
                <Label>Daftar Barang</Label>
                <Button type="button" variant="outline" size="sm" onClick={addItem}>
                  <Plus className="h-4 w-4 mr-1" /> Tambah Produk
                </Button>
              </div>
              
              {form.items.map((item, index) => (
                <div key={index} className="flex gap-2 items-end border p-3 rounded-md">
                  <div className="flex-1 space-y-1">
                    <Label className="text-xs">Produk</Label>
                    <Select value={item.product_id} onValueChange={v => updateItem(index, 'product_id', v)}>
                      <SelectTrigger><SelectValue placeholder="Pilih produk" /></SelectTrigger>
                      <SelectContent>
                        {products.map(p => <SelectItem key={p.id} value={String(p.id)}>{p.sku} - {p.name}</SelectItem>)}
                      </SelectContent>
                    </Select>
                  </div>
                  <div className="w-24 space-y-1">
                    <Label className="text-xs">Jumlah</Label>
                    <Input type="number" value={item.quantity} onChange={e => updateItem(index, 'quantity', e.target.value)} required />
                  </div>
                  <Button type="button" variant="ghost" size="icon" onClick={() => removeItem(index)} disabled={form.items.length === 1}>
                    <Trash2 className="h-4 w-4 text-destructive" />
                  </Button>
                </div>
              ))}
            </div>

            <div className="space-y-2">
              <Label htmlFor="notes">Catatan</Label>
              <Input id="notes" value={form.notes} onChange={e => setForm({...form, notes: e.target.value})} />
            </div>

            <DialogFooter>
              <Button type="button" variant="outline" onClick={() => setShowForm(false)}>Batal</Button>
              <Button type="submit">Kirim Permintaan</Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </div>
  )
}
