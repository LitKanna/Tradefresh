<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Buyer;
use App\Models\Vendor;
use App\Models\Admin;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserManagementController extends Controller
{
    /**
     * Display a listing of all users
     */
    public function index(Request $request)
    {
        $type = $request->get('type', 'all');
        $search = $request->get('search');
        $status = $request->get('status');

        $buyers = Buyer::query()
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('company_name', 'like', "%{$search}%");
                });
            })
            ->when($status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->latest()
            ->paginate(10, ['*'], 'buyers_page');

        $vendors = Vendor::query()
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('business_name', 'like', "%{$search}%");
                });
            })
            ->when($status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->latest()
            ->paginate(10, ['*'], 'vendors_page');

        $admins = Admin::query()
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10, ['*'], 'admins_page');

        $stats = [
            'total_buyers' => Buyer::count(),
            'total_vendors' => Vendor::count(),
            'total_admins' => Admin::count(),
            'active_users' => Buyer::where('status', 'active')->count() + 
                            Vendor::where('status', 'active')->count(),
            'pending_vendors' => Vendor::where('status', 'pending')->count(),
            'suspended_users' => Buyer::where('status', 'suspended')->count() + 
                               Vendor::where('status', 'suspended')->count(),
        ];

        return view('admin.users.index', compact('buyers', 'vendors', 'admins', 'stats', 'type', 'search', 'status'));
    }

    /**
     * Show the form for creating a new user
     */
    public function create(Request $request)
    {
        $type = $request->get('type', 'buyer');
        $roles = Role::all();
        $permissions = Permission::all();

        return view('admin.users.create', compact('type', 'roles', 'permissions'));
    }

    /**
     * Store a newly created user
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:buyer,vendor,admin',
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:buyers,email|unique:vendors,email|unique:admins,email',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string',
            'role' => 'nullable|exists:roles,name',
        ]);

        DB::beginTransaction();
        try {
            $userData = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'phone' => $validated['phone'] ?? null,
                'status' => 'active',
            ];

            switch ($validated['type']) {
                case 'buyer':
                    $userData['company_name'] = $request->company_name;
                    $userData['abn'] = $request->abn;
                    $userData['address'] = $request->address;
                    $user = Buyer::create($userData);
                    break;

                case 'vendor':
                    $userData['business_name'] = $request->business_name;
                    $userData['abn'] = $request->abn;
                    $userData['business_type'] = $request->business_type;
                    $userData['address'] = $request->address;
                    $userData['status'] = 'pending'; // Vendors need approval
                    $user = Vendor::create($userData);
                    break;

                case 'admin':
                    $user = Admin::create($userData);
                    if ($request->role) {
                        $user->assignRole($request->role);
                    }
                    break;
            }

            ActivityLog::create([
                'user_id' => auth()->id(),
                'user_type' => get_class(auth()->user()),
                'action' => 'created_user',
                'description' => "Created new {$validated['type']}: {$validated['name']}",
                'metadata' => json_encode(['user_id' => $user->id, 'type' => $validated['type']]),
            ]);

            DB::commit();

            return redirect()->route('admin.users.index')
                ->with('success', ucfirst($validated['type']) . ' created successfully.');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Failed to create user: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified user
     */
    public function show($type, $id)
    {
        $user = $this->getUserByType($type, $id);
        
        if (!$user) {
            abort(404, 'User not found');
        }

        $activities = ActivityLog::where('user_id', $id)
            ->where('user_type', $this->getModelClass($type))
            ->latest()
            ->limit(50)
            ->get();

        $stats = [];
        
        if ($type === 'buyer') {
            $stats = [
                'total_orders' => $user->orders()->count(),
                'total_spent' => $user->orders()->sum('total_amount'),
                'active_rfqs' => $user->rfqs()->where('status', 'active')->count(),
                'favorite_products' => $user->favoriteProducts()->count(),
            ];
        } elseif ($type === 'vendor') {
            $stats = [
                'total_products' => $user->products()->count(),
                'total_orders' => $user->orders()->count(),
                'total_revenue' => $user->orders()->where('status', 'completed')->sum('total_amount'),
                'average_rating' => $user->ratings()->avg('rating') ?? 0,
                'total_reviews' => $user->ratings()->count(),
            ];
        }

        return view('admin.users.show', compact('user', 'type', 'activities', 'stats'));
    }

    /**
     * Show the form for editing the specified user
     */
    public function edit($type, $id)
    {
        $user = $this->getUserByType($type, $id);
        
        if (!$user) {
            abort(404, 'User not found');
        }

        $roles = Role::all();
        $permissions = Permission::all();

        return view('admin.users.edit', compact('user', 'type', 'roles', 'permissions'));
    }

    /**
     * Update the specified user
     */
    public function update(Request $request, $type, $id)
    {
        $user = $this->getUserByType($type, $id);
        
        if (!$user) {
            abort(404, 'User not found');
        }

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:' . $this->getTableName($type) . ',email,' . $id,
            'phone' => 'nullable|string',
            'status' => 'required|in:active,inactive,suspended,pending',
        ];

        if ($request->filled('password')) {
            $rules['password'] = 'string|min:8|confirmed';
        }

        $validated = $request->validate($rules);

        DB::beginTransaction();
        try {
            $updateData = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'status' => $validated['status'],
            ];

            if ($request->filled('password')) {
                $updateData['password'] = Hash::make($validated['password']);
            }

            if ($type === 'buyer') {
                $updateData['company_name'] = $request->company_name;
                $updateData['abn'] = $request->abn;
                $updateData['address'] = $request->address;
                $updateData['credit_limit'] = $request->credit_limit ?? 0;
            } elseif ($type === 'vendor') {
                $updateData['business_name'] = $request->business_name;
                $updateData['abn'] = $request->abn;
                $updateData['business_type'] = $request->business_type;
                $updateData['address'] = $request->address;
                $updateData['commission_rate'] = $request->commission_rate ?? 10;
            }

            $user->update($updateData);

            if ($type === 'admin' && $request->role) {
                $user->syncRoles([$request->role]);
            }

            ActivityLog::create([
                'user_id' => auth()->id(),
                'user_type' => get_class(auth()->user()),
                'action' => 'updated_user',
                'description' => "Updated {$type}: {$user->name}",
                'metadata' => json_encode(['user_id' => $user->id, 'type' => $type, 'changes' => $updateData]),
            ]);

            DB::commit();

            return redirect()->route('admin.users.show', [$type, $id])
                ->with('success', 'User updated successfully.');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Failed to update user: ' . $e->getMessage()]);
        }
    }

    /**
     * Suspend a user
     */
    public function suspend($type, $id)
    {
        $user = $this->getUserByType($type, $id);
        
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $user->update(['status' => 'suspended']);

        ActivityLog::create([
            'user_id' => auth()->id(),
            'user_type' => get_class(auth()->user()),
            'action' => 'suspended_user',
            'description' => "Suspended {$type}: {$user->name}",
            'metadata' => json_encode(['user_id' => $user->id, 'type' => $type]),
        ]);

        return response()->json(['success' => true, 'message' => 'User suspended successfully']);
    }

    /**
     * Activate a user
     */
    public function activate($type, $id)
    {
        $user = $this->getUserByType($type, $id);
        
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $user->update(['status' => 'active']);

        ActivityLog::create([
            'user_id' => auth()->id(),
            'user_type' => get_class(auth()->user()),
            'action' => 'activated_user',
            'description' => "Activated {$type}: {$user->name}",
            'metadata' => json_encode(['user_id' => $user->id, 'type' => $type]),
        ]);

        return response()->json(['success' => true, 'message' => 'User activated successfully']);
    }

    /**
     * Reset user password
     */
    public function resetPassword(Request $request, $type, $id)
    {
        $user = $this->getUserByType($type, $id);
        
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $newPassword = str()->random(12);
        $user->update(['password' => Hash::make($newPassword)]);

        // Send password reset email notification here
        // Mail::to($user->email)->send(new PasswordResetMail($newPassword));

        ActivityLog::create([
            'user_id' => auth()->id(),
            'user_type' => get_class(auth()->user()),
            'action' => 'reset_password',
            'description' => "Reset password for {$type}: {$user->name}",
            'metadata' => json_encode(['user_id' => $user->id, 'type' => $type]),
        ]);

        return response()->json([
            'success' => true, 
            'message' => 'Password reset successfully',
            'temporary_password' => $newPassword // Show this only in development
        ]);
    }

    /**
     * Impersonate a user
     */
    public function impersonate($type, $id)
    {
        $user = $this->getUserByType($type, $id);
        
        if (!$user) {
            abort(404, 'User not found');
        }

        session(['impersonating' => [
            'admin_id' => auth()->id(),
            'user_id' => $user->id,
            'user_type' => $type,
        ]]);

        $guard = $type === 'buyer' ? 'buyer' : ($type === 'vendor' ? 'vendor' : 'admin');
        auth()->guard($guard)->login($user);

        ActivityLog::create([
            'user_id' => session('impersonating.admin_id'),
            'user_type' => Admin::class,
            'action' => 'impersonate_user',
            'description' => "Started impersonating {$type}: {$user->name}",
            'metadata' => json_encode(['user_id' => $user->id, 'type' => $type]),
        ]);

        return redirect()->route($guard . '.dashboard')
            ->with('info', 'You are now impersonating ' . $user->name);
    }

    /**
     * Export users to CSV
     */
    public function export(Request $request)
    {
        $type = $request->get('type', 'all');
        $filename = 'users_' . $type . '_' . now()->format('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($type) {
            $file = fopen('php://output', 'w');
            
            // Add headers
            fputcsv($file, ['ID', 'Name', 'Email', 'Phone', 'Type', 'Status', 'Created At']);
            
            // Add data based on type
            if ($type === 'all' || $type === 'buyers') {
                foreach (Buyer::all() as $buyer) {
                    fputcsv($file, [
                        $buyer->id,
                        $buyer->name,
                        $buyer->email,
                        $buyer->phone,
                        'Buyer',
                        $buyer->status,
                        $buyer->created_at->format('Y-m-d H:i:s'),
                    ]);
                }
            }
            
            if ($type === 'all' || $type === 'vendors') {
                foreach (Vendor::all() as $vendor) {
                    fputcsv($file, [
                        $vendor->id,
                        $vendor->name,
                        $vendor->email,
                        $vendor->phone,
                        'Vendor',
                        $vendor->status,
                        $vendor->created_at->format('Y-m-d H:i:s'),
                    ]);
                }
            }
            
            if ($type === 'all' || $type === 'admins') {
                foreach (Admin::all() as $admin) {
                    fputcsv($file, [
                        $admin->id,
                        $admin->name,
                        $admin->email,
                        $admin->phone,
                        'Admin',
                        'active',
                        $admin->created_at->format('Y-m-d H:i:s'),
                    ]);
                }
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Helper function to get user by type
     */
    private function getUserByType($type, $id)
    {
        switch ($type) {
            case 'buyer':
                return Buyer::find($id);
            case 'vendor':
                return Vendor::find($id);
            case 'admin':
                return Admin::find($id);
            default:
                return null;
        }
    }

    /**
     * Helper function to get model class by type
     */
    private function getModelClass($type)
    {
        switch ($type) {
            case 'buyer':
                return Buyer::class;
            case 'vendor':
                return Vendor::class;
            case 'admin':
                return Admin::class;
            default:
                return null;
        }
    }

    /**
     * Helper function to get table name by type
     */
    private function getTableName($type)
    {
        switch ($type) {
            case 'buyer':
                return 'buyers';
            case 'vendor':
                return 'vendors';
            case 'admin':
                return 'admins';
            default:
                return null;
        }
    }
}