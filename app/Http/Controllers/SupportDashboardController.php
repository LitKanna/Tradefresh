<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use App\Models\KnowledgeBaseArticle;
use App\Models\ChatSession;
use App\Models\Faq;
use App\Models\VideoTutorial;
use App\Models\ForumTopic;
use App\Models\FeatureRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SupportDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // Get recent tickets for the user
        $recentTickets = SupportTicket::where('user_id', $user->id)
            ->latest()
            ->limit(5)
            ->get();
        
        // Get popular articles
        $popularArticles = Cache::remember('popular_articles', 3600, function () {
            return KnowledgeBaseArticle::published()
                ->popular()
                ->limit(5)
                ->get();
        });
        
        // Check if any agents are available for chat
        $availableAgents = DB::table('users')
            ->where('role', 'support')
            ->where('is_available', true)
            ->count();
        
        // Get system status
        $systemStatus = Cache::remember('system_status', 300, function () {
            return (object)[
                'all_operational' => true,
                'services' => [
                    'website' => 'operational',
                    'api' => 'operational',
                    'database' => 'operational',
                    'payment' => 'operational'
                ]
            ];
        });
        
        // Get user stats
        $stats = [
            'open_tickets' => SupportTicket::where('user_id', $user->id)
                ->whereIn('status', ['open', 'in_progress'])
                ->count(),
            'resolved_tickets' => SupportTicket::where('user_id', $user->id)
                ->where('status', 'resolved')
                ->count(),
            'feature_requests' => FeatureRequest::where('user_id', $user->id)->count(),
            'forum_posts' => DB::table('forum_posts')->where('user_id', $user->id)->count()
        ];
        
        // Get trending topics
        $trendingTopics = Cache::remember('trending_topics', 3600, function () {
            return ForumTopic::withCount('posts')
                ->where('created_at', '>', now()->subDays(7))
                ->orderBy('posts_count', 'desc')
                ->limit(5)
                ->get();
        });
        
        // Get recent FAQs
        $recentFaqs = Cache::remember('recent_faqs', 3600, function () {
            return Faq::active()
                ->featured()
                ->limit(5)
                ->get();
        });
        
        return view('support.dashboard', compact(
            'recentTickets',
            'popularArticles',
            'availableAgents',
            'systemStatus',
            'stats',
            'trendingTopics',
            'recentFaqs'
        ));
    }
    
    public function stats()
    {
        $user = Auth::user();
        
        $stats = [
            'tickets' => [
                'open' => SupportTicket::where('user_id', $user->id)->open()->count(),
                'in_progress' => SupportTicket::where('user_id', $user->id)->inProgress()->count(),
                'resolved' => SupportTicket::where('user_id', $user->id)->resolved()->count(),
                'total' => SupportTicket::where('user_id', $user->id)->count(),
                'avg_response_time' => SupportTicket::where('user_id', $user->id)
                    ->whereNotNull('first_response_at')
                    ->avg(DB::raw('TIMESTAMPDIFF(MINUTE, created_at, first_response_at)')),
                'avg_resolution_time' => SupportTicket::where('user_id', $user->id)
                    ->whereNotNull('resolved_at')
                    ->avg(DB::raw('TIMESTAMPDIFF(HOUR, created_at, resolved_at)'))
            ],
            'chat' => [
                'sessions_today' => ChatSession::where('user_id', $user->id)
                    ->whereDate('created_at', today())
                    ->count(),
                'avg_wait_time' => ChatSession::where('user_id', $user->id)
                    ->whereNotNull('wait_time')
                    ->avg('wait_time'),
                'satisfaction_rating' => ChatSession::where('user_id', $user->id)
                    ->whereNotNull('rating')
                    ->avg('rating')
            ],
            'knowledge_base' => [
                'articles_viewed' => DB::table('user_article_views')
                    ->where('user_id', $user->id)
                    ->count(),
                'helpful_votes' => DB::table('article_feedback')
                    ->where('user_id', $user->id)
                    ->where('is_helpful', true)
                    ->count()
            ],
            'community' => [
                'topics_created' => ForumTopic::where('user_id', $user->id)->count(),
                'posts_created' => DB::table('forum_posts')->where('user_id', $user->id)->count(),
                'solutions_provided' => ForumTopic::where('best_answer_id', function ($query) use ($user) {
                    $query->select('id')
                        ->from('forum_posts')
                        ->where('user_id', $user->id);
                })->count()
            ],
            'feature_requests' => [
                'submitted' => FeatureRequest::where('user_id', $user->id)->count(),
                'implemented' => FeatureRequest::where('user_id', $user->id)
                    ->where('status', 'completed')
                    ->count(),
                'votes_cast' => DB::table('feature_votes')->where('user_id', $user->id)->count()
            ]
        ];
        
        return response()->json($stats);
    }
    
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:3'
        ]);
        
        $query = $request->get('q');
        $results = [];
        
        // Search knowledge base articles
        $articles = KnowledgeBaseArticle::published()
            ->search($query)
            ->limit(5)
            ->get()
            ->map(function ($article) {
                return [
                    'type' => 'article',
                    'title' => $article->title,
                    'excerpt' => $article->excerpt,
                    'url' => route('support.kb.show', $article->slug),
                    'category' => $article->category
                ];
            });
        $results = array_merge($results, $articles->toArray());
        
        // Search FAQs
        $faqs = Faq::active()
            ->search($query)
            ->limit(5)
            ->get()
            ->map(function ($faq) {
                return [
                    'type' => 'faq',
                    'title' => $faq->question,
                    'excerpt' => Str::limit($faq->answer, 150),
                    'url' => route('support.faq.show', $faq->id),
                    'category' => $faq->category->name ?? null
                ];
            });
        $results = array_merge($results, $faqs->toArray());
        
        // Search video tutorials
        $videos = VideoTutorial::published()
            ->search($query)
            ->limit(3)
            ->get()
            ->map(function ($video) {
                return [
                    'type' => 'video',
                    'title' => $video->title,
                    'excerpt' => $video->description,
                    'url' => route('support.videos.show', $video->slug),
                    'category' => $video->category,
                    'duration' => $video->duration
                ];
            });
        $results = array_merge($results, $videos->toArray());
        
        // Search troubleshooting guides
        $guides = DB::table('troubleshooting_guides')
            ->where('status', 'published')
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('problem_description', 'like', "%{$query}%");
            })
            ->limit(3)
            ->get()
            ->map(function ($guide) {
                return [
                    'type' => 'troubleshoot',
                    'title' => $guide->title,
                    'excerpt' => $guide->problem_description,
                    'url' => route('support.troubleshoot.show', $guide->slug),
                    'category' => $guide->category
                ];
            });
        $results = array_merge($results, $guides->toArray());
        
        return response()->json([
            'results' => $results,
            'total' => count($results)
        ]);
    }
}