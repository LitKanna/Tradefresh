{{-- Sydney Markets Logo Component --}}
@php
    $size = $size ?? 'default';
    $sizes = [
        'small' => ['width' => '120', 'height' => '40', 'text' => 'text-sm'],
        'default' => ['width' => '180', 'height' => '60', 'text' => 'text-lg'],
        'large' => ['width' => '240', 'height' => '80', 'text' => 'text-2xl'],
    ];
    $dimensions = $sizes[$size] ?? $sizes['default'];
@endphp

<div class="sydney-markets-logo" style="width: {{ $dimensions['width'] }}px; height: {{ $dimensions['height'] }}px;">
    <svg viewBox="0 0 240 80" fill="none" xmlns="http://www.w3.org/2000/svg" style="width: 100%; height: 100%;">
        <!-- Fresh Produce Leaf Icon -->
        <g transform="translate(10, 20)">
            <path d="M20 20c0-8.837 7.163-16 16-16s16 7.163 16 16-7.163 16-16 16-16-7.163-16-16z" fill="#10B981"/>
            <path d="M36 12c4 0 8 2 10 6-2 1-4 2-6 2-3 0-5-1-7-3-1-1-2-3-1-5h4z" fill="#059669"/>
            <path d="M36 28c-2 4-6 6-10 6 1-2 2-4 2-6 0-3 1-5 3-7 1-1 3-2 5-1v8z" fill="#047857"/>
        </g>

        <!-- Text: SYDNEY MARKETS -->
        <g transform="translate(70, 25)">
            <text x="0" y="15" font-family="system-ui, -apple-system, sans-serif" font-size="18" font-weight="700" fill="#000000">SYDNEY</text>
            <text x="0" y="35" font-family="system-ui, -apple-system, sans-serif" font-size="18" font-weight="700" fill="#10B981">MARKETS</text>
        </g>
    </svg>
</div>

<style>
.sydney-markets-logo {
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
</style>