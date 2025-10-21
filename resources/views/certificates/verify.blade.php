<x-guest-layout>
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto">
            <div class="text-center mb-8">
                <h2 class="text-3xl font-extrabold text-gray-900 dark:text-gray-100">
                    Certificate Verification
                </h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    Verify the authenticity of a certificate
                </p>
            </div>

            @if ($verified === null)
                <!-- Verification Form -->
                <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <form action="{{ route('certificates.verify.post') }}" method="POST">
                            @csrf
                            <div>
                                <label for="certificate_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Certificate Number
                                </label>
                                <div class="mt-1">
                                    <input type="text" name="certificate_number" id="certificate_number" 
                                           class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100" 
                                           placeholder="CERT-XXXXXXXX-YYYY-ZZZZ"
                                           required>
                                </div>
                                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                    Enter the certificate number to verify its authenticity
                                </p>
                            </div>

                            <div class="mt-5">
                                <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Verify Certificate
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @elseif ($verified === true)
                <!-- Valid Certificate -->
                <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <div class="flex items-center justify-center mb-6">
                            <div class="flex-shrink-0">
                                <svg class="h-16 w-16 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>

                        <h3 class="text-center text-2xl font-bold text-green-600 dark:text-green-400 mb-6">
                            Certificate Verified
                        </h3>

                        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                            <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Certificate Number</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 font-mono">{{ $certificate->certificate_number }}</dd>
                                </div>

                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
                                    <dd class="mt-1">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Valid
                                        </span>
                                    </dd>
                                </div>

                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Recipient Name</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $certificate->user->name }}</dd>
                                </div>

                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Email</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $certificate->user->email }}</dd>
                                </div>

                                <div class="sm:col-span-2">
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Course Title</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $certificate->course->title }}</dd>
                                </div>

                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Issue Date</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $certificate->issued_at->format('F d, Y') }}</dd>
                                </div>

                                @if ($certificate->expires_at)
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Expiry Date</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $certificate->expires_at->format('F d, Y') }}</dd>
                                    </div>
                                @else
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Expiry Date</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">Never</dd>
                                    </div>
                                @endif
                            </dl>
                        </div>

                        <div class="mt-6 text-center">
                            <a href="{{ route('certificates.verify') }}" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400">
                                Verify Another Certificate
                            </a>
                        </div>
                    </div>
                </div>
            @else
                <!-- Invalid Certificate -->
                <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <div class="flex items-center justify-center mb-6">
                            <div class="flex-shrink-0">
                                <svg class="h-16 w-16 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>

                        <h3 class="text-center text-2xl font-bold text-red-600 dark:text-red-400 mb-4">
                            Certificate Not Found
                        </h3>

                        <p class="text-center text-sm text-gray-600 dark:text-gray-400 mb-6">
                            {{ $message ?? 'The certificate number you entered could not be verified. Please check the number and try again.' }}
                        </p>

                        <div class="mt-6 text-center">
                            <a href="{{ route('certificates.verify') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                Try Again
                            </a>
                        </div>
                    </div>
                </div>
            @endif

            <div class="mt-8 bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                            About Certificate Verification
                        </h3>
                        <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                            <p>
                                This verification system allows you to confirm the authenticity of certificates issued by our platform. 
                                Each certificate has a unique number that can be verified at any time.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
