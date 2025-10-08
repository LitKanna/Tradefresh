<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Buyer;
use App\Models\Vendor;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    /**
     * Display the user's profile
     */
    public function show()
    {
        $user = $this->getAuthenticatedUser();
        
        if (!$user) {
            abort(401, 'Unauthorized');
        }
        
        return view('profile.show', compact('user'));
    }
    
    /**
     * Show the form for editing the user's profile
     */
    public function edit()
    {
        $user = $this->getAuthenticatedUser();
        
        if (!$user) {
            abort(401, 'Unauthorized');
        }
        
        return view('profile.edit', compact('user'));
    }
    
    /**
     * Update the user's profile
     */
    public function update(Request $request)
    {
        $user = $this->getAuthenticatedUser();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        $rules = $this->getValidationRules($user, $request);
        $request->validate($rules);
        
        try {
            $this->updateUserProfile($user, $request);
            
            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'user' => $user->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update profile',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Update the user's password
     */
    public function updatePassword(Request $request)
    {
        $user = $this->getAuthenticatedUser();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ]);
        
        // Verify current password
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'error' => 'Current password is incorrect'
            ], 400);
        }
        
        // Update password
        $user->password = Hash::make($request->password);
        $user->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Password updated successfully'
        ]);
    }
    
    /**
     * Upload profile photo
     */
    public function uploadPhoto(Request $request)
    {
        $user = $this->getAuthenticatedUser();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);
        
        try {
            // Delete old photo if exists
            if ($user->profile_photo && Storage::exists('public/' . $user->profile_photo)) {
                Storage::delete('public/' . $user->profile_photo);
            }
            
            // Store new photo
            $path = $request->file('photo')->store('profile-photos', 'public');
            
            // Update user
            $user->profile_photo = $path;
            $user->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Profile photo updated successfully',
                'photo_url' => Storage::url($path)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to upload photo',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Remove profile photo
     */
    public function removePhoto()
    {
        $user = $this->getAuthenticatedUser();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        try {
            // Delete photo file
            if ($user->profile_photo && Storage::exists('public/' . $user->profile_photo)) {
                Storage::delete('public/' . $user->profile_photo);
            }
            
            // Update user
            $user->profile_photo = null;
            $user->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Profile photo removed successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to remove photo',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Update notification preferences
     */
    public function updateNotifications(Request $request)
    {
        $user = $this->getAuthenticatedUser();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        $request->validate([
            'email_notifications' => 'boolean',
            'sms_notifications' => 'boolean',
            'order_updates' => 'boolean',
            'rfq_updates' => 'boolean',
            'quote_updates' => 'boolean',
            'payment_updates' => 'boolean',
            'delivery_updates' => 'boolean',
            'promotional' => 'boolean',
        ]);
        
        $preferences = $request->only([
            'email_notifications',
            'sms_notifications',
            'order_updates',
            'rfq_updates',
            'quote_updates',
            'payment_updates',
            'delivery_updates',
            'promotional',
        ]);
        
        $user->notification_preferences = json_encode($preferences);
        $user->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Notification preferences updated',
            'preferences' => $preferences
        ]);
    }
    
    /**
     * Get user activity log
     */
    public function activityLog(Request $request)
    {
        $user = $this->getAuthenticatedUser();
        
        if (!$user) {
            abort(401, 'Unauthorized');
        }
        
        // This would typically use a dedicated activity log table
        // For now, return empty results
        $activities = collect();
        
        return view('profile.activity', compact('activities'));
    }
    
    /**
     * Delete user account
     */
    public function deleteAccount(Request $request)
    {
        $user = $this->getAuthenticatedUser();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        $request->validate([
            'password' => 'required',
            'confirmation' => 'required|in:DELETE',
        ]);
        
        // Verify password
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'error' => 'Password is incorrect'
            ], 400);
        }
        
        try {
            // Soft delete user
            $user->deleted_at = now();
            $user->save();
            
            // Logout user
            Auth::logout();
            
            return response()->json([
                'success' => true,
                'message' => 'Account deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to delete account',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get validation rules based on user type
     */
    protected function getValidationRules($user, $request)
    {
        $baseRules = [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique($user->getTable())->ignore($user->id)
            ],
            'phone' => 'nullable|string|max:20',
            'mobile' => 'nullable|string|max:20',
        ];
        
        if ($user instanceof Buyer) {
            return array_merge($baseRules, [
                'company_name' => 'required|string|max:255',
                'abn' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:255',
                'suburb' => 'nullable|string|max:100',
                'state' => 'nullable|string|max:100',
                'postcode' => 'nullable|string|max:10',
                'contact_person' => 'nullable|string|max:255',
                'trading_hours' => 'nullable|string',
                'business_type' => 'nullable|string|max:100',
            ]);
        } elseif ($user instanceof Vendor) {
            return array_merge($baseRules, [
                'business_name' => 'required|string|max:255',
                'abn' => 'required|string|max:20',
                'address' => 'required|string|max:255',
                'suburb' => 'required|string|max:100',
                'state' => 'required|string|max:100',
                'postcode' => 'required|string|max:10',
                'business_type' => 'required|string|max:100',
                'description' => 'nullable|string|max:1000',
                'website' => 'nullable|url|max:255',
            ]);
        }
        
        return $baseRules;
    }
    
    /**
     * Update user profile based on user type
     */
    protected function updateUserProfile($user, $request)
    {
        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'mobile' => $request->mobile,
        ];
        
        if ($user instanceof Buyer) {
            $data = array_merge($data, [
                'company_name' => $request->company_name,
                'abn' => $request->abn,
                'address' => $request->address,
                'suburb' => $request->suburb,
                'state' => $request->state,
                'postcode' => $request->postcode,
                'contact_person' => $request->contact_person,
                'trading_hours' => $request->trading_hours,
                'business_type' => $request->business_type,
            ]);
        } elseif ($user instanceof Vendor) {
            $data = array_merge($data, [
                'business_name' => $request->business_name,
                'abn' => $request->abn,
                'address' => $request->address,
                'suburb' => $request->suburb,
                'state' => $request->state,
                'postcode' => $request->postcode,
                'business_type' => $request->business_type,
                'description' => $request->description,
                'website' => $request->website,
            ]);
        }
        
        $user->update($data);
    }
    
    /**
     * Get authenticated user from any guard
     */
    protected function getAuthenticatedUser()
    {
        if (Auth::guard('buyer')->check()) {
            return Auth::guard('buyer')->user();
        } elseif (Auth::guard('vendor')->check()) {
            return Auth::guard('vendor')->user();
        } elseif (Auth::guard('admin')->check()) {
            return Auth::guard('admin')->user();
        } elseif (Auth::guard('web')->check()) {
            return Auth::guard('web')->user();
        }
        
        return null;
    }
}