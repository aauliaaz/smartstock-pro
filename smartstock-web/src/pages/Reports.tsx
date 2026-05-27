import { FileText, Download, Eye } from "lucide-react"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"

export default function ReportsPage() {
  const downloadReport = (type: string) => {
    const url = `${import.meta.env.VITE_API_URL || 'http://localhost:8000'}/api/reports/${type}`
    window.open(url, '_blank')
  }

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold tracking-tight">Laporan PDF</h1>
        <p className="text-muted-foreground">Unduh laporan inventaris dalam format PDF</p>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <FileText className="h-5 w-5 text-blue-600" /> Laporan Produk
            </CardTitle>
            <CardDescription>Daftar seluruh produk, harga, dan total stok</CardDescription>
          </CardHeader>
          <CardContent>
            <Button className="w-full" onClick={() => downloadReport('products-pdf')}>
              <Download className="h-4 w-4 mr-2" /> Download PDF
            </Button>
          </CardContent>
        </Card>

        <Card className="opacity-60 grayscale pointer-events-none">
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <FileText className="h-5 w-5 text-green-600" /> Laporan Mutasi (Coming Soon)
            </CardTitle>
            <CardDescription>Ringkasan barang masuk/keluar periode ini</CardDescription>
          </CardHeader>
          <CardContent>
            <Button className="w-full" variant="outline" disabled>
              Coming Soon
            </Button>
          </CardContent>
        </Card>
      </div>
    </div>
  )
}
