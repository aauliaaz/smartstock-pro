import { Shield, UserPlus } from "lucide-react"
import { Button } from "@/components/ui/button"
import { Card, CardContent } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"

export default function UserManagementPage() {
  // Mock data for UI demo
  const mockUsers = [
    { id: 1, name: "Admin Utama", email: "admin@smartstock.id", role: "Admin", status: "Active" },
    { id: 2, name: "Budi Manajer", email: "manager@smartstock.id", role: "Manager", status: "Active" },
    { id: 3, name: "Siti Staf", email: "staff@smartstock.id", role: "Staff", status: "Active" },
  ]

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Manajemen User</h1>
          <p className="text-muted-foreground">Kelola hak akses dan akun pengguna</p>
        </div>
        <Button disabled>
          <UserPlus className="h-4 w-4 mr-2" /> Tambah User
        </Button>
      </div>

      <Card className="border-yellow-200 bg-yellow-50 dark:bg-yellow-900/10 dark:border-yellow-900/50">
        <CardContent className="p-4 flex items-center gap-3 text-yellow-800 dark:text-yellow-200 text-sm">
          <Shield className="h-5 w-5" />
          <p><strong>Catatan:</strong> Fitur ini saat ini hanya tampilan UI. Endpoint <code>UserController</code> belum tersedia di backend.</p>
        </CardContent>
      </Card>

      <Card>
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Nama</TableHead>
              <TableHead>Email</TableHead>
              <TableHead>Role</TableHead>
              <TableHead>Status</TableHead>
              <TableHead className="text-right">Aksi</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {mockUsers.map((u) => (
              <TableRow key={u.id}>
                <TableCell className="font-medium">{u.name}</TableCell>
                <TableCell>{u.email}</TableCell>
                <TableCell><Badge variant="secondary">{u.role}</Badge></TableCell>
                <TableCell><Badge variant="success">{u.status}</Badge></TableCell>
                <TableCell className="text-right">
                  <Button variant="ghost" size="sm" disabled>Edit</Button>
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </Card>
    </div>
  )
}
