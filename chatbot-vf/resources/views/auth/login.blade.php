@extends('layouts.auth_layout')
@section('title')
    {{ __('messages.login') }}
@endsection
@section('meta_content')
    - {{ __('messages.login') }} {{ __('messages.to') }}
@endsection
@section('css')
    @endsection
@section('content')

@vite(['resources/sass/login-custom-style.scss'])

<div class="container">
        <div class="row justify-content-center">
            <div class="p-4 account-container w-100">
                <div class="card-group login-group overflow-hidden">
                    <div class="card p-sm-2 mb-0 login-group__card">
                        <div class="card-body login-group__login-body">
                            <form method="POST" action="{{ route('login') }}" class="login-group__form">
                                @csrf
                                <div style="display:flex;justify-content: center">
                                <img alt="Logo login" width="150" style="margin-bottom: 5px" src="<?= imagenLoginSetting() === null ? "images/login.png" : imagenLoginSetting() ?>" />
                                </div>
                                <h1 class="login-group__title mb-2 text-center" style="color:{{ colorLoginSetting() }}!important">{{ tituloLoginSetting() }}</h1>
                                <p class="text-muted login-group__sub-title mb-8 text-center">{{ subtituloLoginSetting() }}</p>
                                <div class="form-group mt-7 mb-4 login-group__sub-title">
                                    <label for="email" class="m-2">{{ __('messages.email') }}</label><span class="red">*</span>
                                    <div class="col-md-12">
                                        <input id="email" type="email" class="form-control @error('email') is-invalid @enderror login-group__input" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>
                                        @error('email')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="form-group mb-4 login-group__sub-title">
                                    <label for="password" class="m-2">{{ __('messages.password') }}</label><span class="red">*</span>
                                    <div class="col-md-12">
                                        <input id="password" type="password" class="form-control @error('password') is-invalid @enderror login-group__input" name="password" required autocomplete="current-password">
                                        @error('password')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="row flex-sm-row flex-column">
                                    <div class="col-12">
                                        <button type="submit" style="background-color:{{ colorLoginSetting() }}!important" class="btn btn-primary w-100 login-group__btn">{{ __('Login') }}</button>
                                    </div>

                                    @if (Route::has('password.request'))
                                        @if (forgotPasswordSetting())
                                        <!-- <label>Debes tener configurado tu correo electrónico para solicitar la recuperaciòn de contraseña</label> -->
                                        <a class="btn btn-link" href="{{ route('password.request') }}">
                                            {{ __('Forgot Your Password?') }}
                                        </a>
                                        @endif
                                    @endif
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
