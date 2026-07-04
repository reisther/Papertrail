<x-app-layout>
    @php
        $memberWithoutGroup = Auth::user()->isStudent() && Auth::user()->joinedProjects()->doesntExist();
        $leaderWithoutGroup = Auth::user()->canLeadGroup() && Auth::user()->ownedProjects()->doesntExist();
    @endphp

    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Meeting Calendar
            </h2>
            @if(Auth::user()->isTeacher() || Auth::user()->canLeadGroup() || Auth::user()->role === 'Admin')
                <a href="{{ route('defense-schedule.create') }}" 
                   class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors inline-flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Schedule Meeting
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if($leaderWithoutGroup)
                @include('partials.leader-create-group-card')
            @elseif($memberWithoutGroup)
                <div class="bg-white shadow-sm rounded-lg border p-12 text-center">
                    <div class="w-20 h-20 bg-blue-50 rounded-2xl flex items-center justify-center mx-auto mb-6">
                        <svg class="w-10 h-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">You're not in a group yet</h3>
                    <p class="text-gray-600 max-w-md mx-auto">Ask your leader to send you the invitation link for your group. Once you join, your group's calendar, todo dates, and meeting links will appear here.</p>
                </div>
            @else
            <!-- Calendar Legend -->
            <div class="bg-white shadow-sm rounded-lg border mb-6 p-4">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Calendar Legend</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="flex items-center">
                        <div class="w-4 h-4 rounded bg-blue-500 mr-2"></div>
                        <span class="text-sm text-gray-700">Meeting</span>
                    </div>
                    <div class="flex items-center">
                        <div class="w-4 h-4 rounded bg-green-500 mr-2"></div>
                        <span class="text-sm text-gray-700">Consultation</span>
                    </div>
                    <div class="flex items-center">
                        <div class="w-4 h-4 rounded bg-red-500 mr-2"></div>
                        <span class="text-sm text-gray-700">Cancelled</span>
                    </div>
                </div>
            </div>

            <!-- Calendar Container -->
            <div class="bg-white shadow-sm rounded-lg border p-6">
                <div id="calendar-loading" class="text-center p-8 text-gray-600">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto mb-4"></div>
                    Loading calendar...
                </div>
                <div id="calendar"></div>
            </div>
            @endif
        </div>
    </div>

    @if(!$memberWithoutGroup && !$leaderWithoutGroup)
    <!-- Event Details Modal -->
    <div id="eventModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex justify-between items-start mb-4">
                    <h3 id="eventTitle" class="text-lg font-medium text-gray-900 flex-1 pr-4"></h3>
                    <button onclick="closeEventModal()" class="text-gray-400 hover:text-gray-600 flex-shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <div id="eventDetails" class="space-y-3 text-sm max-h-96 overflow-y-auto">
                    <!-- Event details will be populated here -->
                </div>
                
                <div class="flex flex-col sm:flex-row justify-end space-y-2 sm:space-y-0 sm:space-x-3 mt-6 pt-4 border-t border-gray-200">
                    <button onclick="closeEventModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors">
                        Close
                    </button>
                    <div id="eventActions" class="flex space-x-2">
                        <!-- Action buttons will be populated here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Include FullCalendar CSS and JS -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('calendar');
            
            // Check if FullCalendar is loaded
            if (typeof FullCalendar === 'undefined') {
                console.error('FullCalendar is not loaded');
                document.getElementById('calendar').innerHTML = '<div class="text-center p-8 text-red-600">Calendar failed to load. Please refresh the page.</div>';
                return;
            }
            
            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: {
                    url: '{{ route("defense-schedule.events") }}',
                    failure: function() {
                        console.error('Failed to load events');
                        alert('Failed to load calendar events. Please refresh the page.');
                    }
                },
                eventClick: function(info) {
                    info.jsEvent.preventDefault(); // Prevent default link behavior
                    showEventDetails(info.event);
                },
                dateClick: function(info) {
                    console.log('Date clicked: ' + info.dateStr);
                    @if(Auth::user()->isTeacher() || Auth::user()->canLeadGroup() || Auth::user()->role === 'Admin')
                        // For teachers/admins, clicking a date can create a new event
                        const createUrl = '{{ route("defense-schedule.create") }}';
                        const selectedDate = info.dateStr;
                        // You can pass the selected date as a parameter
                        window.location.href = createUrl + '?date=' + selectedDate;
                    @else
                        // For students, just show the date
                        alert('Selected date: ' + info.dateStr);
                    @endif
                },
                height: 'auto',
                eventDisplay: 'block',
                dayMaxEvents: 3,
                moreLinkClick: 'popover',
                eventTimeFormat: {
                    hour: 'numeric',
                    minute: '2-digit',
                    meridiem: 'short'
                },
                loading: function(bool) {
                    if (bool) {
                        console.log('Loading calendar events...');
                    } else {
                        console.log('Calendar events loaded');
                    }
                },
                eventDidMount: function(info) {
                    // Add tooltip to events
                    info.el.setAttribute('title', info.event.title + ' - ' + (info.event.extendedProps.project_title || 'Group'));
                }
            });

            calendar.render();
            
            // Hide loading indicator after calendar renders
            setTimeout(function() {
                document.getElementById('calendar-loading').style.display = 'none';
            }, 1000);
            
            // Debug: Log calendar object
            console.log('Calendar initialized:', calendar);
        });

        function showEventDetails(event) {
            console.log('Showing event details for:', event);
            
            const props = event.extendedProps || {};
            
            // Set the title
            const titleElement = document.getElementById('eventTitle');
            if (titleElement) {
                titleElement.textContent = event.title || 'Meeting';
            }
            
            // Format dates safely
            let startTime = 'Not specified';
            let endTime = 'Not specified';
            
            if (event.start) {
                startTime = event.start.toLocaleString();
            }
            if (event.end) {
                endTime = event.end.toLocaleString();
            }
            
            const detailsHtml = `
                <div class="flex items-start text-gray-600 mb-3">
                    <svg class="w-4 h-4 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div>
                        <div class="font-medium">Time</div>
                        <div>${startTime}</div>
                        <div>to ${endTime}</div>
                    </div>
                </div>
                
                ${props.project_title ? `
                    <div class="flex items-center text-gray-600 mb-2">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m8-8a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        <span><strong>Group Code:</strong> ${props.project_title}</span>
                    </div>
                ` : ''}
                
                ${props.type ? `
                    <div class="flex items-center text-gray-600 mb-2">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                        </svg>
                        <span><strong>Type of Meeting:</strong> ${props.type ? props.type.replace('_', ' ').toUpperCase() : 'Not specified'}</span>
                    </div>
                ` : ''}
                
                ${props.status ? `
                    <div class="flex items-center text-gray-600 mb-2">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span><strong>Status:</strong> ${props.status.toUpperCase()}</span>
                    </div>
                ` : ''}
                
                ${props.description ? `
                    <div class="text-gray-600 mt-4 p-3 bg-gray-50 rounded-lg">
                        <div class="font-medium mb-1">Description:</div>
                        <div>${props.description}</div>
                    </div>
                ` : ''}
                
                ${props.meeting_link ? `
                    <div class="mt-4 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-700">Online Meeting:</span>
                            ${props.meeting_platform === 'google_meet' ? `
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    <svg class="w-3 h-3 mr-1" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                    </svg>
                                    Google Meet
                                </span>
                            ` : `
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    Manual Link
                                </span>
                            `}
                        </div>
                        <div class="flex space-x-2">
                            <a href="${props.meeting_link}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center px-3 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors text-sm font-medium">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                </svg>
                                Join Meeting
                            </a>
                            ${props.google_calendar_link ? `
                                <a href="${props.google_calendar_link}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center px-3 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors text-sm font-medium">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    View in Calendar
                                </a>
                            ` : ''}
                        </div>
                    </div>
                ` : ''}
            `;
            
            // Set the details HTML
            const detailsElement = document.getElementById('eventDetails');
            if (detailsElement) {
                detailsElement.innerHTML = detailsHtml;
            }
            
            // Add action buttons for teachers/admins
            const actionsElement = document.getElementById('eventActions');
            if (actionsElement) {
                @if(Auth::user()->isTeacher() || Auth::user()->canLeadGroup() || Auth::user()->role === 'Admin')
                    const actionsHtml = `
                        <a href="/defense-schedule/${event.id}" class="px-3 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors text-sm">
                            View Details
                        </a>
                        <a href="/defense-schedule/${event.id}/edit" class="px-3 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors text-sm">
                            Edit
                        </a>
                    `;
                    actionsElement.innerHTML = actionsHtml;
                @else
                    const actionsHtml = `
                        <a href="/defense-schedule/${event.id}" class="px-3 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors text-sm">
                            View Details
                        </a>
                    `;
                    actionsElement.innerHTML = actionsHtml;
                @endif
            }
            
            // Show the modal
            const modal = document.getElementById('eventModal');
            if (modal) {
                modal.classList.remove('hidden');
                console.log('Modal should now be visible');
            } else {
                console.error('Modal element not found');
            }
        }

        function closeEventModal() {
            document.getElementById('eventModal').classList.add('hidden');
        }

        // Close modal when clicking outside
        document.getElementById('eventModal').addEventListener('click', function(e) {
            if (e.target === this) closeEventModal();
        });
    </script>
    @endif
</x-app-layout>
