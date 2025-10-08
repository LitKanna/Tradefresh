@extends('layouts.admin')

@section('title', 'Executive Command Center')

@section('content')
<style>
    /* Executive Command Center Theme */
    :root {
        --primary-dark: #0f172a;
        --primary-blue: #2C3E50;
        --accent-blue: #6B7280;
        --accent-green: #10b981;
        --accent-red: #2C3E50;
        --accent-amber: #0D7C66;
        --text-primary: #f1f5f9;
        --text-secondary: #94a3b8;
        --border-color: #1e293b;
        --card-bg: #1e293b;
        --hover-bg: #334155;
    }

    /* Main Container */
    .command-center {
        background: var(--primary-dark);
        padding: calc(1.5rem * var(--scale-factor));
        min-height: calc(100vh - 80px);
        font-family: 'SF Mono', 'Monaco', 'Inconsolata', monospace;
    }

    /* Grid Layout */
    .executive-grid {
        display: grid;
        grid-template-columns: repeat(12, 1fr);
        grid-template-rows: auto auto 1fr auto;
        gap: calc(1rem * var(--scale-factor));
        height: calc(100vh - 128px);
    }

    /* Header Metrics Bar */
    .metrics-bar {
        grid-column: 1 / -1;
        display: grid;
        grid-template-columns: repeat(6, 1fr);
        gap: calc(1rem * var(--scale-factor));
    }

    .metric-tile {
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: calc(4px * var(--scale-factor));
        padding: calc(1rem * var(--scale-factor));
        position: relative;
        overflow: hidden;
        transition: all 0.2s ease;
    }

    .metric-tile::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: calc(3px * var(--scale-factor));
        height: 100%;
        background: var(--accent-blue);
    }

    .metric-tile:hover {
        background: var(--hover-bg);
        transform: translateY(-1px);
    }

    .metric-header {
        font-size: calc(0.625rem * var(--scale-factor));
        color: var(--text-secondary);
        text-transform: uppercase;
        letter-spacing: 0.1em;
        margin-bottom: calc(0.5rem * var(--scale-factor));
        font-weight: 600;
    }

    .metric-main {
        display: flex;
        align-items: baseline;
        gap: calc(0.5rem * var(--scale-factor));
        margin-bottom: calc(0.25rem * var(--scale-factor));
    }

    .metric-number {
        font-size: calc(1.75rem * var(--scale-factor));
        font-weight: 700;
        color: var(--text-primary);
        font-variant-numeric: tabular-nums;
    }

    .metric-unit {
        font-size: calc(0.875rem * var(--scale-factor));
        color: var(--text-secondary);
    }

    .metric-delta {
        display: flex;
        align-items: center;
        gap: calc(0.25rem * var(--scale-factor));
        font-size: calc(0.75rem * var(--scale-factor));
        font-weight: 600;
    }

    .metric-delta.positive {
        color: var(--accent-green);
    }

    .metric-delta.negative {
        color: var(--accent-red);
    }

    .metric-delta svg {
        width: calc(12px * var(--scale-factor));
        height: calc(12px * var(--scale-factor));
    }

    /* Quick Actions Strip */
    .actions-strip {
        grid-column: 1 / -1;
        display: grid;
        grid-template-columns: repeat(8, 1fr);
        gap: calc(0.75rem * var(--scale-factor));
    }

    .action-button {
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: calc(4px * var(--scale-factor));
        padding: calc(0.75rem * var(--scale-factor));
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .action-button:hover {
        background: var(--hover-bg);
        border-color: var(--accent-blue);
        transform: translateY(-1px);
    }

    .action-content {
        display: flex;
        flex-direction: column;
        gap: calc(0.25rem * var(--scale-factor));
    }

    .action-label {
        font-size: calc(0.625rem * var(--scale-factor));
        color: var(--text-secondary);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        font-weight: 600;
    }

    .action-value {
        font-size: calc(1.25rem * var(--scale-factor));
        font-weight: 700;
        color: var(--text-primary);
    }

    .action-indicator {
        width: calc(8px * var(--scale-factor));
        height: calc(8px * var(--scale-factor));
        border-radius: 50%;
        background: var(--accent-amber);
        animation: blink 2s infinite;
    }

    .action-indicator.critical {
        background: var(--accent-red);
    }

    @keyframes blink {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.3; }
    }

    /* Main Dashboard Area */
    .dashboard-main {
        grid-column: 1 / -1;
        display: grid;
        grid-template-columns: 3fr 2fr 2fr;
        gap: calc(1rem * var(--scale-factor));
        min-height: 0;
    }

    /* Transaction Monitor */
    .transaction-monitor {
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: calc(4px * var(--scale-factor));
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .monitor-header {
        background: var(--primary-blue);
        padding: calc(0.75rem * var(--scale-factor)) calc(1rem * var(--scale-factor));
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-bottom: 1px solid var(--border-color);
    }

    .monitor-title {
        font-size: calc(0.75rem * var(--scale-factor));
        color: var(--text-primary);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        display: flex;
        align-items: center;
        gap: calc(0.5rem * var(--scale-factor));
    }

    .live-dot {
        width: calc(6px * var(--scale-factor));
        height: calc(6px * var(--scale-factor));
        background: var(--accent-green);
        border-radius: 50%;
        animation: pulse-live 2s infinite;
    }

    @keyframes pulse-live {
        0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
        70% { box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
        100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
    }

    .monitor-controls {
        display: flex;
        gap: calc(0.5rem * var(--scale-factor));
    }

    .control-btn {
        padding: calc(0.25rem * var(--scale-factor)) calc(0.5rem * var(--scale-factor));
        background: transparent;
        border: 1px solid var(--border-color);
        border-radius: calc(2px * var(--scale-factor));
        color: var(--text-secondary);
        font-size: calc(0.625rem * var(--scale-factor));
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .control-btn:hover {
        background: var(--accent-blue);
        color: white;
        border-color: var(--accent-blue);
    }

    .transaction-feed {
        flex: 1;
        overflow-y: auto;
        padding: calc(0.5rem * var(--scale-factor));
    }

    .transaction-row {
        display: grid;
        grid-template-columns: calc(60px * var(--scale-factor)) calc(80px * var(--scale-factor)) 1fr calc(100px * var(--scale-factor)) calc(80px * var(--scale-factor));
        gap: calc(0.75rem * var(--scale-factor));
        padding: calc(0.5rem * var(--scale-factor));
        border-bottom: 1px solid rgba(30, 41, 59, 0.5);
        font-size: calc(0.75rem * var(--scale-factor));
        align-items: center;
        transition: background 0.15s ease;
    }

    .transaction-row:hover {
        background: rgba(51, 65, 85, 0.3);
    }

    .txn-time {
        color: var(--text-secondary);
        font-family: 'SF Mono', monospace;
        font-size: calc(0.625rem * var(--scale-factor));
    }

    .txn-type {
        padding: calc(0.125rem * var(--scale-factor)) calc(0.375rem * var(--scale-factor));
        border-radius: calc(2px * var(--scale-factor));
        font-size: calc(0.625rem * var(--scale-factor));
        font-weight: 700;
        text-align: center;
        text-transform: uppercase;
    }

    .txn-type.rfq {
        background: rgba(59, 130, 246, 0.2);
        color: #60a5fa;
        border: 1px solid rgba(59, 130, 246, 0.3);
    }

    .txn-type.po {
        background: rgba(16, 185, 129, 0.2);
        color: #34d399;
        border: 1px solid rgba(16, 185, 129, 0.3);
    }

    .txn-type.dispute {
        background: rgba(239, 68, 68, 0.2);
        color: #f87171;
        border: 1px solid rgba(239, 68, 68, 0.3);
    }

    .txn-parties {
        color: var(--text-primary);
        font-size: calc(0.7rem * var(--scale-factor));
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .txn-amount {
        color: var(--text-primary);
        font-weight: 600;
        text-align: right;
        font-variant-numeric: tabular-nums;
    }

    .txn-status {
        font-size: calc(0.625rem * var(--scale-factor));
        color: var(--text-secondary);
        text-align: center;
    }

    /* User Approvals Panel */
    .approvals-panel {
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: calc(4px * var(--scale-factor));
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .approvals-content {
        flex: 1;
        overflow-y: auto;
        padding: calc(0.75rem * var(--scale-factor));
    }

    .approval-item {
        background: rgba(51, 65, 85, 0.3);
        border: 1px solid var(--border-color);
        border-radius: calc(4px * var(--scale-factor));
        padding: calc(0.75rem * var(--scale-factor));
        margin-bottom: calc(0.75rem * var(--scale-factor));
        transition: all 0.2s ease;
    }

    .approval-item:hover {
        background: rgba(51, 65, 85, 0.5);
        transform: translateX(2px);
    }

    .approval-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: calc(0.5rem * var(--scale-factor));
    }

    .approval-type {
        font-size: calc(0.625rem * var(--scale-factor));
        padding: calc(0.125rem * var(--scale-factor)) calc(0.375rem * var(--scale-factor));
        border-radius: calc(2px * var(--scale-factor));
        font-weight: 700;
        text-transform: uppercase;
    }

    .approval-type.vendor {
        background: rgba(139, 92, 246, 0.2);
        color: #a78bfa;
        border: 1px solid rgba(139, 92, 246, 0.3);
    }

    .approval-type.buyer {
        background: rgba(34, 197, 94, 0.2);
        color: #4ade80;
        border: 1px solid rgba(34, 197, 94, 0.3);
    }

    .approval-time {
        font-size: calc(0.625rem * var(--scale-factor));
        color: var(--text-secondary);
    }

    .approval-details {
        font-size: calc(0.7rem * var(--scale-factor));
        color: var(--text-primary);
        margin-bottom: calc(0.5rem * var(--scale-factor));
    }

    .approval-actions {
        display: flex;
        gap: calc(0.5rem * var(--scale-factor));
    }

    .approve-btn, .reject-btn {
        flex: 1;
        padding: calc(0.375rem * var(--scale-factor));
        border: none;
        border-radius: calc(2px * var(--scale-factor));
        font-size: calc(0.625rem * var(--scale-factor));
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s ease;
        text-transform: uppercase;
    }

    .approve-btn {
        background: rgba(16, 185, 129, 0.2);
        color: var(--accent-green);
        border: 1px solid rgba(16, 185, 129, 0.3);
    }

    .approve-btn:hover {
        background: var(--accent-green);
        color: white;
    }

    .reject-btn {
        background: rgba(239, 68, 68, 0.2);
        color: var(--accent-red);
        border: 1px solid rgba(239, 68, 68, 0.3);
    }

    .reject-btn:hover {
        background: var(--accent-red);
        color: white;
    }

    /* System Status Panel */
    .system-panel {
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: calc(4px * var(--scale-factor));
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .system-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: calc(0.75rem * var(--scale-factor));
        padding: calc(0.75rem * var(--scale-factor));
    }

    .system-metric {
        background: rgba(51, 65, 85, 0.3);
        border: 1px solid var(--border-color);
        border-radius: calc(4px * var(--scale-factor));
        padding: calc(0.75rem * var(--scale-factor));
    }

    .system-label {
        font-size: calc(0.625rem * var(--scale-factor));
        color: var(--text-secondary);
        text-transform: uppercase;
        margin-bottom: calc(0.375rem * var(--scale-factor));
    }

    .system-value {
        font-size: calc(1.25rem * var(--scale-factor));
        font-weight: 700;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: calc(0.375rem * var(--scale-factor));
    }

    .system-indicator {
        width: calc(6px * var(--scale-factor));
        height: calc(6px * var(--scale-factor));
        border-radius: 50%;
    }

    .indicator-green {
        background: var(--accent-green);
    }

    .indicator-amber {
        background: var(--accent-amber);
    }

    .indicator-red {
        background: var(--accent-red);
    }

    /* Dispute Resolution Section */
    .disputes-section {
        padding: calc(0.75rem * var(--scale-factor));
        border-top: 1px solid var(--border-color);
    }

    .dispute-card {
        background: rgba(245, 158, 11, 0.1);
        border: 1px solid rgba(245, 158, 11, 0.3);
        border-radius: calc(4px * var(--scale-factor));
        padding: calc(0.75rem * var(--scale-factor));
        margin-bottom: calc(0.5rem * var(--scale-factor));
    }

    .dispute-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: calc(0.375rem * var(--scale-factor));
    }

    .dispute-id {
        font-size: calc(0.7rem * var(--scale-factor));
        font-weight: 600;
        color: var(--accent-amber);
    }

    .dispute-priority {
        font-size: calc(0.625rem * var(--scale-factor));
        padding: calc(0.125rem * var(--scale-factor)) calc(0.375rem * var(--scale-factor));
        border-radius: calc(2px * var(--scale-factor));
        font-weight: 700;
        text-transform: uppercase;
    }

    .priority-high {
        background: var(--accent-red);
        color: white;
    }

    .priority-medium {
        background: var(--accent-amber);
        color: white;
    }

    .dispute-info {
        font-size: calc(0.7rem * var(--scale-factor));
        color: var(--text-primary);
        margin-bottom: calc(0.5rem * var(--scale-factor));
    }

    .dispute-actions {
        display: flex;
        gap: calc(0.375rem * var(--scale-factor));
    }

    .dispute-btn {
        padding: calc(0.25rem * var(--scale-factor)) calc(0.5rem * var(--scale-factor));
        border: 1px solid var(--border-color);
        border-radius: calc(2px * var(--scale-factor));
        font-size: calc(0.625rem * var(--scale-factor));
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s ease;
        text-transform: uppercase;
        background: transparent;
        color: var(--text-secondary);
    }

    .dispute-btn:hover {
        background: var(--accent-amber);
        color: white;
        border-color: var(--accent-amber);
    }

    /* Status Footer */
    .status-footer {
        grid-column: 1 / -1;
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: calc(4px * var(--scale-factor));
        padding: calc(0.75rem * var(--scale-factor)) calc(1.25rem * var(--scale-factor));
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .footer-section {
        display: flex;
        gap: calc(2rem * var(--scale-factor));
    }

    .footer-item {
        display: flex;
        align-items: center;
        gap: calc(0.5rem * var(--scale-factor));
    }

    .footer-label {
        font-size: calc(0.625rem * var(--scale-factor));
        color: var(--text-secondary);
        text-transform: uppercase;
        font-weight: 600;
    }

    .footer-value {
        font-size: calc(0.75rem * var(--scale-factor));
        color: var(--text-primary);
        font-weight: 600;
    }

    .footer-divider {
        width: 1px;
        height: calc(20px * var(--scale-factor));
        background: var(--border-color);
    }

    /* Scrollbar Styling */
    ::-webkit-scrollbar {
        width: calc(8px * var(--scale-factor));
        height: calc(8px * var(--scale-factor));
    }

    ::-webkit-scrollbar-track {
        background: var(--primary-dark);
    }

    ::-webkit-scrollbar-thumb {
        background: var(--primary-blue);
        border-radius: calc(4px * var(--scale-factor));
    }

    ::-webkit-scrollbar-thumb:hover {
        background: var(--hover-bg);
    }
</style>

<div class="command-center">
    <div class="executive-grid">
        <!-- Top Metrics Bar -->
        <div class="metrics-bar">
            <div class="metric-tile">
                <div class="metric-header">GMV (24H)</div>
                <div class="metric-main">
                    <div class="metric-number">847.3</div>
                    <div class="metric-unit">K</div>
                </div>
                <div class="metric-delta positive">
                    <svg viewBox="0 0 12 12" fill="none">
                        <path d="M6 9V3m0 0L3 6m3-3l3 3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    +12.4%
                </div>
            </div>

            <div class="metric-tile">
                <div class="metric-header">Total Revenue</div>
                <div class="metric-main">
                    <div class="metric-number">2.47</div>
                    <div class="metric-unit">M</div>
                </div>
                <div class="metric-delta positive">
                    <svg viewBox="0 0 12 12" fill="none">
                        <path d="M6 9V3m0 0L3 6m3-3l3 3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    +8.7%
                </div>
            </div>

            <div class="metric-tile">
                <div class="metric-header">Active Users</div>
                <div class="metric-main">
                    <div class="metric-number">1,892</div>
                    <div class="metric-unit"></div>
                </div>
                <div class="metric-delta positive">
                    <svg viewBox="0 0 12 12" fill="none">
                        <path d="M6 9V3m0 0L3 6m3-3l3 3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    +127
                </div>
            </div>

            <div class="metric-tile">
                <div class="metric-header">Conversion Rate</div>
                <div class="metric-main">
                    <div class="metric-number">68.4</div>
                    <div class="metric-unit">%</div>
                </div>
                <div class="metric-delta negative">
                    <svg viewBox="0 0 12 12" fill="none">
                        <path d="M6 3v6m0 0l3-3m-3 3L3 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    -2.1%
                </div>
            </div>

            <div class="metric-tile">
                <div class="metric-header">Avg Order Value</div>
                <div class="metric-main">
                    <div class="metric-number">1,247</div>
                    <div class="metric-unit">USD</div>
                </div>
                <div class="metric-delta positive">
                    <svg viewBox="0 0 12 12" fill="none">
                        <path d="M6 9V3m0 0L3 6m3-3l3 3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    +5.3%
                </div>
            </div>

            <div class="metric-tile">
                <div class="metric-header">Platform Health</div>
                <div class="metric-main">
                    <div class="metric-number">99.8</div>
                    <div class="metric-unit">%</div>
                </div>
                <div class="metric-delta positive">
                    <svg viewBox="0 0 12 12" fill="none">
                        <path d="M6 9V3m0 0L3 6m3-3l3 3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    Stable
                </div>
            </div>
        </div>

        <!-- Quick Actions Strip -->
        <div class="actions-strip">
            <div class="action-button">
                <div class="action-content">
                    <div class="action-label">Pending</div>
                    <div class="action-value">24</div>
                </div>
                <div class="action-indicator critical"></div>
            </div>

            <div class="action-button">
                <div class="action-content">
                    <div class="action-label">Disputes</div>
                    <div class="action-value">7</div>
                </div>
                <div class="action-indicator"></div>
            </div>

            <div class="action-button">
                <div class="action-content">
                    <div class="action-label">Quotes</div>
                    <div class="action-value">142</div>
                </div>
                <div class="action-indicator" style="background: var(--accent-green)"></div>
            </div>

            <div class="action-button">
                <div class="action-content">
                    <div class="action-label">Orders</div>
                    <div class="action-value">89</div>
                </div>
                <div class="action-indicator" style="background: var(--accent-green)"></div>
            </div>

            <div class="action-button">
                <div class="action-content">
                    <div class="action-label">Vendors</div>
                    <div class="action-value">342</div>
                </div>
                <div class="action-indicator" style="background: var(--accent-green)"></div>
            </div>

            <div class="action-button">
                <div class="action-content">
                    <div class="action-label">Buyers</div>
                    <div class="action-value">1,550</div>
                </div>
                <div class="action-indicator" style="background: var(--accent-green)"></div>
            </div>

            <div class="action-button">
                <div class="action-content">
                    <div class="action-label">API Calls</div>
                    <div class="action-value">24K</div>
                </div>
                <div class="action-indicator" style="background: var(--accent-green)"></div>
            </div>

            <div class="action-button">
                <div class="action-content">
                    <div class="action-label">Alerts</div>
                    <div class="action-value">2</div>
                </div>
                <div class="action-indicator"></div>
            </div>
        </div>

        <!-- Main Dashboard Area -->
        <div class="dashboard-main">
            <!-- Live Transaction Monitor -->
            <div class="transaction-monitor">
                <div class="monitor-header">
                    <div class="monitor-title">
                        <span class="live-dot"></span>
                        Live Transaction Monitor
                    </div>
                    <div class="monitor-controls">
                        <button class="control-btn">Filter</button>
                        <button class="control-btn">Export</button>
                        <button class="control-btn">Settings</button>
                    </div>
                </div>
                <div class="transaction-feed">
                    <div class="transaction-row">
                        <div class="txn-time">14:32:18</div>
                        <div class="txn-type rfq">RFQ-8472</div>
                        <div class="txn-parties">MetroGrocers → GreenValleyFarms</div>
                        <div class="txn-amount">$45,230</div>
                        <div class="txn-status">PROCESSING</div>
                    </div>
                    <div class="transaction-row">
                        <div class="txn-time">14:31:45</div>
                        <div class="txn-type po">PO-3891</div>
                        <div class="txn-parties">CityRestaurant → QualityMeats</div>
                        <div class="txn-amount">$12,750</div>
                        <div class="txn-status">CONFIRMED</div>
                    </div>
                    <div class="transaction-row">
                        <div class="txn-time">14:30:22</div>
                        <div class="txn-type dispute">DSP-092</div>
                        <div class="txn-parties">BulkBuyers vs OrganicSuppliers</div>
                        <div class="txn-amount">$8,100</div>
                        <div class="txn-status">ESCALATED</div>
                    </div>
                    <div class="transaction-row">
                        <div class="txn-time">14:29:51</div>
                        <div class="txn-type rfq">RFQ-8471</div>
                        <div class="txn-parties">HarborSeafood → OceanFresh</div>
                        <div class="txn-amount">$28,900</div>
                        <div class="txn-status">QUOTED</div>
                    </div>
                    <div class="transaction-row">
                        <div class="txn-time">14:28:12</div>
                        <div class="txn-type po">PO-3890</div>
                        <div class="txn-parties">SuperMartChain → DairyDirect</div>
                        <div class="txn-amount">$67,450</div>
                        <div class="txn-status">DELIVERED</div>
                    </div>
                    <div class="transaction-row">
                        <div class="txn-time">14:27:38</div>
                        <div class="txn-type rfq">RFQ-8470</div>
                        <div class="txn-parties">WholesaleDirect → Multiple</div>
                        <div class="txn-amount">$125,000</div>
                        <div class="txn-status">BIDDING</div>
                    </div>
                    <div class="transaction-row">
                        <div class="txn-time">14:26:45</div>
                        <div class="txn-type po">PO-3889</div>
                        <div class="txn-parties">FreshProduceCo → LocalFarms</div>
                        <div class="txn-amount">$34,200</div>
                        <div class="txn-status">SHIPPING</div>
                    </div>
                    <div class="transaction-row">
                        <div class="txn-time">14:25:19</div>
                        <div class="txn-type rfq">RFQ-8469</div>
                        <div class="txn-parties">RegionalDistributor → ProduceHub</div>
                        <div class="txn-amount">$89,750</div>
                        <div class="txn-status">NEGOTIATING</div>
                    </div>
                    <div class="transaction-row">
                        <div class="txn-time">14:24:33</div>
                        <div class="txn-type dispute">DSP-091</div>
                        <div class="txn-parties">QuickMart vs FastSupply</div>
                        <div class="txn-amount">$4,500</div>
                        <div class="txn-status">REVIEWING</div>
                    </div>
                </div>
            </div>

            <!-- User Approvals Panel -->
            <div class="approvals-panel">
                <div class="monitor-header">
                    <div class="monitor-title">User Approvals Queue</div>
                    <div class="monitor-controls">
                        <button class="control-btn">Auto-Approve</button>
                    </div>
                </div>
                <div class="approvals-content">
                    <div class="approval-item">
                        <div class="approval-header">
                            <span class="approval-type vendor">VENDOR</span>
                            <span class="approval-time">2 min ago</span>
                        </div>
                        <div class="approval-details">
                            <strong>Pacific Seafood Co.</strong><br>
                            Business License: Verified<br>
                            Location: Seattle, WA<br>
                            Category: Seafood Supplier
                        </div>
                        <div class="approval-actions">
                            <button class="approve-btn">Approve</button>
                            <button class="reject-btn">Reject</button>
                        </div>
                    </div>

                    <div class="approval-item">
                        <div class="approval-header">
                            <span class="approval-type buyer">BUYER</span>
                            <span class="approval-time">5 min ago</span>
                        </div>
                        <div class="approval-details">
                            <strong>Urban Kitchen LLC</strong><br>
                            Tax ID: Verified<br>
                            Location: New York, NY<br>
                            Type: Restaurant Chain
                        </div>
                        <div class="approval-actions">
                            <button class="approve-btn">Approve</button>
                            <button class="reject-btn">Reject</button>
                        </div>
                    </div>

                    <div class="approval-item">
                        <div class="approval-header">
                            <span class="approval-type vendor">VENDOR</span>
                            <span class="approval-time">12 min ago</span>
                        </div>
                        <div class="approval-details">
                            <strong>Mountain Dairy Farm</strong><br>
                            Business License: Pending<br>
                            Location: Vermont<br>
                            Category: Dairy Products
                        </div>
                        <div class="approval-actions">
                            <button class="approve-btn">Approve</button>
                            <button class="reject-btn">Reject</button>
                        </div>
                    </div>

                    <div class="approval-item">
                        <div class="approval-header">
                            <span class="approval-type buyer">BUYER</span>
                            <span class="approval-time">18 min ago</span>
                        </div>
                        <div class="approval-details">
                            <strong>Regional Grocers Inc.</strong><br>
                            Tax ID: Verified<br>
                            Location: Chicago, IL<br>
                            Type: Grocery Chain
                        </div>
                        <div class="approval-actions">
                            <button class="approve-btn">Approve</button>
                            <button class="reject-btn">Reject</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Status & Disputes Panel -->
            <div class="system-panel">
                <div class="monitor-header">
                    <div class="monitor-title">System Health & Operations</div>
                    <div class="monitor-controls">
                        <button class="control-btn">Details</button>
                    </div>
                </div>
                
                <!-- System Metrics -->
                <div class="system-grid">
                    <div class="system-metric">
                        <div class="system-label">API Response</div>
                        <div class="system-value">
                            <span class="system-indicator indicator-green"></span>
                            124ms
                        </div>
                    </div>
                    <div class="system-metric">
                        <div class="system-label">DB Load</div>
                        <div class="system-value">
                            <span class="system-indicator indicator-green"></span>
                            28%
                        </div>
                    </div>
                    <div class="system-metric">
                        <div class="system-label">CPU Usage</div>
                        <div class="system-value">
                            <span class="system-indicator indicator-amber"></span>
                            67%
                        </div>
                    </div>
                    <div class="system-metric">
                        <div class="system-label">Memory</div>
                        <div class="system-value">
                            <span class="system-indicator indicator-green"></span>
                            4.2GB
                        </div>
                    </div>
                    <div class="system-metric">
                        <div class="system-label">Queue Size</div>
                        <div class="system-value">
                            <span class="system-indicator indicator-green"></span>
                            18
                        </div>
                    </div>
                    <div class="system-metric">
                        <div class="system-label">Error Rate</div>
                        <div class="system-value">
                            <span class="system-indicator indicator-green"></span>
                            0.02%
                        </div>
                    </div>
                </div>

                <!-- Active Disputes -->
                <div class="disputes-section">
                    <div class="monitor-title" style="margin-bottom: calc(0.75rem * var(--scale-factor));">
                        Active Disputes
                    </div>
                    
                    <div class="dispute-card">
                        <div class="dispute-header">
                            <span class="dispute-id">#DSP-092</span>
                            <span class="dispute-priority priority-high">HIGH</span>
                        </div>
                        <div class="dispute-info">
                            Quality issue - 30% spoilage claimed on $8,100 order
                        </div>
                        <div class="dispute-actions">
                            <button class="dispute-btn">Review</button>
                            <button class="dispute-btn">Mediate</button>
                            <button class="dispute-btn">Escalate</button>
                        </div>
                    </div>

                    <div class="dispute-card">
                        <div class="dispute-header">
                            <span class="dispute-id">#DSP-091</span>
                            <span class="dispute-priority priority-medium">MEDIUM</span>
                        </div>
                        <div class="dispute-info">
                            Pricing discrepancy - $4,500 difference in invoice
                        </div>
                        <div class="dispute-actions">
                            <button class="dispute-btn">Review</button>
                            <button class="dispute-btn">Mediate</button>
                            <button class="dispute-btn">Escalate</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status Footer -->
        <div class="status-footer">
            <div class="footer-section">
                <div class="footer-item">
                    <span class="footer-label">Platform Status:</span>
                    <span class="footer-value" style="color: var(--accent-green);">OPERATIONAL</span>
                </div>
                <div class="footer-divider"></div>
                <div class="footer-item">
                    <span class="footer-label">Last Backup:</span>
                    <span class="footer-value">2 hours ago</span>
                </div>
                <div class="footer-divider"></div>
                <div class="footer-item">
                    <span class="footer-label">Active Sessions:</span>
                    <span class="footer-value">1,247</span>
                </div>
            </div>
            
            <div class="footer-section">
                <div class="footer-item">
                    <span class="footer-label">Daily Volume:</span>
                    <span class="footer-value">$847,320</span>
                </div>
                <div class="footer-divider"></div>
                <div class="footer-item">
                    <span class="footer-label">Success Rate:</span>
                    <span class="footer-value">99.8%</span>
                </div>
                <div class="footer-divider"></div>
                <div class="footer-item">
                    <span class="footer-label">Last Update:</span>
                    <span class="footer-value" id="lastUpdate">Just now</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Real-time updates
    function updateTimestamps() {
        const now = new Date();
        const timeString = now.toTimeString().split(' ')[0];
        
        // Update last update
        document.getElementById('lastUpdate').textContent = timeString;
        
        // Animate live indicators
        document.querySelectorAll('.live-dot').forEach(dot => {
            dot.style.animation = 'none';
            setTimeout(() => {
                dot.style.animation = 'pulse-live 2s infinite';
            }, 10);
        });
    }

    // Update every 5 seconds
    setInterval(updateTimestamps, 5000);

    // Simulate new transactions
    function addNewTransaction() {
        const feed = document.querySelector('.transaction-feed');
        const rows = feed.querySelectorAll('.transaction-row');
        
        // Remove last row if we have more than 10
        if (rows.length >= 10) {
            rows[rows.length - 1].remove();
        }
        
        // Create new transaction
        const types = [
            { class: 'rfq', prefix: 'RFQ', parties: 'NewBuyer → MultipleVendors' },
            { class: 'po', prefix: 'PO', parties: 'Restaurant → Supplier' },
            { class: 'dispute', prefix: 'DSP', parties: 'Buyer vs Vendor' }
        ];
        
        const type = types[Math.floor(Math.random() * types.length)];
        const amount = Math.floor(Math.random() * 100000) + 1000;
        const now = new Date();
        const timeString = now.toTimeString().split(' ')[0];
        const id = Math.floor(Math.random() * 9999);
        
        const newRow = document.createElement('div');
        newRow.className = 'transaction-row';
        newRow.style.opacity = '0';
        newRow.innerHTML = `
            <div class="txn-time">${timeString}</div>
            <div class="txn-type ${type.class}">${type.prefix}-${id}</div>
            <div class="txn-parties">${type.parties}</div>
            <div class="txn-amount">$${amount.toLocaleString()}</div>
            <div class="txn-status">NEW</div>
        `;
        
        feed.insertBefore(newRow, feed.firstChild);
        
        // Animate in
        setTimeout(() => {
            newRow.style.transition = 'opacity 0.5s ease';
            newRow.style.opacity = '1';
        }, 10);
    }

    // Add new transaction every 8-15 seconds
    setInterval(() => {
        if (Math.random() > 0.3) {
            addNewTransaction();
        }
    }, 10000);

    // Handle action buttons
    document.querySelectorAll('.action-button').forEach(button => {
        button.addEventListener('click', function() {
            const label = this.querySelector('.action-label').textContent;
            console.log('Action clicked:', label);
        });
    });

    // Handle approval actions
    document.querySelectorAll('.approve-btn, .reject-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            const action = this.classList.contains('approve-btn') ? 'approved' : 'rejected';
            const item = this.closest('.approval-item');
            const company = item.querySelector('strong').textContent;
            
            // Animate out
            item.style.transition = 'all 0.3s ease';
            item.style.opacity = '0';
            item.style.transform = 'translateX(20px)';
            
            setTimeout(() => {
                item.remove();
                console.log(`${company} ${action}`);
            }, 300);
        });
    });

    // Handle dispute actions
    document.querySelectorAll('.dispute-btn').forEach(button => {
        button.addEventListener('click', function() {
            const action = this.textContent;
            const disputeId = this.closest('.dispute-card').querySelector('.dispute-id').textContent;
            console.log(`Dispute ${disputeId} - Action: ${action}`);
        });
    });

    // Handle control buttons
    document.querySelectorAll('.control-btn').forEach(button => {
        button.addEventListener('click', function() {
            console.log('Control action:', this.textContent);
        });
    });

    // Initialize dashboard
    updateTimestamps();
</script>
@endsection