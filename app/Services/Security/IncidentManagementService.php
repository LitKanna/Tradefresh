<?php

namespace App\Services\Security;

use App\Models\SecurityIncident;
use App\Models\User;
use App\Services\Security\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;

class IncidentManagementService
{
    protected $auditService;
    protected $escalationThresholds = [
        'low' => 48, // hours
        'medium' => 24,
        'high' => 4,
        'critical' => 1
    ];

    protected $incidentTypes = [
        'data_breach' => 'Data Breach',
        'unauthorized_access' => 'Unauthorized Access',
        'malware' => 'Malware Detection',
        'phishing' => 'Phishing Attempt',
        'dos_attack' => 'DoS/DDoS Attack',
        'insider_threat' => 'Insider Threat',
        'physical_security' => 'Physical Security Breach',
        'social_engineering' => 'Social Engineering',
        'system_compromise' => 'System Compromise',
        'data_loss' => 'Data Loss',
        'policy_violation' => 'Security Policy Violation',
        'vulnerability_exploit' => 'Vulnerability Exploitation'
    ];

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Create a new security incident
     */
    public function createIncident(array $data, User $reporter = null): SecurityIncident
    {
        DB::beginTransaction();
        
        try {
            $incident = SecurityIncident::create([
                'incident_id' => $this->generateIncidentId(),
                'type' => $data['type'],
                'severity' => $data['severity'],
                'status' => 'detected',
                'description' => $data['description'],
                'affected_resources' => $data['affected_resources'] ?? [],
                'reported_by' => $reporter ? $reporter->id : null,
                'detected_at' => $data['detected_at'] ?? now(),
                'metadata' => array_merge($data['metadata'] ?? [], [
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'created_at' => now()->toIso8601String()
                ])
            ]);

            // Auto-assign based on severity
            if ($data['severity'] === 'critical' || $data['severity'] === 'high') {
                $this->autoAssignIncident($incident);
            }

            // Log the incident
            $this->auditService->logSecurityEvent(
                $data['type'],
                $data['description'],
                $data['severity'],
                ['incident_id' => $incident->incident_id]
            );

            // Send notifications
            $this->notifySecurityTeam($incident);

            // Check for automatic containment actions
            $this->checkAutomaticContainment($incident);

            DB::commit();

            return $incident;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update incident status
     */
    public function updateIncidentStatus(
        SecurityIncident $incident,
        string $newStatus,
        User $updatedBy,
        array $metadata = null
    ): SecurityIncident {
        $oldStatus = $incident->status;
        
        // Validate status transition
        if (!$this->isValidStatusTransition($oldStatus, $newStatus)) {
            throw new Exception("Invalid status transition from {$oldStatus} to {$newStatus}");
        }

        $incident->status = $newStatus;

        // Update timestamps based on status
        switch ($newStatus) {
            case 'investigating':
                $incident->metadata = array_merge($incident->metadata ?? [], [
                    'investigation_started_at' => now()->toIso8601String(),
                    'investigator_id' => $updatedBy->id
                ]);
                break;
                
            case 'contained':
                $incident->contained_at = now();
                break;
                
            case 'resolved':
                $incident->resolved_at = now();
                break;
        }

        // Add metadata
        if ($metadata) {
            $incident->metadata = array_merge($incident->metadata ?? [], $metadata);
        }

        $incident->save();

        // Log the status change
        $this->auditService->log(
            'incident_status_changed',
            "Incident {$incident->incident_id} status changed from {$oldStatus} to {$newStatus}",
            $incident,
            ['status' => $oldStatus],
            ['status' => $newStatus],
            null,
            $updatedBy
        );

        // Send notifications
        $this->notifyStatusChange($incident, $oldStatus, $newStatus);

        return $incident;
    }

    /**
     * Assign incident to user
     */
    public function assignIncident(SecurityIncident $incident, User $assignee, User $assignedBy): void
    {
        $incident->assigned_to = $assignee->id;
        $incident->metadata = array_merge($incident->metadata ?? [], [
            'assigned_at' => now()->toIso8601String(),
            'assigned_by' => $assignedBy->id
        ]);
        $incident->save();

        // Notify assignee
        $assignee->notify(new \App\Notifications\IncidentAssigned($incident));

        // Log assignment
        $this->auditService->log(
            'incident_assigned',
            "Incident {$incident->incident_id} assigned to {$assignee->email}",
            $incident,
            null,
            ['assigned_to' => $assignee->id],
            null,
            $assignedBy
        );
    }

    /**
     * Add action taken for incident
     */
    public function addActionTaken(
        SecurityIncident $incident,
        string $action,
        User $performedBy,
        array $details = null
    ): void {
        $actionsTaken = $incident->actions_taken ?? [];
        
        $actionsTaken[] = [
            'action' => $action,
            'performed_by' => $performedBy->id,
            'performed_at' => now()->toIso8601String(),
            'details' => $details
        ];

        $incident->actions_taken = $actionsTaken;
        $incident->save();

        // Log action
        $this->auditService->log(
            'incident_action_taken',
            "Action taken on incident {$incident->incident_id}: {$action}",
            $incident,
            null,
            ['action' => $action],
            null,
            $performedBy
        );
    }

    /**
     * Resolve incident
     */
    public function resolveIncident(
        SecurityIncident $incident,
        string $resolution,
        User $resolvedBy,
        array $rootCause = null
    ): void {
        $incident->status = 'resolved';
        $incident->resolved_at = now();
        $incident->resolution = $resolution;
        
        $incident->metadata = array_merge($incident->metadata ?? [], [
            'resolved_by' => $resolvedBy->id,
            'root_cause' => $rootCause
        ]);

        $incident->save();

        // Create post-incident review if high severity
        if (in_array($incident->severity, ['critical', 'high'])) {
            $this->schedulePostIncidentReview($incident);
        }

        // Log resolution
        $this->auditService->log(
            'incident_resolved',
            "Incident {$incident->incident_id} resolved",
            $incident,
            null,
            ['resolution' => $resolution],
            null,
            $resolvedBy
        );

        // Send notifications
        $this->notifyIncidentResolved($incident);
    }

    /**
     * Escalate incident
     */
    public function escalateIncident(SecurityIncident $incident, string $reason = null): void
    {
        // Increase severity if possible
        $severityLevels = ['low', 'medium', 'high', 'critical'];
        $currentIndex = array_search($incident->severity, $severityLevels);
        
        if ($currentIndex < count($severityLevels) - 1) {
            $incident->severity = $severityLevels[$currentIndex + 1];
        }

        $incident->metadata = array_merge($incident->metadata ?? [], [
            'escalated_at' => now()->toIso8601String(),
            'escalation_reason' => $reason
        ]);

        $incident->save();

        // Notify management
        $this->notifyManagement($incident, 'escalation');

        // Auto-assign to senior staff
        $this->autoAssignToSenior($incident);

        // Log escalation
        $this->auditService->logSecurityEvent(
            'incident_escalated',
            "Incident {$incident->incident_id} escalated",
            $incident->severity,
            ['reason' => $reason]
        );
    }

    /**
     * Get incident metrics
     */
    public function getIncidentMetrics(int $days = 30): array
    {
        $startDate = now()->subDays($days);

        return [
            'total_incidents' => SecurityIncident::where('detected_at', '>=', $startDate)->count(),
            'by_severity' => $this->getIncidentsBySeverity($startDate),
            'by_type' => $this->getIncidentsByType($startDate),
            'by_status' => $this->getIncidentsByStatus($startDate),
            'mean_time_to_contain' => $this->calculateMTTC($startDate),
            'mean_time_to_resolve' => $this->calculateMTTR($startDate),
            'daily_trend' => $this->getDailyIncidentTrend($days),
            'top_affected_resources' => $this->getTopAffectedResources($startDate)
        ];
    }

    /**
     * Generate incident report
     */
    public function generateIncidentReport(SecurityIncident $incident): array
    {
        $timeline = $this->buildIncidentTimeline($incident);
        $impact = $this->assessIncidentImpact($incident);
        
        return [
            'incident_details' => [
                'id' => $incident->incident_id,
                'type' => $this->incidentTypes[$incident->type] ?? $incident->type,
                'severity' => $incident->severity,
                'status' => $incident->status,
                'description' => $incident->description
            ],
            'timeline' => $timeline,
            'impact_assessment' => $impact,
            'affected_resources' => $incident->affected_resources,
            'actions_taken' => $incident->actions_taken,
            'resolution' => [
                'resolved' => $incident->resolved_at !== null,
                'resolution_details' => $incident->resolution,
                'time_to_contain' => $this->calculateTimeToContain($incident),
                'time_to_resolve' => $this->calculateTimeToResolve($incident)
            ],
            'recommendations' => $this->generateRecommendations($incident),
            'lessons_learned' => $this->extractLessonsLearned($incident)
        ];
    }

    /**
     * Search incidents
     */
    public function searchIncidents(array $criteria): \Illuminate\Pagination\LengthAwarePaginator
    {
        $query = SecurityIncident::query();

        if (isset($criteria['type'])) {
            $query->where('type', $criteria['type']);
        }

        if (isset($criteria['severity'])) {
            if (is_array($criteria['severity'])) {
                $query->whereIn('severity', $criteria['severity']);
            } else {
                $query->where('severity', $criteria['severity']);
            }
        }

        if (isset($criteria['status'])) {
            if (is_array($criteria['status'])) {
                $query->whereIn('status', $criteria['status']);
            } else {
                $query->where('status', $criteria['status']);
            }
        }

        if (isset($criteria['date_from'])) {
            $query->where('detected_at', '>=', Carbon::parse($criteria['date_from']));
        }

        if (isset($criteria['date_to'])) {
            $query->where('detected_at', '<=', Carbon::parse($criteria['date_to']));
        }

        if (isset($criteria['assigned_to'])) {
            $query->where('assigned_to', $criteria['assigned_to']);
        }

        if (isset($criteria['search'])) {
            $search = $criteria['search'];
            $query->where(function ($q) use ($search) {
                $q->where('incident_id', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('resolution', 'like', "%{$search}%");
            });
        }

        $query->orderBy($criteria['order_by'] ?? 'detected_at', $criteria['order_dir'] ?? 'desc');

        return $query->paginate($criteria['per_page'] ?? 20);
    }

    /**
     * Check for incident patterns
     */
    public function detectIncidentPatterns(): array
    {
        $patterns = [];

        // Check for repeated incidents from same IP
        $ipPatterns = DB::table('security_incidents')
            ->select('metadata->ip_address as ip', DB::raw('COUNT(*) as count'))
            ->where('detected_at', '>=', now()->subDays(7))
            ->groupBy('ip')
            ->having('count', '>', 3)
            ->get();

        if ($ipPatterns->isNotEmpty()) {
            $patterns['repeated_ips'] = $ipPatterns;
        }

        // Check for similar incident types
        $typePatterns = SecurityIncident::where('detected_at', '>=', now()->subDays(7))
            ->groupBy('type')
            ->selectRaw('type, COUNT(*) as count')
            ->having('count', '>', 5)
            ->get();

        if ($typePatterns->isNotEmpty()) {
            $patterns['frequent_types'] = $typePatterns;
        }

        // Check for time-based patterns
        $timePatterns = $this->detectTimeBasedPatterns();
        if (!empty($timePatterns)) {
            $patterns['time_patterns'] = $timePatterns;
        }

        return $patterns;
    }

    /**
     * Helper Methods
     */
    protected function generateIncidentId(): string
    {
        $year = now()->format('Y');
        $month = now()->format('m');
        $random = strtoupper(Str::random(6));
        
        return "INC-{$year}{$month}-{$random}";
    }

    protected function isValidStatusTransition(string $from, string $to): bool
    {
        $transitions = [
            'detected' => ['investigating', 'contained', 'closed'],
            'investigating' => ['contained', 'resolved', 'closed'],
            'contained' => ['resolved', 'closed'],
            'resolved' => ['closed'],
            'closed' => []
        ];

        return in_array($to, $transitions[$from] ?? []);
    }

    protected function autoAssignIncident(SecurityIncident $incident): void
    {
        // Find available security team member based on workload
        $assignee = User::role('security_team')
            ->withCount(['assignedIncidents' => function ($query) {
                $query->whereIn('status', ['detected', 'investigating', 'contained']);
            }])
            ->orderBy('assigned_incidents_count')
            ->first();

        if ($assignee) {
            $incident->assigned_to = $assignee->id;
            $incident->save();
            
            $assignee->notify(new \App\Notifications\IncidentAssigned($incident));
        }
    }

    protected function checkAutomaticContainment(SecurityIncident $incident): void
    {
        // Implement automatic containment based on incident type
        switch ($incident->type) {
            case 'unauthorized_access':
                // Could trigger account lockout
                break;
            case 'dos_attack':
                // Could trigger rate limiting or IP blocking
                break;
            case 'malware':
                // Could trigger system isolation
                break;
        }
    }

    protected function notifySecurityTeam(SecurityIncident $incident): void
    {
        $securityTeam = User::role('security_team')->get();
        
        Notification::send($securityTeam, new \App\Notifications\NewSecurityIncident($incident));

        // For critical incidents, also send SMS/call
        if ($incident->severity === 'critical') {
            // Implement SMS/call notification
        }
    }

    protected function notifyStatusChange(SecurityIncident $incident, string $oldStatus, string $newStatus): void
    {
        if ($incident->reported_by) {
            $reporter = User::find($incident->reported_by);
            $reporter?->notify(new \App\Notifications\IncidentStatusChanged($incident, $oldStatus, $newStatus));
        }

        if ($incident->assigned_to) {
            $assignee = User::find($incident->assigned_to);
            $assignee?->notify(new \App\Notifications\IncidentStatusChanged($incident, $oldStatus, $newStatus));
        }
    }

    protected function notifyIncidentResolved(SecurityIncident $incident): void
    {
        // Notify all stakeholders
        $stakeholders = $this->getIncidentStakeholders($incident);
        
        Notification::send($stakeholders, new \App\Notifications\IncidentResolved($incident));
    }

    protected function notifyManagement(SecurityIncident $incident, string $reason): void
    {
        $management = User::role(['security_manager', 'ciso', 'admin'])->get();
        
        Notification::send($management, new \App\Notifications\IncidentEscalated($incident, $reason));
    }

    protected function autoAssignToSenior(SecurityIncident $incident): void
    {
        $seniorStaff = User::role('security_lead')->first();
        
        if ($seniorStaff && !$incident->assigned_to) {
            $this->assignIncident($incident, $seniorStaff, $seniorStaff);
        }
    }

    protected function schedulePostIncidentReview(SecurityIncident $incident): void
    {
        // Schedule a post-incident review meeting
        // This would integrate with calendar system
    }

    protected function getIncidentStakeholders(SecurityIncident $incident): \Illuminate\Support\Collection
    {
        $stakeholders = collect();

        if ($incident->reported_by) {
            $stakeholders->push(User::find($incident->reported_by));
        }

        if ($incident->assigned_to) {
            $stakeholders->push(User::find($incident->assigned_to));
        }

        // Add affected users based on resources
        foreach ($incident->affected_resources ?? [] as $resource) {
            if (isset($resource['user_ids'])) {
                $users = User::whereIn('id', $resource['user_ids'])->get();
                $stakeholders = $stakeholders->merge($users);
            }
        }

        return $stakeholders->unique('id');
    }

    protected function buildIncidentTimeline(SecurityIncident $incident): array
    {
        $timeline = [
            [
                'timestamp' => $incident->detected_at,
                'event' => 'Incident detected',
                'details' => $incident->description
            ]
        ];

        if ($incident->metadata && isset($incident->metadata['investigation_started_at'])) {
            $timeline[] = [
                'timestamp' => $incident->metadata['investigation_started_at'],
                'event' => 'Investigation started',
                'details' => null
            ];
        }

        foreach ($incident->actions_taken ?? [] as $action) {
            $timeline[] = [
                'timestamp' => $action['performed_at'],
                'event' => 'Action taken',
                'details' => $action['action']
            ];
        }

        if ($incident->contained_at) {
            $timeline[] = [
                'timestamp' => $incident->contained_at,
                'event' => 'Incident contained',
                'details' => null
            ];
        }

        if ($incident->resolved_at) {
            $timeline[] = [
                'timestamp' => $incident->resolved_at,
                'event' => 'Incident resolved',
                'details' => $incident->resolution
            ];
        }

        return $timeline;
    }

    protected function assessIncidentImpact(SecurityIncident $incident): array
    {
        return [
            'affected_users' => $this->countAffectedUsers($incident),
            'affected_systems' => count($incident->affected_resources ?? []),
            'data_exposure' => $this->assessDataExposure($incident),
            'financial_impact' => $this->estimateFinancialImpact($incident),
            'reputation_impact' => $this->assessReputationImpact($incident),
            'regulatory_impact' => $this->assessRegulatoryImpact($incident)
        ];
    }

    protected function countAffectedUsers(SecurityIncident $incident): int
    {
        $count = 0;
        foreach ($incident->affected_resources ?? [] as $resource) {
            $count += count($resource['user_ids'] ?? []);
        }
        return $count;
    }

    protected function assessDataExposure(SecurityIncident $incident): string
    {
        if (in_array($incident->type, ['data_breach', 'unauthorized_access'])) {
            return 'High';
        }
        return 'Low';
    }

    protected function estimateFinancialImpact(SecurityIncident $incident): ?float
    {
        // Implement financial impact calculation
        return null;
    }

    protected function assessReputationImpact(SecurityIncident $incident): string
    {
        if ($incident->severity === 'critical' && in_array($incident->type, ['data_breach'])) {
            return 'High';
        }
        return 'Low';
    }

    protected function assessRegulatoryImpact(SecurityIncident $incident): array
    {
        $impacts = [];
        
        if (in_array($incident->type, ['data_breach', 'data_loss'])) {
            $impacts[] = 'GDPR notification required within 72 hours';
        }

        return $impacts;
    }

    protected function generateRecommendations(SecurityIncident $incident): array
    {
        $recommendations = [];

        switch ($incident->type) {
            case 'unauthorized_access':
                $recommendations[] = 'Review and strengthen access controls';
                $recommendations[] = 'Implement or enhance MFA';
                break;
            case 'data_breach':
                $recommendations[] = 'Conduct security audit';
                $recommendations[] = 'Review data encryption policies';
                break;
            case 'malware':
                $recommendations[] = 'Update antivirus definitions';
                $recommendations[] = 'Conduct security awareness training';
                break;
        }

        return $recommendations;
    }

    protected function extractLessonsLearned(SecurityIncident $incident): array
    {
        // This would be populated from post-incident reviews
        return $incident->metadata['lessons_learned'] ?? [];
    }

    protected function calculateTimeToContain(SecurityIncident $incident): ?string
    {
        if (!$incident->contained_at) {
            return null;
        }

        $duration = $incident->detected_at->diff($incident->contained_at);
        return $this->formatDuration($duration);
    }

    protected function calculateTimeToResolve(SecurityIncident $incident): ?string
    {
        if (!$incident->resolved_at) {
            return null;
        }

        $duration = $incident->detected_at->diff($incident->resolved_at);
        return $this->formatDuration($duration);
    }

    protected function formatDuration(\DateInterval $duration): string
    {
        if ($duration->days > 0) {
            return $duration->days . ' days ' . $duration->h . ' hours';
        }
        if ($duration->h > 0) {
            return $duration->h . ' hours ' . $duration->i . ' minutes';
        }
        return $duration->i . ' minutes';
    }

    protected function getIncidentsBySeverity(Carbon $startDate): array
    {
        return SecurityIncident::where('detected_at', '>=', $startDate)
            ->groupBy('severity')
            ->selectRaw('severity, COUNT(*) as count')
            ->pluck('count', 'severity')
            ->toArray();
    }

    protected function getIncidentsByType(Carbon $startDate): array
    {
        return SecurityIncident::where('detected_at', '>=', $startDate)
            ->groupBy('type')
            ->selectRaw('type, COUNT(*) as count')
            ->pluck('count', 'type')
            ->toArray();
    }

    protected function getIncidentsByStatus(Carbon $startDate): array
    {
        return SecurityIncident::where('detected_at', '>=', $startDate)
            ->groupBy('status')
            ->selectRaw('status, COUNT(*) as count')
            ->pluck('count', 'status')
            ->toArray();
    }

    protected function calculateMTTC(Carbon $startDate): ?float
    {
        $incidents = SecurityIncident::where('detected_at', '>=', $startDate)
            ->whereNotNull('contained_at')
            ->get();

        if ($incidents->isEmpty()) {
            return null;
        }

        $totalMinutes = 0;
        foreach ($incidents as $incident) {
            $totalMinutes += $incident->detected_at->diffInMinutes($incident->contained_at);
        }

        return round($totalMinutes / $incidents->count() / 60, 2); // Return in hours
    }

    protected function calculateMTTR(Carbon $startDate): ?float
    {
        $incidents = SecurityIncident::where('detected_at', '>=', $startDate)
            ->whereNotNull('resolved_at')
            ->get();

        if ($incidents->isEmpty()) {
            return null;
        }

        $totalMinutes = 0;
        foreach ($incidents as $incident) {
            $totalMinutes += $incident->detected_at->diffInMinutes($incident->resolved_at);
        }

        return round($totalMinutes / $incidents->count() / 60, 2); // Return in hours
    }

    protected function getDailyIncidentTrend(int $days): array
    {
        $trend = [];
        
        for ($i = $days; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $count = SecurityIncident::whereDate('detected_at', $date)->count();
            $trend[$date] = $count;
        }

        return $trend;
    }

    protected function getTopAffectedResources(Carbon $startDate): array
    {
        // This would analyze affected_resources field
        return [];
    }

    protected function detectTimeBasedPatterns(): array
    {
        // Analyze incidents by hour of day, day of week, etc.
        return [];
    }
}