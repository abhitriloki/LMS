<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Certificates') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <form method="GET" action="{{ route('admin.certificates.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search</label>
                            <input type="text" name="search" value="{{ request('search') }}" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" placeholder="Number, user, or course" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                            <select name="status" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                <option value="">All</option>
                                <option value="valid" {{ request('status')==='valid' ? 'selected' : '' }}>Valid</option>
                                <option value="invalid" {{ request('status')==='invalid' ? 'selected' : '' }}>Invalid</option>
                                <option value="expiring" {{ request('status')==='expiring' ? 'selected' : '' }}>Expiring Soon</option>
                                <option value="expired" {{ request('status')==='expired' ? 'selected' : '' }}>Expired</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">From</label>
                            <input type="date" name="from_date" value="{{ request('from_date') }}" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">To</label>
                            <input type="date" name="to_date" value="{{ request('to_date') }}" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" />
                        </div>
                        <div class="md:col-span-4 flex justify-end space-x-2">
                            <a href="{{ route('admin.certificates.index') }}" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">Clear</a>
                            <button class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Filter</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @if($certificates->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-900">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Certificate #</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">User</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Course</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Issued</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($certificates as $cert)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                            <td class="px-6 py-4"><a href="{{ route('admin.certificates.show', $cert) }}" class="text-blue-600 dark:text-blue-400 hover:underline">{{ $cert->certificate_number }}</a></td>
                                            <td class="px-6 py-4">{{ $cert->user?->name }}</td>
                                            <td class="px-6 py-4">{{ $cert->course?->title }}</td>
                                            <td class="px-6 py-4">{{ optional($cert->issued_at)->format('M d, Y') }}</td>
                                            <td class="px-6 py-4">
                                                @if($cert->isValid())
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Valid</span>
                                                @elseif($cert->isExpired())
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Expired</span>
                                                @else
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Invalid</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4">
                            {{ $certificates->links() }}
                        </div>
                    @else
                        <div class="text-center py-8 text-gray-500 dark:text-gray-400">No certificates found.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
