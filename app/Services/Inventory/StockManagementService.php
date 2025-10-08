<?php

namespace App\Services\Inventory;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\StockReservation;
use App\Models\StockAlert;
use App\Models\ReorderPoint;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use App\Events\StockLevelLow;
use App\Events\StockLevelCritical;
use App\Events\ProductOutOfStock;
use App\Events\ProductBackInStock;
use App\Jobs\CheckReorderPoints;
use App\Jobs\GenerateStockReport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class StockManagementService
{
    /**
     * Check stock availability for a product
     */
    public function checkAvailability(int $productId, float $quantity, ?int $variantId = null): array
    {
        $cacheKey = "stock_availability_{$productId}_{$variantId}";
        
        return Cache::remember($cacheKey, 60, function () use ($productId, $quantity, $variantId) {
            if ($variantId) {
                $variant = ProductVariant::find($variantId);
                if (!$variant) {
                    return [
                        'available' => false,
                        'message' => 'Product variant not found'
                    ];
                }
                $availableStock = $variant->available_stock;
                $entity = $variant;
            } else {
                $product = Product::find($productId);
                if (!$product) {
                    return [
                        'available' => false,
                        'message' => 'Product not found'
                    ];
                }
                $availableStock = $product->available_stock;
                $entity = $product;
            }
            
            // Calculate available stock (current - reserved)
            $reservedStock = $this->getReservedStock($productId, $variantId);
            $actualAvailable = $availableStock - $reservedStock;
            
            if ($actualAvailable >= $quantity) {
                return [
                    'available' => true,
                    'available_quantity' => $actualAvailable,
                    'requested_quantity' => $quantity,
                    'low_stock' => $actualAvailable < ($entity->reorder_point ?? 10),
                    'message' => 'Stock available'
                ];
            }
            
            // Check if pre-orders are allowed
            if ($entity->allow_preorder) {
                $nextRestock = $this->getNextRestockDate($productId, $variantId);
                return [
                    'available' => true,
                    'pre_order' => true,
                    'available_quantity' => $actualAvailable,
                    'requested_quantity' => $quantity,
                    'expected_date' => $nextRestock,
                    'message' => "Available for pre-order. Expected restock: {$nextRestock}"
                ];
            }
            
            return [
                'available' => false,
                'available_quantity' => $actualAvailable,
                'requested_quantity' => $quantity,
                'message' => $actualAvailable > 0 
                    ? "Only {$actualAvailable} units available" 
                    : 'Out of stock'
            ];
        });
    }

    /**
     * Reserve stock for an order
     */
    public function reserveStock(int $productId, float $quantity, int $orderId, ?int $variantId = null): bool
    {
        return DB::transaction(function () use ($productId, $quantity, $orderId, $variantId) {
            // Check availability first
            $availability = $this->checkAvailability($productId, $quantity, $variantId);
            
            if (!$availability['available'] && !($availability['pre_order'] ?? false)) {
                return false;
            }
            
            // Create reservation
            $reservation = StockReservation::create([
                'product_id' => $productId,
                'variant_id' => $variantId,
                'order_id' => $orderId,
                'quantity' => $quantity,
                'reserved_until' => Carbon::now()->addHours(2), // 2-hour reservation
                'status' => 'active'
            ]);
            
            // Update product stock levels
            if ($variantId) {
                ProductVariant::find($variantId)->decrement('available_stock', $quantity);
            } else {
                Product::find($productId)->decrement('available_stock', $quantity);
            }
            
            // Record stock movement
            $this->recordStockMovement([
                'product_id' => $productId,
                'variant_id' => $variantId,
                'type' => 'reservation',
                'quantity' => -$quantity,
                'reference_type' => 'order',
                'reference_id' => $orderId,
                'notes' => "Reserved for order #{$orderId}"
            ]);
            
            // Clear cache
            Cache::forget("stock_availability_{$productId}_{$variantId}");
            
            // Check for low stock alerts
            $this->checkStockLevels($productId, $variantId);
            
            return true;
        });
    }

    /**
     * Release reserved stock
     */
    public function releaseStock(int $productId, float $quantity, int $orderId, ?int $variantId = null): bool
    {
        return DB::transaction(function () use ($productId, $quantity, $orderId, $variantId) {
            // Find and cancel reservation
            $reservation = StockReservation::where('product_id', $productId)
                ->where('order_id', $orderId)
                ->where('variant_id', $variantId)
                ->where('status', 'active')
                ->first();
            
            if (!$reservation) {
                return false;
            }
            
            $reservation->update([
                'status' => 'released',
                'released_at' => now()
            ]);
            
            // Update product stock levels
            if ($variantId) {
                ProductVariant::find($variantId)->increment('available_stock', $quantity);
            } else {
                Product::find($productId)->increment('available_stock', $quantity);
            }
            
            // Record stock movement
            $this->recordStockMovement([
                'product_id' => $productId,
                'variant_id' => $variantId,
                'type' => 'release',
                'quantity' => $quantity,
                'reference_type' => 'order',
                'reference_id' => $orderId,
                'notes' => "Released reservation for order #{$orderId}"
            ]);
            
            // Clear cache
            Cache::forget("stock_availability_{$productId}_{$variantId}");
            
            // Check if product is back in stock
            $this->checkBackInStock($productId, $variantId);
            
            return true;
        });
    }

    /**
     * Confirm stock reservation (convert to actual sale)
     */
    public function confirmReservation(int $orderId): bool
    {
        return DB::transaction(function () use ($orderId) {
            $reservations = StockReservation::where('order_id', $orderId)
                ->where('status', 'active')
                ->get();
            
            foreach ($reservations as $reservation) {
                $reservation->update([
                    'status' => 'confirmed',
                    'confirmed_at' => now()
                ]);
                
                // Record stock movement
                $this->recordStockMovement([
                    'product_id' => $reservation->product_id,
                    'variant_id' => $reservation->variant_id,
                    'type' => 'sale',
                    'quantity' => -$reservation->quantity,
                    'reference_type' => 'order',
                    'reference_id' => $orderId,
                    'notes' => "Confirmed sale for order #{$orderId}"
                ]);
            }
            
            return true;
        });
    }

    /**
     * Adjust stock levels (for inventory counts, damages, etc.)
     */
    public function adjustStock(array $data): StockMovement
    {
        return DB::transaction(function () use ($data) {
            $product = Product::findOrFail($data['product_id']);
            $variant = isset($data['variant_id']) ? ProductVariant::find($data['variant_id']) : null;
            
            $oldStock = $variant ? $variant->available_stock : $product->available_stock;
            $newStock = $data['new_quantity'];
            $adjustment = $newStock - $oldStock;
            
            // Update stock level
            if ($variant) {
                $variant->update(['available_stock' => $newStock]);
            } else {
                $product->update(['available_stock' => $newStock]);
            }
            
            // Record movement
            $movement = $this->recordStockMovement([
                'product_id' => $data['product_id'],
                'variant_id' => $data['variant_id'] ?? null,
                'type' => $data['type'] ?? 'adjustment',
                'quantity' => $adjustment,
                'old_quantity' => $oldStock,
                'new_quantity' => $newStock,
                'warehouse_id' => $data['warehouse_id'] ?? null,
                'location_id' => $data['location_id'] ?? null,
                'reference_type' => $data['reference_type'] ?? 'manual',
                'reference_id' => $data['reference_id'] ?? null,
                'reason' => $data['reason'] ?? null,
                'notes' => $data['notes'] ?? null,
                'performed_by' => auth()->id()
            ]);
            
            // Clear cache
            Cache::forget("stock_availability_{$data['product_id']}_{$data['variant_id']}");
            
            // Check stock levels for alerts
            $this->checkStockLevels($data['product_id'], $data['variant_id'] ?? null);
            
            return $movement;
        });
    }

    /**
     * Process incoming stock (receiving)
     */
    public function receiveStock(array $data): Collection
    {
        return DB::transaction(function () use ($data) {
            $movements = collect();
            
            foreach ($data['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                $variant = isset($item['variant_id']) ? ProductVariant::find($item['variant_id']) : null;
                
                // Update stock
                if ($variant) {
                    $variant->increment('available_stock', $item['quantity']);
                    $variant->increment('total_stock', $item['quantity']);
                } else {
                    $product->increment('available_stock', $item['quantity']);
                    $product->increment('total_stock', $item['quantity']);
                }
                
                // Record movement
                $movement = $this->recordStockMovement([
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'] ?? null,
                    'type' => 'receiving',
                    'quantity' => $item['quantity'],
                    'warehouse_id' => $data['warehouse_id'] ?? null,
                    'location_id' => $item['location_id'] ?? null,
                    'batch_number' => $item['batch_number'] ?? null,
                    'expiry_date' => $item['expiry_date'] ?? null,
                    'cost_price' => $item['cost_price'] ?? null,
                    'reference_type' => 'purchase_order',
                    'reference_id' => $data['purchase_order_id'] ?? null,
                    'supplier_id' => $data['supplier_id'] ?? null,
                    'notes' => $item['notes'] ?? null,
                    'performed_by' => auth()->id()
                ]);
                
                $movements->push($movement);
                
                // Check if product is back in stock
                $this->checkBackInStock($item['product_id'], $item['variant_id'] ?? null);
            }
            
            // Generate receiving report
            if ($data['generate_report'] ?? false) {
                GenerateStockReport::dispatch('receiving', $movements->pluck('id')->toArray());
            }
            
            return $movements;
        });
    }

    /**
     * Transfer stock between locations
     */
    public function transferStock(array $data): array
    {
        return DB::transaction(function () use ($data) {
            // Create outgoing movement from source
            $outgoingMovement = $this->recordStockMovement([
                'product_id' => $data['product_id'],
                'variant_id' => $data['variant_id'] ?? null,
                'type' => 'transfer_out',
                'quantity' => -$data['quantity'],
                'warehouse_id' => $data['from_warehouse_id'],
                'location_id' => $data['from_location_id'] ?? null,
                'reference_type' => 'transfer',
                'reference_id' => $data['transfer_id'] ?? null,
                'notes' => "Transfer to {$data['to_warehouse_id']}",
                'performed_by' => auth()->id()
            ]);
            
            // Create incoming movement to destination
            $incomingMovement = $this->recordStockMovement([
                'product_id' => $data['product_id'],
                'variant_id' => $data['variant_id'] ?? null,
                'type' => 'transfer_in',
                'quantity' => $data['quantity'],
                'warehouse_id' => $data['to_warehouse_id'],
                'location_id' => $data['to_location_id'] ?? null,
                'reference_type' => 'transfer',
                'reference_id' => $data['transfer_id'] ?? null,
                'notes' => "Transfer from {$data['from_warehouse_id']}",
                'performed_by' => auth()->id()
            ]);
            
            return [
                'outgoing' => $outgoingMovement,
                'incoming' => $incomingMovement
            ];
        });
    }

    /**
     * Set reorder points and alerts
     */
    public function setReorderPoint(int $productId, array $data): ReorderPoint
    {
        return ReorderPoint::updateOrCreate(
            [
                'product_id' => $productId,
                'variant_id' => $data['variant_id'] ?? null,
                'warehouse_id' => $data['warehouse_id'] ?? null
            ],
            [
                'reorder_point' => $data['reorder_point'],
                'reorder_quantity' => $data['reorder_quantity'],
                'max_stock' => $data['max_stock'] ?? null,
                'lead_time_days' => $data['lead_time_days'] ?? 7,
                'safety_stock' => $data['safety_stock'] ?? 0,
                'supplier_id' => $data['supplier_id'] ?? null,
                'auto_reorder' => $data['auto_reorder'] ?? false,
                'notification_emails' => $data['notification_emails'] ?? [],
                'is_active' => $data['is_active'] ?? true
            ]
        );
    }

    /**
     * Check and trigger stock alerts
     */
    protected function checkStockLevels(int $productId, ?int $variantId = null): void
    {
        $product = Product::find($productId);
        $variant = $variantId ? ProductVariant::find($variantId) : null;
        
        $entity = $variant ?? $product;
        $currentStock = $entity->available_stock;
        
        // Get reorder point
        $reorderPoint = ReorderPoint::where('product_id', $productId)
            ->where('variant_id', $variantId)
            ->where('is_active', true)
            ->first();
        
        if (!$reorderPoint) {
            return;
        }
        
        // Check for out of stock
        if ($currentStock <= 0) {
            $this->createStockAlert($productId, $variantId, 'out_of_stock', $currentStock);
            event(new ProductOutOfStock($product, $variant));
        }
        // Check for critical level (below safety stock)
        elseif ($currentStock <= $reorderPoint->safety_stock) {
            $this->createStockAlert($productId, $variantId, 'critical', $currentStock);
            event(new StockLevelCritical($product, $variant, $currentStock));
        }
        // Check for low stock (below reorder point)
        elseif ($currentStock <= $reorderPoint->reorder_point) {
            $this->createStockAlert($productId, $variantId, 'low', $currentStock);
            event(new StockLevelLow($product, $variant, $currentStock));
            
            // Trigger auto-reorder if enabled
            if ($reorderPoint->auto_reorder) {
                $this->triggerAutoReorder($product, $variant, $reorderPoint);
            }
        }
    }

    /**
     * Check if product is back in stock
     */
    protected function checkBackInStock(int $productId, ?int $variantId = null): void
    {
        $product = Product::find($productId);
        $variant = $variantId ? ProductVariant::find($variantId) : null;
        
        $entity = $variant ?? $product;
        $currentStock = $entity->available_stock;
        
        // Check if there was an out of stock alert
        $outOfStockAlert = StockAlert::where('product_id', $productId)
            ->where('variant_id', $variantId)
            ->where('alert_type', 'out_of_stock')
            ->where('status', 'active')
            ->first();
        
        if ($outOfStockAlert && $currentStock > 0) {
            // Resolve the alert
            $outOfStockAlert->update([
                'status' => 'resolved',
                'resolved_at' => now(),
                'resolved_by' => auth()->id()
            ]);
            
            // Fire event
            event(new ProductBackInStock($product, $variant, $currentStock));
        }
    }

    /**
     * Create stock alert
     */
    protected function createStockAlert(int $productId, ?int $variantId, string $type, float $currentStock): StockAlert
    {
        // Check if similar active alert exists
        $existingAlert = StockAlert::where('product_id', $productId)
            ->where('variant_id', $variantId)
            ->where('alert_type', $type)
            ->where('status', 'active')
            ->first();
        
        if ($existingAlert) {
            // Update existing alert
            $existingAlert->update([
                'current_stock' => $currentStock,
                'last_triggered_at' => now()
            ]);
            return $existingAlert;
        }
        
        // Create new alert
        return StockAlert::create([
            'product_id' => $productId,
            'variant_id' => $variantId,
            'alert_type' => $type,
            'current_stock' => $currentStock,
            'threshold' => $this->getThresholdForAlertType($productId, $variantId, $type),
            'status' => 'active',
            'last_triggered_at' => now()
        ]);
    }

    /**
     * Get threshold for alert type
     */
    protected function getThresholdForAlertType(int $productId, ?int $variantId, string $type): float
    {
        $reorderPoint = ReorderPoint::where('product_id', $productId)
            ->where('variant_id', $variantId)
            ->first();
        
        if (!$reorderPoint) {
            return 0;
        }
        
        return match($type) {
            'out_of_stock' => 0,
            'critical' => $reorderPoint->safety_stock,
            'low' => $reorderPoint->reorder_point,
            default => 0
        };
    }

    /**
     * Trigger automatic reorder
     */
    protected function triggerAutoReorder($product, $variant, $reorderPoint): void
    {
        // This would integrate with purchase order system
        Log::info('Auto-reorder triggered', [
            'product_id' => $product->id,
            'variant_id' => $variant?->id,
            'reorder_quantity' => $reorderPoint->reorder_quantity,
            'supplier_id' => $reorderPoint->supplier_id
        ]);
        
        // Dispatch job to create purchase order
        CheckReorderPoints::dispatch($reorderPoint);
    }

    /**
     * Record stock movement
     */
    protected function recordStockMovement(array $data): StockMovement
    {
        return StockMovement::create($data);
    }

    /**
     * Get reserved stock quantity
     */
    protected function getReservedStock(int $productId, ?int $variantId = null): float
    {
        return StockReservation::where('product_id', $productId)
            ->where('variant_id', $variantId)
            ->where('status', 'active')
            ->where('reserved_until', '>', now())
            ->sum('quantity');
    }

    /**
     * Get next restock date
     */
    protected function getNextRestockDate(int $productId, ?int $variantId = null): ?string
    {
        // This would check incoming purchase orders or production schedules
        // For now, return estimate based on lead time
        $reorderPoint = ReorderPoint::where('product_id', $productId)
            ->where('variant_id', $variantId)
            ->first();
        
        if ($reorderPoint) {
            return Carbon::now()->addDays($reorderPoint->lead_time_days)->toDateString();
        }
        
        return Carbon::now()->addDays(7)->toDateString(); // Default 7 days
    }

    /**
     * Get stock status for display
     */
    public function getStockStatus(int $productId, ?int $variantId = null): array
    {
        $availability = $this->checkAvailability($productId, 1, $variantId);
        $availableQty = $availability['available_quantity'] ?? 0;
        
        if ($availableQty <= 0) {
            return [
                'status' => 'out_of_stock',
                'label' => 'Out of Stock',
                'color' => 'red',
                'available' => 0
            ];
        } elseif ($availableQty < 10) {
            return [
                'status' => 'low_stock',
                'label' => 'Low Stock',
                'color' => 'yellow',
                'available' => $availableQty
            ];
        } else {
            return [
                'status' => 'in_stock',
                'label' => 'In Stock',
                'color' => 'green',
                'available' => $availableQty
            ];
        }
    }

    /**
     * Get stock movement history
     */
    public function getStockHistory(int $productId, ?int $variantId = null, array $filters = []): Collection
    {
        $query = StockMovement::where('product_id', $productId);
        
        if ($variantId) {
            $query->where('variant_id', $variantId);
        }
        
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        
        if (isset($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }
        
        if (isset($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }
        
        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Generate stock valuation report
     */
    public function generateValuationReport(?int $warehouseId = null): array
    {
        $query = Product::with(['variants']);
        
        if ($warehouseId) {
            // Filter by warehouse if specified
        }
        
        $products = $query->get();
        
        $totalValue = 0;
        $totalQuantity = 0;
        $items = [];
        
        foreach ($products as $product) {
            if ($product->variants->isNotEmpty()) {
                foreach ($product->variants as $variant) {
                    $value = $variant->available_stock * ($variant->cost_price ?? $product->cost_price ?? 0);
                    $totalValue += $value;
                    $totalQuantity += $variant->available_stock;
                    
                    $items[] = [
                        'product' => $product->name,
                        'variant' => $variant->name,
                        'sku' => $variant->sku,
                        'quantity' => $variant->available_stock,
                        'unit_cost' => $variant->cost_price ?? $product->cost_price ?? 0,
                        'total_value' => $value
                    ];
                }
            } else {
                $value = $product->available_stock * ($product->cost_price ?? 0);
                $totalValue += $value;
                $totalQuantity += $product->available_stock;
                
                $items[] = [
                    'product' => $product->name,
                    'variant' => null,
                    'sku' => $product->sku,
                    'quantity' => $product->available_stock,
                    'unit_cost' => $product->cost_price ?? 0,
                    'total_value' => $value
                ];
            }
        }
        
        return [
            'generated_at' => now(),
            'warehouse_id' => $warehouseId,
            'total_value' => $totalValue,
            'total_quantity' => $totalQuantity,
            'total_products' => $products->count(),
            'items' => $items
        ];
    }

    /**
     * Cleanup expired reservations
     */
    public function cleanupExpiredReservations(): int
    {
        $expired = StockReservation::where('status', 'active')
            ->where('reserved_until', '<', now())
            ->get();
        
        $count = 0;
        foreach ($expired as $reservation) {
            $this->releaseStock(
                $reservation->product_id,
                $reservation->quantity,
                $reservation->order_id,
                $reservation->variant_id
            );
            $count++;
        }
        
        return $count;
    }
}