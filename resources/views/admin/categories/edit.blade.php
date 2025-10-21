<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Edit Category') }}
            </h2>
            <a href="{{ route('admin.categories.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition">
                {{ __('Back to Categories') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @if (session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('admin.categories.update', $category) }}">
                        @csrf
                        @method('PUT')

                        <!-- Name -->
                        <div class="mb-6">
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Category Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   name="name" 
                                   id="name" 
                                   value="{{ old('name', $category->name) }}"
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                   required>
                            @error('name')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Description -->
                        <div class="mb-6">
                            <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Description
                            </label>
                            <textarea name="description" 
                                      id="description" 
                                      rows="3"
                                      class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 focus:ring-blue-500">{{ old('description', $category->description) }}</textarea>
                            @error('description')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Parent Category -->
                        <div class="mb-6">
                            <label for="parent_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Parent Category
                            </label>
                            <select name="parent_id" 
                                    id="parent_id"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 focus:ring-blue-500">
                                <option value="">None (Root Category)</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ old('parent_id', $category->parent_id) == $cat->id ? 'selected' : '' }}>
                                        {{ str_repeat('— ', $cat->ancestors()->count()) }}{{ $cat->name }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Cannot select self or descendants as parent</p>
                            @error('parent_id')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Icon and Color Row -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <!-- Icon -->
                            <div>
                                <label for="icon" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Icon (Emoji)
                                </label>
                                <input type="text" 
                                       name="icon" 
                                       id="icon" 
                                       value="{{ old('icon', $category->icon) }}"
                                       placeholder="📚"
                                       maxlength="10"
                                       class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 focus:ring-blue-500">
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Enter an emoji to represent this category</p>
                                @error('icon')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Color -->
                            <div>
                                <label for="color" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Color
                                </label>
                                <div class="flex items-center space-x-2">
                                    <input type="color" 
                                           name="color" 
                                           id="color" 
                                           value="{{ old('color', $category->color ?? '#3B82F6') }}"
                                           class="h-10 w-20 rounded border-gray-300 dark:border-gray-700">
                                    <input type="text" 
                                           id="color-text"
                                           value="{{ old('color', $category->color ?? '#3B82F6') }}"
                                           readonly
                                           class="flex-1 rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                </div>
                                @error('color')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Icon Picker -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Quick Icon Selection
                            </label>
                            <div class="grid grid-cols-8 gap-2">
                                @foreach(['📚', '💼', '🎓', '💻', '🔧', '🎨', '📊', '🌐', '🔒', '⚡', '🚀', '📱', '🎯', '💡', '🏆', '📈'] as $emoji)
                                    <button type="button" 
                                            onclick="document.getElementById('icon').value = '{{ $emoji }}'"
                                            class="text-2xl p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                        {{ $emoji }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <!-- Color Presets -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Quick Color Selection
                            </label>
                            <div class="flex flex-wrap gap-2">
                                @foreach(['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#06B6D4', '#84CC16'] as $presetColor)
                                    <button type="button" 
                                            onclick="document.getElementById('color').value = '{{ $presetColor }}'; document.getElementById('color-text').value = '{{ $presetColor }}'"
                                            class="w-10 h-10 rounded border-2 border-gray-300 dark:border-gray-600 hover:border-gray-500 transition"
                                            style="background-color: {{ $presetColor }}">
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="flex items-center justify-end space-x-3">
                            <a href="{{ route('admin.categories.index') }}" 
                               class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg transition">
                                Cancel
                            </a>
                            <button type="submit" 
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition">
                                Update Category
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Sync color picker with text input
        document.getElementById('color').addEventListener('input', function(e) {
            document.getElementById('color-text').value = e.target.value;
        });
    </script>
</x-app-layout>
