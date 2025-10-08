<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Data Analysis - {{ $product ?? 'All Products' }}</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: #f3f4f6;
            padding: 20px;
        }

        .container {
            max-width: 1800px;
            margin: 0 auto;
        }

        .header {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .daily-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 10px;
            margin-bottom: 30px;
        }

        .day-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            border: 2px solid transparent;
            transition: all 0.3s;
            cursor: pointer;
        }

        .day-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .day-card.has-data {
            border-color: #10B981;
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
        }

        .day-card.no-data {
            border-color: #ef4444;
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
        }

        .day-card.weekend {
            background: linear-gradient(135deg, #fef3c7 0%, #fed7aa 100%);
        }

        .day-date {
            font-size: 11px;
            color: #666;
            margin-bottom: 5px;
        }

        .day-name {
            font-size: 12px;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }

        .day-price {
            font-size: 16px;
            font-weight: bold;
            color: #10B981;
            margin-bottom: 5px;
        }

        .day-volume {
            font-size: 12px;
            color: #666;
        }

        .day-transactions {
            font-size: 11px;
            color: #999;
            margin-top: 5px;
        }

        .no-data-indicator {
            color: #ef4444;
            font-size: 12px;
        }

        .table-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            overflow-x: auto;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f9fafb;
            padding: 12px;
            text-align: left;
            font-size: 12px;
            font-weight: 600;
            color: #666;
            border-bottom: 2px solid #e5e7eb;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        td {
            padding: 10px 12px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 13px;
        }

        tr:hover {
            background: #f9fafb;
        }

        .quality-high {
            background: #10B981;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
        }

        .quality-medium {
            background: #f59e0b;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
        }

        .quality-low {
            background: #ef4444;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
        }

        .chart-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #3b82f6;
        }

        .stat-label {
            font-size: 11px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .stat-value {
            font-size: 20px;
            font-weight: bold;
            color: #333;
        }

        .legend {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin: 20px 0;
            flex-wrap: wrap;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
        }

        .legend-color {
            width: 20px;
            height: 12px;
            border-radius: 3px;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Daily Data Analysis</h1>
            <p>Complete daily breakdown for {{ $product ?? 'all products' }} | Period: {{ $dateRange }}</p>
        </div>

        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-label">Total Days in Range</div>
                <div class="stat-value">{{ $statistics['totalDays'] }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Days with Sales</div>
                <div class="stat-value" style="color: #10B981;">{{ $statistics['daysWithData'] }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Missing Days</div>
                <div class="stat-value" style="color: #ef4444;">{{ $statistics['daysWithoutData'] }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Data Coverage</div>
                <div class="stat-value">{{ round(($statistics['daysWithData'] / max($statistics['totalDays'], 1)) * 100, 1) }}%</div>
            </div>
        </div>

        <div class="legend">
            <div class="legend-item">
                <div class="legend-color" style="background: #dcfce7; border-color: #10B981;"></div>
                <span>Has Data</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: #fee2e2; border-color: #ef4444;"></div>
                <span>No Data</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: #fed7aa;"></div>
                <span>Weekend</span>
            </div>
        </div>

        <div class="daily-grid">
            @foreach($dailyBreakdown as $date => $dayData)
                @php
                    $dateObj = \Carbon\Carbon::parse($date);
                    $isWeekend = in_array($dateObj->dayOfWeek, [0, 6]);
                    $hasData = !isset($dayData['is_gap']) || !$dayData['is_gap'];
                @endphp

                <div class="day-card {{ $hasData ? 'has-data' : 'no-data' }} {{ $isWeekend ? 'weekend' : '' }}"
                     onclick="showDayDetails('{{ $date }}')">
                    <div class="day-date">{{ $dateObj->format('d M Y') }}</div>
                    <div class="day-name">{{ $dateObj->format('D') }}</div>

                    @if($hasData)
                        <div class="day-price">${{ number_format($dayData['avgPrice'] ?? 0, 2) }}</div>
                        <div class="day-volume">Vol: {{ number_format($dayData['totalVolume'] ?? 0) }}</div>
                        <div class="day-transactions">{{ $dayData['transactionCount'] ?? 0 }} trans</div>
                    @else
                        <div class="no-data-indicator">No Data</div>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="chart-container">
            <h2>Daily Transaction Pattern</h2>
            <canvas id="dailyPattern" height="80"></canvas>
        </div>

        <div class="table-container">
            <h2>Daily Summary Table</h2>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Day</th>
                        <th>Transactions</th>
                        <th>Avg Price</th>
                        <th>Min Price</th>
                        <th>Max Price</th>
                        <th>Total Volume</th>
                        <th>Total Revenue</th>
                        <th>Quality</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($dailyBreakdown as $date => $dayData)
                        @php
                            $dateObj = \Carbon\Carbon::parse($date);
                            $hasData = !isset($dayData['is_gap']) || !$dayData['is_gap'];
                        @endphp
                        <tr>
                            <td><strong>{{ $dateObj->format('d M Y') }}</strong></td>
                            <td>{{ $dateObj->format('l') }}</td>
                            <td>{{ $hasData ? ($dayData['transactionCount'] ?? 0) : '-' }}</td>
                            <td>{{ $hasData && isset($dayData['avgPrice']) ? '$' . number_format($dayData['avgPrice'], 2) : '-' }}</td>
                            <td>{{ $hasData && isset($dayData['minPrice']) ? '$' . number_format($dayData['minPrice'], 2) : '-' }}</td>
                            <td>{{ $hasData && isset($dayData['maxPrice']) ? '$' . number_format($dayData['maxPrice'], 2) : '-' }}</td>
                            <td>{{ $hasData ? number_format($dayData['totalVolume'] ?? 0) : '-' }}</td>
                            <td>{{ $hasData && isset($dayData['totalRevenue']) ? '$' . number_format($dayData['totalRevenue'], 2) : '-' }}</td>
                            <td>
                                @if($hasData)
                                    @if(($dayData['transactionCount'] ?? 0) > 10)
                                        <span class="quality-high">High</span>
                                    @elseif(($dayData['transactionCount'] ?? 0) > 5)
                                        <span class="quality-medium">Medium</span>
                                    @else
                                        <span class="quality-low">Low</span>
                                    @endif
                                @else
                                    <span class="quality-low">No Data</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Daily pattern chart
        const ctx = document.getElementById('dailyPattern').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: @json(array_keys($dailyBreakdown)),
                datasets: [{
                    label: 'Transactions',
                    data: @json(array_map(function($d) {
                        return isset($d['is_gap']) && $d['is_gap'] ? 0 : ($d['transactionCount'] ?? 0);
                    }, $dailyBreakdown)),
                    backgroundColor: function(context) {
                        const value = context.parsed.y;
                        return value === 0 ? 'rgba(239, 68, 68, 0.3)' : 'rgba(16, 185, 129, 0.6)';
                    },
                    borderColor: function(context) {
                        const value = context.parsed.y;
                        return value === 0 ? '#ef4444' : '#10B981';
                    },
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.parsed.y + ' transactions';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        display: false
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Daily Transactions'
                        }
                    }
                }
            }
        });

        function showDayDetails(date) {
            alert('Details for ' + date + ' would be shown here in a modal');
        }
    </script>
</body>
</html>