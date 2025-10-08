<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Price Analysis - {{ $product ?? 'All Products' }}</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/date-fns@2.29.3/index.min.js"></script>
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
            max-width: 1600px;
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

        .header p {
            color: #666;
            font-size: 14px;
        }

        .controls {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .control-row {
            display: flex;
            gap: 20px;
            align-items: center;
            flex-wrap: wrap;
        }

        .control-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .control-group label {
            font-size: 12px;
            color: #666;
            font-weight: 600;
            text-transform: uppercase;
        }

        .control-group select,
        .control-group button {
            padding: 10px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            background: white;
            cursor: pointer;
            transition: all 0.3s;
        }

        .control-group select:focus,
        .control-group select:hover {
            border-color: #10B981;
            outline: none;
        }

        .btn-primary {
            background: #10B981;
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary:hover {
            background: #059669;
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(16, 185, 129, 0.3);
        }

        .time-filters {
            display: flex;
            gap: 10px;
            margin-left: auto;
        }

        .time-btn {
            padding: 8px 16px;
            border: 2px solid #e5e7eb;
            background: white;
            border-radius: 8px;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .time-btn:hover {
            border-color: #10B981;
        }

        .time-btn.active {
            background: #10B981;
            color: white;
            border-color: #10B981;
        }

        .charts-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .chart-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .chart-card h2 {
            color: #333;
            font-size: 18px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .chart-container {
            position: relative;
            height: 400px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .stat-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #10B981;
        }

        .stat-label {
            font-size: 11px;
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

        .stat-change {
            font-size: 13px;
            margin-top: 5px;
        }

        .stat-up {
            color: #10B981;
        }

        .stat-down {
            color: #ef4444;
        }

        .insights-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .insights-card h2 {
            color: #333;
            font-size: 18px;
            margin-bottom: 20px;
        }

        .insight-item {
            background: linear-gradient(to right, #f0fdf4, #dcfce7);
            border-left: 3px solid #10B981;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 8px;
        }

        .insight-item h3 {
            color: #166534;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .insight-item p {
            color: #15803d;
            font-size: 13px;
            line-height: 1.6;
        }

        .data-table {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
        }

        .data-table h2 {
            color: #333;
            font-size: 18px;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-size: 12px;
            color: #666;
            font-weight: 600;
            text-transform: uppercase;
            border-bottom: 2px solid #e5e7eb;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
        }

        tr:hover {
            background: #f8f9fa;
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

        .loading {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        .no-data {
            text-align: center;
            padding: 60px;
            color: #999;
            background: #f8f9fa;
            border-radius: 15px;
        }

        .volume-bar {
            background: #e5e7eb;
            height: 20px;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 10px;
        }

        .volume-fill {
            background: linear-gradient(to right, #10B981, #34d399);
            height: 100%;
            transition: width 0.5s ease;
        }

        .legend-item {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-right: 20px;
            font-size: 13px;
        }

        .legend-color {
            width: 12px;
            height: 12px;
            border-radius: 2px;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="{{ route('demand-forecast.index') }}" class="back-btn">← Back to Forecast Tool</a>

        <div class="header">
            <h1>📊 Historical Price Chart</h1>
            <p>Showing ACTUAL data from your file for {{ $product ?? 'all products' }} | {{ $totalRecords }} records | {{ $dateRange }}</p>

            @if(request()->has('debug'))
            <div style="background: #fef3c7; border: 2px solid #f59e0b; padding: 15px; border-radius: 8px; margin-top: 15px;">
                <h3 style="color: #92400e; margin-bottom: 10px;">🔍 Debug Mode - Data Source</h3>
                <p style="color: #92400e; font-size: 14px;">
                    <strong>Data Source:</strong> Your uploaded file (session data)<br>
                    <strong>Total Records:</strong> {{ $totalRecords }}<br>
                    <strong>Date Range in Data:</strong> {{ $dateRange }}<br>
                    <strong>Products Found:</strong> {{ implode(', ', array_slice($products ?? [], 0, 10)) }}{{ count($products ?? []) > 10 ? '...' : '' }}<br>
                    <strong>First Data Point:</strong>
                    @if(!empty($chartData['labels']))
                        {{ $chartData['labels'][0] ?? 'N/A' }} - Price: ${{ $chartData['prices'][0] ?? '0' }}
                    @else
                        No data points
                    @endif
                </p>
            </div>
            @endif

            <div style="margin-top: 15px;">
                <a href="{{ route('demand-forecast.daily-analysis', ['product' => $product]) }}"
                   style="display: inline-block; background: #3b82f6; color: white; padding: 10px 25px; border-radius: 8px; text-decoration: none; font-weight: 600;">
                    📅 View Daily Analysis
                </a>

                @if(!request()->has('debug'))
                <a href="?{{ http_build_query(array_merge(request()->all(), ['debug' => 1])) }}"
                   style="display: inline-block; background: #f59e0b; color: white; padding: 10px 25px; border-radius: 8px; text-decoration: none; font-weight: 600; margin-left: 10px;">
                    🔍 Show Debug Info
                </a>
                @endif
            </div>
        </div>

        <div class="controls">
            <div class="control-row">
                <div class="control-group">
                    <label>Product</label>
                    <select id="product-select" onchange="updateProduct()">
                        <option value="">All Products</option>
                        @foreach($products as $p)
                            <option value="{{ $p }}" {{ $product == $p ? 'selected' : '' }}>{{ $p }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="control-group">
                    <label>Comparison</label>
                    <select id="compare-select" onchange="updateComparison()">
                        <option value="">None</option>
                        @foreach($products as $p)
                            @if($p != $product)
                                <option value="{{ $p }}">{{ $p }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>

                <div class="time-filters">
                    <button class="time-btn" onclick="setTimeRange(7)">7 Days</button>
                    <button class="time-btn" onclick="setTimeRange(30)">30 Days</button>
                    <button class="time-btn" onclick="setTimeRange(90)">90 Days</button>
                    <button class="time-btn active" onclick="setTimeRange('all')">All Time</button>
                </div>
            </div>
        </div>

        @if($chartData && count($chartData['labels']) > 0)

        @if(request()->has('debug'))
        <!-- Raw Data Sample -->
        <div class="chart-card" style="background: #fef3c7; margin-bottom: 20px;">
            <h2>📋 Raw Data Sample (First 10 records from YOUR file)</h2>
            <div style="overflow-x: auto;">
                <table style="width: 100%; font-size: 12px;">
                    <thead>
                        <tr style="background: #f59e0b; color: white;">
                            <th style="padding: 8px;">#</th>
                            <th style="padding: 8px;">Date</th>
                            <th style="padding: 8px;">Product</th>
                            <th style="padding: 8px;">Quantity</th>
                            <th style="padding: 8px;">Rate</th>
                            <th style="padding: 8px;">Total</th>
                            <th style="padding: 8px;">Buyer</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(array_slice($recentData ?? [], 0, 10) as $index => $row)
                        <tr style="background: white;">
                            <td style="padding: 8px;">{{ $index + 1 }}</td>
                            <td style="padding: 8px;">{{ $row['date'] ?? 'N/A' }}</td>
                            <td style="padding: 8px;"><strong>{{ $row['product'] ?? 'N/A' }}</strong></td>
                            <td style="padding: 8px;">{{ $row['quantity'] ?? 0 }}</td>
                            <td style="padding: 8px;">${{ number_format($row['rate'] ?? 0, 2) }}</td>
                            <td style="padding: 8px;">${{ number_format($row['total'] ?? 0, 2) }}</td>
                            <td style="padding: 8px;">{{ $row['buyer'] ?? 'N/A' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p style="margin-top: 10px; color: #92400e; font-size: 12px;">
                This data comes directly from YOUR uploaded file. No fake data is being used.
            </p>
        </div>
        @endif

        <!-- Data Quality Summary -->
        <div class="chart-card" style="background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); margin-bottom: 20px;">
            <h2>📊 Data Summary</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Date Range</div>
                    <div class="stat-value" style="font-size: 16px;">{{ $chartData['statistics']['dateRange'] ?? 'N/A' }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Days</div>
                    <div class="stat-value">{{ $chartData['statistics']['totalDays'] ?? 0 }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Days with Data</div>
                    <div class="stat-value" style="color: #10B981;">{{ $chartData['statistics']['daysWithData'] ?? 0 }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Missing Days</div>
                    <div class="stat-value" style="color: #ef4444;">{{ $chartData['statistics']['daysWithoutData'] ?? 0 }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Transactions</div>
                    <div class="stat-value">{{ number_format($chartData['statistics']['totalTransactions'] ?? 0) }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Avg Trans/Day</div>
                    <div class="stat-value">{{ $chartData['statistics']['avgTransactionsPerDay'] ?? 0 }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Volume</div>
                    <div class="stat-value">{{ number_format($chartData['statistics']['totalVolume'] ?? 0) }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Revenue</div>
                    <div class="stat-value">${{ number_format($chartData['statistics']['totalRevenue'] ?? 0, 2) }}</div>
                </div>
            </div>
        </div>

        <div class="charts-grid">
            <div class="chart-card">
                <h2>💰 Historical Price Data From Your File</h2>
                <p style="font-size: 12px; color: #666; margin-top: 5px;">
                    Chart shows ONLY the actual data points from your uploaded file. No forecasting or predictions.
                </p>
                <div class="chart-container">
                    <canvas id="priceChart"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <h2>📊 Price Statistics</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-label">Current Price</div>
                        <div class="stat-value">${{ number_format($priceStats['current'], 2) }}</div>
                        <div class="stat-change {{ $priceStats['trend'] > 0 ? 'stat-up' : 'stat-down' }}">
                            {{ $priceStats['trend'] > 0 ? '↑' : '↓' }} {{ abs($priceStats['trend']) }}%
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-label">Average Price</div>
                        <div class="stat-value">${{ number_format($priceStats['average'], 2) }}</div>
                        <div class="stat-change">Last 30 days</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-label">Lowest Price</div>
                        <div class="stat-value">${{ number_format($priceStats['min'], 2) }}</div>
                        <div class="stat-change">{{ $priceStats['min_date'] }}</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-label">Highest Price</div>
                        <div class="stat-value">${{ number_format($priceStats['max'], 2) }}</div>
                        <div class="stat-change">{{ $priceStats['max_date'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="charts-grid">
            <div class="chart-card">
                <h2>📈 Volume vs Price Correlation</h2>
                <div class="chart-container">
                    <canvas id="volumeChart"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <h2>🎯 Price Distribution</h2>
                <div class="chart-container">
                    <canvas id="distributionChart"></canvas>
                </div>
            </div>
        </div>

        <div class="insights-card">
            <h2>💡 Historical Data Analysis</h2>

            @foreach($insights as $insight)
            <div class="insight-item">
                <h3>{{ $insight['title'] }}</h3>
                <p>{{ $insight['description'] }}</p>
            </div>
            @endforeach
        </div>

        <div class="data-table">
            <h2>📋 All Sales Records
                <a href="{{ route('demand-forecast.all-sales', ['product' => $product]) }}"
                   style="float: right; background: #f59e0b; color: white; padding: 8px 20px; border-radius: 6px; text-decoration: none; font-size: 14px; font-weight: 600;">
                    🔍 View All Sales with Daily Max
                </a>
            </h2>
            <div style="max-height: 600px; overflow-y: auto;">
                <table>
                    <thead style="position: sticky; top: 0; background: #f8f9fa; z-index: 10;">
                        <tr>
                            <th>#</th>
                            <th>Date</th>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Total</th>
                            <th>Buyer</th>
                            <th>Daily Max</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            // Calculate daily maximums
                            $dailyMax = [];
                            foreach($recentData as $row) {
                                $date = $row['date'];
                                if (!isset($dailyMax[$date]) || $row['total'] > $dailyMax[$date]) {
                                    $dailyMax[$date] = $row['total'];
                                }
                            }
                        @endphp
                        @foreach($recentData as $index => $row)
                        @php
                            $isMax = isset($dailyMax[$row['date']]) && $dailyMax[$row['date']] == $row['total'];
                        @endphp
                        <tr style="{{ $isMax ? 'background: linear-gradient(to right, #fef3c7, #fde68a); font-weight: 600;' : '' }}">
                            <td>{{ $index + 1 }}</td>
                            <td>{{ \Carbon\Carbon::parse($row['date'])->format('d M Y') }}</td>
                            <td><strong>{{ $row['product'] }}</strong></td>
                            <td>{{ number_format($row['quantity']) }}</td>
                            <td>${{ number_format($row['rate'], 2) }}</td>
                            <td>${{ number_format($row['total'], 2) }}</td>
                            <td>{{ $row['buyer'] ?? '-' }}</td>
                            <td>
                                @if($isMax)
                                    <span style="background: #f59e0b; color: white; padding: 2px 8px; border-radius: 12px; font-size: 11px;">
                                        DAILY MAX
                                    </span>
                                @else
                                    <span style="color: #999;">-</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        @else
        <div class="no-data">
            <h3>No Price Data Available</h3>
            <p>Please upload sales data first to view price analysis.</p>
        </div>
        @endif
    </div>

    @if($chartData && count($chartData['labels']) > 0)
    <script>
        // Enhanced Daily Price Chart with Gap Detection
        const priceCtx = document.getElementById('priceChart').getContext('2d');
        const priceChart = new Chart(priceCtx, {
            type: 'line',
            data: {
                labels: @json($chartData['labels']),
                datasets: [{
                    label: 'Actual Price (From Your Data)',
                    data: @json($chartData['prices']),
                    borderColor: '#10B981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.1,
                    fill: true,
                    pointRadius: 5,
                    pointBackgroundColor: '#10B981',
                    pointBorderColor: '#059669',
                    pointHoverRadius: 7,
                    borderWidth: 2,
                    spanGaps: false // Don't connect across gaps - show actual data only
                }, {
                    label: 'Min Price',
                    data: @json($chartData['minPrices'] ?? []),
                    borderColor: 'rgba(239, 68, 68, 0.3)',
                    backgroundColor: 'transparent',
                    borderDash: [2, 2],
                    pointRadius: 0,
                    borderWidth: 1
                }, {
                    label: 'Max Price',
                    data: @json($chartData['maxPrices'] ?? []),
                    borderColor: 'rgba(34, 197, 94, 0.3)',
                    backgroundColor: 'transparent',
                    borderDash: [2, 2],
                    pointRadius: 0,
                    borderWidth: 1
                }, {
                    label: 'Moving Average (7-day)',
                    data: @json($chartData['movingAverage']),
                    borderColor: '#6366f1',
                    backgroundColor: 'transparent',
                    tension: 0.4,
                    borderDash: [5, 5],
                    pointRadius: 0,
                    borderWidth: 2,
                    spanGaps: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom'
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': $' + context.parsed.y.toFixed(2);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toFixed(2);
                            }
                        },
                        title: {
                            display: true,
                            text: 'Price per Unit'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Date'
                        }
                    }
                }
            }
        });

        // Enhanced Volume & Transaction Chart
        const volumeCtx = document.getElementById('volumeChart').getContext('2d');
        const volumeChart = new Chart(volumeCtx, {
            type: 'bar',
            data: {
                labels: @json($chartData['labels']),
                datasets: [{
                    label: 'Daily Volume',
                    data: @json($chartData['volumes']),
                    backgroundColor: function(context) {
                        const value = context.parsed.y;
                        return value === 0 ? 'rgba(239, 68, 68, 0.3)' : 'rgba(16, 185, 129, 0.6)';
                    },
                    borderColor: function(context) {
                        const value = context.parsed.y;
                        return value === 0 ? '#ef4444' : '#10B981';
                    },
                    borderWidth: 1,
                    yAxisID: 'y'
                }, {
                    label: 'Transaction Count',
                    data: @json($chartData['transactionCounts'] ?? []),
                    type: 'line',
                    borderColor: '#8b5cf6',
                    backgroundColor: 'transparent',
                    tension: 0.3,
                    borderWidth: 2,
                    pointRadius: 2,
                    pointBackgroundColor: '#8b5cf6',
                    yAxisID: 'y2'
                }, {
                    label: 'Daily Revenue',
                    data: @json($chartData['dailyTotals'] ?? []),
                    type: 'line',
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245, 158, 11, 0.1)',
                    tension: 0.3,
                    borderWidth: 2,
                    pointRadius: 0,
                    yAxisID: 'y1',
                    hidden: true // Hidden by default, user can toggle
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom'
                    }
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Daily Volume (Units)'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        grid: {
                            drawOnChartArea: false
                        },
                        title: {
                            display: true,
                            text: 'Revenue ($)'
                        },
                        ticks: {
                            callback: function(value) {
                                return '$' + (value / 1000).toFixed(0) + 'k';
                            }
                        }
                    },
                    y2: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        grid: {
                            drawOnChartArea: false
                        },
                        title: {
                            display: false
                        },
                        ticks: {
                            display: false
                        }
                    }
                }
            }
        });

        // Distribution Chart
        const distCtx = document.getElementById('distributionChart').getContext('2d');
        const distributionChart = new Chart(distCtx, {
            type: 'doughnut',
            data: {
                labels: @json($priceDistribution['labels']),
                datasets: [{
                    data: @json($priceDistribution['values']),
                    backgroundColor: [
                        '#10B981',
                        '#34d399',
                        '#6ee7b7',
                        '#a7f3d0',
                        '#d1fae5'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            font: {
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + context.parsed + '%';
                            }
                        }
                    }
                }
            }
        });

        // Time range functions
        function setTimeRange(days) {
            const buttons = document.querySelectorAll('.time-btn');
            buttons.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');

            const product = document.getElementById('product-select').value;
            const range = days === 'all' ? '' : '&range=' + days;
            window.location.href = `{{ route('demand-forecast.price-chart') }}?product=${product}${range}`;
        }

        function updateProduct() {
            const product = document.getElementById('product-select').value;
            window.location.href = `{{ route('demand-forecast.price-chart') }}?product=${product}`;
        }

        function updateComparison() {
            const product = document.getElementById('product-select').value;
            const compare = document.getElementById('compare-select').value;
            if (compare) {
                window.location.href = `{{ route('demand-forecast.price-chart') }}?product=${product}&compare=${compare}`;
            }
        }
    </script>
    @endif
</body>
</html>