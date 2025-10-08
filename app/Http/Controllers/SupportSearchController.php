<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SupportSearchController extends Controller
{
    /**
     * Display support search page
     */
    public function index(Request $request)
    {
        return view('support.search', [
            'query' => $request->get('q')
        ]);
    }

    /**
     * Search support articles
     */
    public function search(Request $request)
    {
        $query = $request->get('q');
        
        // TODO: Implement actual search logic
        $results = [];
        
        return view('support.results', [
            'query' => $query,
            'results' => $results
        ]);
    }
}