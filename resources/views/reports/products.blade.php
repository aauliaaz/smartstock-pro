<!DOCTYPE html>
<html>
<head>
    <title>{{ $title }}</title>
    <style>
        body { font-family: sans-serif; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        .header h1 { color: #2563eb; margin-bottom: 5px; }
        .meta { margin-bottom: 20px; font-size: 12px; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #eee; padding: 10px; text-align: left; font-size: 12px; }
        th { background-color: #f8fafc; color: #1e293b; font-weight: bold; }
        .text-right { text-align: right; }
        .footer { margin-top: 50px; font-size: 10px; text-align: center; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="header">
        <h1>SmartStock Pro</h1>
        <p>PT Maju Bersama Digital</p>
    </div>

    <div class="meta">
        <strong>Report:</strong> {{ $title }}<br>
        <strong>Generated:</strong> {{ $date }}
    </div>

    <table>
        <thead>
            <tr>
                <th>SKU</th>
                <th>Product Name</th>
                <th>Category</th>
                <th class="text-right">Total Stock</th>
                <th class="text-right">Unit Price</th>
            </tr>
        </thead>
        <tbody>
            @foreach($products as $product)
            <tr>
                <td>{{ $product->sku }}</td>
                <td>{{ $product->name }}</td>
                <td>{{ $product->category->name }}</td>
                <td class="text-right">{{ $product->getTotalStock() }}</td>
                <td class="text-right">Rp {{ number_format($product->unit_price, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        &copy; {{ date('Y') }} PT Maju Bersama Digital. All rights reserved.
    </div>
</body>
</html>
