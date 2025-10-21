<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Create Report') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('admin.reports.store') }}" id="reportForm" x-data="reportBuilder()">
                        @csrf

                        <!-- Report Type -->
                        <div class="mb-6">
                            <label for="report_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Report Type *
                            </label>
                            <select name="report_type" id="report_type" x-model="reportType" @change="updateFilters()" required
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                <option value="">Select Report Type</option>
                                @foreach($reportTypes as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('report_type')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Report Name -->
                        <div class="mb-6">
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Report Name
                            </label>
                            <input type="text" name="name" id="name" x-model="name"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                   placeholder="Leave blank for default name">
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Description -->
                        <div class="mb-6">
                            <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Description
                            </label>
                            <textarea name="description" id="description" rows="3"
                                      class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                      placeholder="Optional description"></textarea>
                        </div>

                        <!-- Date Range -->
                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <div>
                                <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Start Date
                                </label>
                                <input type="date" name="start_date" id="start_date" x-model="startDate"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            </div>
                            <div>
                                <label for="end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    End Date
                                </label>
                                <input type="date" name="end_date" id="end_date" x-model="endDate"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            </div>
                        </div>

                        <!-- Dynamic Filters -->
                        <div x-show="showCourseFilter" class="mb-6">
                            <label for="course_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Course (Optional)
                            </label>
                            <select name="course_id" id="course_id"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                <option value="">All Courses</option>
                                @foreach(\App\Models\Course::where('is_published', true)->get() as $course)
                                    <option value="{{ $course->id }}">{{ $course->title }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div x-show="showDepartmentFilter" class="mb-6">
                            <label for="department_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Department (Optional)
                            </label>
                            <select name="department_id" id="department_id"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                <option value="">All Departments</option>
                                @foreach(\App\Models\Department::all() as $department)
                                    <option value="{{ $department->id }}">{{ $department->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div x-show="showCategoryFilter" class="mb-6">
                            <label for="category_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Category (Optional)
                            </label>
                            <select name="category_id" id="category_id"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                <option value="">All Categories</option>
                                @foreach(\App\Models\CourseCategory::all() as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div x-show="showRoleFilter" class="mb-6">
                            <label for="role" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                User Role (Optional)
                            </label>
                            <select name="role" id="role"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                <option value="">All Roles</option>
                                <option value="employee">Employee</option>
                                <option value="instructor">Instructor</option>
                                <option value="hr_admin">HR Admin</option>
                                <option value="super_admin">Super Admin</option>
                            </select>
                        </div>

                        <!-- Export Format -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Export Format *
                            </label>
                            <div class="flex gap-4">
                                <label class="inline-flex items-center">
                                    <input type="radio" name="format" value="pdf" checked
                                           class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                    <span class="ml-2">PDF</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="radio" name="format" value="excel"
                                           class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                    <span class="ml-2">Excel</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="radio" name="format" value="csv"
                                           class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                    <span class="ml-2">CSV</span>
                                </label>
                            </div>
                        </div>

                        <!-- Preview Button -->
                        <div class="mb-6">
                            <button type="button" @click="previewReport()" class="btn btn-secondary">
                                Preview Report
                            </button>
                        </div>

                        <!-- Preview Area -->
                        <div x-show="showPreview" class="mb-6 p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                            <h3 class="text-lg font-semibold mb-4">Preview</h3>
                            <div x-html="previewHtml"></div>
                        </div>

                        <!-- Actions -->
                        <div class="flex justify-end gap-4">
                            <a href="{{ route('admin.reports.index') }}" class="btn btn-secondary">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                Generate Report
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function reportBuilder() {
            return {
                reportType: '',
                name: '',
                startDate: '',
                endDate: '',
                showCourseFilter: false,
                showDepartmentFilter: false,
                showCategoryFilter: false,
                showRoleFilter: false,
                showPreview: false,
                previewHtml: '',

                updateFilters() {
                    // Reset filters
                    this.showCourseFilter = false;
                    this.showDepartmentFilter = false;
                    this.showCategoryFilter = false;
                    this.showRoleFilter = false;

                    // Show relevant filters based on report type
                    switch(this.reportType) {
                        case 'user_activity':
                            this.showDepartmentFilter = true;
                            this.showRoleFilter = true;
                            break;
                        case 'course_completion':
                            this.showCourseFilter = true;
                            this.showCategoryFilter = true;
                            break;
                        case 'department_performance':
                            this.showDepartmentFilter = true;
                            break;
                        case 'assessment_results':
                            this.showCourseFilter = true;
                            break;
                        case 'enrollment_summary':
                            this.showCourseFilter = true;
                            this.showDepartmentFilter = true;
                            break;
                        case 'learning_hours':
                            this.showDepartmentFilter = true;
                            break;
                    }
                },

                async previewReport() {
                    if (!this.reportType) {
                        alert('Please select a report type');
                        return;
                    }

                    const formData = new FormData(document.getElementById('reportForm'));
                    
                    try {
                        const response = await fetch('{{ route("admin.reports.preview") }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                            },
                            body: formData
                        });

                        const result = await response.json();

                        if (result.success) {
                            this.showPreview = true;
                            this.previewHtml = this.formatPreview(result.data);
                        } else {
                            alert('Failed to generate preview: ' + result.message);
                        }
                    } catch (error) {
                        alert('Error generating preview: ' + error.message);
                    }
                },

                formatPreview(data) {
                    let html = '<div class="overflow-x-auto">';
                    
                    // Summary
                    if (data.summary) {
                        html += '<div class="mb-4"><h4 class="font-semibold mb-2">Summary</h4><ul class="list-disc list-inside">';
                        for (const [key, value] of Object.entries(data.summary)) {
                            html += `<li>${key.replace(/_/g, ' ')}: ${value}</li>`;
                        }
                        html += '</ul></div>';
                    }

                    // Data table (first 10 rows)
                    if (data.data && data.data.length > 0) {
                        html += '<table class="min-w-full divide-y divide-gray-200"><thead><tr>';
                        
                        // Headers
                        for (const key of Object.keys(data.data[0])) {
                            html += `<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">${key.replace(/_/g, ' ')}</th>`;
                        }
                        html += '</tr></thead><tbody>';
                        
                        // Rows (limit to 10)
                        for (const row of data.data.slice(0, 10)) {
                            html += '<tr>';
                            for (const value of Object.values(row)) {
                                html += `<td class="px-4 py-2 text-sm">${value}</td>`;
                            }
                            html += '</tr>';
                        }
                        
                        html += '</tbody></table>';
                        
                        if (data.data.length > 10) {
                            html += `<p class="mt-2 text-sm text-gray-500">Showing 10 of ${data.data.length} rows</p>`;
                        }
                    }
                    
                    html += '</div>';
                    return html;
                }
            }
        }
    </script>
    @endpush
</x-app-layout>
