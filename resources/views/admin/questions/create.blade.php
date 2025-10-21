<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Add Question to {{ $assessment->title }}
            </h2>
            <a href="{{ route('admin.assessments.show', $assessment) }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200">
                ← Back to Assessment
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('admin.questions.store', $assessment) }}" x-data="questionForm()">
                @csrf

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 space-y-6">
                        <!-- Question Type -->
                        <div>
                            <label for="question_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Question Type <span class="text-red-500">*</span>
                            </label>
                            <select name="question_type" id="question_type" x-model="questionType" required
                                class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                <option value="multiple_choice">Multiple Choice</option>
                                <option value="true_false">True/False</option>
                                <option value="fill_in_blank">Fill in the Blank</option>
                                <option value="essay">Essay</option>
                                <option value="matching">Matching</option>
                                <option value="drag_drop">Drag and Drop</option>
                            </select>
                            @error('question_type')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Question Text -->
                        <div>
                            <label for="question_text" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Question <span class="text-red-500">*</span>
                            </label>
                            <textarea name="question_text" id="question_text" rows="4" required
                                class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">{{ old('question_text') }}</textarea>
                            @error('question_text')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Points -->
                        <div>
                            <label for="points" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Points <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="points" id="points" value="{{ old('points', 1) }}" 
                                min="0" step="0.5" required
                                class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                            @error('points')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Options (for multiple choice and true/false) -->
                        <div x-show="questionType === 'multiple_choice' || questionType === 'true_false'">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Answer Options <span class="text-red-500">*</span>
                            </label>
                            
                            <div class="space-y-3">
                                <template x-for="(option, index) in options" :key="index">
                                    <div class="flex items-start space-x-2">
                                        <input type="checkbox" 
                                            :name="'options[' + index + '][is_correct]'" 
                                            value="1"
                                            class="mt-1 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <input type="text" 
                                            :name="'options[' + index + '][option_text]'" 
                                            x-model="option.text"
                                            placeholder="Option text"
                                            class="flex-1 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                        <button type="button" @click="removeOption(index)" 
                                            class="text-red-600 hover:text-red-900 dark:text-red-400">
                                            Remove
                                        </button>
                                    </div>
                                </template>
                            </div>

                            <button type="button" @click="addOption" 
                                class="mt-3 text-blue-600 hover:text-blue-900 dark:text-blue-400">
                                + Add Option
                            </button>
                        </div>

                        <!-- Explanation -->
                        <div>
                            <label for="explanation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Explanation (shown after submission)
                            </label>
                            <textarea name="explanation" id="explanation" rows="3"
                                class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">{{ old('explanation') }}</textarea>
                            @error('explanation')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Submit Buttons -->
                        <div class="flex justify-end space-x-3">
                            <a href="{{ route('admin.assessments.show', $assessment) }}" 
                                class="bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600">
                                Cancel
                            </a>
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                                Add Question
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        function questionForm() {
            return {
                questionType: 'multiple_choice',
                options: [
                    { text: '' },
                    { text: '' }
                ],
                addOption() {
                    this.options.push({ text: '' });
                },
                removeOption(index) {
                    if (this.options.length > 2) {
                        this.options.splice(index, 1);
                    }
                }
            }
        }
    </script>
</x-app-layout>
