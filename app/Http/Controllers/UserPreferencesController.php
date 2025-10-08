<?php

namespace App\Http\Controllers;

use App\Models\UserPreference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class UserPreferencesController extends Controller
{
    public function index()
    {
        $preferences = UserPreference::firstOrCreate(
            ['user_id' => Auth::id()],
            $this->getDefaultPreferences()
        );

        $languages = [
            'en' => 'English',
            'es' => 'Español',
            'fr' => 'Français',
            'de' => 'Deutsch',
            'it' => 'Italiano',
            'pt' => 'Português',
            'zh' => '中文',
            'ja' => '日本語',
            'ko' => '한국어',
            'ar' => 'العربية'
        ];

        $timezones = timezone_identifiers_list();
        $fontSizes = ['small', 'medium', 'large', 'extra-large'];

        return view('support.preferences.index', compact(
            'preferences',
            'languages',
            'timezones',
            'fontSizes'
        ));
    }

    public function updateLanguage(Request $request)
    {
        $request->validate([
            'language' => 'required|string|in:en,es,fr,de,it,pt,zh,ja,ko,ar'
        ]);

        $preferences = UserPreference::updateOrCreate(
            ['user_id' => Auth::id()],
            ['language' => $request->language]
        );

        // Set application locale
        App::setLocale($request->language);
        Session::put('locale', $request->language);

        return response()->json([
            'success' => true,
            'message' => __('Language updated successfully'),
            'language' => $request->language
        ]);
    }

    public function updateAccessibility(Request $request)
    {
        $request->validate([
            'high_contrast_mode' => 'nullable|boolean',
            'screen_reader_mode' => 'nullable|boolean',
            'font_size' => 'nullable|string|in:small,medium,large,extra-large',
            'keyboard_navigation' => 'nullable|boolean',
            'reduced_motion' => 'nullable|boolean'
        ]);

        $preferences = UserPreference::firstOrCreate(
            ['user_id' => Auth::id()],
            $this->getDefaultPreferences()
        );

        $accessibilitySettings = [
            'high_contrast' => $request->high_contrast_mode ?? false,
            'screen_reader' => $request->screen_reader_mode ?? false,
            'keyboard_nav' => $request->keyboard_navigation ?? false,
            'reduced_motion' => $request->reduced_motion ?? false,
            'focus_indicators' => true,
            'alt_text_display' => $request->screen_reader_mode ?? false,
            'captions_enabled' => true
        ];

        $preferences->update([
            'high_contrast_mode' => $request->high_contrast_mode ?? false,
            'screen_reader_mode' => $request->screen_reader_mode ?? false,
            'font_size' => $request->font_size ?? 'medium',
            'keyboard_navigation' => $request->keyboard_navigation ?? false,
            'reduced_motion' => $request->reduced_motion ?? false,
            'accessibility_settings' => $accessibilitySettings
        ]);

        // Apply accessibility CSS classes to session
        $this->applyAccessibilitySettings($preferences);

        return response()->json([
            'success' => true,
            'message' => 'Accessibility settings updated successfully',
            'settings' => $accessibilitySettings
        ]);
    }

    public function updateNotifications(Request $request)
    {
        $request->validate([
            'email_notifications' => 'nullable|boolean',
            'push_notifications' => 'nullable|boolean',
            'sms_notifications' => 'nullable|boolean',
            'ticket_updates' => 'nullable|boolean',
            'feature_updates' => 'nullable|boolean',
            'forum_replies' => 'nullable|boolean',
            'chat_messages' => 'nullable|boolean',
            'newsletter' => 'nullable|boolean'
        ]);

        $preferences = UserPreference::firstOrCreate(
            ['user_id' => Auth::id()],
            $this->getDefaultPreferences()
        );

        $notificationPreferences = [
            'email' => [
                'enabled' => $request->email_notifications ?? true,
                'ticket_updates' => $request->ticket_updates ?? true,
                'feature_updates' => $request->feature_updates ?? true,
                'forum_replies' => $request->forum_replies ?? true,
                'newsletter' => $request->newsletter ?? false
            ],
            'push' => [
                'enabled' => $request->push_notifications ?? false,
                'chat_messages' => $request->chat_messages ?? true,
                'urgent_updates' => true
            ],
            'sms' => [
                'enabled' => $request->sms_notifications ?? false,
                'critical_alerts' => true
            ]
        ];

        $preferences->update([
            'notification_preferences' => $notificationPreferences
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Notification preferences updated successfully',
            'preferences' => $notificationPreferences
        ]);
    }

    public function updateTimezone(Request $request)
    {
        $request->validate([
            'timezone' => 'required|timezone'
        ]);

        $preferences = UserPreference::updateOrCreate(
            ['user_id' => Auth::id()],
            ['timezone' => $request->timezone]
        );

        // Update user's timezone in session
        Session::put('timezone', $request->timezone);

        return response()->json([
            'success' => true,
            'message' => 'Timezone updated successfully',
            'timezone' => $request->timezone
        ]);
    }

    public function updateSupportPreferences(Request $request)
    {
        $request->validate([
            'preferred_contact_method' => 'nullable|string|in:email,chat,phone,ticket',
            'auto_close_resolved_tickets' => 'nullable|boolean',
            'show_community_solutions' => 'nullable|boolean',
            'enable_auto_suggestions' => 'nullable|boolean'
        ]);

        $preferences = UserPreference::firstOrCreate(
            ['user_id' => Auth::id()],
            $this->getDefaultPreferences()
        );

        $supportPreferences = [
            'preferred_contact_method' => $request->preferred_contact_method ?? 'email',
            'auto_close_resolved_tickets' => $request->auto_close_resolved_tickets ?? true,
            'show_community_solutions' => $request->show_community_solutions ?? true,
            'enable_auto_suggestions' => $request->enable_auto_suggestions ?? true,
            'priority_support' => Auth::user()->isPremium() ?? false
        ];

        $preferences->update([
            'support_preferences' => $supportPreferences
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Support preferences updated successfully',
            'preferences' => $supportPreferences
        ]);
    }

    protected function getDefaultPreferences()
    {
        return [
            'language' => 'en',
            'timezone' => 'UTC',
            'high_contrast_mode' => false,
            'screen_reader_mode' => false,
            'font_size' => 'medium',
            'keyboard_navigation' => false,
            'reduced_motion' => false,
            'accessibility_settings' => [
                'high_contrast' => false,
                'screen_reader' => false,
                'keyboard_nav' => false,
                'reduced_motion' => false,
                'focus_indicators' => true,
                'alt_text_display' => false,
                'captions_enabled' => true
            ],
            'notification_preferences' => [
                'email' => [
                    'enabled' => true,
                    'ticket_updates' => true,
                    'feature_updates' => true,
                    'forum_replies' => true,
                    'newsletter' => false
                ],
                'push' => [
                    'enabled' => false,
                    'chat_messages' => true,
                    'urgent_updates' => true
                ],
                'sms' => [
                    'enabled' => false,
                    'critical_alerts' => true
                ]
            ],
            'support_preferences' => [
                'preferred_contact_method' => 'email',
                'auto_close_resolved_tickets' => true,
                'show_community_solutions' => true,
                'enable_auto_suggestions' => true,
                'priority_support' => false
            ]
        ];
    }

    protected function applyAccessibilitySettings($preferences)
    {
        $classes = [];
        
        if ($preferences->high_contrast_mode) {
            $classes[] = 'high-contrast';
        }
        
        if ($preferences->screen_reader_mode) {
            $classes[] = 'screen-reader';
        }
        
        if ($preferences->reduced_motion) {
            $classes[] = 'reduced-motion';
        }
        
        $classes[] = 'font-' . $preferences->font_size;
        
        Session::put('accessibility_classes', implode(' ', $classes));
    }
}