<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold text-gray-900">Select an Adviser</h2>
                    </div>


                    <!-- Current Requests -->
                    @if ($currentRequests->count() > 0)
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold mb-4">Your Adviser Requests</h3>
                            <div class="bg-gray-50 rounded-lg p-4">
                                @foreach ($currentRequests as $request)
                                    <div class="flex items-center justify-between p-3 bg-white rounded border mb-2 last:mb-0">
                                        <div>
                                            <p class="font-medium">{{ $request->adviser->name }}</p>
                                            <p class="text-sm text-gray-600">{{ $request->adviser->course }}</p>
                                            @if ($request->message)
                                                <p class="text-sm text-gray-500 mt-1">Message: "{{ $request->message }}"</p>
                                            @endif
                                            @if ($request->response_message)
                                                <p class="text-sm text-gray-700 mt-1 font-medium">Response: "{{ $request->response_message }}"</p>
                                            @endif
                                            @include('partials.adviser-schedule', [
                                                'adviser' => $request->adviser,
                                                'class' => 'mt-3 rounded-lg border border-green-100 bg-green-50 p-3',
                                                'collapsed' => true,
                                            ])
                                        </div>
                                        <div class="text-right">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                                @if($request->status === 'pending') bg-yellow-100 text-yellow-800
                                                @elseif($request->status === 'approved') bg-green-100 text-green-800
                                                @else bg-red-100 text-red-800 @endif">
                                                {{ ucfirst($request->status) }}
                                            </span>
                                            <p class="text-xs text-gray-500 mt-1">{{ $request->created_at->format('M j, Y') }}</p>
                                            <form method="POST" action="{{ route('advisers.requests.remove', $request) }}" class="mt-3" onsubmit="return confirm('Remove this adviser request?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-xs font-semibold text-red-600 hover:text-red-800">
                                                    Remove Request
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Available Advisers -->
                    <div>
                        <h3 class="text-lg font-semibold mb-4">Available Advisers</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            @forelse ($advisers as $adviser)
                                <div class="bg-white border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow">
                                    <div class="flex items-center space-x-4 mb-4">
                                        <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                                            <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <h4 class="text-lg font-semibold text-gray-900">{{ $adviser->name }}</h4>
                                            <p class="text-sm text-gray-600">{{ $adviser->course }}</p>
                                        </div>
                                    </div>
                                    
                                    <div class="space-y-2 mb-4">
                                        <p class="text-sm"><span class="font-medium">Campus:</span> {{ $adviser->campus }}</p>
                                        <p class="text-sm"><span class="font-medium">Email:</span> {{ $adviser->email }}</p>
                                    </div>

                                    @php
                                        $hasRequest = $currentRequests->where('adviser_id', $adviser->id)->first();
                                    @endphp

                                    @if ($hasRequest)
                                        <button disabled class="w-full bg-gray-300 text-gray-500 py-2 px-4 rounded-md cursor-not-allowed">
                                            Request {{ ucfirst($hasRequest->status) }}
                                        </button>
                                    @else
                                        <button onclick="openRequestModal({{ $adviser->id }}, @js($adviser->name))" 
                                                class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-md transition-colors">
                                            Send Request
                                        </button>
                                    @endif
                                </div>
                            @empty
                                <div class="col-span-full text-center py-8">
                                    <p class="text-gray-500">No advisers available at the moment.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Request Modal -->
    <div id="requestModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Send Request to <span id="adviserName"></span></h3>
                
                <form method="POST" action="{{ route('advisers.send-request') }}">
                    @csrf
                    <input type="hidden" id="adviserId" name="adviser_id" value="">
                    
                    <div class="mb-4">
                        <label for="message" class="block text-sm font-medium text-gray-700 mb-2">
                            Message (Optional)
                        </label>
                        <textarea id="message" name="message" rows="3" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                  placeholder="Introduce yourself or explain why you'd like this adviser..."></textarea>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeRequestModal()" 
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Send Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openRequestModal(adviserId, adviserName) {
            document.getElementById('adviserId').value = adviserId;
            document.getElementById('adviserName').textContent = adviserName;
            document.getElementById('requestModal').classList.remove('hidden');
        }

        function closeRequestModal() {
            document.getElementById('requestModal').classList.add('hidden');
            document.getElementById('message').value = '';
        }

        // Close modal when clicking outside
        document.getElementById('requestModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeRequestModal();
            }
        });
    </script>
</x-app-layout>
