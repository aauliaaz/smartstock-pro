import React, { useEffect, useState } from 'react';
import axios from 'axios';
import { Bar } from 'react-chartjs-2';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    BarElement,
    Title,
    Tooltip,
    Legend,
} from 'chart.js';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/Components/ui/Card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/Components/ui/Table";
import { Badge } from "@/Components/ui/Badge";
import { Package, Warehouse, BarChart3, AlertTriangle, Cpu, HardDrive, Activity, Banknote } from "lucide-react";

ChartJS.register(
    CategoryScale,
    LinearScale,
    BarElement,
    Title,
    Tooltip,
    Legend
);

import { MapContainer, TileLayer, Marker, Popup } from 'react-leaflet';
import 'leaflet/dist/leaflet.css';
import L from 'leaflet';

// Fix Leaflet icon issue
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon-2x.png',
    iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon.png',
    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
});

const Dashboard = () => {
    const [data, setData] = useState(null);
    const [stats, setStats] = useState({ cpu: '0%', memory: '0 GB / 4 GB', uptime: '100%', response_time: '0ms' });

    useEffect(() => {
        axios.get('/api/dashboard')
            .then(res => setData(res.data))
            .catch(err => console.error(err));

        const fetchStats = () => {
            axios.get('/api/system/stats').then(res => setStats(res.data));
        };
        
        fetchStats();
        const interval = setInterval(fetchStats, 5000);
        return () => clearInterval(interval);
    }, []);

    if (!data) return <div className="flex items-center justify-center h-screen text-slate-500">Loading Dashboard...</div>;

    const chartData = {
        labels: data.warehouse_distribution.map(w => w.name),
        datasets: [
            {
                label: 'Stock Level',
                data: data.warehouse_distribution.map(w => w.stock),
                backgroundColor: 'rgba(15, 23, 42, 0.8)',
                borderRadius: 4,
            },
        ],
    };

    return (
        <div className="space-y-8 p-8 pt-6">
            <div className="flex items-center justify-between space-y-2">
                <h2 className="text-3xl font-bold tracking-tight">Dashboard</h2>
            </div>
            
            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle className="text-sm font-medium">Total Products</CardTitle>
                        <Package className="h-4 w-4 text-slate-500" />
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-bold">{data.summary.total_products}</div>
                        <p className="text-xs text-slate-500">Active products in catalog</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle className="text-sm font-medium">Total Warehouses</CardTitle>
                        <Warehouse className="h-4 w-4 text-slate-500" />
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-bold">{data.summary.total_warehouses}</div>
                        <p className="text-xs text-slate-500">Distribution centers nationwide</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle className="text-sm font-medium">Overall Stock</CardTitle>
                        <BarChart3 className="h-4 w-4 text-slate-500" />
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-bold">{data.summary.total_stock}</div>
                        <p className="text-xs text-slate-500">Total units across all warehouses</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle className="text-sm font-medium">Inventory Value (FIFO)</CardTitle>
                        <Banknote className="h-4 w-4 text-green-600" />
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-bold">
                            {new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(data.summary.total_value)}
                        </div>
                        <p className="text-xs text-slate-500">Total asset value based on FIFO</p>
                    </CardContent>
                </Card>
            </div>

            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Card className="bg-slate-900 text-white">
                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle className="text-xs font-medium text-slate-400">CPU Usage</CardTitle>
                        <Cpu className="h-4 w-4 text-blue-400" />
                    </CardHeader>
                    <CardContent>
                        <div className="text-xl font-bold">{stats.cpu}</div>
                        <div className="mt-2 h-1 w-full bg-slate-800 rounded-full overflow-hidden">
                            <div className="h-full bg-blue-500 transition-all duration-500" style={{ width: stats.cpu }}></div>
                        </div>
                    </CardContent>
                </Card>
                <Card className="bg-slate-900 text-white">
                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle className="text-xs font-medium text-slate-400">Memory</CardTitle>
                        <HardDrive className="h-4 w-4 text-green-400" />
                    </CardHeader>
                    <CardContent>
                        <div className="text-xl font-bold">{stats.memory}</div>
                        <div className="mt-2 h-1 w-full bg-slate-800 rounded-full overflow-hidden">
                            <div className="h-full bg-green-500 w-[30%]"></div>
                        </div>
                    </CardContent>
                </Card>
                <Card className="bg-slate-900 text-white">
                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle className="text-xs font-medium text-slate-400">System Uptime</CardTitle>
                        <Activity className="h-4 w-4 text-orange-400" />
                    </CardHeader>
                    <CardContent>
                        <div className="text-xl font-bold">{stats.uptime}</div>
                        <p className="text-[10px] text-slate-500">Response: {stats.response_time}</p>
                    </CardContent>
                </Card>
                <Card className="bg-slate-900 text-white border-blue-500/50 border">
                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle className="text-xs font-medium text-blue-400">Network Status</CardTitle>
                        <Globe className="h-4 w-4 text-blue-400 animate-pulse" />
                    </CardHeader>
                    <CardContent>
                        <div className="text-xl font-bold text-blue-400">CONNECTED</div>
                        <p className="text-[10px] text-slate-500">Latent region: Southeast Asia</p>
                    </CardContent>
                </Card>
            </div>

            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-7">
                <Card className="col-span-4">
                    <CardHeader>
                        <CardTitle>Warehouse Locations</CardTitle>
                        <CardDescription>Interactive map of our distribution centers.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="h-[350px] w-full rounded-md overflow-hidden border">
                            <MapContainer center={[-2.5489, 118.0149]} zoom={5} style={{ height: '100%', width: '100%' }}>
                                <TileLayer
                                    url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
                                    attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                                />
                                {data.warehouse_distribution.map(w => (
                                    <Marker key={w.id} position={[w.latitude, w.longitude]}>
                                        <Popup>
                                            <div className="text-sm">
                                                <div className="font-bold">{w.name}</div>
                                                <div>{w.city}</div>
                                                <div className="mt-1">Current Stock: <span className="font-semibold">{w.stock} units</span></div>
                                            </div>
                                        </Popup>
                                    </Marker>
                                ))}
                            </MapContainer>
                        </div>
                    </CardContent>
                </Card>

                <Card className="col-span-3">
                    <CardHeader>
                        <CardTitle>Stock Distribution</CardTitle>
                        <CardDescription>Inventory levels by location</CardDescription>
                    </CardHeader>
                    <CardContent className="pl-2">
                        <div className="h-[350px]">
                            <Bar 
                                data={chartData} 
                                options={{ 
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: { display: false }
                                    },
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            grid: { color: 'rgba(0,0,0,0.05)' }
                                        },
                                        x: {
                                            grid: { display: false }
                                        }
                                    }
                                }} 
                            />
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div className="grid gap-4 md:grid-cols-1 lg:grid-cols-7">
                <Card className="col-span-7">
                    <CardHeader className="flex flex-row items-center space-x-2">
                        <AlertTriangle className="h-5 w-5 text-red-500" />
                        <div>
                            <CardTitle>Critical Alerts</CardTitle>
                            <CardDescription>Items below minimum threshold</CardDescription>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Product</TableHead>
                                    <TableHead>Category</TableHead>
                                    <TableHead>Current Total Stock</TableHead>
                                    <TableHead>Min Threshold</TableHead>
                                    <TableHead className="text-right">Action</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {data.low_stock_alerts.length > 0 ? data.low_stock_alerts.map(p => (
                                    <TableRow key={p.id}>
                                        <TableCell className="font-medium">{p.name}</TableCell>
                                        <TableCell>{p.category?.name}</TableCell>
                                        <TableCell>
                                            <Badge variant="destructive">
                                                {p.stock_movements.reduce((acc, move) => move.type === 'IN' ? acc + move.quantity : acc - move.quantity, 0)}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>{p.min_threshold}</TableCell>
                                        <TableCell className="text-right">
                                            <Button variant="outline" size="sm">Restock</Button>
                                        </TableCell>
                                    </TableRow>
                                )) : (
                                    <TableRow>
                                        <TableCell colSpan={5} className="h-24 text-center text-slate-500">
                                            No critical alerts found.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </div>
    );
};

export default Dashboard;
