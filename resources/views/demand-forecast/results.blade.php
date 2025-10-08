<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forecast Results - Sydney Markets</title>
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
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .header h1 {
            color: #333;
            margin-bottom: 10px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .stat-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
        }

        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #10B981;
        }

        .stat-label {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .product-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .product-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .product-name {
            font-size: 20px;
            font-weight: bold;
            color: #333;
        }

        .trend-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .trend-up {
            background: #d4edda;
            color: #155724;
        }

        .trend-down {
            background: #f8d7da;
            color: #721c24;
        }

        .trend-stable {
            background: #fff3cd;
            color: #856404;
        }

        .historical-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }

        .hist-stat {
            text-align: center;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .hist-stat-value {
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }

        .hist-stat-label {
            font-size: 11px;
            color: #666;
            margin-top: 3px;
        }

        .forecast-table {
            width: 100%;
            margin-bottom: 20px;
        }

        .forecast-table th {
            background: #f8f9fa;
            padding: 10px;
            text-align: left;
            font-size: 12px;
            color: #666;
            font-weight: 600;
        }

        .forecast-table td {
            padding: 10px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
        }

        .forecast-table tr:last-child td {
            border-bottom: none;
        }

        .weekend {
            background: #fff9e6;
        }

        .confidence-range {
            color: #666;
            font-size: 12px;
        }

        .insights {
            background: #e8f5e9;
            border-left: 4px solid #10B981;
            padding: 15px;
            border-radius: 8px;
        }

        .insights h4 {
            color: #2e7d32;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .insights ul {
            list-style: none;
            padding: 0;
        }

        .insights li {
            padding: 5px 0;
            font-size: 13px;
            color: #1b5e20;
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

        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .mini-chart {
            display: flex;
            align-items: flex-end;
            justify-content: space-around;
            height: 100px;
            margin-top: 15px;
        }

        .bar {
            flex: 1;
            background: linear-gradient(to top, #10B981, #34d399);
            margin: 0 2px;
            border-radius: 3px 3px 0 0;
            position: relative;
            transition: all 0.3s;
        }

        .bar:hover {
            background: linear-gradient(to top, #059669, #10B981);
        }

        .bar-label {
            position: absolute;
            bottom: -20px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 10px;
            color: #666;
        }

        .bar-value {
            position: absolute;
            top: -20px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 10px;
            font-weight: bold;
            color: #333;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="{{ route('demand-forecast.index') }}" class="back-btn">← Back to Upload</a>

        <div class="header">
            <h1>📊 Demand Forecast Results</h1>
            <div style="margin-top: 15px;">
                <a href="{{ route('demand-forecast.price-chart') }}"
                   style="display: inline-block; background: #10B981; color: white; padding: 10px 25px; border-radius: 8px; text-decoration: none; font-weight: 600;">
                    📈 View All Price Charts
                </a>
            </div>

            @if($uploadStats)
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value">{{ $uploadStats['valid_rows'] }}</div>
                    <div class="stat-label">Records Processed</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">{{ count($uploadStats['products']) }}</div>
                    <div class="stat-label">Products Found</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">{{ count($uploadStats['buyers']) }}</div>
                    <div class="stat-label">Unique Buyers</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">${{ number_format($uploadStats['total_revenue'], 2) }}</div>
                    <div class="stat-label">Total Revenue</div>
                </div>
                @if($uploadStats['date_range']['start'])
                <div class="stat-card">
                    <div class="stat-value">{{ \Carbon\Carbon::parse($uploadStats['date_range']['start'])->format('d M') }}</div>
                    <div class="stat-label">Start Date</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">{{ \Carbon\Carbon::parse($uploadStats['date_range']['end'])->format('d M') }}</div>
                    <div class="stat-label">End Date</div>
                </div>
                @endif
            </div>
            @endif
        </div>

        @if($forecastData && count($forecastData) > 0)
            <div class="chart-container">
                <h3>📈 Top Products - 7 Day Forecast</h3>
                <p style="color: #666; margin-top: 5px; font-size: 14px;">
                    Showing demand predictions for your highest volume products
                </p>
            </div>

            <div class="products-grid">
                @foreach($forecastData as $product => $forecast)
                    @if(!isset($forecast['error']))
                    <div class="product-card">
                        <div class="product-header">
                            <div class="product-name">
                                {{ $product }}
                                <a href="{{ route('demand-forecast.price-chart', ['product' => $product]) }}"
                                   style="font-size: 14px; color: #10B981; text-decoration: none; margin-left: 10px;">
                                    📈 View Price Chart
                                </a>
                            </div>
                            <div class="trend-badge trend-{{ $forecast['trend']['direction'] }}">
                                @if($forecast['trend']['direction'] == 'increasing')
                                    ↑ {{ abs($forecast['trend']['percentage']) }}%
                                @elseif($forecast['trend']['direction'] == 'decreasing')
                                    ↓ {{ abs($forecast['trend']['percentage']) }}%
                                @else
                                    → Stable
                                @endif
                            </div>
                        </div>

                        <div class="historical-stats">
                            <div class="hist-stat">
                                <div class="hist-stat-value">{{ $forecast['historical']['average'] }}</div>
                                <div class="hist-stat-label">Avg Daily</div>
                            </div>
                            <div class="hist-stat">
                                <div class="hist-stat-value">{{ $forecast['historical']['recent_average'] }}</div>
                                <div class="hist-stat-label">Recent Avg</div>
                            </div>
                            <div class="hist-stat">
                                <div class="hist-stat-value">{{ $forecast['historical']['data_points'] }}</div>
                                <div class="hist-stat-label">Data Points</div>
                            </div>
                        </div>

                        <div class="mini-chart">
                            @foreach($forecast['predictions'] as $pred)
                                @php
                                    $maxVal = max(array_column($forecast['predictions'], 'quantity'));
                                    $height = $maxVal > 0 ? ($pred['quantity'] / $maxVal) * 100 : 0;
                                @endphp
                                <div class="bar" style="height: {{ $height }}%">
                                    <div class="bar-value">{{ round($pred['quantity']) }}</div>
                                    <div class="bar-label">{{ substr($pred['day'], 0, 3) }}</div>
                                </div>
                            @endforeach
                        </div>

                        <table class="forecast-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Day</th>
                                    <th>Forecast</th>
                                    <th>Range (95%)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($forecast['predictions'] as $pred)
                                <tr class="{{ in_array($pred['day'], ['Friday', 'Saturday', 'Sunday']) ? 'weekend' : '' }}">
                                    <td>{{ \Carbon\Carbon::parse($pred['date'])->format('d M') }}</td>
                                    <td>{{ $pred['day'] }}</td>
                                    <td><strong>{{ $pred['quantity'] }}</strong></td>
                                    <td class="confidence-range">
                                        {{ $pred['confidence_lower'] }} - {{ $pred['confidence_upper'] }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>

                        @if(!empty($forecast['insights']))
                        <div class="insights">
                            <h4>💡 Insights & Recommendations</h4>
                            <ul>
                                @foreach($forecast['insights'] as $insight)
                                    <li>{{ $insight }}</li>
                                @endforeach
                            </ul>
                        </div>
                        @endif
                    </div>
                    @endif
                @endforeach
            </div>

            @if(count($products) > 10)
            <div class="chart-container">
                <h3>📋 Additional Products Found</h3>
                <p style="color: #666; margin-top: 10px;">
                    We found {{ count($products) }} products in your data. Showing forecasts for top 10 by volume.
                    Other products: {{ implode(', ', array_slice($products, 10, 20)) }}{{ count($products) > 30 ? '...' : '' }}
                </p>
            </div>
            @endif

        @else
            <div class="chart-container">
                <div class="no-data">
                    <h3>No forecast data available</h3>
                    <p>Please upload a file with sales data to generate forecasts.</p>
                </div>
            </div>
        @endif
    </div>
</body>
</html>