<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('User Management') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Statistics Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-4 mb-8">
                <div class="bg-white p-4 rounded-lg shadow-sm border">
                    <div class="text-2xl font-bold text-gray-900">{{ $stats['total'] }}</div>
                    <div class="text-sm text-gray-600">Total Users</div>
                </div>
                <div class="bg-blue-50 p-4 rounded-lg shadow-sm border">
                    <div class="text-2xl font-bold text-blue-600">{{ $stats['students'] }}</div>
                    <div class="text-sm text-blue-600">Students</div>
                </div>
                <div class="bg-indigo-50 p-4 rounded-lg shadow-sm border">
                    <div class="text-2xl font-bold text-indigo-600">{{ $stats['leaders'] }}</div>
                    <div class="text-sm text-indigo-600">Leaders</div>
                </div>
                <div class="bg-green-50 p-4 rounded-lg shadow-sm border">
                    <div class="text-2xl font-bold text-green-600">{{ $stats['teachers'] }}</div>
                    <div class="text-sm text-green-600">Teachers</div>
                </div>
                <div class="bg-purple-50 p-4 rounded-lg shadow-sm border">
                    <div class="text-2xl font-bold text-purple-600">{{ $stats['admins'] }}</div>
                    <div class="text-sm text-purple-600">Admins</div>
                </div>
                <div class="bg-green-50 p-4 rounded-lg shadow-sm border">
                    <div class="text-2xl font-bold text-green-600">{{ $stats['verified'] }}</div>
                    <div class="text-sm text-green-600">Verified</div>
                </div>
                <div class="bg-yellow-50 p-4 rounded-lg shadow-sm border">
                    <div class="text-2xl font-bold text-yellow-600">{{ $stats['pending'] }}</div>
                    <div class="text-sm text-yellow-600">Pending</div>
                </div>
                <div class="bg-red-50 p-4 rounded-lg shadow-sm border">
                    <div class="text-2xl font-bold text-red-600">{{ $stats['rejected'] }}</div>
                    <div class="text-sm text-red-600">Rejected</div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white p-6 rounded-lg shadow-sm border mb-6">
                <form method="GET" class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-[minmax(0,1fr)_10rem_10rem_auto] lg:items-end">
                    <div class="min-w-0">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                        <input type="text" name="search" value="{{ request('search') }}" 
                               placeholder="Search by name, email, or course..."
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="min-w-0">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                        <select name="role" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">All Roles</option>
                            <option value="Student" {{ request('role') === 'Student' ? 'selected' : '' }}>Student</option>
                            <option value="Leader" {{ request('role') === 'Leader' ? 'selected' : '' }}>Leader</option>
                            <option value="Teacher" {{ request('role') === 'Teacher' ? 'selected' : '' }}>Teacher</option>
                            <option value="Admin" {{ request('role') === 'Admin' ? 'selected' : '' }}>Admin</option>
                        </select>
                    </div>
                    <div class="min-w-0">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">All Status</option>
                            <option value="Verified" {{ request('status') === 'Verified' ? 'selected' : '' }}>Verified</option>
                            <option value="Pending" {{ request('status') === 'Pending' ? 'selected' : '' }}>Pending</option>
                            <option value="Rejected" {{ request('status') === 'Rejected' ? 'selected' : '' }}>Rejected</option>
                        </select>
                    </div>
                    <div class="flex gap-2 md:col-span-2 lg:col-span-1">
                        <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md lg:flex-none">
                            Filter
                        </button>
                        <a href="{{ route('admin.all-users') }}" class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-md text-center lg:flex-none">
                            Clear
                        </a>
                    </div>
                </form>
            </div>

            <!-- Users Table -->
            <div class="bg-white shadow-sm rounded-lg border overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'id', 'direction' => request('direction') === 'asc' ? 'desc' : 'asc']) }}" class="hover:text-gray-700">
                                        ID
                                    </a>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'firstname', 'direction' => request('direction') === 'asc' ? 'desc' : 'asc']) }}" class="hover:text-gray-700">
                                        Name
                                    </a>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'email', 'direction' => request('direction') === 'asc' ? 'desc' : 'asc']) }}" class="hover:text-gray-700">
                                        Email
                                    </a>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'role', 'direction' => request('direction') === 'asc' ? 'desc' : 'asc']) }}" class="hover:text-gray-700">
                                        Role
                                    </a>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'status', 'direction' => request('direction') === 'asc' ? 'desc' : 'asc']) }}" class="hover:text-gray-700">
                                        Status
                                    </a>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Adviser
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'last_login_at', 'direction' => request('direction') === 'asc' ? 'desc' : 'asc']) }}" class="hover:text-gray-700">
                                        Last Login
                                    </a>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($users as $user)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $user->id }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center mr-3">
                                                <span class="text-xs font-medium text-gray-600">{{ substr($user->firstname, 0, 1) }}</span>
                                            </div>
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">{{ $user->name }}</div>
                                                <div class="text-sm text-gray-500">{{ $user->course }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $user->email }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                            @if($user->role === 'Admin') bg-purple-100 text-purple-800
                                            @elseif($user->role === 'Teacher') bg-green-100 text-green-800
                                            @elseif($user->role === 'Leader') bg-indigo-100 text-indigo-800
                                            @else bg-blue-100 text-blue-800 @endif">
                                            {{ $user->role }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                            @if($user->status === 'Verified') bg-green-100 text-green-800
                                            @elseif($user->status === 'Pending') bg-yellow-100 text-yellow-800
                                            @else bg-red-100 text-red-800 @endif">
                                            {{ $user->status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        @if($user->isStudentGroupRole())
                                            @php
                                                $group = $user->canLeadGroup()
                                                    ? $user->ownedProjects()->with('adviser')->latest()->first()
                                                    : $user->joinedProjects()->with('adviser')->latest('project_members.joined_at')->first();
                                                $adviserName = $group?->adviser?->name
                                                    ?? $user->advisers()->where('status', 'approved')->with('adviser')->first()?->adviser?->name;
                                            @endphp
                                            {{ $adviserName ?? 'No Adviser' }}
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $user->last_login_at ? $user->last_login_at->format('M j, Y g:i A') : 'Never' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            @if($user->id !== Auth::id())
                                                <button onclick="openRoleModal({{ $user->id }}, '{{ $user->name }}', '{{ $user->role }}')" 
                                                        class="text-blue-600 hover:text-blue-900">Role</button>
                                            @endif
                                            <button onclick="openStatusModal({{ $user->id }}, '{{ $user->name }}', '{{ $user->status }}')" 
                                                    class="text-green-600 hover:text-green-900">Status</button>
                                            <a href="{{ route('admin.account-recovery.create', $user) }}"
                                               class="text-amber-600 hover:text-amber-900">Recovery</a>
                                            @if($user->id !== Auth::id())
                                                <button onclick="confirmDelete({{ $user->id }}, '{{ $user->name }}')" 
                                                        class="text-red-600 hover:text-red-900">Delete</button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $users->links() }}
                </div>
            </div>
        </div>
    </div>

    <!-- Role Modal -->
    <div id="roleModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Update User Role</h3>
            <form method="POST" id="roleForm">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                    <select name="role" id="roleSelect" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        <option value="Student">Student</option>
                        <option value="Leader">Leader</option>
                        <option value="Teacher">Teacher</option>
                        <option value="Admin">Admin</option>
                    </select>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeRoleModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md">Update Role</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Status Modal -->
    <div id="statusModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Update User Status</h3>
            <form method="POST" id="statusForm">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select name="status" id="statusSelect" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        <option value="Pending">Pending</option>
                        <option value="Verified">Verified</option>
                        <option value="Rejected">Rejected</option>
                    </select>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeStatusModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md">Update Status</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete User Modal -->
    <div id="deleteUserModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                    <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 text-center mb-2">Delete User</h3>
                <p class="text-sm text-gray-500 text-center mb-6">
                    Are you sure you want to delete user "<span id="deleteUserName" class="font-medium text-gray-900"></span>"? This action cannot be undone and will permanently remove all their data.
                </p>
                
                <div class="flex justify-center space-x-3">
                    <button type="button" onclick="closeDeleteUserModal()" 
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors">
                        Cancel
                    </button>
                    <button type="button" onclick="executeDeleteUser()" 
                            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md transition-colors">
                        Delete User
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openRoleModal(userId, userName, currentRole) {
            document.getElementById('roleForm').action = `/admin/users/${userId}/update-role`;
            document.getElementById('roleSelect').value = currentRole;
            document.getElementById('roleModal').classList.remove('hidden');
        }

        function closeRoleModal() {
            document.getElementById('roleModal').classList.add('hidden');
        }

        function openStatusModal(userId, userName, currentStatus) {
            document.getElementById('statusForm').action = `/admin/users/${userId}/update-status`;
            document.getElementById('statusSelect').value = currentStatus;
            document.getElementById('statusModal').classList.remove('hidden');
        }

        function closeStatusModal() {
            document.getElementById('statusModal').classList.add('hidden');
        }

        let deleteUserId = null;
        let deleteUserName = '';

        function confirmDelete(userId, userName) {
            deleteUserId = userId;
            deleteUserName = userName;
            document.getElementById('deleteUserName').textContent = userName;
            document.getElementById('deleteUserModal').classList.remove('hidden');
        }

        function executeDeleteUser() {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/admin/users/${deleteUserId}`;
            form.innerHTML = `@csrf @method('DELETE')`;
            document.body.appendChild(form);
            form.submit();
        }

        function closeDeleteUserModal() {
            document.getElementById('deleteUserModal').classList.add('hidden');
            deleteUserId = null;
            deleteUserName = '';
        }
    </script>
</x-app-layout>
