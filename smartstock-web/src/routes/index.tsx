import React from 'react';
import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider } from '@/context/AuthContext';
import ProtectedRoute from '@/components/RequireAuth';
import AppLayout from '@/components/layout/AppLayout';
import LoginPage from '@/pages/Login';
import Dashboard from '@/pages/Dashboard';
import Products from '@/pages/Products';
import Categories from '@/pages/Categories';
import Warehouses from '@/pages/Warehouses';
import Suppliers from '@/pages/Suppliers';
import StockIn from '@/pages/StockIn';
import StockOut from '@/pages/StockOut';
import StockTransfer from '@/pages/StockTransfer';
import Notifications from '@/pages/Notifications';
import AuditLogs from '@/pages/AuditLogs';
import ErrorLogs from '@/pages/ErrorLogs';
import Reports from '@/pages/Reports';
import ImportExport from '@/pages/ImportExport';
import UserManagement from '@/pages/UserManagement';
import ServerMonitoring from '@/pages/ServerMonitoring';

const AppRoutes: React.FC = () => {
  return (
    <Router>
      <AuthProvider>
        <Routes>
          <Route path="/login" element={<LoginPage />} />
          
          <Route path="/" element={
            <ProtectedRoute>
              <AppLayout />
            </ProtectedRoute>
          }>
            <Route index element={<Dashboard />} />
            <Route path="products" element={<Products />} />
            <Route path="categories" element={<Categories />} />
            <Route path="warehouses" element={<Warehouses />} />
            <Route path="suppliers" element={<Suppliers />} />
            <Route path="stock-in" element={<StockIn />} />
            <Route path="stock-out" element={<StockOut />} />
            <Route path="transfers" element={<StockTransfer />} />
            <Route path="notifications" element={<Notifications />} />
            <Route path="audit-logs" element={<AuditLogs />} />
            <Route path="error-logs" element={<ErrorLogs />} />
            <Route path="reports" element={<Reports />} />
            <Route path="import" element={<ImportExport />} />
            <Route path="users" element={<UserManagement />} />
            <Route path="monitoring" element={<ServerMonitoring />} />
            <Route path="*" element={<Navigate to="/" replace />} />
          </Route>
        </Routes>
      </AuthProvider>
    </Router>
  );
};

export default AppRoutes;
