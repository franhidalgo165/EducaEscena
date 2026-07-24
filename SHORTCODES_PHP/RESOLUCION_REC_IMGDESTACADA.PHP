function cambiar_texto_imagen_por_js() {
    ?>
    <script type="text/javascript">
        document.addEventListener("DOMContentLoaded", function() {
            // Buscamos todos los párrafos con la clase 'howto' dentro del panel
            const textosAyuda = document.querySelectorAll("p.howto");
            textosAyuda.forEach(function(el) {
                if (el.textContent.includes("16:9")) {
                    el.textContent = "Recomendamos una resolución exacta de 580x320 píxeles para las imágenes destacadas.";
                }
            });
        });
    </script>
    <?php
}
add_action( 'admin_footer', 'cambiar_texto_imagen_por_js' );