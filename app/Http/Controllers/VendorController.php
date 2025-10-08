<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\Product;
use App\Models\VendorRating;
use App\Models\Order;
use App\Models\Buyer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VendorController extends Controller
{
    /**
     * Display vendor directory
     */
    public function index(Request $request)
    {
        $query = Vendor::with(['products', 'ratings'])
            ->active()
            ->verified();
        
        // Search functionality
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('business_name', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%")
                  ->orWhere('business_type', 'LIKE', "%{$search}%")
                  ->orWhere('location', 'LIKE', "%{$search}%");
            });
        }
        
        // Filter by business type
        if ($businessType = $request->get('business_type')) {
            $query->where('business_type', $businessType);
        }
        
        // Filter by location
        if ($location = $request->get('location')) {
            $query->where('location', $location);
        }
        
        // Filter by rating
        if ($minRating = $request->get('min_rating')) {
            $query->whereHas('ratings', function ($q) use ($minRating) {
                $q->havingRaw('AVG(rating) >= ?', [$minRating]);
            });
        }
        
        // Sorting
        $sortBy = $request->get('sort', 'business_name');
        switch ($sortBy) {
            case 'rating':
                $query->withAvg('ratings', 'rating')
                      ->orderByDesc('ratings_avg_rating');
                break;
            case 'products':
                $query->withCount('products')
                      ->orderByDesc('products_count');
                break;
            case 'newest':
                $query->latest();
                break;
            default:
                $query->orderBy('business_name');
        }
        
        $vendors = $query->paginate(24)->withQueryString();
        
        // Get filter options
        $businessTypes = Vendor::active()->distinct()->pluck('business_type')->filter();
        $locations = Vendor::active()->distinct()->pluck('location')->filter();
        
        return view('vendors.index', compact('vendors', 'businessTypes', 'locations'));
    }
    
    /**
     * Show vendor profile
     */
    public function show($id)
    {
        $vendor = Vendor::with([
            'products' => function ($query) {
                $query->active()->inStock()->latest()->limit(20);
            },
            'ratings.buyer'
        ])->findOrFail($id);
        
        if (!$vendor->is_active || !$vendor->is_verified) {
            abort(404, 'Vendor not found');
        }
        
        // Get vendor statistics
        $stats = [
            'total_products' => $vendor->products()->active()->count(),
            'total_orders' => $vendor->orders()->completed()->count(),
            'average_rating' => $vendor->ratings()->avg('rating') ?: 0,
            'total_reviews' => $vendor->ratings()->count(),
            'response_time' => $vendor->average_response_time ?? '2-4 hours',
            'fulfillment_rate' => $vendor->fulfillment_rate ?? 98,
        ];
        
        // Check if buyer has favorited this vendor
        $isFavorited = false;
        if (Auth::guard('buyer')->check()) {
            $buyer = Auth::guard('buyer')->user();
            $isFavorited = $buyer->favoriteVendors()->where('vendor_id', $vendor->id)->exists();
        }
        
        // Get recent reviews
        $recentReviews = $vendor->ratings()
            ->with('buyer')
            ->latest()
            ->limit(5)
            ->get();
        
        // Get product categories
        $categories = $vendor->products()
            ->active()
            ->select('category_id')
            ->with('category')
            ->groupBy('category_id')
            ->get()
            ->pluck('category')
            ->filter();
        
        return view('vendors.show', compact(
            'vendor',
            'stats',
            'isFavorited',
            'recentReviews',
            'categories'
        ));
    }
    
    /**
     * Show vendor's products
     */
    public function products($id, Request $request)
    {
        $vendor = Vendor::active()->verified()->findOrFail($id);
        
        $query = $vendor->products()
            ->with('category')
            ->active()
            ->inStock();
        
        // Search within vendor's products
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%")
                  ->orWhere('sku', 'LIKE', "%{$search}%");
            });
        }
        
        // Filter by category
        if ($categoryId = $request->get('category')) {
            $query->where('category_id', $categoryId);
        }
        
        // Price range filter
        if ($minPrice = $request->get('min_price')) {
            $query->where('price', '>=', $minPrice);
        }
        if ($maxPrice = $request->get('max_price')) {
            $query->where('price', '<=', $maxPrice);
        }
        
        // Sorting
        $sortBy = $request->get('sort', 'name');
        switch ($sortBy) {
            case 'price_asc':
                $query->orderBy('price');
                break;
            case 'price_desc':
                $query->orderByDesc('price');
                break;
            case 'newest':
                $query->latest();
                break;
            default:
                $query->orderBy('name');
        }
        
        $products = $query->paginate(24)->withQueryString();
        
        // Get categories for filter
        $categories = $vendor->products()
            ->active()
            ->select('category_id')
            ->with('category')
            ->groupBy('category_id')
            ->get()
            ->pluck('category')
            ->filter();
        
        return view('vendors.products', compact('vendor', 'products', 'categories'));
    }
    
    /**
     * Toggle favorite vendor
     */
    public function toggleFavorite(Request $request, $id)
    {
        if (!Auth::guard('buyer')->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        $buyer = Auth::guard('buyer')->user();
        $vendor = Vendor::findOrFail($id);
        
        if ($buyer->favoriteVendors()->where('vendor_id', $id)->exists()) {
            $buyer->favoriteVendors()->detach($id);
            $isFavorited = false;
            $message = 'Vendor removed from favorites';
        } else {
            $buyer->favoriteVendors()->attach($id);
            $isFavorited = true;
            $message = 'Vendor added to favorites';
        }
        
        return response()->json([
            'success' => true,
            'message' => $message,
            'isFavorited' => $isFavorited
        ]);
    }
    
    /**
     * Rate vendor
     */
    public function rate(Request $request, $id)
    {
        $buyer = Auth::guard('buyer')->user();
        
        if (!$buyer) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        $vendor = Vendor::findOrFail($id);
        
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:1000',
            'order_id' => 'nullable|exists:orders,id',
        ]);
        
        // Check if buyer has purchased from this vendor
        $hasPurchased = Order::where('buyer_id', $buyer->id)
            ->where('vendor_id', $vendor->id)
            ->where('status', 'delivered')
            ->exists();
        
        if (!$hasPurchased) {
            return response()->json([
                'error' => 'You can only rate vendors you have purchased from'
            ], 403);
        }
        
        // Check if buyer has already rated this vendor
        $existingRating = VendorRating::where('buyer_id', $buyer->id)
            ->where('vendor_id', $vendor->id)
            ->first();
        
        if ($existingRating) {
            // Update existing rating
            $existingRating->update([
                'rating' => $request->rating,
                'review' => $request->review,
                'order_id' => $request->order_id,
            ]);
            $rating = $existingRating;
        } else {
            // Create new rating
            $rating = VendorRating::create([
                'buyer_id' => $buyer->id,
                'vendor_id' => $vendor->id,
                'order_id' => $request->order_id,
                'rating' => $request->rating,
                'review' => $request->review,
            ]);
        }
        
        // Update vendor's average rating
        $this->updateVendorRating($vendor);
        
        return response()->json([
            'success' => true,
            'message' => 'Rating submitted successfully',
            'rating' => [
                'id' => $rating->id,
                'rating' => $rating->rating,
                'review' => $rating->review,
                'created_at' => $rating->created_at->format('M d, Y'),
            ]
        ]);
    }
    
    /**
     * Get vendor ratings
     */
    public function ratings($id, Request $request)
    {
        $vendor = Vendor::findOrFail($id);
        
        $query = $vendor->ratings()->with('buyer');
        
        // Filter by rating
        if ($rating = $request->get('rating')) {
            $query->where('rating', $rating);
        }
        
        // Filter by review existence
        if ($request->get('with_review') === 'true') {
            $query->whereNotNull('review');
        }
        
        $ratings = $query->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();
        
        // Get rating distribution
        $distribution = $vendor->ratings()
            ->select('rating', DB::raw('COUNT(*) as count'))
            ->groupBy('rating')
            ->orderBy('rating', 'desc')
            ->get()
            ->pluck('count', 'rating');
        
        // Fill missing ratings with 0
        for ($i = 1; $i <= 5; $i++) {
            if (!isset($distribution[$i])) {
                $distribution[$i] = 0;
            }
        }
        
        $totalRatings = $distribution->sum();
        $averageRating = $totalRatings > 0 ? $vendor->ratings()->avg('rating') : 0;
        
        if ($request->ajax()) {
            return response()->json([
                'ratings' => $ratings->items(),
                'pagination' => [
                    'current_page' => $ratings->currentPage(),
                    'last_page' => $ratings->lastPage(),
                    'total' => $ratings->total(),
                ],
                'distribution' => $distribution,
                'average_rating' => round($averageRating, 1),
                'total_ratings' => $totalRatings,
            ]);
        }
        
        return view('vendors.ratings', compact(
            'vendor',
            'ratings',
            'distribution',
            'averageRating',
            'totalRatings'
        ));
    }
    
    /**
     * Contact vendor
     */
    public function contact(Request $request, $id)
    {
        $buyer = Auth::guard('buyer')->user();
        
        if (!$buyer) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        $vendor = Vendor::findOrFail($id);
        
        $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:2000',
            'inquiry_type' => 'nullable|in:general,product,quote,order,complaint',
        ]);
        
        try {
            // Create conversation and message (using MessageController logic)
            $messageController = app(MessageController::class);
            
            $response = $messageController->send(new Request([
                'recipient_type' => 'App\Models\Vendor',
                'recipient_id' => $vendor->id,
                'subject' => $request->subject,
                'message' => $request->message,
            ]));
            
            if ($response->getData()->success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Message sent successfully to vendor',
                    'conversation_id' => $response->getData()->conversation_id,
                ]);
            } else {
                return response()->json([
                    'error' => 'Failed to send message',
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to send message',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Search vendors (AJAX)
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2',
        ]);
        
        $query = $request->get('q');
        
        $vendors = Vendor::active()
            ->verified()
            ->where(function ($q) use ($query) {
                $q->where('business_name', 'LIKE', "%{$query}%")
                  ->orWhere('description', 'LIKE', "%{$query}%")
                  ->orWhere('business_type', 'LIKE', "%{$query}%");
            })
            ->limit(10)
            ->get(['id', 'business_name', 'business_type', 'location']);
        
        return response()->json([
            'vendors' => $vendors->map(function ($vendor) {
                return [
                    'id' => $vendor->id,
                    'name' => $vendor->business_name,
                    'type' => $vendor->business_type,
                    'location' => $vendor->location,
                    'url' => route('vendors.show', $vendor->id),
                ];
            })
        ]);
    }
    
    /**
     * Get vendor statistics (for internal use)
     */
    public function statistics($id)
    {
        $vendor = Vendor::findOrFail($id);
        
        // This would typically require admin authentication
        if (!Auth::guard('admin')->check()) {
            abort(403, 'Unauthorized');
        }
        
        $stats = [
            'total_products' => $vendor->products()->count(),
            'active_products' => $vendor->products()->active()->count(),
            'total_orders' => $vendor->orders()->count(),
            'completed_orders' => $vendor->orders()->where('status', 'delivered')->count(),
            'cancelled_orders' => $vendor->orders()->where('status', 'cancelled')->count(),
            'total_revenue' => $vendor->orders()->where('status', 'delivered')->sum('total_amount'),
            'average_order_value' => $vendor->orders()->where('status', 'delivered')->avg('total_amount'),
            'average_rating' => $vendor->ratings()->avg('rating'),
            'total_reviews' => $vendor->ratings()->count(),
            'response_rate' => $vendor->response_rate ?? 95,
            'fulfillment_rate' => $vendor->fulfillment_rate ?? 98,
        ];
        
        return response()->json($stats);
    }
    
    /**
     * Update vendor's average rating
     */
    protected function updateVendorRating(Vendor $vendor)
    {
        $averageRating = $vendor->ratings()->avg('rating');
        $totalRatings = $vendor->ratings()->count();
        
        $vendor->update([
            'average_rating' => $averageRating,
            'total_reviews' => $totalRatings,
        ]);
    }
}