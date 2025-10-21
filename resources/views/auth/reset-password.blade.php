@extends('layouts.guest')

@section('title', 'Reset Password')

@section('content')
<div>
    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Reset your password</h2>

    <form method="POST" action="{{ route('password.store') }}" class="space-y-6">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <!-- Email Address -->
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Email address
            </label>
            <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus
                   class="input @error('email') border-red-500 @enderror">
            @error('email')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <!-- Password -->
        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                New Password
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
                Reset Password
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
