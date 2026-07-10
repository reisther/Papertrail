<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Document Verification') }} - {{ $user->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                
                <!-- User Information -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div class="flex justify-between items-start mb-6">
                            <h3 class="text-lg font-semibold">User Information</h3>
                            <a href="{{ route('admin.pending-users') }}" 
                               class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm transition-colors">
                                ← Back to Queue
                            </a>
                        </div>

                        <div class="space-y-4">
                            <div class="flex items-center space-x-4">
                                <div class="w-16 h-16 bg-gray-300 rounded-full flex items-center justify-center">
                                    <svg class="w-8 h-8 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-xl font-semibold text-gray-900">{{ $user->name }}</h4>
                                    <p class="text-gray-600">{{ $user->email }}</p>
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                        {{ $user->status === 'Pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800' }}">
                                        {{ $user->status }}
                                    </span>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4 border-t">
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Role</p>
                                    <p class="mt-1">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold uppercase tracking-wide
                                            @if($user->role === 'Leader') bg-indigo-100 text-indigo-800 ring-2 ring-indigo-300
                                            @elseif($user->role === 'Teacher') bg-purple-100 text-purple-800 ring-2 ring-purple-300
                                            @else bg-green-100 text-green-800 ring-2 ring-green-300
                                            @endif">
                                            Signing up as {{ $user->role_display_name }}
                                        </span>
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Campus</p>
                                    <p class="text-gray-900">{{ $user->campus }}</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Course/Department</p>
                                    <p class="text-gray-900">{{ $user->course }}</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Section</p>
                                    <p class="text-gray-900">{{ $user->section }}</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Registration Date</p>
                                    <p class="text-gray-900">{{ $user->created_at->format('M j, Y g:i A') }}</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Time Since Registration</p>
                                    <p class="text-gray-900">{{ $user->created_at->diffForHumans() }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Verification Actions -->
                        <div class="mt-8 pt-6 border-t">
                            <h4 class="text-lg font-semibold mb-4">Verification Actions</h4>
                            
                            @if ($user->hasDocument())
                                <div class="space-y-3">
                                    <button onclick="openVerifyModal()" 
                                            class="w-full bg-green-600 hover:bg-green-700 text-white py-3 px-4 rounded-md font-medium transition-colors">
                                        ✓ Approve Registration
                                    </button>
                                    <button onclick="openRejectModal()" 
                                            class="w-full bg-red-600 hover:bg-red-700 text-white py-3 px-4 rounded-md font-medium transition-colors">
                                        ✗ Reject Registration
                                    </button>
                                </div>
                            @else
                                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                                    <p class="text-red-700 font-medium">⚠️ No Document Uploaded</p>
                                    <p class="text-red-600 text-sm mt-1">This user has not uploaded any identification documents. Registration cannot be approved without proper documentation.</p>
                                    <button onclick="openRejectModal()" 
                                            class="mt-3 bg-red-600 hover:bg-red-700 text-white py-2 px-4 rounded-md text-sm transition-colors">
                                        Reject Due to Missing Documents
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Document Viewer -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-semibold mb-6">Uploaded Documents</h3>
                        
                        @if ($user->hasDocument())
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        @if ($user->isDocumentImage())
                                            <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"></path>
                                            </svg>
                                            <span class="font-medium text-green-700">ID Image ({{ strtoupper($user->getDocumentExtension()) }})</span>
                                        @elseif ($user->isDocumentPdf())
                                            <svg class="w-6 h-6 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"></path>
                                            </svg>
                                            <span class="font-medium text-red-700">PDF Document</span>
                                        @else
                                            <svg class="w-6 h-6 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"></path>
                                            </svg>
                                            <span class="font-medium text-gray-700">{{ strtoupper($user->getDocumentExtension()) }} File</span>
                                        @endif
                                    </div>
                                    <a href="{{ route('admin.view-document', $user) }}" 
                                       target="_blank"
                                       class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm transition-colors">
                                        Open in New Tab
                                    </a>
                                </div>

                                <!-- Document Preview -->
                                <div class="border rounded-lg overflow-hidden">
                                    @if ($user->isDocumentImage())
                                        <div class="bg-gray-100 p-4">
                                            <img src="{{ route('admin.view-document', $user) }}" 
                                                 alt="ID Document" 
                                                 class="max-w-full h-auto mx-auto rounded shadow-lg"
                                                 style="max-height: 500px;">
                                        </div>
                                    @elseif ($user->isDocumentPdf())
                                        <div class="bg-gray-100 p-4">
                                            <iframe src="{{ route('admin.view-document', $user) }}" 
                                                    class="w-full h-96 border-0 rounded"
                                                    title="PDF Document">
                                            </iframe>
                                        </div>
                                    @else
                                        <div class="bg-gray-100 p-8 text-center">
                                            <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"></path>
                                            </svg>
                                            <p class="text-gray-600">Preview not available for this file type.</p>
                                            <p class="text-sm text-gray-500 mt-1">Click "Open in New Tab" to download and view the file.</p>
                                        </div>
                                    @endif
                                </div>

                                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                    <h4 class="font-medium text-blue-800 mb-2">Verification Checklist</h4>
                                    <ul class="text-sm text-blue-700 space-y-1">
                                        <li>• Verify the document is a valid student ID or COR</li>
                                        <li>• Check that the name matches the registration information</li>
                                        <li>• Ensure the document is clear and readable</li>
                                        <li>• Confirm the institution/campus matches</li>
                                        <li>• Look for signs of tampering or forgery</li>
                                    </ul>
                                </div>
                            </div>
                        @else
                            <div class="text-center py-12">
                                <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"></path>
                                </svg>
                                <h4 class="text-lg font-medium text-gray-900 mb-2">No Document Uploaded</h4>
                                <p class="text-gray-500">This user has not uploaded any identification documents.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Verify Modal -->
    <div id="verifyModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    Approve Registration for {{ $user->name }}
                </h3>
                
                <form method="POST" action="{{ route('admin.verify-user', $user) }}">
                    @csrf
                    
                    <div class="mb-4">
                        <label for="admin_notes" class="block text-sm font-medium text-gray-700 mb-2">
                            Admin Notes (Optional)
                        </label>
                        <textarea id="admin_notes" name="admin_notes" rows="3" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                                  placeholder="Add any notes about the verification..."></textarea>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeVerifyModal()" 
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md">
                            Approve Registration
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div id="rejectModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    Reject Registration for {{ $user->name }}
                </h3>
                
                <form method="POST" action="{{ route('admin.reject-user', $user) }}">
                    @csrf
                    
                    <div class="mb-4">
                        <label for="rejection_reason" class="block text-sm font-medium text-gray-700 mb-2">
                            Rejection Reason <span class="text-red-500">*</span>
                        </label>
                        <textarea id="rejection_reason" name="rejection_reason" rows="3" required
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500"
                                  placeholder="Please provide a detailed reason for rejection..."></textarea>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeRejectModal()" 
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md">
                            Reject Registration
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openVerifyModal() {
            document.getElementById('verifyModal').classList.remove('hidden');
        }

        function closeVerifyModal() {
            document.getElementById('verifyModal').classList.add('hidden');
            document.getElementById('admin_notes').value = '';
        }

        function openRejectModal() {
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
