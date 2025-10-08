<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug - Parsing Results</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', monospace;
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
            line-height: 1.6;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: #2d2d30;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #3e3e42;
        }

        h1 {
            color: #4ec9b0;
            font-size: 24px;
            margin-bottom: 10px;
        }

        h2 {
            color: #569cd6;
            font-size: 18px;
            margin: 20px 0 10px;
            padding: 10px;
            background: #2d2d30;
            border-left: 3px solid #569cd6;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }

        .info-card {
            background: #2d2d30;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #3e3e42;
        }

        .info-label {
            color: #808080;
            font-size: 12px;
            text-transform: uppercase;
        }

        .info-value {
            color: #ce9178;
            font-size: 20px;
            font-weight: bold;
            margin-top: 5px;
        }

        .raw-content {
            background: #1e1e1e;
            border: 1px solid #3e3e42;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            margin: 10px 0;
        }

        .line {
            white-space: pre;
            font-size: 12px;
            line-height: 1.4;
        }

        .line-number {
            color: #858585;
            margin-right: 15px;
            display: inline-block;
            width: 30px;
            text-align: right;
        }

        .parsed-table {
            width: 100%;
            background: #2d2d30;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 12px;
        }

        .parsed-table th {
            background: #007acc;
            color: white;
            padding: 8px;
            text-align: left;
            font-weight: normal;
            position: sticky;
            top: 0;
        }

        .parsed-table td {
            padding: 8px;
            border-bottom: 1px solid #3e3e42;
            color: #d4d4d4;
        }

        .parsed-table tr:hover {
            background: #3e3e42;
        }

        .null-value {
            color: #6a6a6a;
            font-style: italic;
        }

        .string-value {
            color: #ce9178;
        }

        .number-value {
            color: #b5cea8;
        }

        .date-value {
            color: #4fc1ff;
        }

        .success {
            color: #4ec9b0;
        }

        .warning {
            color: #dcdcaa;
        }

        .error {
            color: #f48771;
        }

        .actions {
            margin: 30px 0;
            display: flex;
            gap: 15px;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #007acc;
            color: white;
        }

        .btn-primary:hover {
            background: #005a9e;
        }

        .btn-success {
            background: #4ec9b0;
            color: #1e1e1e;
        }

        .btn-success:hover {
            background: #3ba394;
        }

        .btn-secondary {
            background: #3e3e42;
            color: #d4d4d4;
        }

        .btn-secondary:hover {
            background: #505053;
        }

        .delimiter-badge {
            display: inline-block;
            background: #007acc;
            color: white;
            padding: 3px 10px;
            border-radius: 3px;
            font-size: 12px;
            margin-left: 10px;
        }

        .stats-section {
            background: #252526;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border: 1px solid #3e3e42;
        }

        .highlight {
            background: #264f78;
            padding: 2px 4px;
            border-radius: 3px;
        }

        .scroll-container {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #3e3e42;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 DEBUG MODE - Parsing Results</h1>
            <p style="color: #808080;">File: <span class="string-value">{{ $filename }}</span></p>
            <p style="color: #808080;">Delimiter Detected: <span class="delimiter-badge">{{ $delimiter }}</span></p>
        </div>

        <div class="info-grid">
            <div class="info-card">
                <div class="info-label">Total Records Parsed</div>
                <div class="info-value {{ $totalRecords > 0 ? 'success' : 'error' }}">
                    {{ $totalRecords }}
                </div>
            </div>
            <div class="info-card">
                <div class="info-label">Valid Rows</div>
                <div class="info-value">{{ $stats['valid_rows'] ?? 0 }}</div>
            </div>
            <div class="info-card">
                <div class="info-label">Products Found</div>
                <div class="info-value">{{ count($stats['products'] ?? []) }}</div>
            </div>
            <div class="info-card">
                <div class="info-label">Columns Detected</div>
                <div class="info-value">{{ count($headers) }}</div>
            </div>
        </div>

        <h2>📄 RAW FILE CONTENT (First 10 Lines)</h2>
        <div class="raw-content">
            @foreach($sampleLines as $index => $line)
                <div class="line">
                    <span class="line-number">{{ $index + 1 }}</span>{{ htmlspecialchars($line) }}
                </div>
            @endforeach
        </div>

        <h2>🎯 DETECTED COLUMN MAPPING</h2>
        <div class="stats-section">
            @if(!empty($headerMapping))
                <p style="margin-bottom: 15px;">How your columns were mapped:</p>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 2px solid #3e3e42;">
                            <th style="text-align: left; padding: 8px; color: #569cd6;">Original Header</th>
                            <th style="text-align: center; padding: 8px;">→</th>
                            <th style="text-align: left; padding: 8px; color: #4ec9b0;">Mapped Field</th>
                            <th style="text-align: left; padding: 8px; color: #dcdcaa;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($headerMapping as $mapping)
                            <tr style="border-bottom: 1px solid #3e3e42;">
                                <td style="padding: 8px; color: #ce9178;">"{{ $mapping['original'] }}"</td>
                                <td style="text-align: center; padding: 8px;">→</td>
                                <td style="padding: 8px; color: #4ec9b0; font-weight: bold;">{{ $mapping['mapped'] }}</td>
                                <td style="padding: 8px;">
                                    @if(in_array($mapping['mapped'], ['product', 'sales_date', 'quantity', 'rate', 'total', 'buyer_name']))
                                        <span style="color: #4ec9b0;">✓ Critical Field</span>
                                    @else
                                        <span style="color: #808080;">Optional</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div style="margin-top: 15px; padding: 10px; background: #1e1e1e; border-radius: 5px;">
                    <p style="color: #dcdcaa; margin-bottom: 5px;">⚠️ <strong>Critical Fields for Analysis:</strong></p>
                    <ul style="margin-left: 20px; color: #4ec9b0;">
                        <li><strong>product</strong> - Must be correctly identified</li>
                        <li><strong>sales_date</strong> - Required for historical analysis</li>
                        <li><strong>quantity</strong> - Main metric</li>
                        <li><strong>rate/total</strong> - For revenue analysis</li>
                    </ul>
                    <div style="margin-top: 10px; padding: 10px; background: #3e1e1e; border: 1px solid #ff6b6b; border-radius: 5px;">
                        <p style="color: #ff6b6b; margin: 0;">
                            📅 <strong>Date Validation:</strong> Future dates (after {{ date('M d, Y') }}) are automatically corrected to previous year.
                        </p>
                    </div>
                </div>
            @else
                <p class="error">❌ No columns could be mapped!</p>
            @endif
        </div>

        <h2>✅ SUCCESSFULLY PARSED DATA (First 20 Records)</h2>
        @if(!empty($parsedData))
            <div class="scroll-container">
                <table class="parsed-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            @foreach($headers as $header)
                                <th>{{ $header }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($parsedData as $index => $row)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                @foreach($headers as $header)
                                    <td>
                                        @if(isset($row[$header]))
                                            @if(is_null($row[$header]))
                                                <span class="null-value">NULL</span>
                                            @elseif(is_numeric($row[$header]))
                                                <span class="number-value">{{ $row[$header] }}</span>
                                            @elseif(strpos($header, 'date') !== false)
                                                <span class="date-value">{{ $row[$header] }}</span>
                                            @else
                                                <span class="string-value">{{ $row[$header] }}</span>
                                            @endif
                                        @else
                                            <span class="null-value">-</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="stats-section">
                <h3 style="color: #4ec9b0; margin-bottom: 15px;">📊 Parsing Statistics</h3>

                @if(!empty($stats['products']))
                    <p><strong>Products:</strong> {{ implode(', ', array_slice($stats['products'], 0, 10)) }}{{ count($stats['products']) > 10 ? '...' : '' }}</p>
                @endif

                @if(!empty($stats['date_range']['start']))
                    <p><strong>Date Range:</strong>
                        <span class="date-value">{{ $stats['date_range']['start'] }}</span> to
                        <span class="date-value">{{ $stats['date_range']['end'] }}</span>
                    </p>
                @endif

                @if(isset($stats['total_revenue']))
                    <p><strong>Total Revenue:</strong> <span class="number-value">${{ number_format($stats['total_revenue'], 2) }}</span></p>
                @endif
            </div>
        @else
            <div class="stats-section">
                <p class="error">❌ No data could be parsed from the file!</p>
                <p style="margin-top: 10px;">Possible issues:</p>
                <ul style="margin-left: 20px; color: #dcdcaa;">
                    <li>Incorrect delimiter detected</li>
                    <li>Header row not recognized</li>
                    <li>Unexpected file encoding</li>
                    <li>Empty or corrupted file</li>
                </ul>
            </div>
        @endif

        <div class="actions">
            <form action="{{ route('demand-forecast.process') }}" method="POST" enctype="multipart/form-data" style="display: inline;">
                @csrf
                <input type="file" name="file" id="reupload-file" style="display: none;">
                <button type="submit" class="btn btn-success">
                    ✅ Continue to Forecast
                </button>
            </form>

            <a href="{{ route('demand-forecast.index') }}" class="btn btn-secondary">
                ← Upload Different File
            </a>
        </div>

        <div class="stats-section" style="background: #1e3a1e; border-color: #2d5a2d;">
            <h3 style="color: #4ec9b0; margin-bottom: 15px;">💡 What This Shows</h3>
            <p>This debug view shows you:</p>
            <ul style="margin-left: 20px; margin-top: 10px; color: #b5cea8;">
                <li>✅ The raw content of your file (first 10 lines)</li>
                <li>✅ Which delimiter was detected (pipe |, comma ,, etc.)</li>
                <li>✅ How columns were mapped to standard fields</li>
                <li>✅ The actual parsed data with proper types (dates, numbers, strings)</li>
                <li>✅ Statistics about what was found</li>
            </ul>
            <p style="margin-top: 15px; color: #dcdcaa;">
                If the parsing looks correct, click "Continue to Forecast" to generate predictions.
                If not, check your file format and try again.
            </p>
        </div>
    </div>

    <script>
        // Re-upload the same file for continue
        document.querySelector('.btn-success').addEventListener('click', function(e) {
            const fileInput = document.getElementById('reupload-file');
            if (!fileInput.files.length) {
                e.preventDefault();
                alert('Please go back and upload the file again.');
            }
        });
    </script>
</body>
</html>