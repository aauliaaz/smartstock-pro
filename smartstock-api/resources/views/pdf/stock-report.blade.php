<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #222; }
        .header { border-bottom: 3px solid #1e3a8a; padding-bottom: 10px; margin-bottom: 15px; }
        .header h1 { color: #1e3a8a; margin: 0; font-size: 20px; }
        .header .subtitle { color: #666; font-size: 12px; margin-top: 4px; }
        .meta { display: flex; justify-content: space-between; font-size: 10px; color: #555; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        thead th { background: #1e3a8a; color: #fff; padding: 6px; text-align: left; font-size: 10px; }
        tbody td { padding: 5px 6px; border-bottom: 1px solid #e5e7eb; }
        tbody tr:nth-child(even) { background: #f9fafb; }
        .summary { margin-top: 15px; padding: 10px; background: #eef2ff; border-left: 4px solid #1e3a8a; }
        .summary h3 { margin: 0 0 6px 0; color: #1e3a8a; font-size: 12px; }
        .summary table { width: auto; }
        .summary td { padding: 2px 12px 2px 0; border: none; }
        .num { text-align: right; }
        .footer { margin-top: 30px; font-size: 9px; color: #888; text-align: center; border-top: 1px solid #ccc; padding-top: 6px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>SmartStock Pro &mdash; {{ $title }}</h1>
        <div class="subtitle">{{ $subtitle ?? '' }}</div>
    </div>
    <div class="meta">
        <div>Digenerate oleh: <strong>{{ $generated_by ?? '-' }}</strong></div>
        <div>Tanggal: <strong>{{ $generated_at ?? now()->format('d M Y H:i') }}</strong></div>
    </div>

    @if(isset($rows) && count($rows) > 0)
        @php $first = (array) $rows[0]; @endphp
        <table>
            <thead>
                <tr>
                    @foreach($first as $key => $val)
                        <th>{{ ucwords(str_replace('_', ' ', $key)) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                    @php $r = (array) $row; @endphp
                    <tr>
                        @foreach($r as $val)
                            <td @class(['num' => is_numeric($val) && !str_contains((string)$val, '-')])>
                                @if(is_numeric($val) && $val >= 1000)
                                    {{ number_format((float) $val, 0, ',', '.') }}
                                @else
                                    {{ $val }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p style="text-align:center; color:#666; padding: 30px;">Tidak ada data untuk ditampilkan.</p>
    @endif

    @if(isset($summary) && is_array($summary))
        <div class="summary">
            <h3>RINGKASAN</h3>
            <table>
                @foreach($summary as $key => $val)
                    <tr>
                        <td><strong>{{ ucwords(str_replace('_', ' ', $key)) }}:</strong></td>
                        <td>
                            @if(is_numeric($val))
                                {{ number_format((float) $val, 0, ',', '.') }}
                            @else
                                {{ $val }}
                            @endif
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>
    @endif

    <div class="footer">
        SmartStock Pro &copy; {{ date('Y') }} PT Maju Bersama Digital &mdash; Dokumen ini digenerate otomatis oleh sistem
    </div>
</body>
</html>
