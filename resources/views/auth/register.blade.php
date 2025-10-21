@extends('layouts.guest')

@section('title', 'Register')

@section('content')
<div>
    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Create your account</h2>

    <form method="POST" action="{{ route('register') }}" class="space-y-6">
        @csrf

        <!-- Name -->
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Full Name
            </label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus
                   class="input @error('name') border-red-500 @enderror">
            @error('name')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <!-- Email Address -->
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Email address
            </label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required
                   class="input @error('email') border-red-500 @enderror">
            @error('email')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <!-- Department -->
        <div>
            <label for="department_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Department (Optional)
            </label>
            <select id="department_id" name="department_id"
                    class="input @error('department_id') border-red-500 @enderror">
                <option value="">Select a department</option>
                @foreach(\App\Models\Department::all() as $department)
                    <option value="{{ $department->id }}" {{ old('department_id') == $department->id ? 'selected' : '' }}>
                        {{ $department->name }}
                    </option>
                @endforeach
            </select>
            @error('department_id')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <!-- Position -->
        <div>
            <label for="position" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Position (Optional)
            </label>
            <input id="position" type="text" name="position" value="{{ old('position') }}"
                   class="input @error('position') border-red-500 @enderror">
            @error('position')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <!-- Phone -->
        <div>
            <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Phone Number (Optional)
            </label>
            <input id="phone" type="tel" name="phone" value="{{ old('phone') }}"
                   class="input @error('phone') border-red-500 @enderror">
            @error('phone')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <!-- Password -->
        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Password
            </label>
            <input id="password" type="password" name="password" required
                   class="input @error('password') border-red-500 @enderror">
            @error('password')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <!-- Confirm Password -->
        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Confirm Password
            </label>
            <input id="password_confirmation" type="password" name="password_confirmation" required
                   class="input">
        </div>

        <!-- Submit Button -->
        <div>
            <button type="submit" class="w-full btn btn-primary">
                Create Account
            </button>
        </div>

        <!-- Login Link -->
        <div class="text-center text-sm">
            <span class="text-gray-600 dark:text-gray-400">Already have an account?</span>
            <a href="{{ route('login') }}" class="font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400">
                Sign in here
            </a>
        </div>
    </form>
</div>
@endsection
