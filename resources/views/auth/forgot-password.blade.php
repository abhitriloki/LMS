@extends('layouts.guest')

@section('title', 'Forgot Password')

@section('content')
<div>
    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Forgot your password?</h2>
    <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
        No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.
    </p>

    @if (session('success'))
        <div class="mb-4 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
            <p class="text-sm text-green-600 dark:text-green-400">{{ session('success') }}</p>
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="space-y-6">
        @csrf

        <!-- Email Address -->
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Email address
            </label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                   class="input @error('email') border-red-500 @enderror">
            @error('email')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <!-- Submit Button -->
        <div>
            <button type="submit" class="w-full btn btn-primary">
                Email Password Reset Link
            </button>
        </div>

        <!-- Back to Login Link -->
        <div class="text-center text-sm">
            <a href="{{ route('login') }}" class="font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400">
                Back to login
            </a>
        </div>
    </form>
</div>
@endsection
