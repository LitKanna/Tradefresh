<div class="relative" x-data="{
    showDropdown: @entangle('showDropdown').live,
    notifications: @entangle('notifications').live
}">
    <!-- Notification Bell -->
    <button
        @click="showDropdown = !showDropdown"
        class="relative p-2 text-gray-600 hover:text-gray-900 transition-colors duration-200"
        aria-label="Notifications"
    >
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9">
            </path>
        </svg>

        <!-- Unread Count Badge -->
        @if($unreadCount > 0)
        <span class="absolute -top-1 -right-1 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-500 rounded-full">
            {{ $unreadCount > 9 ? '9+' : $unreadCount }}
        </span>
        @endif
    </button>

    <!-- Notification Dropdown -->
    <div
        x-show="showDropdown"
        @click.away="showDropdown = false"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute right-0 z-50 mt-2 w-80 bg-white rounded-lg shadow-xl border border-gray-200"
        style="max-height: 500px;"
    >
        <!-- Header -->
        <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-900">Notifications</h3>
            <div class="flex items-center gap-2">
                @if(count($notifications) > 0)
                <button
                    wire:click="markAllAsRead"
                    class="text-xs text-blue-600 hover:text-blue-800"
                >
                    Mark all read
                </button>
                <button
                    wire:click="clearAll"
                    class="text-xs text-red-600 hover:text-red-800"
                >
                    Clear
                </button>
                @endif
            </div>
        </div>

        <!-- Notifications List -->
        <div class="overflow-y-auto" style="max-height: 400px;">
            @forelse($notifications as $notification)
            <div class="px-4 py-3 hover:bg-gray-50 border-b border-gray-100 {{ !$notification['read'] ? 'bg-blue-50' : '' }}">
                <div class="flex items-start">
                    <span class="text-2xl mr-3">{{ $notification['icon'] }}</span>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-900">
                            {{ $notification['title'] }}
                        </p>
                        <p class="text-sm text-gray-600 mt-1">
                            {{ $notification['message'] }}
                        </p>
                        <p class="text-xs text-gray-500 mt-1">
                            {{ $notification['time'] }}
                        </p>
                    </div>
                </div>
            </div>
            @empty
            <div class="px-4 py-8 text-center">
                <div class="text-gray-400">
                    <svg class="mx-auto h-12 w-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4">
                        </path>
                    </svg>
                    <p class="mt-2 text-sm">No notifications</p>
                </div>
            </div>
            @endforelse
        </div>
    </div>
</div>
