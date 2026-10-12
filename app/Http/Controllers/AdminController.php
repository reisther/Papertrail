<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AccountDeletionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    /**
     * Show admin dashboard
     */
    public function dashboard()
    {
        if (!Auth::user() || Auth::user()->role !== 'Admin') {
            abort(403, 'Access denied. Admins only.');
        }

        $pendingCount = User::where('status', 'Pending')->count();
        $studentsCount = User::where('role', 'Student')->where('status', 'Verified')->count();
        $leadersCount = User::where('role', 'Leader')->where('status', 'Verified')->count();
        $teachersCount = User::where('role', 'Teacher')->where('status', 'Verified')->count();

        return view('admin.dashboard', compact('pendingCount', 'studentsCount', 'leadersCount', 'teachersCount'));
    }

    /**
     * Show pending user registrations for verification
     */
    public function pendingUsers()
    {
        if (!Auth::user() || Auth::user()->role !== 'Admin') {
            abort(403, 'Access denied. Admins only.');
        }

        $pendingUsers = User::where('status', 'Pending')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.pending-users', compact('pendingUsers'));
    }

    /**
     * Show detailed view of a pending user for document verification
     */
    public function viewUser(User $user)
    {
        if (!Auth::user() || Auth::user()->role !== 'Admin') {
            abort(403, 'Access denied. Admins only.');
        }

        if ($user->status !== 'Pending') {
            return redirect()->route('admin.pending-users')
                ->with('error', 'This user has already been processed.');
        }

        return view('admin.view-user', compact('user'));
    }

    /**
     * Verify a user after document review
     */
    public function verifyUser(Request $request, User $user)
    {
        if (!Auth::user() || Auth::user()->role !== 'Admin') {
            abort(403, 'Access denied. Admins only.');
        }

        $request->validate([
            'admin_notes' => 'nullable|string|max:500'
        ]);

        $user->update([
            'status' => 'Verified',
            'verified_at' => now(),
            'verified_by' => Auth::id(),
            'admin_notes' => $request->admin_notes
        ]);

        return redirect()->route('admin.pending-users')
            ->with('success', "User {$user->name} has been verified successfully!");
    }

    /**
     * Reject a user registration
     */
    public function rejectUser(Request $request, User $user)
    {
        if (!Auth::user() || Auth::user()->role !== 'Admin') {
            abort(403, 'Access denied. Admins only.');
        }

        $request->validate([
            'rejection_reason' => 'required|string|max:500'
        ]);

        // Store rejection info before deletion
        $userName = $user->name;
        $userEmail = $user->email;

        // Delete the uploaded document if it exists
        if ($user->id_document_path) {
            Storage::disk('public')->delete($user->id_document_path);
        }

        // Update status to rejected (or delete if preferred)
        $user->update([
            'status' => 'Rejected',
            'rejected_at' => now(),
            'rejected_by' => Auth::id(),
            'rejection_reason' => $request->rejection_reason
        ]);

        // Or delete completely:
        // $user->delete();

        return redirect()->route('admin.pending-users')
            ->with('success', "Registration for {$userName} ({$userEmail}) has been rejected.");
    }

    /**
     * Download or view uploaded document
     */
    public function viewDocument(User $user)
    {
        if (!Auth::user() || Auth::user()->role !== 'Admin') {
            abort(403, 'Access denied. Admins only.');
        }

        if (!$user->id_document_path || !Storage::disk('public')->exists($user->id_document_path)) {
            abort(404, 'Document not found.');
        }

        $filePath = storage_path('app/public/' . $user->id_document_path);
        $mimeType = mime_content_type($filePath);

        return response()->file($filePath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . basename($user->id_document_path) . '"'
        ]);
    }

    /**
     * Get all users for management
     */
    public function allUsers(Request $request)
    {
        if (!Auth::user() || Auth::user()->role !== 'Admin') {
            abort(403, 'Access denied. Admins only.');
        }

        $query = User::with(['verifiedBy', 'rejectedBy'])->where('status', '!=', 'Deleted');

        // Apply filters
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('firstname', 'like', "%{$search}%")
                  ->orWhere('lastname', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('course', 'like', "%{$search}%");
            });
        }

        // Sort by
        $sortBy = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        
        $allowedSorts = ['created_at', 'firstname', 'lastname', 'email', 'role', 'status', 'last_login_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        $users = $query->paginate(20)->withQueryString();

        // Get statistics
        $stats = [
            'total' => User::where('status', '!=', 'Deleted')->count(),
            'students' => User::where('role', 'Student')->count(),
            'leaders' => User::where('role', 'Leader')->count(),
            'teachers' => User::where('role', 'Teacher')->count(),
            'admins' => User::where('role', 'Admin')->count(),
            'verified' => User::where('status', 'Verified')->count(),
            'pending' => User::where('status', 'Pending')->count(),
            'rejected' => User::where('status', 'Rejected')->count(),
        ];

        return view('admin.all-users', compact('users', 'stats'));
    }

    /**
     * Update user role
     */
    public function updateUserRole(Request $request, User $user)
    {
        if (!Auth::user() || Auth::user()->role !== 'Admin') {
            abort(403, 'Access denied. Admins only.');
        }

        if ($user->id === Auth::id()) {
            return back()->with('error', 'You cannot change your own role.');
        }

        if ($user->status === 'Deleted') {
            return back()->with('error', 'This account has already been deleted.');
        }

        $request->validate([
            'role' => 'required|in:Student,Leader,Teacher,Admin'
        ]);

        $attributes = ['role' => $request->role];
        if ($request->role === 'Teacher') {
            $attributes['course'] = User::ADVISER_DEPARTMENT;
            $attributes['section'] = null;
        }

        $user->update($attributes);
        $user->syncRoleProfile();

        return back()->with('success', "User role updated to {$request->role} successfully!");
    }

    /**
     * Update user status
     */
    public function updateUserStatus(Request $request, User $user)
    {
        if (!Auth::user() || Auth::user()->role !== 'Admin') {
            abort(403, 'Access denied. Admins only.');
        }

        if ($user->status === 'Deleted') {
            return back()->with('error', 'This account has already been deleted.');
        }

        $request->validate([
            'status' => 'required|in:Pending,Verified,Rejected'
        ]);

        $updateData = ['status' => $request->status];

        if ($request->status === 'Verified') {
            $updateData['verified_at'] = now();
            $updateData['verified_by'] = Auth::id();
        } elseif ($request->status === 'Rejected') {
            $updateData['rejected_at'] = now();
            $updateData['rejected_by'] = Auth::id();
        }

        $user->update($updateData);

        return back()->with('success', "User status updated to {$request->status} successfully!");
    }

    /**
     * Delete user
     */
    public function deleteUser(User $user, AccountDeletionService $deletions)
    {
        if (!Auth::user() || Auth::user()->role !== 'Admin') {
            abort(403, 'Access denied. Admins only.');
        }

        if ($user->id === Auth::id()) {
            return back()->with('error', 'You cannot delete your own account!');
        }

        if ($user->status === 'Deleted') {
            return back()->with('error', 'This account has already been deleted.');
        }

        $userName = $user->name;

        if (! $deletions->deleteNow($user)) {
            return back()->with('error', 'The account could not be deleted. Please try again.');
        }

        return back()->with('success', "User {$userName} deleted. Shared project files and chats remain archived.");
    }
}
