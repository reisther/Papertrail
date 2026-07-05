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
                                        <div class="flex min-w-0 items-start gap-3">
                                            @include('partials.user-avatar', ['user' => $request->adviser, 'size' => 'h-11 w-11', 'textSize' => 'text-sm', 'bg' => 'bg-green-100', 'text' => 'text-green-700'])
                                            <div class="min-w-0">
                                                <p class="font-medium text-gray-900">{{ $request->adviser->name }}</p>
                                                <p class="text-sm text-gray-600">{{ $request->adviser->course }}</p>
                                                @if ($request->message)
                                                    <p class="text-sm text-gray-500 mt-1">Message: "{{ $request->message }}"</p>
                                                @endif
                                                @if ($request->response_message)
                                                    <p class="text-sm text-gray-700 mt-1 font-medium">Response: "{{ $request->response_message }}"</p>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                                @if($request->status === 'pending') bg-yellow-100 text-yellow-800
                                                @elseif($request->status === 'approved') bg-green-100 text-green-800
                                                @else bg-red-100 text-red-800 @endif">
                                                {{ ucfirst($request->status) }}
                                            </span>
                                            <p class="text-xs text-gray-500 mt-1">{{ $request->created_at->format('M j, Y') }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif


<!-- AI Suggested Advisers -->
@if(isset($recommendedAdvisers) && count($recommendedAdvisers) > 0)
<div class="mb-10">
    <h3 class="text-lg font-semibold mb-4">
        AI Suggested Advisers
    </h3>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

        @foreach($recommendedAdvisers as $adviser)

           @php
             $hasRequest = $currentRequests->where('adviser_id', $adviser->id)->first();
           @endphp

            <div class="border-2 border-green-500 rounded-lg p-6 bg-green-50">

                <div class="flex items-start justify-between gap-4 mb-4">
                    <div class="flex min-w-0 items-center gap-3">
                        @include('partials.user-avatar', ['user' => $adviser, 'size' => 'h-12 w-12', 'textSize' => 'text-sm', 'bg' => 'bg-green-100', 'text' => 'text-green-700'])
                        <h4 class="text-lg font-bold text-gray-900 truncate">
                            {{ $adviser->name }}
                        </h4>
                    </div>

                    <span class="shrink-0 bg-green-600 text-white px-3 py-1 rounded-full text-sm font-semibold">
                        {{ $adviser->match_score }}%
                    </span>
                </div>

                <p class="text-sm text-gray-600 mb-2">
                    {{ $adviser->course }}
                </p>

                <p class="text-sm text-gray-700 mb-4">
                    <span class="font-semibold">
                        Match:
                    </span>

                    {{ $adviser->reason }}
                </p>

                @if (!empty($adviser->matched_expertise))
                    <div class="flex flex-wrap gap-2 mb-4">
                        @foreach ($adviser->matched_expertise as $expertise)
                            <span class="inline-flex rounded-full bg-white px-2.5 py-1 text-xs font-medium text-green-700 border border-green-200">
                                {{ $expertise }}
                            </span>
                        @endforeach
                    </div>
                @endif

                @if ($hasRequest)
                    <button disabled
                        class="w-full bg-gray-300 text-gray-500 py-2 rounded-md">

                        Request {{ ucfirst($hasRequest->status) }}

                    </button>
                @else
                    <button
                        onclick="openRequestModal({{ $adviser->id }}, @js($adviser->name))"

                        class="w-full bg-green-600 hover:bg-green-700 text-white py-2 rounded-md">

                        Send Request

                    </button>
                @endif

            </div>

        @endforeach

    </div>
</div>
@else
    <div class="mb-10 rounded-lg border border-yellow-200 bg-yellow-50 p-5 text-sm text-yellow-900">
        No close adviser expertise match was found for these titles. Try using clearer technology keywords in the titles or ask an admin to update adviser expertise profiles.
    </div>
@endif


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
