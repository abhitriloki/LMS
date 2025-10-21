<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $data['title'] ?? 'Report' }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #3b82f6;
        }
        .header h1 {
            color: #1e3a8a;
            margin: 0 0 10px 0;
        }
        .header .period {
            color: #64748b;
            font-size: 14px;
        }
        .summary {
            background-color: #f1f5f9;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .summary h2 {
            margin: 0 0 10px 0;
            color: #1e3a8a;
            font-size: 16px;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        .summary-item {
            padding: 5px 0;
        }
        .summary-label {
            font-weight: bold;
            color: #475569;
        }
        .summary-value {
            color: #0f172a;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th {
            background-color: #3b82f6;
            color: white;
            padding: 10px;
            text-align: left;
            font-weight: bold;
        }
        td {
            padding: 8px 10px;
            border-bottom: 1px solid #e2e8f0;
        }
        tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            color: #64748b;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $data['title'] ?? 'Report' }}</h1>
        @if(isset($data['period']))
            <div class="period">Period: {{ $data['period'] }}</div>
        @endif
    </div>

    @if(isset($data['summary']))
        <div class="summary">
            <h2>Summary</h2>
            <div class="summary-grid">
                @foreach($data['summary'] as $key => $value)
                    <div class="summary-item">
                        <span class="summary-label">{{ ucwords(str_replace('_', ' ', $key)) }}:</span>
                        <span class="summary-value">{{ is_numeric($value) ? number_format($value, 2) : $value }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if(isset($data['data']) && count($data['data']) > 0)
        <table>
            <thead>
                <tr>
                    @foreach(array_keys($data['data'][0]) as $header)
                        <th>{{ ucwords(str_replace('_', ' ', $header)) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($data['data'] as $row)
                    <tr>
                        @foreach($row as $cell)
                            <td>{{ is_numeric($cell) && !is_string($cell) ? number_format($cell, 2) : $cell }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p style="text-align: center; color: #64748b; padding: 40px;">No data available for this report.</p>
    @endif

    <div class="footer">
        <p>Generated on {{ now()->format('F d, Y \a\t H:i:s') }}</p>
        <p>Corporate LMS - Analytics & Reporting System</p>
    </div>
</body>
</html>
