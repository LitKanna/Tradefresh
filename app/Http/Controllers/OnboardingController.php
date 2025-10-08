<?php

namespace App\Http\Controllers;

use App\Models\OnboardingStep;
use App\Models\UserOnboardingProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class OnboardingController extends Controller
{
    public function index()
    {
        $steps = OnboardingStep::active()
            ->ordered()
            ->get();

        $userProgress = UserOnboardingProgress::where('user_id', Auth::id())
            ->with('step')
            ->get()
            ->keyBy('step_id');

        $completedSteps = $userProgress->filter(function ($progress) {
            return $progress->status === 'completed';
        })->count();

        $totalSteps = $steps->count();
        $progressPercentage = $totalSteps > 0 ? ($completedSteps / $totalSteps) * 100 : 0;

        $currentStep = $this->getCurrentStep($steps, $userProgress);

        return view('support.onboarding.index', compact(
            'steps',
            'userProgress',
            'completedSteps',
            'totalSteps',
            'progressPercentage',
            'currentStep'
        ));
    }

    public function start()
    {
        $firstStep = OnboardingStep::active()
            ->ordered()
            ->first();

        if (!$firstStep) {
            return redirect()->route('support.dashboard')
                ->with('info', 'No onboarding steps available.');
        }

        // Create or update progress
        UserOnboardingProgress::updateOrCreate(
            [
                'user_id' => Auth::id(),
                'step_id' => $firstStep->id
            ],
            [
                'status' => 'in_progress',
                'started_at' => now()
            ]
        );

        return $this->showStep($firstStep);
    }

    public function completeStep(Request $request, OnboardingStep $step)
    {
        $progress = UserOnboardingProgress::updateOrCreate(
            [
                'user_id' => Auth::id(),
                'step_id' => $step->id
            ],
            [
                'status' => 'completed',
                'completed_at' => now(),
                'time_spent' => $request->time_spent ?? null,
                'interaction_data' => $request->interaction_data ?? null
            ]
        );

        // Get next step
        $nextStep = $this->getNextStep($step);

        if ($nextStep) {
            // Start next step
            UserOnboardingProgress::updateOrCreate(
                [
                    'user_id' => Auth::id(),
                    'step_id' => $nextStep->id
                ],
                [
                    'status' => 'in_progress',
                    'started_at' => now()
                ]
            );

            return response()->json([
                'success' => true,
                'next_step' => $nextStep,
                'redirect' => route('support.onboarding.index')
            ]);
        }

        // All steps completed
        $this->completeOnboarding();

        return response()->json([
            'success' => true,
            'completed' => true,
            'redirect' => route('support.dashboard')
        ]);
    }

    public function skipStep(OnboardingStep $step)
    {
        UserOnboardingProgress::updateOrCreate(
            [
                'user_id' => Auth::id(),
                'step_id' => $step->id
            ],
            [
                'status' => 'skipped',
                'completed_at' => now()
            ]
        );

        $nextStep = $this->getNextStep($step);

        if ($nextStep) {
            return redirect()->route('support.onboarding.index')
                ->with('info', 'Step skipped. You can return to it later.');
        }

        return redirect()->route('support.dashboard')
            ->with('success', 'Onboarding completed!');
    }

    public function progress()
    {
        $steps = OnboardingStep::active()->ordered()->get();
        
        $progress = UserOnboardingProgress::where('user_id', Auth::id())
            ->with('step')
            ->get()
            ->keyBy('step_id');

        $data = $steps->map(function ($step) use ($progress) {
            $userProgress = $progress->get($step->id);
            
            return [
                'id' => $step->id,
                'name' => $step->name,
                'description' => $step->description,
                'type' => $step->type,
                'status' => $userProgress ? $userProgress->status : 'pending',
                'completed_at' => $userProgress ? $userProgress->completed_at : null,
                'time_spent' => $userProgress ? $userProgress->time_spent : null
            ];
        });

        return response()->json([
            'steps' => $data,
            'overall_progress' => $this->calculateOverallProgress($progress, $steps->count())
        ]);
    }

    public function reset()
    {
        UserOnboardingProgress::where('user_id', Auth::id())->delete();

        return redirect()->route('support.onboarding.start')
            ->with('success', 'Onboarding progress reset. Starting from the beginning.');
    }

    public function tour($tourId)
    {
        $tour = Cache::remember("onboarding_tour_{$tourId}", 3600, function () use ($tourId) {
            return OnboardingStep::where('type', 'tour')
                ->where('slug', $tourId)
                ->active()
                ->first();
        });

        if (!$tour) {
            abort(404);
        }

        return response()->json([
            'tour' => $tour,
            'steps' => json_decode($tour->content)
        ]);
    }

    protected function getCurrentStep($steps, $userProgress)
    {
        foreach ($steps as $step) {
            $progress = $userProgress->get($step->id);
            
            if (!$progress || $progress->status === 'pending' || $progress->status === 'in_progress') {
                return $step;
            }
        }

        return null;
    }

    protected function getNextStep(OnboardingStep $currentStep)
    {
        return OnboardingStep::active()
            ->where('order', '>', $currentStep->order)
            ->ordered()
            ->first();
    }

    protected function showStep(OnboardingStep $step)
    {
        $viewName = 'support.onboarding.steps.' . $step->type;
        
        if (!view()->exists($viewName)) {
            $viewName = 'support.onboarding.steps.default';
        }

        return view($viewName, compact('step'));
    }

    protected function completeOnboarding()
    {
        // Mark user as onboarded
        Auth::user()->update(['onboarding_completed_at' => now()]);

        // Send completion notification
        Auth::user()->notify(new \App\Notifications\OnboardingCompletedNotification());

        // Log completion
        activity()
            ->causedBy(Auth::user())
            ->log('User completed onboarding');
    }

    protected function calculateOverallProgress($progress, $totalSteps)
    {
        if ($totalSteps === 0) {
            return 0;
        }

        $completedSteps = $progress->filter(function ($p) {
            return $p->status === 'completed';
        })->count();

        return round(($completedSteps / $totalSteps) * 100, 2);
    }
}