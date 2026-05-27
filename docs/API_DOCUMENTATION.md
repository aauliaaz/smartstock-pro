# API Documentation - SmartStock Pro

Base URL: `http://localhost:8000/api`

## Authentication
- **POST /login**: Login user (Session based)
- **POST /logout**: Logout user
- **GET /me**: Get current authenticated user

## Products
- **GET /products**: List products (Paginated)
- **POST /products**: Create product
- **GET /products/{id}**: Product details
- **PUT /products/{id}**: Update product
- **DELETE /products/{id}**: Delete product
- **POST /products/import**: Import products via file

## Master Data
- **GET /categories**: List categories
- **POST /categories**: Create category
- **GET /warehouses**: List warehouses
- **GET /suppliers**: List suppliers

## Inventory Transactions
- **GET /stock-movements**: List history (Filter by type=IN/OUT)
- **POST /stock-movements**: Create IN/OUT transaction
- **GET /transfers**: List transfers
- **POST /transfers**: Request transfer
- **PATCH /transfers/{id}/approve**: Approve transfer

## System & Dashboard
- **GET /dashboard**: Summary and alerts
- **GET /notifications**: User notifications
- **PATCH /notifications/{id}/read**: Mark as read
- **GET /audit-logs**: System activity logs
- **GET /error-logs**: Error logs
- **GET /system/stats**: Server performance stats
- **GET /reports/products-pdf**: Download product report PDF
