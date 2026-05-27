import { useEffect, useState } from "react"
import { Plus, Edit, Trash2, MapPin } from "lucide-react"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Card } from "@/components/ui/card"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog"
import { Label } from "@/components/ui/label"
import client from "@/api/client"
import { toast } from "react-hot-toast"
import { useAuth } from "@/context/AuthContext"

export default function WarehousesPage() {
  const { hasRole } = useAuth()
  const canEdit = hasRole(["admin", "manager"])

  const [warehouses, setWarehouses] = useState<any[]>([])
  const [loading, setLoading] = useState(true)
  const [showForm, setShowForm] = useState(false)
  const [editId, setEditId] = useState<number | null>(null)
  const [form, setForm] = useState({ name: "", city: "", latitude: "", longitude: "" })

  const load = async () => {
    setLoading(true)
    try {
      const { data } = await client.get("/api/warehouses")
      setWarehouses(data)
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => { load() }, [])

  const openCreate = () => {
    setEditId(null)
    setForm({ name: "", city: "", latitude: "", longitude: "" })
    setShowForm(true)
  }

  const openEdit = (w: any) => {
    setEditId(w.id)
    setForm({ 
      name: w.name, 
      city: w.city || "", 
      latitude: String(w.latitude || ""), 
      longitude: String(w.longitude || "") 
    })
    setShowForm(true)
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    const payload = {
      ...form,
      latitude: form.latitude ? parseFloat(form.latitude) : null,
      longitude: form.longitude ? parseFloat(form.longitude) : null,
    }
    try {
      if (editId) {
        await client.put(`/api/warehouses/${editId}`, payload)
        toast.success("Gudang diupdate")
      } else {
        await client.post("/api/warehouses", payload)
        toast.success("Gudang ditambahkan")
      }
      setShowForm(false)
      load()
    } catch (err: any) {
      toast.error(err.response?.data?.message || "Terjadi kesalahan")
    }
  }

  const handleDelete = async (id: number) => {
    if (!confirm("Yakin hapus gudang ini?")) return
    try {
      await client.delete(`/api/warehouses/${id}`)
      toast.success("Gudang dihapus")
      load()
    } catch (err: any) {
      toast.error(err.response?.data?.message || "Terjadi kesalahan")
    }
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Gudang</h1>
          <p className="text-muted-foreground">Lokasi penyimpanan inventaris</p>
        </div>
        {canEdit && (
          <Button onClick={openCreate}>
            <Plus className="h-4 w-4 mr-2" /> Tambah Gudang
          </Button>
        )}
      </div>

      <Card>
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Nama Gudang</TableHead>
              <TableHead>Kota</TableHead>
              <TableHead>Koordinat</TableHead>
              {canEdit && <TableHead className="text-right">Aksi</TableHead>}
            </TableRow>
          </TableHeader>
          <TableBody>
            {loading ? (
              <TableRow><TableCell colSpan={4} className="text-center py-8 text-muted-foreground">Memuat...</TableCell></TableRow>
            ) : warehouses.length === 0 ? (
              <TableRow><TableCell colSpan={4} className="text-center py-8 text-muted-foreground">Tidak ada gudang.</TableCell></TableRow>
            ) : warehouses.map((w) => (
              <TableRow key={w.id}>
                <TableCell className="font-medium">{w.name}</TableCell>
                <TableCell>{w.city || "-"}</TableCell>
                <TableCell className="text-muted-foreground text-xs">
                  {w.latitude && w.longitude ? (
                    <span className="flex items-center gap-1">
                      <MapPin className="h-3 w-3" /> {w.latitude}, {w.longitude}
                    </span>
                  ) : "-"}
                </TableCell>
                {canEdit && (
                  <TableCell className="text-right">
                    <div className="flex justify-end gap-1">
                      <Button size="icon" variant="ghost" onClick={() => openEdit(w)}>
                        <Edit className="h-4 w-4" />
                      </Button>
                      <Button size="icon" variant="ghost" onClick={() => handleDelete(w.id)}>
                        <Trash2 className="h-4 w-4 text-destructive" />
                      </Button>
                    </div>
                  </TableCell>
                )}
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </Card>

      <Dialog open={showForm} onOpenChange={setShowForm}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>{editId ? "Edit Gudang" : "Tambah Gudang"}</DialogTitle>
          </DialogHeader>
          <form onSubmit={handleSubmit} className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="name">Nama Gudang</Label>
              <Input id="name" value={form.name} onChange={e => setForm({...form, name: e.target.value})} required />
            </div>
            <div className="space-y-2">
              <Label htmlFor="city">Kota</Label>
              <Input id="city" value={form.city} onChange={e => setForm({...form, city: e.target.value})} required />
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label htmlFor="latitude">Latitude</Label>
                <Input id="latitude" type="number" step="any" value={form.latitude} onChange={e => setForm({...form, latitude: e.target.value})} />
              </div>
              <div className="space-y-2">
                <Label htmlFor="longitude">Longitude</Label>
                <Input id="longitude" type="number" step="any" value={form.longitude} onChange={e => setForm({...form, longitude: e.target.value})} />
              </div>
            </div>
            <DialogFooter>
              <Button type="button" variant="outline" onClick={() => setShowForm(false)}>Batal</Button>
              <Button type="submit">Simpan</Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </div>
  )
}
