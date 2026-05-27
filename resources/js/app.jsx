import './bootstrap';
import '../css/app.css';

import React from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter, Routes, Route, Link, useLocation, Navigate } from 'react-router-dom';
import Dashboard from './Pages/Dashboard';
import Products from './Pages/Products';
import Login from './Pages/Auth/Login';
import InventoryMovement from './Pages/InventoryMovement';
import WarehouseTransfer from './Pages/WarehouseTransfer';
import Logs from './Pages/Logs';
import Settings from './Pages/Settings';
import Categories from './Pages/Categories';
import Suppliers from './Pages/Suppliers';
import Warehouses from './Pages/Warehouses';
import { cn } from "@/lib/utils";
import { LayoutDashboard, Package, ArrowLeftRight, FileText, Settings as SettingsIcon, User, LogOut, ArrowDownCircle, ArrowUpCircle, ScrollText, Bell, Tags, Truck, Warehouse as WarehouseIcon } from "lucide-react";
import { Badge } from "@/Components/ui/Badge";

const NavItem = ({ to, icon: Icon, children }) => {
    const location = useLocation();
    const isActive = location.pathname === to;
    
    return (
        <Link
            to={to}
            className={cn(
                "flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-all hover:bg-slate-100",
                isActive ? "bg-slate-100 text-slate-900" : "text-slate-500 hover:text-slate-900"
            )}
        >
            <Icon className="h-4 w-4" />
            {children}
        </Link>
    );
};

const AppLayout = ({ children, user, onLogout }) => {
    const [notifications, setNotifications] = React.useState([]);
    const [showNotifications, setShowNotifications] = React.useState(false);

    React.useEffect(() => {
        if (user) {
            axios.get('/api/notifications')
                .then(res => setNotifications(res.data))
                .catch(err => console.error(err));
        }
    }, [user]);

    const unreadCount = notifications.filter(n => !n.read_at).length;

    const markAsRead = async (id) => {
        try {
            await axios.patch(`/api/notifications/${id}/read`);
            setNotifications(notifications.map(n => n.id === id ? { ...n, read_at: new Date().toISOString() } : n));
        } catch (err) {
            console.error(err);
        }
    };

    if (!user) {
        return <Navigate to="/login" />;
    }

    const role = user.role?.slug;

    return (
        <div className="flex min-h-screen bg-slate-50/50">
            {/* Sidebar */}
            <aside className="hidden border-r bg-white md:block w-64 shrink-0">
                <div className="flex h-full flex-col">
                    <div className="flex h-14 items-center border-b px-6">
                        <Link to="/" className="flex items-center gap-2 font-semibold">
                            <Package className="h-6 w-6 text-blue-600" />
                            <span className="text-lg">SmartStock Pro</span>
                        </Link>
                    </div>
                    <div className="flex-1 overflow-auto py-4 px-4 space-y-1">
                        <NavItem to="/" icon={LayoutDashboard}>Dashboard</NavItem>
                        {(role === 'admin' || role === 'staff' || role === 'manager') && (
                            <div className="pt-2 pb-1 px-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Inventory</div>
                        )}
                        {(role === 'admin' || role === 'staff') && (
                            <>
                                <NavItem to="/products" icon={Package}>Products</NavItem>
                                <NavItem to="/categories" icon={Tags}>Categories</NavItem>
                                <NavItem to="/inventory/in" icon={ArrowDownCircle}>Stock In</NavItem>
                                <NavItem to="/inventory/out" icon={ArrowUpCircle}>Stock Out</NavItem>
                            </>
                        )}
                        {(role === 'admin' || role === 'manager' || role === 'staff') && (
                            <NavItem to="/transfers" icon={ArrowLeftRight}>Transfers</NavItem>
                        )}
                        
                        {(role === 'admin') && (
                            <>
                                <div className="pt-4 pb-1 px-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Master Data</div>
                                <NavItem to="/warehouses" icon={WarehouseIcon}>Warehouses</NavItem>
                                <NavItem to="/suppliers" icon={Truck}>Suppliers</NavItem>
                            </>
                        )}

                        <div className="pt-4 pb-1 px-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider">System</div>
                        <NavItem to="/reports" icon={FileText}>Reports</NavItem>
                        {(role === 'admin') && (
                            <NavItem to="/logs" icon={ScrollText}>System Logs</NavItem>
                        )}
                    </div>
                    <div className="p-4 border-t">
                        <NavItem to="/settings" icon={SettingsIcon}>Settings</NavItem>
                        <div className="mt-4 flex items-center justify-between gap-3 px-3 py-2">
                            <div className="flex items-center gap-3 overflow-hidden">
                                <div className="h-8 w-8 rounded-full bg-slate-200 flex items-center justify-center text-slate-500 font-bold uppercase">
                                    {user.name.charAt(0)}
                                </div>
                                <div className="flex-1 overflow-hidden">
                                    <p className="text-sm font-medium leading-none">{user.name}</p>
                                    <p className="text-xs text-slate-500 truncate">{user.role?.name}</p>
                                </div>
                            </div>
                            <button 
                                onClick={onLogout}
                                className="text-slate-400 hover:text-red-600 transition-colors"
                                title="Logout"
                            >
                                <LogOut className="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </aside>

            {/* Main Content */}
            <div className="flex flex-1 flex-col">
                <header className="flex h-14 items-center justify-between gap-4 border-b bg-white px-6">
                    <div className="flex items-center gap-4 md:hidden">
                        <Link to="/" className="flex items-center gap-2 font-semibold">
                            <Package className="h-6 w-6 text-blue-600" />
                            <span className="text-lg">SmartStock Pro</span>
                        </Link>
                    </div>
                    <div className="flex-1 hidden md:block">
                        <p className="text-sm text-slate-500 font-medium">System Status: <span className="text-green-600">All services operational</span></p>
                    </div>
                    <div className="flex items-center gap-4 relative">
                        <button 
                            className="relative p-2 text-slate-400 hover:text-slate-900 transition-colors"
                            onClick={() => setShowNotifications(!showNotifications)}
                        >
                            <Bell className="h-5 w-5" />
                            {unreadCount > 0 && (
                                <span className="absolute top-1 right-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-600 text-[10px] font-bold text-white ring-2 ring-white">
                                    {unreadCount}
                                </span>
                            )}
                        </button>

                        {showNotifications && (
                            <div className="absolute right-0 top-full mt-2 w-80 rounded-md border bg-white shadow-lg z-50 overflow-hidden">
                                <div className="flex items-center justify-between border-b px-4 py-2">
                                    <h3 className="text-sm font-bold">Notifications</h3>
                                    <button 
                                        className="text-[10px] text-blue-600 hover:underline"
                                        onClick={async () => {
                                            await axios.post('/notifications/read-all');
                                            setNotifications(notifications.map(n => ({ ...n, read_at: new Date().toISOString() })));
                                        }}
                                    >
                                        Mark all read
                                    </button>
                                </div>
                                <div className="max-h-64 overflow-y-auto">
                                    {notifications.length > 0 ? notifications.map(n => (
                                        <div 
                                            key={n.id} 
                                            className={cn(
                                                "px-4 py-3 text-xs border-b last:border-0 hover:bg-slate-50 cursor-pointer",
                                                !n.read_at && "bg-blue-50/50"
                                            )}
                                            onClick={() => markAsRead(n.id)}
                                        >
                                            <div className="font-bold mb-1">{n.title}</div>
                                            <div className="text-slate-600">{n.message}</div>
                                            <div className="text-[10px] text-slate-400 mt-1">{new Date(n.created_at).toLocaleString()}</div>
                                        </div>
                                    )) : (
                                        <div className="p-8 text-center text-slate-500 text-xs">No new notifications</div>
                                    )}
                                </div>
                            </div>
                        )}
                    </div>
                </header>
                <main className="flex-1 overflow-y-auto">
                    {children}
                </main>
            </div>
        </div>
    );
};

const App = () => {
    const [user, setUser] = React.useState(null);
    const [loading, setLoading] = React.useState(true);

    React.useEffect(() => {
        axios.get('/api/me')
            .then(res => {
                setUser(res.data);
                setLoading(false);
            })
            .catch(() => {
                setLoading(false);
            });
    }, []);

    const handleLogout = async () => {
        try {
            await axios.post('/logout');
            setUser(null);
            window.location.href = '/login';
        } catch (err) {
            console.error(err);
        }
    };

    if (loading) {
        return <div className="flex h-screen items-center justify-center">Loading...</div>;
    }

    return (
        <BrowserRouter>
            <Routes>
                <Route path="/login" element={user ? <Navigate to="/" /> : <Login onLogin={setUser} />} />
                <Route path="/*" element={
                    <AppLayout user={user} onLogout={handleLogout}>
                        <Routes>
                            <Route path="/" element={<Dashboard />} />
                            <Route path="/products" element={<Products />} />
                            <Route path="/categories" element={<Categories />} />
                            <Route path="/warehouses" element={<Warehouses />} />
                            <Route path="/suppliers" element={<Suppliers />} />
                            <Route path="/inventory/in" element={<InventoryMovement type="IN" />} />
                            <Route path="/inventory/out" element={<InventoryMovement type="OUT" />} />
                            <Route path="/transfers" element={<WarehouseTransfer />} />
                            <Route path="/reports" element={<div className="p-8">Reports Module (Use PDF Export on Products page for demo)</div>} />
                            <Route path="/logs" element={<Logs />} />
                            <Route path="/settings" element={<Settings />} />
                        </Routes>
                    </AppLayout>
                } />
            </Routes>
        </BrowserRouter>
    );
};

const root = createRoot(document.getElementById('app'));
root.render(<App />);
