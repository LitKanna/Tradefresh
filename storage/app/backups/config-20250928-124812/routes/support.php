<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\KnowledgeBaseController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\SupportTicketController;
use App\Http\Controllers\LiveChatController;
use App\Http\Controllers\VideoTutorialController;
use App\Http\Controllers\ForumController;
use App\Http\Controllers\TroubleshootingController;
use App\Http\Controllers\FeatureRequestController;
use App\Http\Controllers\OnboardingController;

Route::prefix('support')->middleware(['auth'])->group(function () {
    
    // Support Dashboard
    Route::get('/', [App\Http\Controllers\SupportDashboardController::class, 'index'])->name('support.dashboard');
    
    // Knowledge Base
    Route::prefix('knowledge-base')->group(function () {
        Route::get('/', [KnowledgeBaseController::class, 'index'])->name('support.kb.index');
        Route::get('/search', [KnowledgeBaseController::class, 'search'])->name('support.kb.search');
        Route::get('/category/{category}', [KnowledgeBaseController::class, 'category'])->name('support.kb.category');
        Route::get('/{slug}', [KnowledgeBaseController::class, 'show'])->name('support.kb.show');
        Route::post('/{article}/helpful', [KnowledgeBaseController::class, 'markHelpful'])->name('support.kb.helpful');
        Route::get('/{article}/download/{index}', [KnowledgeBaseController::class, 'downloadAttachment'])->name('support.kb.download');
    });
    
    // FAQ
    Route::prefix('faq')->group(function () {
        Route::get('/', [FaqController::class, 'index'])->name('support.faq.index');
        Route::get('/search', [FaqController::class, 'search'])->name('support.faq.search');
        Route::get('/popular', [FaqController::class, 'popular'])->name('support.faq.popular');
        Route::get('/category/{slug}', [FaqController::class, 'category'])->name('support.faq.category');
        Route::get('/{id}', [FaqController::class, 'show'])->name('support.faq.show');
        Route::post('/{faq}/helpful', [FaqController::class, 'markHelpful'])->name('support.faq.helpful');
    });
    
    // Support Tickets
    Route::prefix('tickets')->group(function () {
        Route::get('/', [SupportTicketController::class, 'index'])->name('support.tickets.index');
        Route::get('/create', [SupportTicketController::class, 'create'])->name('support.tickets.create');
        Route::post('/', [SupportTicketController::class, 'store'])->name('support.tickets.store');
        Route::get('/{ticket}', [SupportTicketController::class, 'show'])->name('support.tickets.show');
        Route::post('/{ticket}/reply', [SupportTicketController::class, 'reply'])->name('support.tickets.reply');
        Route::post('/{ticket}/close', [SupportTicketController::class, 'close'])->name('support.tickets.close');
        Route::post('/{ticket}/reopen', [SupportTicketController::class, 'reopen'])->name('support.tickets.reopen');
        Route::post('/{ticket}/rate', [SupportTicketController::class, 'rate'])->name('support.tickets.rate');
        Route::get('/{ticket}/download/{message}/{index}', [SupportTicketController::class, 'downloadAttachment'])->name('support.tickets.download');
    });
    
    // Live Chat
    Route::prefix('chat')->group(function () {
        Route::get('/', [LiveChatController::class, 'index'])->name('support.chat.index');
        Route::post('/start', [LiveChatController::class, 'startSession'])->name('support.chat.start');
        Route::get('/session/{session}/messages', [LiveChatController::class, 'getMessages'])->name('support.chat.messages');
        Route::post('/session/{session}/send', [LiveChatController::class, 'sendMessage'])->name('support.chat.send');
        Route::post('/session/{session}/end', [LiveChatController::class, 'endSession'])->name('support.chat.end');
        Route::post('/session/{session}/rate', [LiveChatController::class, 'rateSession'])->name('support.chat.rate');
        Route::post('/session/{session}/typing', [LiveChatController::class, 'typing'])->name('support.chat.typing');
        Route::post('/session/{session}/transfer', [LiveChatController::class, 'transferSession'])->name('support.chat.transfer');
        Route::get('/active', [LiveChatController::class, 'getActiveChats'])->name('support.chat.active');
    });
    
    // Video Tutorials
    Route::prefix('videos')->group(function () {
        Route::get('/', [VideoTutorialController::class, 'index'])->name('support.videos.index');
        Route::get('/search', [VideoTutorialController::class, 'search'])->name('support.videos.search');
        Route::get('/category/{category}', [VideoTutorialController::class, 'category'])->name('support.videos.category');
        Route::get('/playlist/{id}', [VideoTutorialController::class, 'playlist'])->name('support.videos.playlist');
        Route::get('/{slug}', [VideoTutorialController::class, 'show'])->name('support.videos.show');
        Route::post('/{video}/rate', [VideoTutorialController::class, 'rate'])->name('support.videos.rate');
        Route::post('/{video}/progress', [VideoTutorialController::class, 'updateProgress'])->name('support.videos.progress');
        Route::get('/{video}/download/{index}', [VideoTutorialController::class, 'downloadResource'])->name('support.videos.download');
    });
    
    // Community Forums
    Route::prefix('forums')->group(function () {
        Route::get('/', [ForumController::class, 'index'])->name('support.forums.index');
        Route::get('/category/{slug}', [ForumController::class, 'category'])->name('support.forums.category');
        Route::get('/topic/create', [ForumController::class, 'createTopic'])->name('support.forums.topic.create');
        Route::post('/topic', [ForumController::class, 'storeTopic'])->name('support.forums.topic.store');
        Route::get('/topic/{slug}', [ForumController::class, 'showTopic'])->name('support.forums.topic.show');
        Route::post('/topic/{topic}/reply', [ForumController::class, 'reply'])->name('support.forums.reply');
        Route::post('/post/{post}/edit', [ForumController::class, 'editPost'])->name('support.forums.post.edit');
        Route::delete('/post/{post}', [ForumController::class, 'deletePost'])->name('support.forums.post.delete');
        Route::post('/post/{post}/like', [ForumController::class, 'likePost'])->name('support.forums.post.like');
        Route::post('/topic/{topic}/solved', [ForumController::class, 'markSolved'])->name('support.forums.topic.solved');
        Route::post('/topic/{topic}/lock', [ForumController::class, 'lockTopic'])->name('support.forums.topic.lock');
        Route::post('/topic/{topic}/pin', [ForumController::class, 'pinTopic'])->name('support.forums.topic.pin');
        Route::get('/search', [ForumController::class, 'search'])->name('support.forums.search');
        Route::get('/my-topics', [ForumController::class, 'myTopics'])->name('support.forums.my-topics');
    });
    
    // Troubleshooting
    Route::prefix('troubleshoot')->group(function () {
        Route::get('/', [TroubleshootingController::class, 'index'])->name('support.troubleshoot.index');
        Route::get('/guide/{slug}', [TroubleshootingController::class, 'show'])->name('support.troubleshoot.show');
        Route::post('/guide/{guide}/start', [TroubleshootingController::class, 'start'])->name('support.troubleshoot.start');
        Route::post('/guide/{guide}/step', [TroubleshootingController::class, 'nextStep'])->name('support.troubleshoot.step');
        Route::post('/guide/{guide}/complete', [TroubleshootingController::class, 'complete'])->name('support.troubleshoot.complete');
        Route::post('/guide/{guide}/helpful', [TroubleshootingController::class, 'markHelpful'])->name('support.troubleshoot.helpful');
        Route::get('/diagnostic/{tool}', [TroubleshootingController::class, 'runDiagnostic'])->name('support.troubleshoot.diagnostic');
        Route::get('/search', [TroubleshootingController::class, 'search'])->name('support.troubleshoot.search');
    });
    
    // Feature Requests
    Route::prefix('feature-requests')->group(function () {
        Route::get('/', [FeatureRequestController::class, 'index'])->name('support.features.index');
        Route::get('/create', [FeatureRequestController::class, 'create'])->name('support.features.create');
        Route::post('/', [FeatureRequestController::class, 'store'])->name('support.features.store');
        Route::get('/{id}', [FeatureRequestController::class, 'show'])->name('support.features.show');
        Route::post('/{feature}/vote', [FeatureRequestController::class, 'vote'])->name('support.features.vote');
        Route::post('/{feature}/comment', [FeatureRequestController::class, 'comment'])->name('support.features.comment');
        Route::get('/status/{status}', [FeatureRequestController::class, 'byStatus'])->name('support.features.status');
        Route::get('/my-requests', [FeatureRequestController::class, 'myRequests'])->name('support.features.mine');
    });
    
    // Onboarding
    Route::prefix('onboarding')->group(function () {
        Route::get('/', [OnboardingController::class, 'index'])->name('support.onboarding.index');
        Route::get('/start', [OnboardingController::class, 'start'])->name('support.onboarding.start');
        Route::post('/step/{step}/complete', [OnboardingController::class, 'completeStep'])->name('support.onboarding.step.complete');
        Route::post('/step/{step}/skip', [OnboardingController::class, 'skipStep'])->name('support.onboarding.step.skip');
        Route::get('/progress', [OnboardingController::class, 'progress'])->name('support.onboarding.progress');
        Route::post('/reset', [OnboardingController::class, 'reset'])->name('support.onboarding.reset');
        Route::get('/tour/{tour}', [OnboardingController::class, 'tour'])->name('support.onboarding.tour');
    });
    
    // User Preferences
    Route::prefix('preferences')->group(function () {
        Route::get('/', [App\Http\Controllers\UserPreferencesController::class, 'index'])->name('support.preferences.index');
        Route::post('/language', [App\Http\Controllers\UserPreferencesController::class, 'updateLanguage'])->name('support.preferences.language');
        Route::post('/accessibility', [App\Http\Controllers\UserPreferencesController::class, 'updateAccessibility'])->name('support.preferences.accessibility');
        Route::post('/notifications', [App\Http\Controllers\UserPreferencesController::class, 'updateNotifications'])->name('support.preferences.notifications');
    });
    
    // API endpoints for AJAX
    Route::prefix('api')->group(function () {
        Route::get('/search', [App\Http\Controllers\SupportSearchController::class, 'search'])->name('support.api.search');
        Route::get('/suggestions', [App\Http\Controllers\SupportSearchController::class, 'suggestions'])->name('support.api.suggestions');
        Route::get('/stats', [App\Http\Controllers\SupportDashboardController::class, 'stats'])->name('support.api.stats');
    });
});

// Publicly accessible routes (no auth required)
Route::prefix('support/public')->group(function () {
    // Route::get('/status', [App\Http\Controllers\SystemStatusController::class, 'index'])->name('support.status');
    Route::get('/kb/{slug}', [KnowledgeBaseController::class, 'publicShow'])->name('support.kb.public');
    Route::get('/faq', [FaqController::class, 'publicIndex'])->name('support.faq.public');
});