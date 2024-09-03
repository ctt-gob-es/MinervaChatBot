<footer class="app-footer">
    <div class="d-flex justify-content-center w-100">
        <div class="d-flex justify-content-center align-items-center">
            <?php
            $imageSrc = getImageSetting(); // Obtener el origen de la imagen

            // Verificar si la imagen tiene un origen válido
            if ($imageSrc !== null) {
                // Mostrar la imagen solo si el origen es válido
                echo '<img alt="logo footer" class="logo-ayuntamiento-footer" src="' . $imageSrc . '" />';
            }
            ?>
            <span>{{ getFooterData()}}</span>
        </div>
    </div>
</footer>
