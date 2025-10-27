<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Certificate Details') }}
            </h2>
            <a href="{{ route('admin.certificates.index') }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200">← Back to Certificates</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Certificate Number</div>
                            <div class="text-lg font-semibold text-gray-900 dark:text-white">{{ $certificate->certificate_number }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Issued At</div>
                            <div class="text-lg font-semibold text-gray-900 dark:text-white">{{ optional($certificate->issued_at)->format('M d, Y') ?: '—' }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">User</div>
                            <div class="text-lg font-semibold text-gray-900 dark:text-white">{{ $certificate->user?->name }} <span class="text-sm text-gray-500">{{ $certificate->user?->email }}</span></div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Course</div>
                            <div class="text-lg font-semibold text-gray-900 dark:text-white">{{ $certificate->course?->title }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Expires At</div>
                            <div class="text-lg font-semibold text-gray-900 dark:text-white">{{ optional($certificate->expires_at)->format('M d, Y') ?: 'Never' }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Status</div>
                            <div>
                                @if($certificate->isValid())
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Valid</span>
                                @elseif($certificate->isExpired())
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Expired</span>
                                @else
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Invalid</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center space-x-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <a href="{{ route('certificates.download', $certificate->id) }}" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Download PDF</a>
                        <form method="POST" action="{{ route('admin.certificates.regenerate', $certificate) }}">
                            @csrf
                            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Regenerate PDF</button>
                        </form>
                        <a href="{{ route('admin.certificates.index') }}" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">Back</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
