<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Sales Data - {{ $product }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .header .subtitle {
            color: #666;
            font-size: 14px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .stat-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .stat-value {
            font-size: 24px;
            color: #333;
            font-weight: bold;
        }

        .stat-detail {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }

        .daily-max-card {
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            border-left: 4px solid #d97706;
        }

        .daily-max-card .stat-label {
            color: #92400e;
        }

        .daily-max-card .stat-value {
            color: #451a03;
        }

        .table-container {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow-x: auto;
        }

        .table-container h2 {
            color: #333;
            font-size: 20px;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-size: 12px;
            color: #666;
            font-weight: 600;
            text-transform: uppercase;
            border-bottom: 2px solid #e5e7eb;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        tbody td {
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
        }

        tbody tr:hover {
            background: #f8f9fa;
        }

        .daily-max-row {
            background: linear-gradient(to right, #fef3c7, #fde68a) !important;
            font-weight: 600;
        }

        .daily-max-row td {
            position: relative;
        }

        .daily-max-row .badge {
            display: inline-block;
            background: #f59e0b;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 10px;
            margin-left: 8px;
            font-weight: 600;
        }

        .date-separator {
            background: #1e293b;
            color: white;
            font-weight: bold;
            text-align: center;
        }

        .date-separator td {
            padding: 8px;
            font-size: 12px;
            text-transform: uppercase;
        }

        .back-btn {
            display: inline-block;
            background: white;
            color: #667eea;
            padding: 12px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 20px;
            transition: all 0.3s;
        }

        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .filter-controls {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .filter-group label {
            font-size: 11px;
            color: #666;
            text-transform: uppercase;
            font-weight: 600;
        }

        .filter-group select,
        .filter-group input {
            padding: 8px 12px;
            border: 2px solid #e5e7eb;
            border-radius: 6px;
            font-size: 14px;
        }

        .summary-section {
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 15px;
        }

        .summary-item {
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 8px;
            border-left: 3px solid rgba(255, 255, 255, 0.5);
        }

        .summary-item h3 {
            font-size: 14px;
            margin-bottom: 5px;
            opacity: 0.9;
        }

        .summary-item .value {
            font-size: 20px;
            font-weight: bold;
        }

        .summary-item .detail {
            font-size: 12px;
            opacity: 0.8;
            margin-top: 5px;
        }

        .price-badge {
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }

        .price-high {
            background: #fee2e2;
            color: #991b1b;
        }

        .price-low {
            background: #dcfce7;
            color: #166534;
        }

        .price-avg {
            background: #e0e7ff;
            color: #3730a3;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="{{ route('demand-forecast.price-chart', ['product' => $product]) }}" class="back-btn">← Back to Price Chart</a>

        <div class="header">
            <h1>📊 Complete Sales History - {{ $product }}</h1>
            <p class="subtitle">
                Showing ALL {{ $totalRecords }} transactions |
                {{ $dateRange }} |
                Daily maximums highlighted in yellow
            </p>
        </div>

        <!-- Daily Maximum Summary -->
        <div class="summary-section">
            <h2>🏆 Daily Maximum Sales Analysis</h2>
            <div class="summary-grid">
                <div class="summary-item">
                    <h3>Highest Single Sale</h3>
                    <div class="value">${{ number_format($overallMax['total'], 2) }}</div>
                    <div class="detail">
                        {{ $overallMax['quantity'] }} units to {{ $overallMax['buyer'] }}<br>
                        on {{ \Carbon\Carbon::parse($overallMax['date'])->format('M d, Y') }}
                    </div>
                </div>
                <div class="summary-item">
                    <h3>Best Sales Day</h3>
                    <div class="value">${{ number_format($bestDay['total'], 2) }}</div>
                    <div class="detail">
                        {{ $bestDay['transactions'] }} transactions<br>
                        on {{ \Carbon\Carbon::parse($bestDay['date'])->format('M d, Y') }}
                    </div>
                </div>
                <div class="summary-item">
                    <h3>Top Buyer by Volume</h3>
                    <div class="value">{{ $topBuyer['name'] }}</div>
                    <div class="detail">
                        {{ number_format($topBuyer['quantity']) }} units<br>
                        ${{ number_format($topBuyer['total'], 2) }} total
                    </div>
                </div>
                <div class="summary-item">
                    <h3>Average Daily Max</h3>
                    <div class="value">${{ number_format($avgDailyMax, 2) }}</div>
                    <div class="detail">
                        Across {{ $uniqueDays }} trading days
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Transactions</div>
                <div class="stat-value">{{ number_format($totalRecords) }}</div>
                <div class="stat-detail">All sales records</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total Volume</div>
                <div class="stat-value">{{ number_format($totalVolume) }}</div>
                <div class="stat-detail">Units sold</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total Revenue</div>
                <div class="stat-value">${{ number_format($totalRevenue, 2) }}</div>
                <div class="stat-detail">Combined sales</div>
            </div>
            <div class="stat-card daily-max-card">
                <div class="stat-label">Daily Max Count</div>
                <div class="stat-value">{{ $uniqueDays }}</div>
                <div class="stat-detail">Peak sales per day</div>
            </div>
        </div>

        <!-- Filter Controls -->
        <div class="filter-controls">
            <div class="filter-group">
                <label>Sort By</label>
                <select onchange="sortTable(this.value)">
                    <option value="date">Date (Default)</option>
                    <option value="total">Total Amount</option>
                    <option value="quantity">Quantity</option>
                    <option value="rate">Unit Price</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Filter Buyer</label>
                <select onchange="filterBuyer(this.value)">
                    <option value="">All Buyers</option>
                    @foreach($buyers as $buyer)
                        <option value="{{ $buyer }}">{{ $buyer }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-group">
                <label>Show Only</label>
                <select onchange="filterView(this.value)">
                    <option value="all">All Records</option>
                    <option value="daily-max">Daily Maximums Only</option>
                    <option value="above-avg">Above Average Sales</option>
                </select>
            </div>
        </div>

        <!-- Data Table -->
        <div class="table-container">
            <h2>📋 All Sales Transactions</h2>
            <table id="salesTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Date</th>
                        <th>Buyer</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Total</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $currentDate = '';
                        $dayCount = 0;
                        $avgPrice = $totalRevenue / max($totalVolume, 1);
                    @endphp

                    @foreach($salesData as $index => $sale)
                        @if($sale['date'] !== $currentDate)
                            @php
                                $currentDate = $sale['date'];
                                $dayCount++;
                            @endphp
                            <tr class="date-separator">
                                <td colspan="7">
                                    📅 {{ \Carbon\Carbon::parse($sale['date'])->format('l, F d, Y') }}
                                    (Day {{ $dayCount }})
                                </td>
                            </tr>
                        @endif

                        <tr class="{{ $sale['is_daily_max'] ? 'daily-max-row' : '' }}"
                            data-date="{{ $sale['date'] }}"
                            data-buyer="{{ $sale['buyer'] }}"
                            data-total="{{ $sale['total'] }}"
                            data-quantity="{{ $sale['quantity'] }}">
                            <td>{{ $index + 1 }}</td>
                            <td>
                                {{ \Carbon\Carbon::parse($sale['date'])->format('M d') }}
                                @if($sale['is_daily_max'])
                                    <span class="badge">MAX</span>
                                @endif
                            </td>
                            <td><strong>{{ $sale['buyer'] ?? 'Unknown' }}</strong></td>
                            <td>{{ number_format($sale['quantity']) }}</td>
                            <td>
                                ${{ number_format($sale['rate'], 2) }}
                                @if($sale['rate'] > $avgPrice * 1.2)
                                    <span class="price-badge price-high">HIGH</span>
                                @elseif($sale['rate'] < $avgPrice * 0.8)
                                    <span class="price-badge price-low">LOW</span>
                                @endif
                            </td>
                            <td style="font-weight: bold;">
                                ${{ number_format($sale['total'], 2) }}
                            </td>
                            <td>
                                @if($sale['is_daily_max'])
                                    <span style="color: #f59e0b; font-weight: bold;">Daily Maximum</span>
                                @elseif($sale['total'] > $avgDailyMax)
                                    <span style="color: #10B981;">Above Average</span>
                                @else
                                    <span style="color: #999;">Standard</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function sortTable(sortBy) {
            const table = document.getElementById('salesTable');
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr:not(.date-separator)'));

            rows.sort((a, b) => {
                switch(sortBy) {
                    case 'total':
                        return parseFloat(b.dataset.total) - parseFloat(a.dataset.total);
                    case 'quantity':
                        return parseInt(b.dataset.quantity) - parseInt(a.dataset.quantity);
                    case 'date':
                    default:
                        return new Date(a.dataset.date) - new Date(b.dataset.date);
                }
            });

            // Clear and rebuild table
            tbody.innerHTML = '';
            let currentDate = '';
            rows.forEach(row => {
                if (row.dataset.date !== currentDate) {
                    currentDate = row.dataset.date;
                    const separator = document.createElement('tr');
                    separator.className = 'date-separator';
                    separator.innerHTML = `<td colspan="7">📅 ${new Date(currentDate).toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}</td>`;
                    tbody.appendChild(separator);
                }
                tbody.appendChild(row);
            });
        }

        function filterBuyer(buyer) {
            const rows = document.querySelectorAll('#salesTable tbody tr');
            rows.forEach(row => {
                if (row.classList.contains('date-separator')) return;
                if (buyer === '' || row.dataset.buyer === buyer) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function filterView(view) {
            const rows = document.querySelectorAll('#salesTable tbody tr');
            rows.forEach(row => {
                if (row.classList.contains('date-separator')) return;

                switch(view) {
                    case 'daily-max':
                        row.style.display = row.classList.contains('daily-max-row') ? '' : 'none';
                        break;
                    case 'above-avg':
                        const total = parseFloat(row.dataset.total);
                        const avgMax = {{ $avgDailyMax }};
                        row.style.display = total > avgMax ? '' : 'none';
                        break;
                    default:
                        row.style.display = '';
                }
            });
        }
    </script>
</body>
</html>