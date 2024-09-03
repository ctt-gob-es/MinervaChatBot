@vite(['resources/sass/custom-style.scss'])
<div class="sidebar">
    <nav class="sidebar-nav">
        <div class="d-flex align-items-center justify-content-between mb-3 overflow-hidden sidebar-inner-header w-100">
            <?php
            $imageSrc = getImageSetting(); // Obtener el origen de la imagen

            // Verificar si la imagen tiene un origen válido
            if ($imageSrc !== null) {
                // Mostrar la imagen solo si el origen es válido
                echo '<a class="navbar-brand d-flex align-items-center w-100 mr-1 ml-1"><img alt="Logo headers" class="logo-ayuntamiento" src="' . $imageSrc . '" /></a>';
            } else {
                echo '<a class="navbar-brand d-flex align-items-center w-100 logo-ayunta"></a>';
            }
            ?>
        </div>
        <ul class="nav">
            @include('layouts.menu')
        </ul>
    </nav>
</div>
