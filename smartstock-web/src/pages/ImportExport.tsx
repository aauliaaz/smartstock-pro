import { useState } from "react"
import { FileSpreadsheet, Upload, Download, Loader2, CheckCircle2 } from "lucide-react"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import client from "@/api/client"
import { toast } from "react-hot-toast"

export default function ImportExportPage() {
  const [file, setFile] = useState<File | null>(null)
  const [loading, setLoading] = useState(false)
  const [status, setStatus] = useState<'idle' | 'success'>('idle')

  const handleImport = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!file) return
    
    setLoading(true)
    const formData = new FormData()
    formData.append('file', file)

    try {
      await client.post("/api/products/import", formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })
      toast.success("Job import telah dijalankan di background")
      setStatus('success')
    } catch (err: any) {
      toast.error(err.response?.data?.message || "Gagal mengimport data")
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold tracking-tight">Import & Export</h1>
        <p className="text-muted-foreground">Kelola data masal menggunakan CSV atau Excel</p>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <Upload className="h-5 w-5 text-primary" /> Import Produk
            </CardTitle>
            <CardDescription>Upload file CSV/Excel untuk menambah produk secara masal</CardDescription>
          </CardHeader>
          <CardContent>
            {status === 'success' ? (
              <div className="text-center py-6 space-y-4">
                <CheckCircle2 className="h-12 w-12 text-green-500 mx-auto" />
                <p className="text-sm font-medium">Import sedang diproses!</p>
                <Button variant="outline" onClick={() => setStatus('idle')}>Import Lagi</Button>
              </div>
            ) : (
              <form onSubmit={handleImport} className="space-y-4">
                <div className="space-y-2">
                  <Label htmlFor="file">Pilih File (.csv, .xlsx)</Label>
                  <Input id="file" type="file" onChange={e => setFile(e.target.files?.[0] || null)} required />
                  <p className="text-[10px] text-muted-foreground italic">
                    Format: SKU, Nama, Kategori ID, Harga Unit, Threshold
                  </p>
                </div>
                <Button className="w-full" disabled={!file || loading}>
                  {loading ? <Loader2 className="h-4 w-4 animate-spin mr-2" /> : <FileSpreadsheet className="h-4 w-4 mr-2" />}
                  Mulai Import
                </Button>
              </form>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <Download className="h-5 w-5 text-blue-600" /> Export Data
            </CardTitle>
            <CardDescription>Download seluruh data katalog produk ke format Excel</CardDescription>
          </CardHeader>
          <CardContent className="flex items-center justify-center py-10">
            <Button variant="outline" className="h-24 w-full flex-col gap-2 border-dashed">
              <Download className="h-8 w-8" />
              <span>Export ke Excel (.xlsx)</span>
              <span className="text-[10px] text-muted-foreground">(Coming Soon)</span>
            </Button>
          </CardContent>
        </Card>
      </div>
    </div>
  )
}
