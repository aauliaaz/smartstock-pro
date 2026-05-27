import React, { useState, useEffect } from 'react';
import axios from 'axios';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/Components/ui/Card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/Components/ui/Table";
import { Button } from "@/Components/ui/Button";
import { Input } from "@/Components/ui/Input";
import { Label } from "@/Components/ui/Label";
import { Select } from "@/Components/ui/Select";
import { Badge } from "@/Components/ui/Badge";
import { ArrowDownCircle, ArrowUpCircle, Plus, Loader2 } from "lucide-react";

const InventoryMovement = ({ type }) => {
    const [products, setProducts] = useState([]);
    const [warehouses, setWarehouses] = useState([]);
    const [movements, setMovements] = useState([]);
    const [loading, setLoading] = useState(true);
    const [submitting, setSubmitting] = useState(false);
    
    const [formData, setFormData] = useState({
        product_id: '',
        warehouse_id: '',
        quantity: '',
        reference: '',
        notes: ''
    });

    useEffect(() => {
        fetchData();
    }, [type]);

    const fetchData = async () => {
        setLoading(true);
        try {
            const [prodRes, warehouseRes, moveRes] = await Promise.all([
                axios.get('/api/products'),
                axios.get('/api/warehouses'), 
                axios.get(`/api/stock-movements?type=${type}`)
            ]);
            setProducts(prodRes.data.data);
            setWarehouses(warehouseRes.data);
            setMovements(moveRes.data.data);
        } catch (err) {
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setSubmitting(true);
        try {
            await axios.post('/api/stock-movements', {
                ...formData,
                type: type
            });
            setFormData({ product_id: '', warehouse_id: '', quantity: '', reference: '', notes: '' });
            fetchData();
        } catch (err) {
            alert(err.response?.data?.message || 'Error recording movement');
        } finally {
            setSubmitting(false);
        }
    };

    if (loading) return <div className="flex items-center justify-center h-screen text-slate-500">Loading...</div>;

    return (
        <div className="space-y-6 p-8 pt-6">
            <div className="flex items-center gap-2">
                {type === 'IN' ? <ArrowDownCircle className="h-8 w-8 text-green-600" /> : <ArrowUpCircle className="h-8 w-8 text-red-600" />}
                <h2 className="text-3xl font-bold tracking-tight">Stock {type === 'IN' ? 'Entry' : 'Exit'}</h2>
            </div>

            <div className="grid gap-6 md:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Record New Movement</CardTitle>
                        <CardDescription>Input the details for stock {type === 'IN' ? 'arrival' : 'dispatch'}.</CardDescription>
                    </CardHeader>
                    <form onSubmit={handleSubmit}>
                        <CardContent className="space-y-4">
                            <div className="space-y-2">
                                <Label>Product</Label>
                                <Select 
                                    value={formData.product_id} 
                                    onChange={(e) => setFormData({...formData, product_id: e.target.value})}
                                    required
                                >
                                    <option value="">Select a product</option>
                                    {products.map(p => <option key={p.id} value={p.id}>{p.name} ({p.sku})</option>)}
                                </Select>
                            </div>
                            <div className="space-y-2">
                                <Label>Warehouse</Label>
                                <Select 
                                    value={formData.warehouse_id} 
                                    onChange={(e) => setFormData({...formData, warehouse_id: e.target.value})}
                                    required
                                >
                                    <option value="">Select warehouse</option>
                                    {warehouses.map(w => <option key={w.id} value={w.id}>{w.name}</option>)}
                                </Select>
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label>Quantity</Label>
                                    <Input 
                                        type="number" 
                                        value={formData.quantity} 
                                        onChange={(e) => setFormData({...formData, quantity: e.target.value})}
                                        placeholder="0"
                                        required
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label>Reference #</Label>
                                    <Input 
                                        placeholder="PO-12345" 
                                        value={formData.reference} 
                                        onChange={(e) => setFormData({...formData, reference: e.target.value})}
                                    />
                                </div>
                            </div>
                            <div className="space-y-2">
                                <Label>Notes</Label>
                                <Input 
                                    placeholder="Optional notes..." 
                                    value={formData.notes} 
                                    onChange={(e) => setFormData({...formData, notes: e.target.value})}
                                />
                            </div>
                        </CardContent>
                        <CardContent>
                            <Button className="w-full" type="submit" disabled={submitting}>
                                {submitting ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <Plus className="mr-2 h-4 w-4" />}
                                Submit {type === 'IN' ? 'Stock In' : 'Stock Out'}
                            </Button>
                        </CardContent>
                    </form>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Recent {type === 'IN' ? 'Entries' : 'Exits'}</CardTitle>
                        <CardDescription>The last 10 movements recorded.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Product</TableHead>
                                    <TableHead>Qty</TableHead>
                                    <TableHead>Warehouse</TableHead>
                                    <TableHead className="text-right">Date</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {movements.length > 0 ? movements.map(m => (
                                    <TableRow key={m.id}>
                                        <TableCell className="font-medium">{m.product?.name}</TableCell>
                                        <TableCell>
                                            <Badge variant={type === 'IN' ? 'secondary' : 'destructive'}>
                                                {type === 'IN' ? '+' : '-'}{m.quantity}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-xs">{m.warehouse?.name}</TableCell>
                                        <TableCell className="text-right text-xs text-slate-500">
                                            {new Date(m.created_at).toLocaleDateString()}
                                        </TableCell>
                                    </TableRow>
                                )) : (
                                    <TableRow>
                                        <TableCell colSpan={4} className="h-24 text-center text-slate-500">No movements found.</TableCell>
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

export default InventoryMovement;
