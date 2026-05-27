import React, { useState, useEffect } from 'react';
import axios from 'axios';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/Components/ui/Card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/Components/ui/Table";
import { Button } from "@/Components/ui/Button";
import { Badge } from "@/Components/ui/Badge";
import { Plus, Tags, MoreHorizontal, Edit, Trash2 } from "lucide-react";

const Categories = () => {
    const [categories, setCategories] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetchCategories();
    }, []);

    const fetchCategories = async () => {
        setLoading(true);
        try {
            const res = await axios.get('/api/categories');
            setCategories(res.data);
        } catch (err) {
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    if (loading) return <div className="flex items-center justify-center h-screen text-slate-500">Loading Categories...</div>;

    return (
        <div className="space-y-6 p-8 pt-6">
            <div className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                    <Tags className="h-8 w-8 text-blue-600" />
                    <h2 className="text-3xl font-bold tracking-tight">Categories</h2>
                </div>
                <Button className="flex items-center gap-2">
                    <Plus className="h-4 w-4" /> Add Category
                </Button>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Product Categories</CardTitle>
                    <CardDescription>Manage how your products are grouped.</CardDescription>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Category Name</TableHead>
                                <TableHead>Slug</TableHead>
                                <TableHead>Products Count</TableHead>
                                <TableHead>Description</TableHead>
                                <TableHead className="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {categories.length > 0 ? categories.map(c => (
                                <TableRow key={c.id}>
                                    <TableCell className="font-medium">{c.name}</TableCell>
                                    <TableCell className="text-xs font-mono">{c.slug}</TableCell>
                                    <TableCell>
                                        <Badge variant="secondary">{c.products_count} products</Badge>
                                    </TableCell>
                                    <TableCell className="text-xs text-slate-500 max-w-[300px] truncate">
                                        {c.description || 'No description'}
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
                                    <TableCell colSpan={5} className="h-24 text-center text-slate-500">No categories found.</TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>
    );
};

export default Categories;
