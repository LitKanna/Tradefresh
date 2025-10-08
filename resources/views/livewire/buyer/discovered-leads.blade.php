<div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <h1 class="text-3xl font-bold text-gray-900">🔍 Discovered Business Leads</h1>
            <p class="text-gray-600 mt-2">Potential buyers discovered by BulkHunter with contact information</p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="text-2xl font-bold text-gray-900">{{ $stats['total'] }}</div>
                <div class="text-sm text-gray-600">Total Leads</div>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <div class="text-2xl font-bold text-green-600">{{ $stats['with_email'] }}</div>
                <div class="text-sm text-gray-600">With Email</div>
                <div class="text-xs text-gray-500 mt-1">{{ round($stats['with_email'] / max($stats['total'], 1) * 100, 1) }}%</div>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <div class="text-2xl font-bold text-green-600">{{ $stats['with_phone'] }}</div>
                <div class="text-sm text-gray-600">With Phone</div>
                <div class="text-xs text-gray-500 mt-1">{{ round($stats['with_phone'] / max($stats['total'], 1) * 100, 1) }}%</div>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <div class="text-2xl font-bold text-gray-900">{{ $stats['contacted'] }}</div>
                <div class="text-sm text-gray-600">Contacted</div>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <div class="text-2xl font-bold text-gray-900">{{ $stats['qualified'] }}</div>
                <div class="text-sm text-gray-600">Qualified</div>
            </div>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Search -->
                <div>
                    <input type="text"
                           wire:model.live="search"
                           placeholder="Search businesses, emails, phones..."
                           class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>

                <!-- Status Filter -->
                <div>
                    <select wire:model.live="filterStatus"
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                        <option value="all">All Statuses</option>
                        <option value="new">New</option>
                        <option value="contacted">Contacted</option>
                        <option value="qualified">Qualified</option>
                        <option value="not_interested">Not Interested</option>
                    </select>
                </div>

                <!-- Email Filter -->
                <div>
                    <select wire:model.live="filterHasEmail"
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                        <option value="all">All Leads</option>
                        <option value="yes">With Email</option>
                        <option value="no">Without Email</option>
                    </select>
                </div>

                <!-- Export Button -->
                <div>
                    <button wire:click="exportLeads"
                            class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                        📥 Export to CSV
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Leads Table -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
                            wire:click="sortBy('business_name')">
                            Business Name
                            @if($sortBy === 'business_name')
                                <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Contact Info
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
                            wire:click="sortBy('google_rating')">
                            Rating
                            @if($sortBy === 'google_rating')
                                <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($leads as $lead)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div>
                                <div class="text-sm font-medium text-gray-900">{{ $lead->business_name }}</div>
                                <div class="text-sm text-gray-500">{{ Str::limit($lead->address, 50) }}</div>
                                @if($lead->website)
                                    <a href="{{ $lead->website }}" target="_blank" class="text-xs text-green-600 hover:underline">
                                        🌐 {{ parse_url($lead->website, PHP_URL_HOST) }}
                                    </a>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="space-y-1">
                                @if($lead->email)
                                    <div class="flex items-center">
                                        <span class="text-sm text-gray-900">📧 {{ $lead->email }}</span>
                                        <button onclick="navigator.clipboard.writeText('{{ $lead->email }}')"
                                                class="ml-2 text-xs text-gray-500 hover:text-gray-700">
                                            📋
                                        </button>
                                    </div>
                                @else
                                    <div class="text-sm text-gray-400">📧 No email found</div>
                                @endif

                                @if($lead->phone)
                                    <div class="flex items-center">
                                        <span class="text-sm text-gray-900">📞 {{ $lead->phone }}</span>
                                        <button onclick="navigator.clipboard.writeText('{{ $lead->phone }}')"
                                                class="ml-2 text-xs text-gray-500 hover:text-gray-700">
                                            📋
                                        </button>
                                    </div>
                                @else
                                    <div class="text-sm text-gray-400">📞 No phone found</div>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div>
                                <div class="flex items-center">
                                    <span class="text-sm font-medium">{{ $lead->google_rating ?? 'N/A' }}</span>
                                    <span class="text-yellow-400 ml-1">⭐</span>
                                </div>
                                <div class="text-xs text-gray-500">{{ $lead->google_reviews_count ?? 0 }} reviews</div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <select wire:change="updateStatus({{ $lead->id }}, $event.target.value)"
                                    class="text-sm border rounded px-2 py-1
                                           {{ $lead->status === 'qualified' ? 'bg-green-50 text-green-700' : '' }}
                                           {{ $lead->status === 'contacted' ? 'bg-gray-50 text-gray-700' : '' }}
                                           {{ $lead->status === 'not_interested' ? 'bg-red-50 text-red-700' : '' }}">
                                <option value="new" {{ $lead->status === 'new' ? 'selected' : '' }}>New</option>
                                <option value="contacted" {{ $lead->status === 'contacted' ? 'selected' : '' }}>Contacted</option>
                                <option value="qualified" {{ $lead->status === 'qualified' ? 'selected' : '' }}>Qualified</option>
                                <option value="not_interested" {{ $lead->status === 'not_interested' ? 'selected' : '' }}>Not Interested</option>
                            </select>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex space-x-2">
                                @if($lead->email)
                                    <a href="mailto:{{ $lead->email }}"
                                       class="text-sm text-green-600 hover:text-green-700">
                                        ✉️ Email
                                    </a>
                                @endif
                                @if($lead->phone)
                                    <a href="tel:{{ $lead->phone }}"
                                       class="text-sm text-green-600 hover:text-green-700">
                                        📞 Call
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-4">
            {{ $leads->links() }}
        </div>
    </div>

    <!-- Flash Message -->
    @if (session()->has('message'))
        <div x-data="{ show: true }"
             x-show="show"
             x-init="setTimeout(() => show = false, 3000)"
             class="fixed bottom-4 right-4 bg-green-600 text-white px-6 py-3 rounded-lg shadow-lg">
            {{ session('message') }}
        </div>
    @endif
</div>