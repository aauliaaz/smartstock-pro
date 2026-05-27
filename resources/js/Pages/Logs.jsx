import React, { useState, useEffect } from 'react';
import axios from 'axios';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/Components/ui/Card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/Components/ui/Table";
import { Badge } from "@/Components/ui/Badge";
import { ScrollText, ShieldAlert, History, AlertCircle } from "lucide-react";

const Logs = () => {
    const [auditLogs, setAuditLogs] = useState([]);
    const [errorLogs, setErrorLogs] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetchLogs();
    }, []);

    const fetchLogs = async () => {
        setLoading(true);
        try {
            const [auditRes, errorRes] = await Promise.all([
                axios.get('/api/audit-logs'),
                axios.get('/api/error-logs')
            ]);
            setAuditLogs(auditRes.data.data);
            setErrorLogs(errorRes.data.data);
        } catch (err) {
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    if (loading) return <div className="flex items-center justify-center h-screen text-slate-500">Loading Logs...</div>;

    return (
        <div className="space-y-6 p-8 pt-6">
            <div className="flex items-center gap-2">
                <ScrollText className="h-8 w-8 text-slate-700" />
                <h2 className="text-3xl font-bold tracking-tight">System Logs</h2>
            </div>

            <div className="grid gap-6 md:grid-cols-2">
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between">
                        <div>
                            <CardTitle className="flex items-center gap-2">
                                <History className="h-5 w-5 text-blue-500" /> Audit Trail
                            </CardTitle>
                            <CardDescription>Track all user activities and data changes.</CardDescription>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>User</TableHead>
                                    <TableHead>Action</TableHead>
                                    <TableHead className="text-right">Time</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {auditLogs.map(log => (
                                    <TableRow key={log.id}>
                                        <TableCell>
                                            <div className="font-medium text-xs">{log.user.name}</div>
                                            <div className="text-[10px] text-slate-400">{log.ip_address}</div>
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant="outline" className="text-[10px] uppercase font-bold tracking-wider">
                                                {log.action}
                                            </Badge>
                                            <span className="ml-2 text-[10px] text-slate-500">{log.model_type}</span>
                                        </TableCell>
                                        <TableCell className="text-right text-[10px] text-slate-500">
                                            {new Date(log.created_at).toLocaleTimeString()}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between">
                        <div>
                            <CardTitle className="flex items-center gap-2">
                                <ShieldAlert className="h-5 w-5 text-red-500" /> Error Logs
                            </CardTitle>
                            <CardDescription>Monitor system exceptions and failures.</CardDescription>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Severity</TableHead>
                                    <TableHead>Message</TableHead>
                                    <TableHead className="text-right">Time</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {errorLogs.map(log => (
                                    <TableRow key={log.id}>
                                        <TableCell>
                                            <Badge variant={log.severity === 'CRITICAL' ? 'destructive' : 'default'} className="text-[10px]">
                                                {log.severity}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            <div className="text-xs truncate max-w-[200px]">{log.message}</div>
                                            <div className="text-[10px] text-slate-400">{log.url}</div>
                                        </TableCell>
                                        <TableCell className="text-right text-[10px] text-slate-500">
                                            {new Date(log.created_at).toLocaleTimeString()}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </div>
    );
};

export default Logs;
