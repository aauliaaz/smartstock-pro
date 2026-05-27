import React, { useState, useEffect } from 'react';
import axios from 'axios';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/Components/ui/Card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/Components/ui/Table";
import { Button } from "@/Components/ui/Button";
import { Plus, Truck, Edit, Trash2, Mail, Phone } from "lucide-react";

const Suppliers = () => {
    const [suppliers, setSuppliers] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetchSuppliers();
    }, []);

    const fetchSuppliers = async () => {
        setLoading(true);
        try {
            const res = await axios.get('/api/suppliers');
            setSuppliers(res.data);
        } catch (err) {
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    if (loading) return <div className="flex items-center justify-center h-screen text-slate-500">Loading Suppliers...</div>;

    return (
        <div className="space-y-6 p-8 pt-6">
            <div className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                    <Truck className="h-8 w-8 text-blue-600" />
                    <h2 className="text-3xl font-bold tracking-tight">Suppliers</h2>
                </div>
                <Button className="flex items-center gap-2">
                    <Plus className="h-4 w-4" /> Add Supplier
                </Button>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Business Partners</CardTitle>
                    <CardDescription>Manage your product sources and contact info.</CardDescription>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Supplier Name</TableHead>
                                <TableHead>Contact</TableHead>
                                <TableHead>Address</TableHead>
                                <TableHead className="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {suppliers.length > 0 ? suppliers.map(s => (
                                <TableRow key={s.id}>
                                    <TableCell className="font-medium">{s.name}</TableCell>
                                    <TableCell>
                                        <div className="flex flex-col gap-1">
                                            {s.email && (
                                                <div className="flex items-center gap-1 text-[10px] text-slate-500">
                                                    <Mail className="h-3 w-3" /> {s.email}
                                                </div>
                                            )}
                                            {s.phone && (
                                                <div className="flex items-center gap-1 text-[10px] text-slate-500">
                                                    <Phone className="h-3 w-3" /> {s.phone}
                                                </div>
                                            )}
                                        </div>
                                    </TableCell>
                                    <TableCell className="text-xs text-slate-500 max-w-[250px] truncate">
                                        {s.address || 'No address'}
                                    </TableCell>
                                    <TableCell className="text-right space-x-2">
                                        <Button variant="ghost" size="icon" className="h-8 w-8 text-blue-600">
                                            <Edit className="h-4 w-4" />
                                        </Button>
                                        <Button variant="ghost" size="icon" className="h-8 w-8 text-red-600">
                                            <Trash2 className="h-4 w-4" />
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            )) : (
                                <TableRow>
                                    <TableCell colSpan={4} className="h-24 text-center text-slate-500">No suppliers found.</TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>
    );
};

export default Suppliers;
