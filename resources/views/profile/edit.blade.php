@extends('layouts.dashboard')

@section('title', 'Profile Settings')

@section('page-title', 'Profile Settings')

@section('content')
<div class="max-w-4xl">
    @if (session('success'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif

    <div class="card mb-6">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Profile Information</h3>
        
        <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PATCH')

            <!-- Avatar -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Profile Picture
                </label>
                <div class="flex items-center gap-4">
                    @if($user->avatar)
                        <img src="{{ Storage::url($user->avatar) }}" alt="{{ $user->name }}" class="h-20 w-20 rounded-full object-cover">
                    @else
                        <div class="h-20 w-20 rounded-full bg-blue-600 flex items-center justify-center text-white text-2xl font-semibold">
                            {{ substr($user->name, 0, 1) }}
                        </div>
                    @endif
                    <div class="flex-1">
                        <input type="file" name="avatar" id="avatar" accept="image/*"
                            class="@error('avatar') border-red-500 @enderror"
                            onchange="previewAvatar(event)">
                        @error('avatar')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">JPG, PNG or GIF (max 2MB)</p>
                    </div>
                </div>
                <div id="avatar-preview" class="mt-2 hidden">
                    <img src="" alt="Avatar preview" class="h-20 w-20 rounded-full object-cover">
                </div>
            </div>

            <!-- Name -->
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Name
                </label>
                <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" required
                       class="input @error('name') border-red-500 @enderror">
                @error('name')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Email -->
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Email
                </label>
                <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required
                       class="input @error('email') border-red-500 @enderror">
                @error('email')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Phone -->
            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Phone
                </label>
                <input id="phone" type="text" name="phone" value="{{ old('phone', $user->phone) }}"
                       class="input @error('phone') border-red-500 @enderror">
                @error('phone')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Position -->
            <div>
                <label for="position" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Position
                </label>
                <input id="position" type="text" name="position" value="{{ old('position', $user->position) }}"
                       class="input @error('position') border-red-500 @enderror">
                @error('position')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Bio -->
            <div>
                <label for="bio" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Bio
                </label>
                <textarea id="bio" name="bio" rows="4"
                       class="input @error('bio') border-red-500 @enderror">{{ old('bio', $user->bio) }}</textarea>
                @error('bio')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end">
                <button type="submit" class="btn btn-primary">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function previewAvatar(event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.getElementById('avatar-preview');
                const img = preview.querySelector('img');
                img.src = e.target.result;
                preview.classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        }
    }
</script>
@endsection
