import React from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/Components/ui/Card";
import { Button } from "@/Components/ui/Button";
import { Input } from "@/Components/ui/Input";
import { Label } from "@/Components/ui/Label";
import { User, Bell, Shield, Database, Globe } from "lucide-react";

const Settings = () => {
    return (
        <div className="space-y-6 p-8 pt-6">
            <div>
                <h2 className="text-3xl font-bold tracking-tight">Settings</h2>
                <p className="text-slate-500 text-sm">Manage your application preferences and security.</p>
            </div>

            <div className="grid gap-6 md:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <User className="h-5 w-5" /> Profile Settings
                        </CardTitle>
                        <CardDescription>Update your personal information.</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="space-y-2">
                            <Label>Full Name</Label>
                            <Input placeholder="Administrator" />
                        </div>
                        <div className="space-y-2">
                            <Label>Email Address</Label>
                            <Input type="email" placeholder="admin@smartstock.pro" />
                        </div>
                        <Button>Save Changes</Button>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Shield className="h-5 w-5" /> Security
                        </CardTitle>
                        <CardDescription>Manage your password and authentication.</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="space-y-2">
                            <Label>Current Password</Label>
                            <Input type="password" />
                        </div>
                        <div className="space-y-2">
                            <Label>New Password</Label>
                            <Input type="password" />
                        </div>
                        <Button variant="outline">Update Password</Button>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Bell className="h-5 w-5" /> Notifications
                        </CardTitle>
                        <CardDescription>Configure how you receive alerts.</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="flex items-center justify-between py-2 border-b">
                            <div className="space-y-0.5">
                                <p className="text-sm font-medium">Email Notifications</p>
                                <p className="text-xs text-slate-500">Receive alerts for low stock via email.</p>
                            </div>
                            <div className="h-5 w-10 bg-blue-600 rounded-full"></div>
                        </div>
                        <div className="flex items-center justify-between py-2 border-b">
                            <div className="space-y-0.5">
                                <p className="text-sm font-medium">System Critical Alerts</p>
                                <p className="text-xs text-slate-500">In-app notifications for system errors.</p>
                            </div>
                            <div className="h-5 w-10 bg-blue-600 rounded-full"></div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Database className="h-5 w-5" /> Data Management
                        </CardTitle>
                        <CardDescription>Export or backup your inventory data.</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <p className="text-xs text-slate-500">Last backup: Today at 02:00 AM</p>
                        <div className="flex gap-2">
                            <Button variant="outline" size="sm">Download Backup</Button>
                            <Button variant="outline" size="sm">Export All Data</Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    );
};

export default Settings;
