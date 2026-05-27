import React, { useEffect, useState } from 'react';
import axios from 'axios';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/Components/ui/Card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/Components/ui/Table";
import { Button } from "@/Components/ui/Button";
import { Badge } from "@/Components/ui/Badge";
import { Plus, Search, Filter, MoreHorizontal, FileDown, FileUp, Loader2 } from "lucide-react";

const Products = () => {
    const [products, setProducts] = useState([]);
    const [loading, setLoading] = useState(true);
    const [importing, setImporting] = useState(false);

    useEffect(() => {
        fetchProducts();
    }, []);

    const fetchProducts = async () => {
        setLoading(true);
        try {
            const res = await axios.get('/api/products');
            setProducts(res.data.data);
        } catch (err) {
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    const handleExport = () => {
        window.open('/api/reports/products-pdf', '_blank');
    };

    const handleImport = async (e) => {
        const file = e.target.files[0];
        if (!file) return;

        setImporting(true);
        try {
            await axios.post('/api/products/import');
            alert('Import job started in background (Parallel Processing). Products will appear shortly.');
            setTimeout(() => {
                fetchProducts();
                setImporting(false);
            }, 3000);
        } catch (err) {
            alert('Error starting import');
            setImporting(false);
        }
    };

    if (loading) return <div className="flex items-center justify-center h-screen text-slate-500">Loading Products...</div>;

    return (
        <div className="space-y-6 p-8 pt-6">
            <div className="flex items-center justify-between">
                <div>
                    <h2 className="text-3xl font-bold tracking-tight">Products</h2>
                    <p className="text-slate-500 text-sm">Manage your inventory catalog and stock levels.</p>
                </div>
                <div className="flex items-center gap-2">
                    <Button variant="outline" size="sm" onClick={handleExport}>
                        <FileDown className="mr-2 h-4 w-4" /> Export PDF
                    </Button>
                    <div className="relative">
                        <input 
                            type="file" 
                            className="absolute inset-0 opacity-0 cursor-pointer" 
                            accept=".csv,.xlsx" 
                            onChange={handleImport}
                            disabled={importing}
                        />
                        <Button variant="outline" size="sm" disabled={importing}>
                            {importing ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <FileUp className="mr-2 h-4 w-4" />}
                            Import CSV
                        </Button>
                    </div>
                    <Button className="flex items-center gap-2">
                        <Plus className="h-4 w-4" /> Add Product
                    </Button>
                </div>
            </div>

            <Card>
                <CardHeader>
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-2 bg-slate-100 px-3 py-2 rounded-md w-full max-w-sm">
                            <Search className="h-4 w-4 text-slate-500" />
                            <input 
                                type="text" 
                                placeholder="Search products..." 
                                className="bg-transparent border-none focus:ring-0 text-sm w-full"
                            />
                        </div>
                        <Button variant="outline" size="sm" className="flex items-center gap-2">
                            <Filter className="h-4 w-4" /> Filter
                        </Button>
                    </div>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="w-[80px]">Image</TableHead>
                                <TableHead>Product Name</TableHead>
                                <TableHead>SKU</TableHead>
                                <TableHead>Category</TableHead>
                                <TableHead>Price</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {products.map(p => (
                                <TableRow key={p.id}>
                                    <TableCell>
                                        <div className="h-10 w-10 rounded-md border bg-slate-50 overflow-hidden">
                                            {p.images?.length > 0 ? (
                                                <img 
                                                    src={`/storage/${p.images.find(i => i.is_primary)?.image_path}`} 
                                                    alt={p.name} 
                                                    className="h-full w-full object-cover"
                                                />
                                            ) : (
                                                <div className="flex h-full w-full items-center justify-center text-[10px] text-slate-400">No Image</div>
                                            )}
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <div className="font-medium">{p.name}</div>
                                        <div className="text-xs text-slate-500 truncate max-w-[200px]">{p.description}</div>
                                    </TableCell>
                                    <TableCell className="font-mono text-xs">{p.sku}</TableCell>
                                    <TableCell>
                                        <Badge variant="secondary">{p.category?.name}</Badge>
                                    </TableCell>
                                    <TableCell>
                                        {new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(p.unit_price)}
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant="outline" className="text-green-600 border-green-200 bg-green-50">Active</Badge>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <Button variant="ghost" size="icon">
                                            <MoreHorizontal className="h-4 w-4" />
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>
    );
};

export default Products;
