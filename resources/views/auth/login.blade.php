@extends('layouts.guest')

@section('title', 'Login')

@section('content')
<div>
    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Sign in to your account</h2>

    <form method="POST" action="{{ route('login') }}" class="space-y-6">
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

        <!-- Remember Me -->
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <input id="remember" type="checkbox" name="remember"
                       class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                <label for="remember" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                    Remember me
                </label>
            </div>

            <div class="text-sm">
                <a href="{{ route('password.request') }}" class="font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400">
                    Forgot password?
                </a>
            </div>
        </div>

        <!-- Submit Button -->
        <div>
            <button type="submit" class="w-full btn btn-primary">
                Sign in
            </button>
        </div>

        <!-- Register Link -->
        <div class="text-center text-sm">
            <span class="text-gray-600 dark:text-gray-400">Don't have an account?</span>
            <a href="{{ route('register') }}" class="font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400">
                Register here
            </a>
        </div>
    </form>
</div>
@endsection
