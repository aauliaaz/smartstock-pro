import { useState } from "react"
import { useNavigate } from "react-router-dom"
import { BoxesIcon, Loader2 } from "lucide-react"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { useAuth } from "@/context/AuthContext"
import { toast } from "react-hot-toast"
import type { AxiosError } from "axios"

export default function LoginPage() {
  const [email, setEmail] = useState("admin@smartstock.pro")
  const [password, setPassword] = useState("password")
  const [submitting, setSubmitting] = useState(false)
  const { login } = useAuth()
  const navigate = useNavigate()

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setSubmitting(true)
    try {
      await login({ email, password })
      toast.success("Login berhasil")
      navigate("/")
    } catch (err) {
      const ax = err as AxiosError<{ message?: string; errors?: Record<string, string[]> }>
      const errs = ax.response?.data?.errors
      const firstErr = errs ? Object.values(errs).flat()[0] : ax.response?.data?.message
      toast.error(firstErr ?? "Login gagal. Periksa email dan password Anda.")
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 flex items-center justify-center p-4">
      <div className="w-full max-w-md">
        <div className="text-center mb-8">
          <div className="inline-flex items-center justify-center w-16 h-16 rounded-full bg-primary text-primary-foreground mb-4">
            <BoxesIcon className="h-8 w-8" />
          </div>
          <h1 className="text-3xl font-bold tracking-tight">SmartStock Pro</h1>
          <p className="text-muted-foreground mt-1">Sistem Manajemen Inventaris PT Maju Bersama Digital</p>
        </div>

        <Card>
          <CardHeader>
            <CardTitle>Masuk ke Sistem</CardTitle>
            <CardDescription>Gunakan akun yang telah diberikan administrator.</CardDescription>
          </CardHeader>
          <CardContent>
            <form onSubmit={handleSubmit} className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="email">Email</Label>
                <Input id="email" type="email" placeholder="anda@smartstock.id" value={email} onChange={(e) => setEmail(e.target.value)} required autoFocus />
              </div>
              <div className="space-y-2">
                <Label htmlFor="password">Password</Label>
                <Input id="password" type="password" value={password} onChange={(e) => setPassword(e.target.value)} required />
              </div>
              <Button type="submit" className="w-full" disabled={submitting}>
                {submitting && <Loader2 className="h-4 w-4 animate-spin mr-2" />} Login
              </Button>
            </form>

            <div className="mt-6 border-t pt-4">
              <p className="text-xs font-semibold mb-2 text-muted-foreground uppercase tracking-wider">Akun Demo:</p>
              <div className="grid grid-cols-1 gap-1 text-xs text-muted-foreground">
                <p>👤 <strong>Admin:</strong> admin@smartstock.pro / password</p>
                <p>👔 <strong>Manajer:</strong> manager.sby@smartstock.pro / password</p>
                <p>📦 <strong>Staf:</strong> staff.jkt@smartstock.pro / password</p>
                <p>👀 <strong>Viewer:</strong> viewer@smartstock.pro / password</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <p className="text-center text-xs text-muted-foreground mt-6">
          © {new Date().getFullYear()} PT Maju Bersama Digital. SmartStock Pro v1.0
        </p>
      </div>
    </div>
  )
}
