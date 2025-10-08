<div class="messages-full-overlay">
    <div class="messenger-container">
        <div class="conversations-panel">
            <!-- Skeleton Header -->
            <div class="conversations-header">
                <div class="skeleton-title"></div>
                <div class="skeleton-close-btn"></div>
            </div>

            <!-- Skeleton Conversation List -->
            <div class="conversations-list">
                @for($i = 0; $i < 5; $i++)
                    <div class="skeleton-conversation">
                        <div class="skeleton-avatar"></div>
                        <div class="skeleton-content">
                            <div class="skeleton-name"></div>
                            <div class="skeleton-preview"></div>
                        </div>
                    </div>
                @endfor
            </div>
        </div>
    </div>

    <!-- Skeleton Styles with Shimmer Animation -->
    <style>
        .skeleton-title {
            width: 120px;
            height: 24px;
            background: linear-gradient(90deg, #D1D5DB 25%, #E5E7EB 50%, #D1D5DB 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
            border-radius: 8px;
        }

        .skeleton-close-btn {
            width: 36px;
            height: 36px;
            background: linear-gradient(90deg, #D1D5DB 25%, #E5E7EB 50%, #D1D5DB 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
            border-radius: 50%;
        }

        .skeleton-conversation {
            display: flex;
            align-items: center;
            padding: 14px 16px;
            margin-bottom: 8px;
            background: #E0E5EC;
            border-radius: 16px;
            gap: 14px;
        }

        .skeleton-avatar {
            width: 44px;
            height: 44px;
            background: linear-gradient(90deg, #D1D5DB 25%, #E5E7EB 50%, #D1D5DB 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .skeleton-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .skeleton-name {
            width: 60%;
            height: 16px;
            background: linear-gradient(90deg, #D1D5DB 25%, #E5E7EB 50%, #D1D5DB 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
            border-radius: 4px;
        }

        .skeleton-preview {
            width: 85%;
            height: 14px;
            background: linear-gradient(90deg, #D1D5DB 25%, #E5E7EB 50%, #D1D5DB 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
            border-radius: 4px;
        }

        @keyframes shimmer {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
    </style>

    <!-- Load messenger CSS (for container structure) -->
    <link rel="stylesheet" href="{{ asset('assets/css/shared/messaging/messenger.css') }}">
</div>
