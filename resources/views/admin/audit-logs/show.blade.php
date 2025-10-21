<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Audit Log Details') }}
            </h2>
            <a href="{{ route('admin.audit-logs.index') }}" class="text-blue-600 hover:text-blue-800">
                ← Back to Audit Logs
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Basic Information -->
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Basic Information</h3>
                            
                            <dl class="space-y-3">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Event Type</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            @if(str_contains($log->event_type, 'failed') || str_contains($log->event_type, 'deleted'))
                                                bg-red-100 text-red-800
                                            @elseif(str_contains($log->event_type, 'created'))
                                                bg-green-100 text-green-800
                                            @elseif(str_contains($log->event_type, 'updated'))
                                                bg-yellow-100 text-yellow-800
                                            @else
                                                bg-blue-100 text-blue-800
                                            @endif">
                                            {{ ucwords(str_replace('_', ' ', $log->event_type)) }}
                                        </span>
                                    </dd>
                                </div>

                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">User</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                        @if($log->user)
                                            {{ $log->user->name }} ({{ $log->user->email }})
                                        @else
                                            System
                                        @endif
                                    </dd>
                                </div>

                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Timestamp</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                        {{ $log->created_at->format('F j, Y g:i:s A') }}
                                    </dd>
                                </div>

                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Description</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                        {{ $log->description }}
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        <!-- Technical Information -->
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Technical Information</h3>
                            
                            <dl class="space-y-3">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">IP Address</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $log->ip_address }}</dd>
                                </div>

                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">User Agent</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 break-all">{{ $log->user_agent }}</dd>
                                </div>

                                @if($log->auditable_type)
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Model Type</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ class_basename($log->auditable_type) }}</dd>
                                    </div>

                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Model ID</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $log->auditable_id }}</dd>
                                    </div>
                                @endif
                            </dl>
                        </div>
                    </div>

                    <!-- Changes -->
                    @if($log->old_values || $log->new_values)
                        <div class="mt-8">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Changes</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                @if($log->old_values)
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Old Values</h4>
                                        <pre class="bg-gray-100 dark:bg-gray-900 p-4 rounded-lg text-xs overflow-x-auto">{{ json_encode($log->old_values, JSON_PRETTY_PRINT) }}</pre>
                                    </div>
                                @endif

                                @if($log->new_values)
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">New Values</h4>
                                        <pre class="bg-gray-100 dark:bg-gray-900 p-4 rounded-lg text-xs overflow-x-auto">{{ json_encode($log->new_values, JSON_PRETTY_PRINT) }}</pre>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Metadata -->
                    @if($log->metadata)
                        <div class="mt-8">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Additional Metadata</h3>
                            <pre class="bg-gray-100 dark:bg-gray-900 p-4 rounded-lg text-xs overflow-x-auto">{{ json_encode($log->metadata, JSON_PRETTY_PRINT) }}</pre>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
