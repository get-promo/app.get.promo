@php
$customizerHidden = 'customizer-hide';
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Logowanie')

@section('page-style')
<link rel="stylesheet" href="{{ asset('assets/vendor/css/pages/page-auth.css') }}" />
@endsection

@section('content')
<div class="container-xxl">
  <div class="authentication-wrapper authentication-basic container-p-y">
    <div class="authentication-inner py-6">
      <!-- Login -->
      <div class="card">
        <div class="card-body">
          <!-- Logo -->
          <div class="app-brand justify-content-center mb-6">
            <a href="{{url('/')}}" class="app-brand-link">
              <span class="app-brand-logo demo">@include('_partials.macros')</span>
            </a>
          </div>
          <!-- /Logo -->
          <h4 class="mb-1">Witaj w {{ config('variables.templateName') }}! 👋</h4>
          <p class="mb-6">Zaloguj się do swojego konta</p>

          @if (session('success'))
            <div class="alert alert-success alert-dismissible" role="alert">
              {{ session('success') }}
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
          @endif

          @if ($errors->any())
            <div class="alert alert-danger alert-dismissible" role="alert">
              <ul class="mb-0">
                @foreach ($errors->all() as $error)
                  <li>{{ $error }}</li>
                @endforeach
              </ul>
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
          @endif

          <form id="formAuthentication" class="mb-4" action="{{ route('login') }}" method="POST">
            @csrf
            <div class="mb-6">
              <label for="email" class="form-label">Email</label>
              <input type="email" class="form-control @error('email') is-invalid @enderror" 
                     id="email" name="email" 
                     placeholder="Wprowadź swój email" 
                     value="{{ old('email') }}"
                     autofocus>
              @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
            <div class="mb-6 form-password-toggle">
              <label class="form-label" for="password">Hasło</label>
              <div class="input-group input-group-merge">
                <input type="password" id="password" 
                       class="form-control @error('password') is-invalid @enderror" 
                       name="password" 
                       placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" 
                       aria-describedby="password" />
                <span class="input-group-text cursor-pointer"><i class="ti ti-eye-off"></i></span>
              </div>
              @error('password')
                <div class="invalid-feedback d-block">{{ $message }}</div>
              @enderror
            </div>
            <div class="my-8">
              <div class="form-check mb-0 ms-2">
                <input class="form-check-input" type="checkbox" id="remember" name="remember">
                <label class="form-check-label" for="remember">
                  Zapamiętaj mnie
                </label>
              </div>
            </div>
            <div class="mb-6">
              <button class="btn btn-primary d-grid w-100" type="submit">Zaloguj się</button>
            </div>
          </form>
        </div>
      </div>
      <!-- /Login -->
    </div>
  </div>
</div>
@endsection
