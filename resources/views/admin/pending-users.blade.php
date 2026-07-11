<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Pending User Registrations') }}
        </h2>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-4 text-gray-900 sm:p-6">
                    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <h3 class="text-xl font-semibold leading-snug sm:text-lg">Document Verification Queue</h3>
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:space-x-2 sm:gap-0">
                            <span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-sm font-medium">
                                {{ $pendingUsers->count() }} Pending
                            </span>
                            <a href="{{ route('admin.dashboard') }}" 
                               class="inline-flex items-center justify-center rounded-md bg-gray-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-gray-700">
                                ← Back to Dashboard
                            </a>
                        </div>
                    </div>

                    @if ($pendingUsers->count() > 0)
                        <div class="grid grid-cols-1 gap-6">
                            @foreach ($pendingUsers as $user)
                                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 sm:p-6">
                                    <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                                        <div class="min-w-0 flex-1">
                                            <div class="mb-4 flex items-start gap-3 sm:gap-4">
                                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gray-300 sm:h-12 sm:w-12">
                                                    <svg class="w-6 h-6 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                                                    </svg>
                                                </div>
                                                <div class="min-w-0">
                                                    <h4 class="break-words text-lg font-semibold leading-snug text-gray-900">{{ $user->name }}</h4>
                                                    <p class="break-all text-sm text-gray-600">{{ $user->email }}</p>
                                                    <div class="mt-2 flex flex-wrap items-center gap-2">
                                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide
                                                            @if($user->role === 'Leader') bg-indigo-100 text-indigo-800 ring-2 ring-indigo-300
                                                            @elseif($user->role === 'Teacher') bg-purple-100 text-purple-800 ring-2 ring-purple-300
                                                            @else bg-green-100 text-green-800 ring-2 ring-green-300
                                                            @endif">
                                                            Signing up as {{ $user->role_display_name }}
                                                        </span>
                                                        <span class="text-sm text-gray-600">{{ $user->course }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                                                <div>
                                                    <p class="text-sm"><span class="font-medium">Campus:</span> {{ $user->campus }}</p>
                                                    <p class="text-sm"><span class="font-medium">Section:</span> {{ $user->section }}</p>
                                                </div>
                                                <div>
                                                    <p class="text-sm"><span class="font-medium">Requested Role:</span> <span class="font-bold text-gray-900">{{ $user->role_display_name }}</span></p>
                                                    <p class="text-sm"><span class="font-medium">Registered:</span> {{ $user->created_at->format('M j, Y g:i A') }}</p>
                                                    <p class="text-sm"><span class="font-medium">Status:</span> 
                                                        <span class="px-2 py-1 rounded text-xs bg-yellow-100 text-yellow-800">
                                                            {{ $user->status }}
                                                        </span>
                                                    </p>
                                                </div>
                                            </div>

                                            <div class="mb-4">
                                                <p class="text-sm font-medium text-gray-700 mb-2">Uploaded Document:</p>
                                                @if ($user->hasDocument())
                                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-3">
                                                        <div class="flex items-center gap-2">
                                                            @if ($user->isDocumentImage())
                                                                <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                                                    <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"></path>
                                                                </svg>
                                                                <span class="text-sm text-green-700">Image ({{ strtoupper($user->getDocumentExtension()) }})</span>
                                                            @elseif ($user->isDocumentPdf())
                                                                <svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                                                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"></path>
                                                                </svg>
                                                                <span class="text-sm text-red-700">PDF Document</span>
                                                            @else
                                                                <svg class="w-5 h-5 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                                                                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"></path>
                                                                </svg>
                                                                <span class="text-sm text-gray-700">{{ strtoupper($user->getDocumentExtension()) }} File</span>
                                                            @endif
                                                        </div>
                                                        <a href="{{ route('admin.view-document', $user) }}" 
                                                           target="_blank"
                                                           class="text-sm font-medium text-blue-600 hover:text-blue-800">
                                                            View Document →
                                                        </a>
                                                    </div>
                                                @else
                                                    <p class="text-sm text-red-600">No document uploaded</p>
                                                @endif
                                            </div>
                                        </div>
                                        
                                        <div class="grid w-full grid-cols-1 gap-2 sm:grid-cols-3 lg:w-40 lg:grid-cols-1">
                                            <a href="{{ route('admin.view-user', $user) }}" 
                                               class="inline-flex min-h-11 items-center justify-center rounded-md bg-blue-600 px-4 py-2 text-center text-sm font-medium text-white transition-colors hover:bg-blue-700">
                                                Review Details
                                            </a>
                                            
                                            @if ($user->hasDocument())
                                                <button onclick="openVerifyModal({{ $user->id }}, '{{ $user->name }}')" 
                                                        class="min-h-11 rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-green-700">
                                                    Quick Approve
                                                </button>
                                                <button onclick="openRejectModal({{ $user->id }}, '{{ $user->name }}')" 
                                                        class="min-h-11 rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-red-700">
                                                    Reject
                                                </button>
                                            @else
                                                <button disabled 
                                                        class="min-h-11 cursor-not-allowed rounded-md bg-gray-300 px-4 py-2 text-sm font-medium text-gray-500 sm:col-span-2 lg:col-span-1">
                                                    No Document
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-12">
                            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">All Caught Up!</h3>
                            <p class="text-gray-500 mb-6">No pending user registrations to review.</p>
                            <a href="{{ route('admin.dashboard') }}" 
                               class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-md transition-colors">
                                Back to Dashboard
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Verify Modal -->
    <div id="verifyModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto w-[calc(100%-2rem)] max-w-md rounded-md border bg-white p-5 shadow-lg">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    Approve Registration for <span id="verifyUserName"></span>
                </h3>
                
                <form method="POST" id="verifyForm">
                    @csrf
                    
                    <div class="mb-4">
                        <label for="admin_notes" class="block text-sm font-medium text-gray-700 mb-2">
                            Admin Notes (Optional)
                        </label>
                        <textarea id="admin_notes" name="admin_notes" rows="3" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                                  placeholder="Add any notes about the verification..."></textarea>
                    </div>
                    
                    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end sm:space-x-3 sm:gap-0">
                        <button type="button" onclick="closeVerifyModal()" 
                                class="rounded-md bg-gray-300 px-4 py-2 text-gray-700 hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="rounded-md bg-green-600 px-4 py-2 text-white hover:bg-green-700">
                            Approve Registration
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div id="rejectModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto w-[calc(100%-2rem)] max-w-md rounded-md border bg-white p-5 shadow-lg">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    Reject Registration for <span id="rejectUserName"></span>
                </h3>
                
                <form method="POST" id="rejectForm">
                    @csrf
                    
                    <div class="mb-4">
                        <label for="rejection_reason" class="block text-sm font-medium text-gray-700 mb-2">
                            Rejection Reason <span class="text-red-500">*</span>
                        </label>
                        <textarea id="rejection_reason" name="rejection_reason" rows="3" required
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500"
                                  placeholder="Please provide a reason for rejection..."></textarea>
                    </div>
                    
                    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end sm:space-x-3 sm:gap-0">
                        <button type="button" onclick="closeRejectModal()" 
                                class="rounded-md bg-gray-300 px-4 py-2 text-gray-700 hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="rounded-md bg-red-600 px-4 py-2 text-white hover:bg-red-700">
                            Reject Registration
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openVerifyModal(userId, userName) {
            document.getElementById('verifyForm').action = `/admin/users/${userId}/verify`;
            document.getElementById('verifyUserName').textContent = userName;
            document.getElementById('verifyModal').classList.remove('hidden');
        }

        function closeVerifyModal() {
            document.getElementById('verifyModal').classList.add('hidden');
            document.getElementById('admin_notes').value = '';
        }

        function openRejectModal(userId, userName) {
            document.getElementById('rejectForm').action = `/admin/users/${userId}/reject`;
            document.getElementById('rejectUserName').textContent = userName;
            document.getElementById('rejectModal').classList.remove('hidden');
        }

        function closeRejectModal() {
            document.getElementById('rejectModal').classList.add('hidden');
            document.getElementById('rejection_reason').value = '';
        }

        // Close modals when clicking outside
        document.getElementById('verifyModal').addEventListener('click', function(e) {
            if (e.target === this) closeVerifyModal();
        });

        document.getElementById('rejectModal').addEventListener('click', function(e) {
            if (e.target === this) closeRejectModal();
        });
    </script>
</x-app-layout>
