<!DOCTYPE html>
<html>

<head>
    <base href="/">
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <link rel="icon" href="images/logo-azu_peq-150x150.png" type="image/x-icon">
    <meta name="description" content="@yield('title') - Alicante}}">
    <meta name="keyword" content="CoreUI,Bootstrap,Admin,Template,InfyOm,Open,Source,jQuery,CSS,HTML,RWD,Dashboard">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Bootstrap 5.3.2 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://getbootstrap.com/docs/5.3/assets/css/docs.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>


    @vite(['resources/sass/app.scss', 'resources/js/app.js', 'resources/css/app.css'])

    <link rel="stylesheet" href="/assets/css/font-awesome.css">
    @yield('page_css')
    @yield('css')
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/custom-style.css">
    @vite('resources/css/custom-color.css')
    @vite(['resources/css/login.css'])
    <style>
        :root {
            --primary-color: {{ getColorSetting() }};
            --logo-image: url({{ getImageSetting() }});
        }

        .cssbotonMenuHtml {
            /* margin-left: -250px; */

        }

        @media (max-width: 992px) {
            .ocultar {
                display: none;
            }

            .correr {
                margin-left: -180px!important;
            }
        }

        @media (max-width: 992px) {
            .ocultar {
                display: none;
            }
        .correr {
            margin-left: -180px !important;
        }
        }

        @media (min-width: 993px) {
            .ocultar {
                display: none;
            }
        .correr {
            margin-left: -250px !important;
        }
        }

        /* Estilos para el loader */
        .loader {
            border: 15px solid #f3f3f3;
            border-radius: 50%;
            border-top: 15px solid #3498db;
            width: 150px;
            height: 150px;
            animation: spin 1s linear infinite;
            position: fixed;
            top: 30%;
            left: 50%;
            z-index: 9999;
            display: none;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body class="app header-fixed sidebar-fixed aside-menu-fixed sidebar-lg-show">
    @auth
    <header class="app-header navbar">
        <div class="botonMenuHtml">
            <button class="sidede-c navbar-toggler sidebar-toggler" type="button" onclick="botonMenu()">
                <i class="fa fa-angle-right header-arrow-small" aria-hidden="true"></i>
                <i class="fa fa-chevron-right header-arrow" aria-hidden="true"></i>
            </button>
        </div>
        <ul class="nav navbar-nav ms-auto d-flex">
            <li class="nav-item dropdown notification">

                <div class="mr-5 d-flex justify-content-center">
                    <div class="input-group">
                        <select id="miSelect" class="form-select" onchange="saveSelection()">
                            @foreach (getCityCouncilsForAdmin() as $cityCouncil)
                                <option value="{{ $cityCouncil->id }}">{{ $cityCouncil->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            <li class="nav-item dropdown">
                <a class="nav-link avatar-name" style="margin-right: 10px" data-bs-toggle="dropdown" href="#"
                    role="button" aria-haspopup="true" aria-expanded="false">
                    <span class="profile-name">{{ htmlspecialchars_decode(getLoggedInUser()->name) ?? '' }}</span>
                    <img class="img-avatar"src="{{ getLoggedInUser()->photo_url }}"
                        style="width: 40px; height: 40px; object-fit: cover;" alt="InfyOm">
                </a>
                <div class="dropdown-menu dropdown-menu-right">
                    <div class="dropdown-header text-center dropdown-text header-menu">
                        <strong>{{ __('messages.account') }}</strong>
                    </div>
                    @can('edit_profile')
                        <a class="dropdown-item header-menu-item" href="{{ url('/profile') }}">
                            <i class="fa fa-user"></i>Editar perfil</a>
                    @endcan
                    @if (session('impersonated_by'))
                        <a class="dropdown-item header-menu-item" href="{{ route('impersonate.userLogout') }}">
                            <i class="fa fa-external-link"></i>{{ __('messages.back_to_admin') }}</a>
                    @endif
                    <a class="dropdown-item header-menu-item" class="btn btn-default btn-flat"
                        onclick="event.preventDefault(); localStorage.clear();  document.getElementById('logout-form').submit();">
                        <i class="fa fa-sign-out"></i>{{ __('messages.logout') }}
                    </a>
                    <form id="logout-form" action="{{ url('/logout') }}" method="POST" style="display: none;">
                        {{ csrf_field() }}
                    </form>
                </div>
            </li>
            </li>
        </ul>
    </header>
    @endauth
    <div class="app-body">
        <!-- ... (resto del contenido) ... -->
        @auth
        <div class="contenido_sidebar">
            @include('layouts.sidebar')
        </div>
        @include('layouts.footer')
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                // Obtenemos el enlace del perfil
                var profileLink = document.querySelector('a[href="{{ url('/profile') }}"]');

                // Verificamos si se encontró el enlace
                if (profileLink) {
                    // Obtenemos el ID del usuario logueado
                    var userId = "{{ getLoggedInUser()->id }}";

                    // Modificamos el atributo href del enlace
                    profileLink.href = profileLink.href + '/' + userId;
                }
            });
        </script>
        @endauth
        <div id="loader" class="loader"></div>

        <div class="main px-2 pr-4">
            @yield('content')
        </div>
    </div>
    @auth
    @include('layouts.footer')

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Obtenemos el enlace del perfil
            var profileLink = document.querySelector('a[href="{{ url('/profile') }}"]');

            // Verificamos si se encontró el enlace
            if (profileLink) {
                // Obtenemos el ID del usuario logueado
                var userId = "{{ getLoggedInUser()->id }}";

                // Modificamos el atributo href del enlace
                profileLink.href = profileLink.href + '/' + userId;
            }
        });
    </script>
    @endauth
</body>
@stack('scripts')
@yield('scripts')
<script>
    @auth
    function saveSelection() {
        document.getElementById("loader").style.display = "block";
        var seleccion = document.getElementById("miSelect").value;
        localStorage.setItem("id_city", seleccion);
        saveSelectionIdCity();
        setTimeout(function() {
            document.getElementById("loader").style.display = "none";
            window.location.reload();
        }, 1000);
    }

    window.onload = function() {
        var seleccionGuardada = localStorage.getItem("id_city");
        if (seleccionGuardada !== null) {
            document.getElementById("miSelect").value = seleccionGuardada;
            saveSelectionIdCity();
        } else {
            var miSelect = document.getElementById("miSelect");
            if (miSelect.options.length > 0) {
                document.getElementById("loader").style.display = "block";
                miSelect.value = miSelect.options[0].value;
                localStorage.setItem("id_city", miSelect.value);
                saveSelectionIdCity();
                setTimeout(function() {
                    document.getElementById("loader").style.display = "none";
                    window.location.reload();
                }, 1000);
            }
        }
    };


    function botonMenu() {
        const sidebar = document.querySelector('.contenido_sidebar');
        const app = document.querySelector('#app');
        const botonMenuHtml = document.querySelector('.botonMenuHtml');
        sidebar.classList.toggle('ocultar');
        app.classList.toggle('correr');
        botonMenuHtml.classList.toggle('cssbotonMenuHtml');
    }

    function saveSelectionIdCity() {
        var idCity = localStorage.getItem('id_city');
        if (idCity !== null) {
            $.ajax({
                url: "{{ route('saveSelection') }}",
                type: "POST",
                data: {
                    id_city: idCity,
                    "_token": "{{ csrf_token() }}"
                },
                success: function(response) {},
                error: function(xhr, status, error) {
                    console.error(xhr.responseText);
                }
            });
        }
    }
    @endauth
    @auth
    window.Permissions = {!! json_encode(Auth::user()->allPermissions, true) !!};
    @else
        window.Permissions = [];
    @endauth
</script>
<!-- <script type="text/javascript" src="http://127.0.0.1:8000/chatbot" chatbot-id="6e93c090-0f4e-4b85-a2c1-2fe158677fc4"></script> -->
</html>
