<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BulkHunter\BulkHunterService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BulkHunterController extends Controller
{
    protected BulkHunterService $bulkHunter;

    public function __construct(BulkHunterService $bulkHunter)
    {
        $this->bulkHunter = $bulkHunter;
    }

    /**
     * Search for new businesses via ABN
     */
    public function discoverLeads(Request $request): JsonResponse
    {
        $request->validate([
            'keyword' => 'nullable|string|max:100',
            'postcode' => 'nullable|string|max:10',
            'limit' => 'nullable|integer|min:1|max:500'
        ]);

        try {
            $results = $this->bulkHunter->searchFoodBusinesses([
                'keyword' => $request->keyword ?? 'restaurant',
                'postcode' => $request->postcode,
                'limit' => $request->limit ?? 50
            ]);

            return response()->json([
                'success' => true,
                'message' => count($results) . ' businesses discovered',
                'data' => $results,
                'count' => count($results)
            ]);

        } catch (\Exception $e) {
            Log::error('BulkHunter discovery error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to discover businesses',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enrich a lead with Google Maps data
     */
    public function enrichLead(Request $request, $leadId): JsonResponse
    {
        try {
            $success = $this->bulkHunter->enrichWithGoogleMaps($leadId);

            if ($success) {
                $lead = DB::table('buyer_leads')->find($leadId);
                return response()->json([
                    'success' => true,
                    'message' => 'Lead enriched successfully',
                    'data' => $lead
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to enrich lead'
            ], 400);

        } catch (\Exception $e) {
            Log::error('Lead enrichment error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to enrich lead',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all leads with filtering
     */
    public function getLeads(Request $request): JsonResponse
    {
        try {
            $query = DB::table('buyer_leads');

            // Apply filters
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('classification')) {
                $query->where('size_classification', $request->classification);
            }

            if ($request->has('category')) {
                $query->where('category', $request->category);
            }

            if ($request->has('postcode')) {
                $query->where('postcode', $request->postcode);
            }

            if ($request->has('min_score')) {
                $query->where('final_score', '>=', $request->min_score);
            }

            // Sorting
            $sortBy = $request->sort_by ?? 'final_score';
            $sortOrder = $request->sort_order ?? 'desc';
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->per_page ?? 50;
            $leads = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $leads->items(),
                'total' => $leads->total(),
                'per_page' => $leads->perPage(),
                'current_page' => $leads->currentPage(),
                'last_page' => $leads->lastPage()
            ]);

        } catch (\Exception $e) {
            Log::error('Get leads error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve leads',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single lead details
     */
    public function getLead($leadId): JsonResponse
    {
        try {
            $lead = DB::table('buyer_leads')->find($leadId);

            if (!$lead) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lead not found'
                ], 404);
            }

            // Get contacts
            $contacts = DB::table('lead_contacts')
                ->where('lead_id', $leadId)
                ->get();

            // Get activities
            $activities = DB::table('lead_activities')
                ->where('lead_id', $leadId)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            // Get campaigns
            $campaigns = DB::table('outreach_campaigns')
                ->where('lead_id', $leadId)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'lead' => $lead,
                    'contacts' => $contacts,
                    'activities' => $activities,
                    'campaigns' => $campaigns
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Get lead error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve lead',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update lead status
     */
    public function updateLead(Request $request, $leadId): JsonResponse
    {
        $request->validate([
            'status' => 'nullable|in:NEW,ENRICHED,QUALIFIED,CONTACTED,NEGOTIATING,CONVERTED,LOST',
            'assigned_vendor_id' => 'nullable|exists:vendors,id',
            'current_supplier' => 'nullable|string|max:255',
            'unhappy_with_supplier' => 'nullable|boolean',
            'supplier_pain_points' => 'nullable|string'
        ]);

        try {
            $updateData = $request->only([
                'status', 'assigned_vendor_id', 'current_supplier',
                'unhappy_with_supplier', 'supplier_pain_points'
            ]);
            $updateData['updated_at'] = now();

            if ($request->status === 'CONTACTED') {
                $updateData['last_contacted_at'] = now();
            } elseif ($request->status === 'CONVERTED') {
                $updateData['converted_at'] = now();
            }

            DB::table('buyer_leads')
                ->where('id', $leadId)
                ->update($updateData);

            $lead = DB::table('buyer_leads')->find($leadId);

            return response()->json([
                'success' => true,
                'message' => 'Lead updated successfully',
                'data' => $lead
            ]);

        } catch (\Exception $e) {
            Log::error('Update lead error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update lead',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add contact to lead
     */
    public function addContact(Request $request, $leadId): JsonResponse
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'role' => 'nullable|string|max:100',
            'department' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'mobile' => 'nullable|string|max:20',
            'linkedin_url' => 'nullable|url|max:500',
            'is_decision_maker' => 'nullable|boolean'
        ]);

        try {
            $contactData = $request->all();
            $contactData['lead_id'] = $leadId;
            $contactData['created_at'] = now();
            $contactData['updated_at'] = now();

            $contactId = DB::table('lead_contacts')->insertGetId($contactData);
            $contact = DB::table('lead_contacts')->find($contactId);

            return response()->json([
                'success' => true,
                'message' => 'Contact added successfully',
                'data' => $contact
            ]);

        } catch (\Exception $e) {
            Log::error('Add contact error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to add contact',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Log activity for lead
     */
    public function logActivity(Request $request, $leadId): JsonResponse
    {
        $request->validate([
            'activity_type' => 'required|string|max:50',
            'description' => 'nullable|string',
            'outcome' => 'nullable|string|max:100',
            'next_action' => 'nullable|string|max:255',
            'next_action_date' => 'nullable|date'
        ]);

        try {
            $activityData = $request->all();
            $activityData['lead_id'] = $leadId;
            $activityData['created_by'] = $request->user()->id ?? null;
            $activityData['created_at'] = now();
            $activityData['updated_at'] = now();

            $activityId = DB::table('lead_activities')->insertGetId($activityData);
            $activity = DB::table('lead_activities')->find($activityId);

            return response()->json([
                'success' => true,
                'message' => 'Activity logged successfully',
                'data' => $activity
            ]);

        } catch (\Exception $e) {
            Log::error('Log activity error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to log activity',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk discover and enrich
     */
    public function bulkProcess(Request $request): JsonResponse
    {
        $request->validate([
            'keywords' => 'nullable|array',
            'keywords.*' => 'string|max:100',
            'limit' => 'nullable|integer|min:1|max:1000'
        ]);

        try {
            $results = $this->bulkHunter->bulkDiscoverAndEnrich(
                $request->keywords,
                $request->limit ?? 100
            );

            return response()->json([
                'success' => true,
                'message' => 'Bulk processing completed',
                'total_discovered' => count($results),
                'data' => $results
            ]);

        } catch (\Exception $e) {
            Log::error('Bulk process error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Bulk processing failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get statistics
     */
    public function getStatistics(): JsonResponse
    {
        try {
            $stats = [
                'total_leads' => DB::table('buyer_leads')->count(),
                'new_leads' => DB::table('buyer_leads')->where('status', 'NEW')->count(),
                'enriched_leads' => DB::table('buyer_leads')->where('status', 'ENRICHED')->count(),
                'qualified_leads' => DB::table('buyer_leads')->where('status', 'QUALIFIED')->count(),
                'contacted_leads' => DB::table('buyer_leads')->where('status', 'CONTACTED')->count(),
                'converted_leads' => DB::table('buyer_leads')->where('status', 'CONVERTED')->count(),
                'by_classification' => DB::table('buyer_leads')
                    ->select('size_classification', DB::raw('count(*) as count'))
                    ->whereNotNull('size_classification')
                    ->groupBy('size_classification')
                    ->get(),
                'by_category' => DB::table('buyer_leads')
                    ->select('category', DB::raw('count(*) as count'))
                    ->whereNotNull('category')
                    ->groupBy('category')
                    ->get(),
                'total_volume_estimate' => DB::table('buyer_leads')
                    ->sum('weekly_volume_estimate'),
                'total_spend_estimate' => DB::table('buyer_leads')
                    ->sum('monthly_spend_estimate')
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Get statistics error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}