<?php

namespace App\Livewire\Buyer;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

class DiscoveredLeads extends Component
{
    use WithPagination;

    public $search = '';
    public $filterStatus = 'all';
    public $filterHasEmail = 'all';
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';

    protected $queryString = ['search', 'filterStatus', 'filterHasEmail'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function render()
    {
        $query = DB::table('buyer_leads');

        // Search filter
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('business_name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%')
                  ->orWhere('phone', 'like', '%' . $this->search . '%')
                  ->orWhere('address', 'like', '%' . $this->search . '%');
            });
        }

        // Status filter
        if ($this->filterStatus !== 'all') {
            $query->where('status', $this->filterStatus);
        }

        // Email filter
        if ($this->filterHasEmail === 'yes') {
            $query->whereNotNull('email')->where('email', '!=', '');
        } elseif ($this->filterHasEmail === 'no') {
            $query->where(function ($q) {
                $q->whereNull('email')->orWhere('email', '=', '');
            });
        }

        // Sorting
        $query->orderBy($this->sortBy, $this->sortDirection);

        $leads = $query->paginate(20);

        // Statistics
        $stats = [
            'total' => DB::table('buyer_leads')->count(),
            'with_email' => DB::table('buyer_leads')->whereNotNull('email')->where('email', '!=', '')->count(),
            'with_phone' => DB::table('buyer_leads')->whereNotNull('phone')->where('phone', '!=', '')->count(),
            'contacted' => DB::table('buyer_leads')->where('status', 'contacted')->count(),
            'qualified' => DB::table('buyer_leads')->where('status', 'qualified')->count(),
        ];

        return view('livewire.buyer.discovered-leads', [
            'leads' => $leads,
            'stats' => $stats
        ]);
    }

    public function updateStatus($leadId, $status)
    {
        DB::table('buyer_leads')
            ->where('id', $leadId)
            ->update([
                'status' => $status,
                'updated_at' => now()
            ]);

        session()->flash('message', 'Lead status updated successfully!');
    }

    public function exportLeads()
    {
        $leads = DB::table('buyer_leads')
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->get();

        $csv = "Business Name,Email,Phone,Address,Website,Rating,Status\n";

        foreach ($leads as $lead) {
            $csv .= '"' . $lead->business_name . '",';
            $csv .= '"' . $lead->email . '",';
            $csv .= '"' . ($lead->phone ?? '') . '",';
            $csv .= '"' . $lead->address . '",';
            $csv .= '"' . ($lead->website ?? '') . '",';
            $csv .= '"' . $lead->google_rating . ' (' . $lead->google_reviews_count . ' reviews)",';
            $csv .= '"' . $lead->status . '"' . "\n";
        }

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="discovered_leads_' . date('Y-m-d') . '.csv"');
    }
}