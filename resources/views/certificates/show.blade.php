<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Certificate Details') }}
            </h2>
            <a href="{{ route('certificates.index') }}" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400">
                ← Back to Certificates
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            {{ $certificate->course->title }}
                        </h3>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $certificate->isValid() ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $certificate->isValid() ? 'Valid' : 'Expired' }}
                        </span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Certificate Information</h4>
                            <dl class="space-y-2">
                                <div>
                                    <dt class="text-sm text-gray-600 dark:text-gray-400">Certificate Number</dt>
                                    <dd class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $certificate->certificate_number }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm text-gray-600 dark:text-gray-400">Recipient</dt>
                                    <dd class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $certificate->user->name }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm text-gray-600 dark:text-gray-400">Email</dt>
                                    <dd class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $certificate->user->email }}</dd>
                                </div>
                            </dl>
                        </div>

                        <div>
                            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Course Details</h4>
                            <dl class="space-y-2">
                                <div>
                                    <dt class="text-sm text-gray-600 dark:text-gray-400">Issued Date</dt>
                                    <dd class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $certificate->issued_at->format('F d, Y') }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm text-gray-600 dark:text-gray-400">Completion Date</dt>
                                    <dd class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $certificate->enrollment->completion_date?->format('F d, Y') ?? 'N/A' }}</dd>
                                </div>
                                @if ($certificate->enrollment->final_score)
                                    <div>
                                        <dt class="text-sm text-gray-600 dark:text-gray-400">Final Score</dt>
                                        <dd class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ number_format($certificate->enrollment->final_score, 2) }}%</dd>
                                    </div>
                                @endif
                                @if ($certificate->expires_at)
                                    <div>
                                        <dt class="text-sm text-gray-600 dark:text-gray-400">Expiry Date</dt>
                                        <dd class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $certificate->expires_at->format('F d, Y') }}</dd>
                                    </div>
                                @endif
                            </dl>
                        </div>
                    </div>

                    @if ($certificate->qr_code_path)
                        <div class="mb-6">
                            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Verification QR Code</h4>
                            <img src="{{ Storage::url($certificate->qr_code_path) }}" alt="QR Code" class="w-32 h-32 border border-gray-300 rounded">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Scan to verify certificate authenticity</p>
                        </div>
                    @endif

                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('certificates.download', $certificate) }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            Download PDF
                        </a>

                        <form action="{{ route('certificates.email', $certificate) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-600">
                                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                Email Certificate
                            </button>
                        </form>

                        <a href="{{ route('certificates.verify', $certificate->certificate_number) }}" target="_blank" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-600">
                            <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Verify Certificate
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
