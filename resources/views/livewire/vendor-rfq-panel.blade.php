<div class="h-full flex flex-col bg-white rounded-lg shadow-lg" x-data="{
    selectedTab: 'active',
    showDetails: false
}">
    <!-- Header with Notification Badge -->
    <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-green-50 to-white">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <h2 class="text-xl font-bold text-gray-900">Live RFQ Feed</h2>
                @if($showNotificationBadge)
                <span class="relative flex h-3 w-3">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                </span>
                @endif
                @if($newRfqCount > 0)
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                    {{ $newRfqCount }} new
                </span>
                @endif
            </div>

            <div class="flex items-center space-x-2">
                <!-- Filter Dropdown -->
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" class="px-3 py-1.5 text-sm bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                        <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                        </svg>
                        Filter
                    </button>
                    <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10">
                        <div class="py-1">
                            <button wire:click="filterByUrgency('all')" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left">All RFQs</button>
                            <button wire:click="filterByUrgency('urgent')" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left">🔴 Urgent (Today)</button>
                            <button wire:click="filterByUrgency('high')" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left">🟠 High (2 days)</button>
                            <button wire:click="filterByUrgency('medium')" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left">🟡 Medium (7 days)</button>
                        </div>
                    </div>
                </div>

                <!-- Sort Dropdown -->
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" class="px-3 py-1.5 text-sm bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                        <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
                        </svg>
                        Sort
                    </button>
                    <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10">
                        <div class="py-1">
                            <button wire:click="sortRfqs('newest')" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left">Newest First</button>
                            <button wire:click="sortRfqs('urgent')" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left">Most Urgent</button>
                            <button wire:click="sortRfqs('items')" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left">Most Items</button>
                        </div>
                    </div>
                </div>

                @if($newRfqCount > 0)
                <button wire:click="markAllAsRead" class="text-sm text-green-600 hover:text-green-700">
                    Mark all read
                </button>
                @endif
            </div>
        </div>

        <!-- Tabs -->
        <div class="mt-4 flex space-x-4 border-b border-gray-200">
            <button
                @click="selectedTab = 'active'"
                :class="selectedTab === 'active' ? 'border-green-500 text-green-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                class="pb-2 px-1 border-b-2 font-medium text-sm transition-colors"
            >
                Active RFQs ({{ $activeRfqs->count() }})
            </button>
            <button
                @click="selectedTab = 'recent'"
                :class="selectedTab === 'recent' ? 'border-green-500 text-green-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                class="pb-2 px-1 border-b-2 font-medium text-sm transition-colors"
            >
                Recent (24h)
            </button>
        </div>
    </div>

    <!-- RFQ List -->
    <div class="flex-1 overflow-hidden flex">
        <!-- Left Panel: RFQ List -->
        <div class="w-1/2 border-r border-gray-200 overflow-y-auto">
            <!-- Active RFQs Tab -->
            <div x-show="selectedTab === 'active'" class="p-4 space-y-3">
                @forelse($activeRfqs as $rfqData)
                <div
                    wire:click="selectRfq({{ $rfqData['rfq']['id'] }})"
                    class="p-4 border rounded-lg cursor-pointer transition-all hover:shadow-md {{ $selectedRfqId == $rfqData['rfq']['id'] ? 'border-green-500 bg-green-50' : 'border-gray-200 hover:border-green-300' }}"
                >
                    <!-- Urgency Badge -->
                    <div class="flex items-start justify-between mb-2">
                        <div class="flex items-center space-x-2">
                            @switch($rfqData['rfq']['urgency'])
                                @case('urgent')
                                    <span class="px-2 py-0.5 text-xs font-medium bg-red-100 text-red-800 rounded-full">🔴 URGENT</span>
                                    @break
                                @case('high')
                                    <span class="px-2 py-0.5 text-xs font-medium bg-orange-100 text-orange-800 rounded-full">🟠 High Priority</span>
                                    @break
                                @case('medium')
                                    <span class="px-2 py-0.5 text-xs font-medium bg-yellow-100 text-yellow-800 rounded-full">🟡 Medium</span>
                                    @break
                                @default
                                    <span class="px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">Normal</span>
                            @endswitch
                            <span class="text-xs text-gray-500">{{ $rfqData['rfq']['time_ago'] }}</span>
                        </div>
                        <span class="text-xs font-medium text-gray-600">{{ $rfqData['rfq']['reference_number'] }}</span>
                    </div>

                    <!-- Buyer Info -->
                    <div class="mb-2">
                        <h3 class="font-semibold text-gray-900">{{ $rfqData['buyer']['business_name'] }}</h3>
                        <p class="text-sm text-gray-600">📍 {{ $rfqData['buyer']['suburb'] }}</p>
                    </div>

                    <!-- Items Summary -->
                    <div class="mb-2">
                        <p class="text-sm font-medium text-gray-700">{{ $rfqData['total_items'] }} items requested</p>
                        <div class="mt-1 flex flex-wrap gap-1">
                            @foreach(array_slice($rfqData['items'], 0, 3) as $item)
                            <span class="px-2 py-0.5 text-xs bg-gray-100 text-gray-700 rounded">
                                {{ $item['product_name'] }} ({{ $item['quantity'] }}{{ $item['unit'] }})
                            </span>
                            @endforeach
                            @if(count($rfqData['items']) > 3)
                            <span class="px-2 py-0.5 text-xs bg-gray-100 text-gray-700 rounded">
                                +{{ count($rfqData['items']) - 3 }} more
                            </span>
                            @endif
                        </div>
                    </div>

                    <!-- Delivery Info -->
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600">
                            📅 {{ \Carbon\Carbon::parse($rfqData['rfq']['delivery_date'])->format('D, M j') }}
                        </span>
                        <span class="text-gray-600">
                            🚚 {{ $rfqData['rfq']['delivery_time'] }}
                        </span>
                    </div>
                </div>
                @empty
                <div class="text-center py-8">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <p class="mt-2 text-sm text-gray-600">No active RFQs available</p>
                </div>
                @endforelse
            </div>

            <!-- Recent RFQs Tab -->
            <div x-show="selectedTab === 'recent'" class="p-4 space-y-3">
                @forelse($recentRfqs as $rfqData)
                <div
                    wire:click="selectRfq({{ $rfqData['rfq']['id'] }})"
                    class="p-3 border rounded-lg cursor-pointer transition-all hover:shadow-md border-gray-200 hover:border-green-300"
                >
                    <div class="flex items-center justify-between mb-1">
                        <h3 class="font-medium text-gray-900">{{ $rfqData['buyer']['business_name'] }}</h3>
                        <span class="text-xs text-gray-500">{{ $rfqData['rfq']['time_ago'] }}</span>
                    </div>
                    <p class="text-sm text-gray-600">{{ $rfqData['total_items'] }} items • {{ \Carbon\Carbon::parse($rfqData['rfq']['delivery_date'])->format('M j') }}</p>
                </div>
                @empty
                <div class="text-center py-8">
                    <p class="text-sm text-gray-600">No recent RFQs in the last 24 hours</p>
                </div>
                @endforelse
            </div>
        </div>

        <!-- Right Panel: Selected RFQ Details -->
        <div class="w-1/2 overflow-y-auto">
            @if($selectedRfq)
            <div class="p-6">
                <!-- Header -->
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-2">
                        <h2 class="text-xl font-bold text-gray-900">{{ $selectedRfq['rfq']['reference_number'] }}</h2>
                        @switch($selectedRfq['rfq']['urgency'])
                            @case('urgent')
                                <span class="px-3 py-1 text-sm font-medium bg-red-100 text-red-800 rounded-full">🔴 URGENT - Quote Today!</span>
                                @break
                            @case('high')
                                <span class="px-3 py-1 text-sm font-medium bg-orange-100 text-orange-800 rounded-full">🟠 High Priority</span>
                                @break
                            @case('medium')
                                <span class="px-3 py-1 text-sm font-medium bg-yellow-100 text-yellow-800 rounded-full">🟡 Medium Priority</span>
                                @break
                        @endswitch
                    </div>
                </div>

                <!-- Buyer Information -->
                <div class="bg-gray-50 rounded-lg p-4 mb-6">
                    <h3 class="text-sm font-medium text-gray-700 mb-3">Buyer Information</h3>
                    <div class="space-y-2">
                        <p class="text-sm"><span class="font-medium">Business:</span> {{ $selectedRfq['buyer']['business_name'] }}</p>
                        <p class="text-sm"><span class="font-medium">Location:</span> {{ $selectedRfq['buyer']['suburb'] }}</p>
                        <p class="text-sm"><span class="font-medium">Rating:</span>
                            @for($i = 1; $i <= 5; $i++)
                                @if($i <= $selectedRfq['buyer']['rating'])
                                    ⭐
                                @else
                                    ☆
                                @endif
                            @endfor
                            ({{ $selectedRfq['buyer']['rating'] }})
                        </p>
                    </div>
                </div>

                <!-- Delivery Details -->
                <div class="bg-green-50 rounded-lg p-4 mb-6">
                    <h3 class="text-sm font-medium text-gray-700 mb-3">Delivery Requirements</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-gray-600">Delivery Date</p>
                            <p class="text-sm font-medium">{{ \Carbon\Carbon::parse($selectedRfq['rfq']['delivery_date'])->format('l, F j, Y') }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-600">Preferred Time</p>
                            <p class="text-sm font-medium">{{ $selectedRfq['rfq']['delivery_time'] }}</p>
                        </div>
                    </div>
                    @if($selectedRfq['rfq']['special_instructions'])
                    <div class="mt-3">
                        <p class="text-xs text-gray-600">Special Instructions</p>
                        <p class="text-sm">{{ $selectedRfq['rfq']['special_instructions'] }}</p>
                    </div>
                    @endif
                </div>

                <!-- Items List -->
                <div class="mb-6">
                    <h3 class="text-sm font-medium text-gray-700 mb-3">Items Requested ({{ count($selectedRfq['items']) }})</h3>
                    <div class="space-y-2">
                        @foreach($selectedRfq['items'] as $item)
                        <div class="flex items-center justify-between p-3 bg-white border border-gray-200 rounded-lg">
                            <div class="flex-1">
                                <p class="font-medium text-gray-900">{{ $item['product_name'] }}</p>
                                @if($item['notes'])
                                <p class="text-xs text-gray-600 mt-1">Note: {{ $item['notes'] }}</p>
                                @endif
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-gray-900">{{ $item['quantity'] }} {{ $item['unit'] }}</p>
                                <p class="text-xs text-gray-600">{{ $item['category'] }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex space-x-3">
                    <button
                        wire:click="startQuote({{ $selectedRfq['rfq']['id'] }})"
                        class="flex-1 px-6 py-3 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition-colors"
                    >
                        <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Create Quote
                    </button>
                    <button class="px-6 py-3 bg-gray-200 text-gray-700 font-medium rounded-lg hover:bg-gray-300 transition-colors">
                        Skip
                    </button>
                </div>
            </div>
            @else
            <div class="flex items-center justify-center h-full">
                <div class="text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <p class="mt-2 text-sm text-gray-600">Select an RFQ to view details</p>
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Real-time Connection Status -->
    <div class="px-4 py-2 bg-gray-50 border-t border-gray-200">
        <div class="flex items-center justify-between text-xs">
            <div class="flex items-center space-x-2">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                </span>
                <span class="text-gray-600">Connected to Live RFQ Feed</span>
            </div>
            <span class="text-gray-500">Last update: Just now</span>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Listen for WebSocket connection
    document.addEventListener('DOMContentLoaded', function() {
        Echo.channel('vendors.all')
            .listen('.rfq.new', (e) => {
                console.log('New RFQ received via WebSocket:', e);
            });

        // Play notification sound
        Livewire.on('play-notification-sound', () => {
            const audio = new Audio('/sounds/notification.mp3');
            audio.play().catch(e => console.log('Could not play sound:', e));
        });
    });
</script>
@endpush