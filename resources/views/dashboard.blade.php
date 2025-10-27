@extends('layouts.dashboard')

@section('title', 'Dashboard')

@section('page-title', 'Dashboard')

@section('content')
@if(Auth::user()->role === 'super_admin' || Auth::user()->role === 'admin')
    @include('dashboard.admin')
@elseif(Auth::user()->role === 'instructor')
    @include('dashboard.instructor')
@else
    @include('dashboard.employee')
@endif
@endsection
