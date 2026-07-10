<x-app-layout>
    @php
        $displayStatus = $isArchivedForCurrentUser ? 'archived' : $project->status;
        $statusBadgeClass = match ($displayStatus) {
            'active' => 'bg-green-100 text-green-800',
            'completed' => 'bg-blue-100 text-blue-800',
            'archived' => 'bg-gray-100 text-gray-800',
            default => 'bg-yellow-100 text-yellow-800',
        };
    @endphp

    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ $project->title }}
                </h2>
                <div class="flex items-center space-x-4 mt-1">
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $statusBadgeClass }}">
                        {{ ucfirst($displayStatus) }}
                    </span>
                    @if($isArchivedForCurrentUser)
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-700">
                            Archived Files
                        </span>
                    @endif
                    <span class="text-sm text-gray-600">{{ $project->documents_count }} files • {{ $project->folders_count }} folders • {{ $submissionCount }} submitted work</span>
                </div>
            </div>
            <div class="flex space-x-2">
                <a href="{{ $isArchivedForCurrentUser ? route('projects.archived') : route('projects.index') }}" 
                   class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                    ← Back to Projects
                </a>
                @if($canEditProject)
                    <a href="{{ route('projects.edit', $project) }}" 
                       class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                        Edit Project
                    </a>
                    <form method="POST" action="{{ route('projects.invitations.generate', $project) }}">
                        @csrf
                        <button type="submit"
                                class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                            Invite Members
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('invite_link'))
                <div class="bg-indigo-50 border border-indigo-200 rounded-lg mb-6 p-4">
                    <label class="block text-sm font-medium text-indigo-900 mb-2">Member Invitation Link</label>
                    <input type="text" readonly value="{{ session('invite_link') }}"
                           class="w-full px-3 py-2 border border-indigo-200 rounded-md bg-white text-sm text-indigo-900">
                </div>
            @endif

            @if($isArchivedForCurrentUser)
                <div class="mb-6 rounded-lg border border-gray-200 bg-gray-50 p-4">
                    <div class="flex items-start gap-3">
                        <svg class="mt-0.5 h-5 w-5 shrink-0 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 012-2h10a2 2 0 012 2M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                        </svg>
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900">Archived files</h3>
                            <p class="mt-1 text-sm text-gray-600">This group has been archived for your adviser history. You can preview and download saved student uploads, but uploads and file changes are disabled.</p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Project Info Bar -->
            <div class="bg-white shadow-sm rounded-lg mb-6 p-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
                    <div>
                        <span class="font-medium text-gray-700">Owner:</span>
                        <span class="text-gray-900">{{ $project->owner->name }}</span>
                    </div>
                    @if($project->adviser)
                        <div>
                            <span class="font-medium text-gray-700">Adviser:</span>
                            <span class="text-gray-900">{{ $project->adviser->name }}</span>
                        </div>
                    @endif
                    @if($project->due_date)
                        <div>
                            <span class="font-medium text-gray-700">Due Date:</span>
                            <span class="text-gray-900 {{ $project->due_date->isPast() ? 'text-red-600' : '' }}">
                                {{ $project->due_date->format('M j, Y') }}
                            </span>
                        </div>
                    @endif
                    <div>
                        <span class="font-medium text-gray-700">Total Size:</span>
                        <span class="text-gray-900">{{ $project->formatted_list_size }}</span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700">Submitted Work:</span>
                        <a href="{{ route('projects.show', ['project' => $project, 'folder' => $submissionFolder->id]) }}" class="text-blue-600 hover:text-blue-800 font-medium">
                            {{ $submissionCount }}
                        </a>
                    </div>
                </div>
            </div>

            <!-- Breadcrumb Navigation -->
            @if(count($breadcrumb) > 0)
                <div class="bg-white shadow-sm rounded-lg mb-6 p-4">
                    <nav class="flex" aria-label="Breadcrumb">
                        <ol class="flex items-center space-x-2">
                            <li>
                                <a href="{{ route('projects.show', $project) }}" 
                                   class="text-blue-600 hover:text-blue-800 font-medium">
                                    {{ $project->title }}
                                </a>
                            </li>
                            @foreach($breadcrumb as $folder)
                                <li class="flex items-center">
                                    <svg class="w-4 h-4 text-gray-400 mx-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                    @if($loop->last)
                                        <span class="text-gray-700 font-medium">{{ $folder['name'] }}</span>
                                    @else
                                        <a href="{{ route('projects.show', ['project' => $project, 'folder' => $folder['id']]) }}" 
                                           class="text-blue-600 hover:text-blue-800">
                                            {{ $folder['name'] }}
                                        </a>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    </nav>
                </div>
            @endif

            <!-- Action Bar -->
            <div class="bg-white shadow-sm rounded-lg mb-6 p-4">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex flex-wrap items-center gap-3">
                        @if($canUploadToProject)
                            <button onclick="openUploadModal()" 
                                    class="inline-flex items-center bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors shadow-sm">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                </svg>
                                {{ $currentFolder && $currentFolder->id === $submissionFolder->id ? 'Submit Work' : 'Upload Files' }}
                            </button>
                        @endif
                        @if($canDeleteProjectItems)
                            <button onclick="openFolderModal()" 
                                    class="inline-flex items-center bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors shadow-sm">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                New Folder
                            </button>
                        @endif
                        @if(!$canEditProject)
                            <div class="flex items-center text-sm text-gray-600 bg-gray-50 px-4 py-2 rounded-lg">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                Viewing as {{ Auth::user()->role === 'Teacher' ? 'Adviser' : 'Member' }}
                            </div>
                        @endif
                    </div>
                    <div class="flex items-center space-x-4 text-sm text-gray-600">
                        <span>{{ $folders->count() + $documents->count() }} items</span>
                    </div>
                </div>
            </div>

            <!-- File Explorer -->
            <div class="bg-white shadow-sm rounded-lg">
                @if($folders->count() > 0 || $documents->count() > 0)
                    <!-- Folders -->
                    @if($folders->count() > 0)
                        <div class="p-4 border-b border-gray-200">
                            <h3 class="text-sm font-medium text-gray-700 mb-3">Folders</h3>
                            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                                @foreach($folders as $folder)
                                    <div class="group cursor-pointer" onclick="navigateToFolder({{ $folder->id }})">
                                        <div class="flex flex-col items-center p-3 rounded-lg hover:bg-gray-50 transition-colors">
                                            <div class="relative">
                                                <svg class="w-12 h-12 mb-2" style="color: {{ $folder->color }}" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"></path>
                                                </svg>
                                                @if($canDeleteProjectItems && ! $folder->isSubmissionsFolder())
                                                    <button onclick="event.stopPropagation(); deleteFolder({{ $folder->id }}, '{{ $folder->name }}')" 
                                                            class="absolute -top-1 -right-1 w-6 h-6 bg-red-500 hover:bg-red-600 text-white rounded-full text-xs opacity-0 group-hover:opacity-100 transition-all duration-200 flex items-center justify-center">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                        </svg>
                                                    </button>
                                                @endif
                                            </div>
                                            <span class="text-sm text-center text-gray-900 truncate w-full">{{ $folder->name }}</span>
                                            <span class="text-xs text-gray-500">
                                                {{ $folder->documents_count }} {{ $folder->id === $submissionFolder->id ? 'submitted work' : 'files' }}
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Documents -->
                    @if($documents->count() > 0)
                        <div class="p-4">
                            <h3 class="text-sm font-medium text-gray-700 mb-3">Files</h3>
                            <div class="space-y-2">
                                @foreach($documents as $document)
                                    <div class="flex items-center justify-between p-3 hover:bg-gray-50 rounded-lg group">
                                        <div class="flex items-center space-x-3 flex-1 min-w-0">
                                            <div class="flex-shrink-0">
                                                @if($document->icon === 'pdf')
                                                    <svg class="w-8 h-8 {{ $document->icon_color }}" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"></path>
                                                    </svg>
                                                @elseif($document->icon === 'word')
                                                    <svg class="w-8 h-8 {{ $document->icon_color }}" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"></path>
                                                    </svg>
                                                @elseif($document->icon === 'image')
                                                    <svg class="w-8 h-8 {{ $document->icon_color }}" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"></path>
                                                    </svg>
                                                @else
                                                    <svg class="w-8 h-8 {{ $document->icon_color }}" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"></path>
                                                    </svg>
                                                @endif
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium text-gray-900 truncate">{{ $document->original_name }}</p>
                                                <div class="flex items-center space-x-4 text-xs text-gray-500">
                                                    <span>{{ $document->uploader->name }}</span>
                                                    <span>{{ $document->updated_at->format('M j, Y g:i A') }}</span>
                                                    <span>{{ $document->formatted_size }}</span>
                                                    @if($document->download_count > 0)
                                                        <span>{{ $document->download_count }} downloads</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <a href="{{ route('projects.preview-document', [$project, $document]) }}" 
                                               target="_blank"
                                               class="inline-flex items-center text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                </svg>
                                                Preview
                                            </a>
                                            <a href="{{ route('projects.download-document', [$project, $document]) }}" 
                                               class="inline-flex items-center text-blue-600 hover:text-blue-800 text-sm font-medium">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-4-4m4 4l4-4m5-5v8a2 2 0 01-2 2H5a2 2 0 01-2-2v-8a2 2 0 012-2h8l2 2h4a2 2 0 012 2z"></path>
                                                </svg>
                                                Download
                                            </a>
                                            @if(!$isArchivedForCurrentUser && ($canDeleteProjectItems || $document->uploaded_by === Auth::id()))
                                                <button onclick="deleteDocument({{ $document->id }}, '{{ $document->original_name }}')" 
                                                        class="inline-flex items-center text-red-600 hover:text-red-800 text-sm font-medium">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                    Delete
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @else
                    <div class="p-16 text-center">
                        <div class="w-24 h-24 bg-gradient-to-br from-gray-50 to-gray-100 rounded-2xl flex items-center justify-center mx-auto mb-6">
                            <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-3">No Files Yet</h3>
                        @if($canUploadToProject)
                            <p class="text-gray-600 mb-8 max-w-md mx-auto">Start by uploading your project documents or creating folders to organize your work.</p>
                            <div class="flex flex-col sm:flex-row justify-center gap-3">
                                <button onclick="openUploadModal()" 
                                        class="inline-flex items-center bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium transition-colors shadow-sm">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                    </svg>
                                    Upload Files
                                </button>
                                @if($canDeleteProjectItems)
                                <button onclick="openFolderModal()" 
                                        class="inline-flex items-center bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-medium transition-colors shadow-sm">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    Create Folder
                                </button>
                                @endif
                            </div>
                        @else
                            <p class="text-gray-600 mb-4 max-w-md mx-auto">{{ $isArchivedForCurrentUser ? 'This archived project does not have saved files yet.' : "This project doesn't have any files yet. The project owner can upload documents and create folders." }}</p>
                            <div class="inline-flex items-center text-sm text-gray-500 bg-gray-50 px-4 py-2 rounded-lg">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                {{ $isArchivedForCurrentUser ? 'Archived file access' : 'You have view access to this project' }}
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if($canUploadToProject)
    <!-- Upload Modal -->
    <div id="uploadModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    {{ $currentFolder && $currentFolder->id === $submissionFolder->id ? 'Submit Work' : 'Upload Files' }}
                </h3>
                
                <form method="POST" action="{{ route('projects.upload-documents', $project) }}" enctype="multipart/form-data">
                    @csrf
                    @if($currentFolder)
                        <input type="hidden" name="folder_id" value="{{ $currentFolder->id }}">
                    @endif
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            {{ $currentFolder && $currentFolder->id === $submissionFolder->id ? 'Select Work Files' : 'Select Files' }}
                        </label>
                        <input type="file" name="files[]" multiple required
                               accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="text-xs text-gray-500 mt-1">Supported: PDF, DOC, DOCX, Images (Max 10MB each)</p>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeUploadModal()" 
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md">
                            {{ $currentFolder && $currentFolder->id === $submissionFolder->id ? 'Submit Work' : 'Upload Files' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- Folder Modal -->
    <div id="folderModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Create New Folder</h3>
                
                <form method="POST" action="{{ route('projects.create-folder', $project) }}">
                    @csrf
                    @if($currentFolder)
                        <input type="hidden" name="parent_id" value="{{ $currentFolder->id }}">
                    @endif
                    
                    <div class="mb-4">
                        <label for="folder_name" class="block text-sm font-medium text-gray-700 mb-2">
                            Folder Name
                        </label>
                        <input type="text" id="folder_name" name="name" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                               placeholder="Enter folder name">
                    </div>

                    <div class="mb-4">
                        <label for="folder_color" class="block text-sm font-medium text-gray-700 mb-2">
                            Folder Color
                        </label>
                        <input type="color" id="folder_color" name="color" value="#3B82F6"
                               class="w-full h-10 border border-gray-300 rounded-md">
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeFolderModal()" 
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md">
                            Create Folder
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Document Modal -->
    <div id="deleteDocumentModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                    <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 text-center mb-2">Delete Document</h3>
                <p class="text-sm text-gray-500 text-center mb-6">
                    Are you sure you want to delete "<span id="deleteDocumentName" class="font-medium"></span>"? This action cannot be undone.
                </p>
                
                <div class="flex justify-center space-x-3">
                    <button type="button" onclick="closeDeleteDocumentModal()" 
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors">
                        Cancel
                    </button>
                    <button type="button" onclick="confirmDeleteDocument()" 
                            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md transition-colors">
                        Delete Document
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Folder Modal -->
    <div id="deleteFolderModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                    <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 text-center mb-2">Delete Folder</h3>
                <p class="text-sm text-gray-500 text-center mb-6">
                    Are you sure you want to delete the folder "<span id="deleteFolderName" class="font-medium"></span>" and all its contents? This action cannot be undone.
                </p>
                
                <div class="flex justify-center space-x-3">
                    <button type="button" onclick="closeDeleteFolderModal()" 
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors">
                        Cancel
                    </button>
                    <button type="button" onclick="confirmDeleteFolder()" 
                            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md transition-colors">
                        Delete Folder
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openUploadModal() {
            document.getElementById('uploadModal')?.classList.remove('hidden');
        }

        function closeUploadModal() {
            document.getElementById('uploadModal')?.classList.add('hidden');
        }

        function openFolderModal() {
            document.getElementById('folderModal').classList.remove('hidden');
        }

        function closeFolderModal() {
            document.getElementById('folderModal').classList.add('hidden');
        }

        function navigateToFolder(folderId) {
            window.location.href = `{{ route('projects.show', $project) }}?folder=${folderId}`;
        }

        let deleteDocumentId = null;
        let deleteDocumentName = '';
        let deleteFolderId = null;
        let deleteFolderName = '';

        function deleteDocument(documentId, fileName) {
            deleteDocumentId = documentId;
            deleteDocumentName = fileName;
            document.getElementById('deleteDocumentName').textContent = fileName;
            document.getElementById('deleteDocumentModal').classList.remove('hidden');
        }

        function confirmDeleteDocument() {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/projects/{{ $project->id }}/documents/${deleteDocumentId}`;
            form.innerHTML = `
                @csrf
                @method('DELETE')
            `;
            document.body.appendChild(form);
            form.submit();
        }

        function closeDeleteDocumentModal() {
            document.getElementById('deleteDocumentModal').classList.add('hidden');
            deleteDocumentId = null;
            deleteDocumentName = '';
        }

        function deleteFolder(folderId, folderName) {
            deleteFolderId = folderId;
            deleteFolderName = folderName;
            document.getElementById('deleteFolderName').textContent = folderName;
            document.getElementById('deleteFolderModal').classList.remove('hidden');
        }

        function confirmDeleteFolder() {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/projects/{{ $project->id }}/folders/${deleteFolderId}`;
            form.innerHTML = `
                @csrf
                @method('DELETE')
            `;
            document.body.appendChild(form);
            form.submit();
        }

        function closeDeleteFolderModal() {
            document.getElementById('deleteFolderModal').classList.add('hidden');
            deleteFolderId = null;
            deleteFolderName = '';
        }

        // Close modals when clicking outside
        document.getElementById('uploadModal')?.addEventListener('click', function(e) {
            if (e.target === this) closeUploadModal();
        });

        document.getElementById('folderModal').addEventListener('click', function(e) {
            if (e.target === this) closeFolderModal();
        });

        document.getElementById('deleteDocumentModal').addEventListener('click', function(e) {
            if (e.target === this) closeDeleteDocumentModal();
        });

        document.getElementById('deleteFolderModal').addEventListener('click', function(e) {
            if (e.target === this) closeDeleteFolderModal();
        });
    </script>
</x-app-layout>
