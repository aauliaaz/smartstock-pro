import { useEffect, useState } from "react"
import { Link } from "react-router-dom"
import { Plus, Search, Edit, Trash2, AlertTriangle, Filter } from "lucide-react"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Card } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog"
import { Label } from "@/components/ui/label"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import client from "@/api/client"
import { formatCurrency, formatNumber } from "@/lib/utils"
import { toast } from "react-hot-toast"
import { useAuth } from "@/context/AuthContext"
import type { Product, Category, PaginationMeta } from "@/types"

interface ProductForm {
  sku: string
  name: string
  description: string
  category_id: string
  unit: string
  min_threshold: string
  unit_price: string
  image: File | null
}

const emptyForm: ProductForm = {
  sku: "", name: "", description: "", category_id: "", unit: "pcs",
  min_threshold: "0", unit_price: "0", image: null
}

export default function ProductsPage() {
  const { hasRole } = useAuth()
  const canEdit = hasRole(["admin", "manager"])

  const [products, setProducts] = useState<Product[]>([])
  const [categories, setCategories] = useState<Category[]>([])
  const [meta, setMeta] = useState<PaginationMeta | null>(null)
  const [loading, setLoading] = useState(true)
  const [search, setSearch] = useState("")
  const [filterCategory, setFilterCategory] = useState<string>("all")
  const [page, setPage] = useState(1)
  const [showForm, setShowForm] = useState(false)
  const [editId, setEditId] = useState<number | null>(null)
  const [form, setForm] = useState<ProductForm>(emptyForm)

  const load = async () => {
    setLoading(true)
    try {
      const params: Record<string, string | number> = { page }
      if (search) params.search = search
      if (filterCategory !== "all") params.category_id = filterCategory
      const { data } = await client.get("/api/products", { params })
      setProducts(data.data)
      setMeta({
        current_page: data.current_page,
        last_page: data.last_page,
        per_page: data.per_page,
        total: data.total,
        from: data.from,
        to: data.to
      })
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    client.get("/api/categories").then(({ data }) => setCategories(data))
  }, [])

  useEffect(() => {
    const t = setTimeout(load, 300)
    return () => clearTimeout(t)
  }, [search, filterCategory, page])

  const openCreate = () => {
    setEditId(null)
    setForm(emptyForm)
    setShowForm(true)
  }

  const openEdit = (p: any) => {
    setEditId(p.id)
    setForm({
      sku: p.sku,
      name: p.name,
      description: p.description ?? "",
      category_id: String(p.category_id ?? ""),
      unit: p.unit || "pcs",
      min_threshold: String(p.min_threshold),
      unit_price: String(p.unit_price),
      image: null
    })
    setShowForm(true)
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    
    const formData = new FormData()
    formData.append('sku', form.sku)
    formData.append('name', form.name)
    formData.append('description', form.description)
    formData.append('category_id', form.category_id)
    formData.append('unit', form.unit)
    formData.append('min_threshold', form.min_threshold)
    formData.append('unit_price', form.unit_price)
    if (form.image) {
      formData.append('image', form.image)
    }

    try {
      if (editId) {
        // Use POST with _method=PUT for multipart/form-data support in Laravel
        formData.append('_method', 'PUT')
        await client.post(`/api/products/${editId}`, formData, {
          headers: { 'Content-Type': 'multipart/form-data' }
        })
        toast.success("Produk diupdate")
      } else {
        await client.post("/api/products", formData, {
          headers: { 'Content-Type': 'multipart/form-data' }
        })
        toast.success("Produk ditambahkan")
      }
      setShowForm(false)
      load()
    } catch (err: any) {
      toast.error(err.response?.data?.message || "Terjadi kesalahan")
    }
  }

  const handleDelete = async (id: number) => {
    if (!confirm("Yakin hapus produk ini?")) return
    try {
      await client.delete(`/api/products/${id}`)
      toast.success("Produk dihapus")
      load()
    } catch (err: any) {
      toast.error(err.response?.data?.message || "Terjadi kesalahan")
    }
  }

  return (
    <div className="space-y-6">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Produk</h1>
          <p className="text-muted-foreground">Katalog seluruh produk inventaris</p>
        </div>
        {canEdit && (
          <Button onClick={openCreate}>
            <Plus className="h-4 w-4 mr-2" /> Tambah Produk
          </Button>
        )}
      </div>

      <Card className="p-4">
        <div className="flex flex-col sm:flex-row gap-3">
          <div className="relative flex-1">
            <Search className="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
            <Input
              placeholder="Cari SKU atau nama..."
              value={search}
              onChange={(e) => { setSearch(e.target.value); setPage(1) }}
              className="pl-10"
            />
          </div>
          <Select value={filterCategory} onValueChange={(v) => { setFilterCategory(v); setPage(1) }}>
            <SelectTrigger className="w-full sm:w-[200px]">
              <SelectValue placeholder="Semua kategori" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">Semua kategori</SelectItem>
              {categories.map((c) => (
                <SelectItem key={c.id} value={String(c.id)}>{c.name}</SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
      </Card>

      <Card>
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>SKU</TableHead>
              <TableHead>Nama Produk</TableHead>
              <TableHead>Kategori</TableHead>
              <TableHead className="text-right">Stok</TableHead>
              <TableHead className="text-right">Min</TableHead>
              <TableHead className="text-right">Harga Unit</TableHead>
              <TableHead>Status</TableHead>
              {canEdit && <TableHead className="text-right">Aksi</TableHead>}
            </TableRow>
          </TableHeader>
          <TableBody>
            {loading ? (
              <TableRow><TableCell colSpan={canEdit ? 8 : 7} className="text-center py-8 text-muted-foreground">Memuat...</TableCell></TableRow>
            ) : products.length === 0 ? (
              <TableRow><TableCell colSpan={canEdit ? 8 : 7} className="text-center py-8 text-muted-foreground">Tidak ada produk.</TableCell></TableRow>
            ) : products.map((p: any) => (
              <TableRow key={p.id}>
                <TableCell className="font-mono text-xs">{p.sku}</TableCell>
                <TableCell className="font-medium">{p.name}</TableCell>
                <TableCell><Badge variant="outline">{p.category?.name}</Badge></TableCell>
                <TableCell className="text-right font-medium">{formatNumber(p.total_stock)} {p.unit || 'pcs'}</TableCell>
                <TableCell className="text-right text-muted-foreground">{p.min_threshold}</TableCell>
                <TableCell className="text-right">{formatCurrency(p.unit_price)}</TableCell>
                <TableCell>
                  {p.is_low_stock ? (
                    <Badge variant="warning" className="gap-1"><AlertTriangle className="h-3 w-3" />Stok Menipis</Badge>
                  ) : (
                    <Badge variant="success">Aman</Badge>
                  )}
                </TableCell>
                {canEdit && (
                  <TableCell className="text-right">
                    <div className="flex justify-end gap-1">
                      <Button size="icon" variant="ghost" onClick={() => openEdit(p)}>
                        <Edit className="h-4 w-4" />
                      </Button>
                      <Button size="icon" variant="ghost" onClick={() => handleDelete(p.id)}>
                        <Trash2 className="h-4 w-4 text-destructive" />
                      </Button>
                    </div>
                  </TableCell>
                )}
              </TableRow>
            ))}
          </TableBody>
        </Table>

        {meta && meta.last_page > 1 && (
          <div className="flex items-center justify-between p-4 border-t">
            <p className="text-sm text-muted-foreground">
              Halaman {meta.current_page} dari {meta.last_page} ({formatNumber(meta.total)} produk)
            </p>
            <div className="flex gap-2">
              <Button variant="outline" size="sm" onClick={() => setPage(p => p - 1)} disabled={meta.current_page === 1}>‹ Prev</Button>
              <Button variant="outline" size="sm" onClick={() => setPage(p => p + 1)} disabled={meta.current_page === meta.last_page}>Next ›</Button>
            </div>
          </div>
        )}
      </Card>

      <Dialog open={showForm} onOpenChange={setShowForm}>
        <DialogContent className="max-w-2xl">
          <DialogHeader>
            <DialogTitle>{editId ? "Edit Produk" : "Tambah Produk Baru"}</DialogTitle>
          </DialogHeader>
          <form onSubmit={handleSubmit} className="space-y-4">
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label htmlFor="sku">SKU</Label>
                <Input id="sku" value={form.sku} onChange={e => setForm({...form, sku: e.target.value})} required disabled={!!editId} />
              </div>
              <div className="space-y-2">
                <Label htmlFor="name">Nama Produk</Label>
                <Input id="name" value={form.name} onChange={e => setForm({...form, name: e.target.value})} required />
              </div>
              <div className="space-y-2">
                <Label htmlFor="category">Kategori</Label>
                <Select value={form.category_id} onValueChange={v => setForm({...form, category_id: v})}>
                  <SelectTrigger>
                    <SelectValue placeholder="Pilih kategori" />
                  </SelectTrigger>
                  <SelectContent>
                    {categories.map(c => (
                      <SelectItem key={c.id} value={String(c.id)}>{c.name}</SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
              <div className="space-y-2">
                <Label htmlFor="unit">Satuan (Unit)</Label>
                <Input id="unit" value={form.unit} onChange={e => setForm({...form, unit: e.target.value})} placeholder="pcs, box, kg..." required />
              </div>
              <div className="space-y-2">
                <Label htmlFor="min_threshold">Threshold Minimum</Label>
                <Input id="min_threshold" type="number" value={form.min_threshold} onChange={e => setForm({...form, min_threshold: e.target.value})} required />
              </div>
              <div className="space-y-2">
                <Label htmlFor="unit_price">Harga Unit</Label>
                <Input id="unit_price" type="number" value={form.unit_price} onChange={e => setForm({...form, unit_price: e.target.value})} required />
              </div>
            </div>
            <div className="space-y-2">
              <Label htmlFor="description">Deskripsi</Label>
              <Input id="description" value={form.description} onChange={e => setForm({...form, description: e.target.value})} />
            </div>
            <div className="space-y-2">
              <Label htmlFor="image">Gambar Produk</Label>
              <Input id="image" type="file" onChange={e => setForm({...form, image: e.target.files?.[0] || null})} accept="image/*" />
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
