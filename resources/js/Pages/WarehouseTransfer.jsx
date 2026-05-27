import React, { useState, useEffect } from 'react';
import axios from 'axios';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/Components/ui/Card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/Components/ui/Table";
import { Button } from "@/Components/ui/Button";
import { Input } from "@/Components/ui/Input";
import { Label } from "@/Components/ui/Label";
import { Select } from "@/Components/ui/Select";
import { Badge } from "@/Components/ui/Badge";
import { ArrowLeftRight, Plus, Loader2, CheckCircle2, XCircle } from "lucide-react";

const WarehouseTransfer = () => {
    const [products, setProducts] = useState([]);
    const [warehouses, setWarehouses] = useState([]);
    const [transfers, setTransfers] = useState([]);
    const [loading, setLoading] = useState(true);
    const [submitting, setSubmitting] = useState(false);
    
    const [formData, setFormData] = useState({
        from_warehouse_id: '',
        to_warehouse_id: '',
        items: [{ product_id: '', quantity: '' }],
        notes: ''
    });

    useEffect(() => {
        fetchData();
    }, []);

    const fetchData = async () => {
        setLoading(true);
        try {
            const [prodRes, warehouseRes, transRes] = await Promise.all([
                axios.get('/api/products'),
                axios.get('/api/warehouses'),
                axios.get('/api/transfers')
            ]);
            setProducts(prodRes.data.data);
            setWarehouses(warehouseRes.data);
            setTransfers(transRes.data.data);
        } catch (err) {
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    const handleAddItem = () => {
        setFormData({
            ...formData,
            items: [...formData.items, { product_id: '', quantity: '' }]
        });
    };

    const handleItemChange = (index, field, value) => {
        const newItems = [...formData.items];
        newItems[index][field] = value;
        setFormData({ ...formData, items: newItems });
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setSubmitting(true);
        try {
            await axios.post('/api/transfers', formData);
            setFormData({
                from_warehouse_id: '',
                to_warehouse_id: '',
                items: [{ product_id: '', quantity: '' }],
                notes: ''
            });
            fetchData();
        } catch (err) {
            alert(err.response?.data?.message || 'Error creating transfer');
        } finally {
            setSubmitting(false);
        }
    };

    const handleApprove = async (id) => {
        try {
            await axios.patch(`/api/transfers/${id}/approve`);
            fetchData();
        } catch (err) {
            alert('Error approving transfer');
        }
    };

    if (loading) return <div className="flex items-center justify-center h-screen text-slate-500">Loading...</div>;

    return (
        <div className="space-y-6 p-8 pt-6">
            <div className="flex items-center gap-2">
                <ArrowLeftRight className="h-8 w-8 text-blue-600" />
                <h2 className="text-3xl font-bold tracking-tight">Warehouse Transfers</h2>
            </div>

            <div className="grid gap-6 lg:grid-cols-5">
                <Card className="lg:col-span-2">
                    <CardHeader>
                        <CardTitle>Create Transfer</CardTitle>
                        <CardDescription>Move stock between distribution centers.</CardDescription>
                    </CardHeader>
                    <form onSubmit={handleSubmit}>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label>From</Label>
                                    <Select 
                                        value={formData.from_warehouse_id} 
                                        onChange={(e) => setFormData({...formData, from_warehouse_id: e.target.value})}
                                        required
                                    >
                                        <option value="">Source</option>
                                        {warehouses.map(w => <option key={w.id} value={w.id}>{w.name}</option>)}
                                    </Select>
                                </div>
                                <div className="space-y-2">
                                    <Label>To</Label>
                                    <Select 
                                        value={formData.to_warehouse_id} 
                                        onChange={(e) => setFormData({...formData, to_warehouse_id: e.target.value})}
                                        required
                                    >
                                        <option value="">Destination</option>
                                        {warehouses.map(w => <option key={w.id} value={w.id}>{w.name}</option>)}
                                    </Select>
                                </div>
                            </div>

                            <div className="space-y-3">
                                <Label>Products</Label>
                                {formData.items.map((item, index) => (
                                    <div key={index} className="grid grid-cols-5 gap-2 items-end">
                                        <div className="col-span-3">
                                            <Select 
                                                value={item.product_id} 
                                                onChange={(e) => handleItemChange(index, 'product_id', e.target.value)}
                                                required
                                            >
                                                <option value="">Select product</option>
                                                {products.map(p => <option key={p.id} value={p.id}>{p.name}</option>)}
                                            </Select>
                                        </div>
                                        <div className="col-span-2">
                                            <Input 
                                                type="number" 
                                                placeholder="Qty" 
                                                value={item.quantity} 
                                                onChange={(e) => handleItemChange(index, 'quantity', e.target.value)}
                                                required
                                            />
                                        </div>
                                    </div>
                                ))}
                                <Button type="button" variant="outline" size="sm" className="w-full" onClick={handleAddItem}>
                                    <Plus className="mr-2 h-3 w-3" /> Add More
                                </Button>
                            </div>

                            <div className="space-y-2">
                                <Label>Notes</Label>
                                <Input 
                                    placeholder="Reason for transfer..." 
                                    value={formData.notes} 
                                    onChange={(e) => setFormData({...formData, notes: e.target.value})}
                                />
                            </div>
                        </CardContent>
                        <CardContent>
                            <Button className="w-full" type="submit" disabled={submitting}>
                                {submitting && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                                Request Transfer
                            </Button>
                        </CardContent>
                    </form>
                </Card>

                <Card className="lg:col-span-3">
                    <CardHeader>
                        <CardTitle>Transfer History</CardTitle>
                        <CardDescription>Monitor and approve pending transfers.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Number</TableHead>
                                    <TableHead>Route</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="text-right">Action</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {transfers.length > 0 ? transfers.map(t => (
                                    <TableRow key={t.id}>
                                        <TableCell className="font-mono text-xs">{t.transfer_number}</TableCell>
                                        <TableCell>
                                            <div className="text-xs font-medium">{t.from_warehouse?.name}</div>
                                            <div className="text-[10px] text-slate-400">to {t.to_warehouse?.name}</div>
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant={t.status === 'COMPLETED' ? 'secondary' : 'outline'}>
                                                {t.status}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-right">
                                            {t.status === 'PENDING' && (
                                                <Button size="sm" onClick={() => handleApprove(t.id)}>Approve</Button>
                                            )}
                                            {t.status === 'COMPLETED' && <CheckCircle2 className="h-5 w-5 text-green-500 ml-auto" />}
                                        </TableCell>
                                    </TableRow>
                                )) : (
                                    <TableRow>
                                        <TableCell colSpan={4} className="h-24 text-center text-slate-500">No transfers found.</TableCell>
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

export default WarehouseTransfer;
