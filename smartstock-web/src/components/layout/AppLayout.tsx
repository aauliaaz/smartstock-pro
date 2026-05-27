import { useEffect, useState } from "react"
import { Link, NavLink, Outlet, useNavigate } from "react-router-dom"
import {
  LayoutDashboard, Package, Tags, Warehouse, Truck, Users as UsersIcon,
  ArrowDownToLine, ArrowUpFromLine, FileSpreadsheet, FileText,
  Bell, ClipboardList, AlertCircle, Activity, Settings, LogOut, Menu,
  ChevronDown, BoxesIcon, Building2
} from "lucide-react"
import { useAuth } from "@/context/AuthContext"
import client from "@/api/client"
import { Button } from "@/components/ui/button"
import { Badge } from "@/components/ui/badge"
import {
  DropdownMenu, DropdownMenuContent, DropdownMenuItem,
  DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger
} from "@/components/ui/dropdown-menu"
import { cn } from "@/lib/utils"

type Role = "admin" | "manager" | "staff" | "viewer"

interface NavItem {
  to: string
  label: string
  icon: React.ComponentType<{ className?: string }>
  roles: Role[]
}

const navGroups: { label: string; items: NavItem[] }[] = [
  {
    label: "Utama",
    items: [
      { to: "/", label: "Dashboard", icon: LayoutDashboard, roles: ["admin", "manager", "staff", "viewer"] },
    ],
  },
  {
    label: "Inventaris",
    items: [
      { to: "/products", label: "Produk", icon: Package, roles: ["admin", "manager", "staff", "viewer"] },
      { to: "/categories", label: "Kategori", icon: Tags, roles: ["admin", "manager", "staff", "viewer"] },
      { to: "/warehouses", label: "Gudang", icon: Warehouse, roles: ["admin", "manager", "staff", "viewer"] },
      { to: "/suppliers", label: "Supplier", icon: Building2, roles: ["admin", "manager", "staff", "viewer"] },
    ],
  },
  {
    label: "Transaksi",
    items: [
      { to: "/stock-in", label: "Barang Masuk", icon: ArrowDownToLine, roles: ["admin", "manager", "staff"] },
      { to: "/stock-out", label: "Barang Keluar", icon: ArrowUpFromLine, roles: ["admin", "manager", "staff"] },
      { to: "/transfers", label: "Transfer Gudang", icon: Truck, roles: ["admin", "manager", "staff", "viewer"] },
    ],
  },
  {
    label: "Data & Laporan",
    items: [
      { to: "/import", label: "Import Data", icon: FileSpreadsheet, roles: ["admin", "manager", "staff"] },
      { to: "/reports", label: "Laporan PDF", icon: FileText, roles: ["admin", "manager", "staff", "viewer"] },
    ],
  },
  {
    label: "Sistem",
    items: [
      { to: "/users", label: "Manajemen User", icon: UsersIcon, roles: ["admin"] },
      { to: "/audit-logs", label: "Audit Log", icon: ClipboardList, roles: ["admin", "manager"] },
      { to: "/error-logs", label: "Error Log", icon: AlertCircle, roles: ["admin"] },
      { to: "/monitoring", label: "Monitoring Server", icon: Activity, roles: ["admin"] },
    ],
  },
]

export default function AppLayout() {
  const { user, logout } = useAuth()
  const navigate = useNavigate()
  const [unreadCount, setUnreadCount] = useState(0)
  const [sidebarOpen, setSidebarOpen] = useState(true)

  useEffect(() => {
    const fetchNotifications = async () => {
      try {
        const { data } = await client.get("/api/notifications")
        const unread = data.filter((n: any) => !n.read_at).length
        setUnreadCount(unread)
      } catch {}
    }
    fetchNotifications()
    const interval = setInterval(fetchNotifications, 30000)
    return () => clearInterval(interval)
  }, [])

  const handleLogout = async () => {
    await logout()
    navigate("/login")
  }

  const role = (user?.role?.slug ?? "viewer") as Role

  return (
    <div className="min-h-screen bg-muted/30">
      {/* Top Bar */}
      <header className="sticky top-0 z-30 flex h-14 items-center gap-4 border-b bg-background px-4 lg:px-6">
        <Button variant="ghost" size="icon" onClick={() => setSidebarOpen(!sidebarOpen)} className="lg:hidden">
          <Menu className="h-5 w-5" />
        </Button>
        <Link to="/" className="flex items-center gap-2 font-semibold">
          <BoxesIcon className="h-6 w-6 text-primary" />
          <span className="hidden sm:inline">SmartStock Pro</span>
        </Link>

        <div className="ml-auto flex items-center gap-3">
          <Link to="/notifications" className="relative">
            <Button variant="ghost" size="icon">
              <Bell className="h-5 w-5" />
              {unreadCount > 0 && (
                <Badge variant="destructive" className="absolute -top-1 -right-1 h-5 min-w-5 px-1 text-[10px]">
                  {unreadCount > 99 ? "99+" : unreadCount}
                </Badge>
              )}
            </Button>
          </Link>

          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button variant="ghost" className="flex items-center gap-2 h-9">
                <div className="h-7 w-7 rounded-full bg-primary text-primary-foreground flex items-center justify-center text-xs font-semibold">
                  {user?.name?.charAt(0).toUpperCase()}
                </div>
                <div className="text-left hidden sm:block">
                  <p className="text-sm font-medium leading-none">{user?.name}</p>
                  <p className="text-xs text-muted-foreground">{user?.role?.name}</p>
                </div>
                <ChevronDown className="h-4 w-4 opacity-50" />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-56">
              <DropdownMenuLabel>
                <p className="text-sm">{user?.email}</p>
              </DropdownMenuLabel>
              <DropdownMenuSeparator />
              <DropdownMenuItem onClick={() => navigate("/settings")}>
                <Settings className="h-4 w-4 mr-2" /> Pengaturan
              </DropdownMenuItem>
              <DropdownMenuSeparator />
              <DropdownMenuItem onClick={handleLogout} className="text-destructive">
                <LogOut className="h-4 w-4 mr-2" /> Logout
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      </header>

      <div className="flex">
        {/* Sidebar */}
        <aside
          className={cn(
            "fixed lg:sticky top-14 z-20 h-[calc(100vh-3.5rem)] w-64 border-r bg-background overflow-y-auto transition-transform",
            sidebarOpen ? "translate-x-0" : "-translate-x-full lg:translate-x-0"
          )}
        >
          <nav className="p-3 space-y-4">
            {navGroups.map((group) => {
              const visibleItems = group.items.filter((i) => i.roles.includes(role))
              if (visibleItems.length === 0) return null
              return (
                <div key={group.label}>
                  <p className="px-3 py-1 text-xs font-semibold uppercase text-muted-foreground tracking-wider">
                    {group.label}
                  </p>
                  <div className="space-y-0.5">
                    {visibleItems.map((item) => (
                      <NavLink
                        key={item.to}
                        to={item.to}
                        end={item.to === "/"}
                        className={({ isActive }) =>
                          cn(
                            "flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors",
                            isActive
                              ? "bg-primary text-primary-foreground"
                              : "text-muted-foreground hover:bg-accent hover:text-accent-foreground"
                          )
                        }
                      >
                        <item.icon className="h-4 w-4" />
                        {item.label}
                      </NavLink>
                    ))}
                  </div>
                </div>
              )
            })}
          </nav>
        </aside>

        {/* Main content */}
        <main className="flex-1 lg:ml-0 p-4 lg:p-6 max-w-full overflow-x-hidden">
          <Outlet />
        </main>
      </div>
    </div>
  )
}
