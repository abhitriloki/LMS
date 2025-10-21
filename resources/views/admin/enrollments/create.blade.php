<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Bulk Enroll Users') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form action="{{ route('admin.enrollments.store') }}" method="POST" x-data="{ targetType: '{{ old('target_type', 'users') }}' }">
                        @csrf

                        <!-- Course Selection -->
                        <div class="mb-6">
                            <label for="course_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Course <span class="text-red-500">*</span>
                            </label>
                            <select name="course_id" id="course_id" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Select a course</option>
                                @foreach($courses as $courseOption)
                                    <option value="{{ $courseOption->id }}" {{ (old('course_id', $course?->id) == $courseOption->id) ? 'selected' : '' }}>
                                        {{ $courseOption->title }}
                                    </option>
                                @endforeach
                            </select>
                            @error('course_id')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Enrollment Type -->
                        <div class="mb-6">
                            <label for="enrollment_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Enrollment Type <span class="text-red-500">*</span>
                            </label>
                            <select name="enrollment_type" id="enrollment_type" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="assigned" {{ old('enrollment_type') == 'assigned' ? 'selected' : '' }}>Assigned</option>
                                <option value="mandatory" {{ old('enrollment_type') == 'mandatory' ? 'selected' : '' }}>Mandatory</option>
                            </select>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Mandatory enrollments cannot be unenrolled by users.
                            </p>
                            @error('enrollment_type')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Target Type Selection -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Enroll <span class="text-red-500">*</span>
                            </label>
                            <div class="space-y-2">
                                <label class="inline-flex items-center">
                                    <input type="radio" name="target_type" value="users" x-model="targetType" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50" {{ old('target_type', 'users') == 'users' ? 'checked' : '' }}>
                                    <span class="ml-2 text-gray-700 dark:text-gray-300">Specific Users</span>
                                </label>
                                <label class="inline-flex items-center ml-6">
                                    <input type="radio" name="target_type" value="department" x-model="targetType" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50" {{ old('target_type') == 'department' ? 'checked' : '' }}>
                                    <span class="ml-2 text-gray-700 dark:text-gray-300">Entire Department</span>
                                </label>
                            </div>
                            @error('target_type')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- User Selection -->
                        <div class="mb-6" x-show="targetType === 'users'">
                            <label for="user_ids" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Select Users <span class="text-red-500">*</span>
                            </label>
                            <select name="user_ids[]" id="user_ids" multiple size="10" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ in_array($user->id, old('user_ids', [])) ? 'selected' : '' }}>
                                        {{ $user->name }} ({{ $user->email }}) - {{ $user->department->name ?? 'No Department' }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Hold Ctrl (Cmd on Mac) to select multiple users.
                            </p>
                            @error('user_ids')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Department Selection -->
                        <div class="mb-6" x-show="targetType === 'department'">
                            <label for="department_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Select Department <span class="text-red-500">*</span>
                            </label>
                            <select name="department_id" id="department_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Select a department</option>
                                @foreach($departments as $department)
                                    <option value="{{ $department->id }}" {{ old('department_id') == $department->id ? 'selected' : '' }}>
                                        {{ $department->name }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                All users in this department will be enrolled.
                            </p>
                            @error('department_id')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Deadline -->
                        <div class="mb-6">
                            <label for="deadline" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Deadline (Optional)
                            </label>
                            <input type="date" name="deadline" id="deadline" value="{{ old('deadline') }}" min="{{ date('Y-m-d', strtotime('+1 day')) }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Leave empty for no deadline.
                            </p>
                            @error('deadline')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end space-x-3 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <a href="{{ route('admin.enrollments.index') }}" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                                Cancel
                            </a>
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Enroll Users
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
