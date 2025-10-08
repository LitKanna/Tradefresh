<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demand Forecasting Tool - Sydney Markets</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 900px;
            width: 100%;
            padding: 40px;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
        }

        .header h1 {
            color: #333;
            font-size: 32px;
            margin-bottom: 10px;
        }

        .header p {
            color: #666;
            font-size: 16px;
        }

        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .feature {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .feature-icon {
            font-size: 36px;
            margin-bottom: 10px;
        }

        .feature h3 {
            color: #333;
            font-size: 16px;
            margin-bottom: 5px;
        }

        .feature p {
            color: #666;
            font-size: 14px;
        }

        .upload-section {
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
            border-radius: 15px;
            padding: 30px;
            color: white;
        }

        .upload-form {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            cursor: pointer;
            background: white;
            color: #10B981;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
            margin-bottom: 20px;
        }

        .file-input-wrapper:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .file-input-wrapper input[type=file] {
            position: absolute;
            left: -9999px;
        }

        .file-name {
            margin-bottom: 20px;
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 5px;
            display: none;
        }

        .submit-btn {
            background: white;
            color: #10B981;
            border: none;
            padding: 15px 40px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .submit-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .progress-container {
            display: none;
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }

        .progress-bar {
            width: 100%;
            height: 30px;
            background: #e5e7eb;
            border-radius: 15px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #10B981, #059669);
            width: 0%;
            transition: width 0.5s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        .file-info {
            background: #fef3c7;
            border: 2px solid #f59e0b;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
            display: none;
        }

        .file-info h4 {
            color: #92400e;
            margin-bottom: 10px;
        }

        .file-size-warning {
            background: #fee2e2;
            border: 2px solid #ef4444;
            color: #991b1b;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
            display: none;
        }

        .instructions {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
            margin-top: 40px;
        }

        .instructions h3 {
            color: #333;
            margin-bottom: 15px;
        }

        .instructions ol {
            color: #666;
            padding-left: 20px;
        }

        .instructions li {
            margin-bottom: 10px;
        }

        .supported-formats {
            text-align: center;
            margin-top: 15px;
            color: rgba(255, 255, 255, 0.9);
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-error {
            background: #fee;
            border: 1px solid #fcc;
            color: #c00;
        }

        .alert-success {
            background: #efe;
            border: 1px solid #cfc;
            color: #060;
        }

        .sample-data {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            margin-top: 20px;
            border-radius: 5px;
        }

        .sample-data h4 {
            color: #1976d2;
            margin-bottom: 10px;
        }

        .sample-data pre {
            background: white;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Sales Data Analysis Tool</h1>
            <p>Upload your historical sales data to analyze trends and patterns</p>
        </div>

        @if(session('error'))
            <div class="alert alert-error">
                {{ session('error') }}
            </div>
        @endif

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <div class="features">
            <div class="feature">
                <div class="feature-icon">🤖</div>
                <h3>Smart Parsing</h3>
                <p>Auto-detects file format</p>
            </div>
            <div class="feature">
                <div class="feature-icon">📈</div>
                <h3>ML Predictions</h3>
                <p>7-day demand forecast</p>
            </div>
            <div class="feature">
                <div class="feature-icon">🎯</div>
                <h3>Insights</h3>
                <p>Actionable recommendations</p>
            </div>
            <div class="feature">
                <div class="feature-icon">📊</div>
                <h3>Trends</h3>
                <p>Identify patterns</p>
            </div>
        </div>

        <div class="upload-section">
            <form action="{{ route('demand-forecast.process') }}" method="POST" enctype="multipart/form-data" class="upload-form" id="upload-form">
                @csrf
                <label class="file-input-wrapper">
                    <input type="file" name="file" id="file-input" accept=".txt,.csv" required>
                    📁 Choose Sales Data File
                </label>

                <div class="file-name" id="file-name"></div>

                <div class="file-info" id="file-info">
                    <h4>📊 File Information</h4>
                    <p><strong>File:</strong> <span id="info-filename"></span></p>
                    <p><strong>Size:</strong> <span id="info-size"></span></p>
                    <p><strong>Type:</strong> <span id="info-type"></span></p>
                    <p><strong>Estimated Rows:</strong> <span id="info-rows"></span></p>

                    <div class="file-size-warning" id="size-warning">
                        ⚠️ Large file detected! Processing may take 2-5 minutes. The system will process up to 10,000 recent records for optimal performance.
                    </div>
                </div>

                <div style="margin: 15px 0;">
                    <label style="display: flex; align-items: center; justify-content: center; gap: 10px; cursor: pointer;">
                        <input type="checkbox" name="debug" value="1" style="width: 18px; height: 18px;">
                        <span style="font-size: 14px;">🔍 Show Debug View (See exactly what's being parsed)</span>
                    </label>
                </div>

                <button type="submit" class="submit-btn" id="submit-btn" disabled>
                    📊 Analyze Data
                </button>

                <div class="progress-container" id="progress-container">
                    <h4 style="margin-bottom: 15px;">⏳ Processing Large File...</h4>
                    <div class="progress-bar">
                        <div class="progress-fill" id="progress-fill">0%</div>
                    </div>
                    <p style="margin-top: 10px; color: #666; font-size: 14px;">
                        Please wait while we process your file. This may take a few minutes for large datasets.
                    </p>
                </div>

                <div class="supported-formats">
                    <strong>Supported:</strong> TXT, CSV (up to 100MB)<br>
                    <strong>Delimiters:</strong> pipe | comma , tab or semicolon<br>
                    <strong>Optimized for:</strong> Files up to 5 years of data
                </div>
            </form>
        </div>

        <div class="instructions">
            <h3>📋 Required Data Fields</h3>
            <ol>
                <li><strong>Sales Date</strong> - Date of sale</li>
                <li><strong>Product</strong> - Product name</li>
                <li><strong>Quantity</strong> - Units sold</li>
                <li><strong>Rate/Price</strong> - Unit price</li>
                <li><strong>Total</strong> - Total amount</li>
                <li><strong>Buyer Name</strong> - Customer name (optional)</li>
            </ol>
        </div>

        <div class="sample-data">
            <h4>📝 Sample Data Format</h4>
            <pre>"Sales Date"|"Docket Number"|"Buyer Name"|Product|Quantity|Rate|Total
"01/01/2025"|"D001"|"Fresh Foods Co"|Tomatoes|100|5.50|550.00
"01/01/2025"|"D002"|"Market Cafe"|Lettuce|50|3.20|160.00
"02/01/2025"|"D003"|"Green Grocers"|Carrots|75|2.80|210.00</pre>
        </div>
    </div>

    <script>
        const fileInput = document.getElementById('file-input');
        const fileName = document.getElementById('file-name');
        const submitBtn = document.getElementById('submit-btn');
        const fileInfo = document.getElementById('file-info');
        const sizeWarning = document.getElementById('size-warning');
        const progressContainer = document.getElementById('progress-container');
        const progressFill = document.getElementById('progress-fill');
        const uploadForm = document.getElementById('upload-form');

        fileInput.addEventListener('change', function(e) {
            if (e.target.files.length > 0) {
                const file = e.target.files[0];
                const sizeMB = file.size / (1024 * 1024);
                const sizeDisplay = sizeMB > 1
                    ? `${sizeMB.toFixed(2)} MB`
                    : `${(file.size / 1024).toFixed(2)} KB`;

                // Show file info
                fileName.textContent = `📄 Selected: ${file.name}`;
                fileName.style.display = 'block';

                // Show detailed info
                document.getElementById('info-filename').textContent = file.name;
                document.getElementById('info-size').textContent = sizeDisplay;
                document.getElementById('info-type').textContent = file.type || 'text/plain';

                // Estimate rows (assuming average 100 bytes per row)
                const estimatedRows = Math.floor(file.size / 100);
                document.getElementById('info-rows').textContent = estimatedRows.toLocaleString();

                fileInfo.style.display = 'block';

                // Show warning for large files
                if (sizeMB > 10) {
                    sizeWarning.style.display = 'block';
                    submitBtn.textContent = '📊 Analyze Large Dataset';
                } else {
                    sizeWarning.style.display = 'none';
                    submitBtn.textContent = '📊 Analyze Data';
                }

                // Check file size limit (100MB)
                if (sizeMB > 100) {
                    alert('⚠️ File is too large! Maximum size is 100MB. Please split your data or use a smaller date range.');
                    submitBtn.disabled = true;
                    submitBtn.textContent = '❌ File Too Large';
                } else {
                    submitBtn.disabled = false;
                }
            } else {
                fileName.style.display = 'none';
                fileInfo.style.display = 'none';
                submitBtn.disabled = true;
            }
        });

        // Enhanced submit handler with progress simulation
        uploadForm.addEventListener('submit', function(e) {
            const file = fileInput.files[0];
            if (!file) return;

            const sizeMB = file.size / (1024 * 1024);

            submitBtn.textContent = '⏳ Processing...';
            submitBtn.disabled = true;

            // Show progress for large files
            if (sizeMB > 5) {
                progressContainer.style.display = 'block';

                // Simulate progress (since we can't track actual server progress easily)
                let progress = 0;
                const estimatedTime = Math.min(sizeMB * 2, 60); // 2 seconds per MB, max 60 seconds
                const increment = 100 / (estimatedTime * 2); // Update every 500ms

                const progressInterval = setInterval(() => {
                    progress += increment;
                    if (progress >= 90) {
                        progress = 90; // Stay at 90% until actual completion
                        clearInterval(progressInterval);
                    }

                    progressFill.style.width = progress + '%';
                    progressFill.textContent = Math.round(progress) + '%';
                }, 500);

                // Store interval ID to clear on page unload
                window.progressInterval = progressInterval;
            }
        });

        // Clear interval on page unload
        window.addEventListener('beforeunload', function() {
            if (window.progressInterval) {
                clearInterval(window.progressInterval);
            }
        });
    </script>
</body>
</html>