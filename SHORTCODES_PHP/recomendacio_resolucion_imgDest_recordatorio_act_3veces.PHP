function indgenio_personalizar_textos_admin_js() {
    ?>
    <script type="text/javascript">
        document.addEventListener("DOMContentLoaded", function() {
            // 1. Cambiar texto de resolución de la imagen destacada
            const textosAyuda = document.querySelectorAll("p.howto");
            textosAyuda.forEach(function(el) {
                if (el.textContent.includes("16:9")) {
                    el.textContent = "Recomendamos una resolución exacta de 580x320 píxeles para las imágenes destacadas.";
                }
            });

            // 2. Añadir aviso limpio con margen superior e inferior
            const submitBox = document.querySelector("#submitpost");
            if (submitBox && !document.querySelector("#aviso-actualizar-extra")) {
                const aviso = document.createElement("div");
                aviso.id = "aviso-actualizar-extra";
                // Añadido margin-bottom de 12px para separarlo de la línea de abajo
                aviso.style.cssText = "background: #fff5f5; border-left: 3px solid #b91c1c; padding: 10px 12px; margin: 12px 12px 12px 12px; font-size: 12px; color: #991b1b; font-weight: 700; line-height: 1.4; border-radius: 4px;";
                aviso.innerHTML = "⚠️ Recuerda pulsar 3 veces el botón de actualizar al hacer cambios en el evento.";
                
                // Lo colocamos justo antes de la caja de acciones inferiores
                const majorActions = document.querySelector("#major-publishing-actions");
                if (majorActions) {
                    submitBox.insertBefore(aviso, majorActions);
                }
            }
        });
    </script>
    <?php
}
add_action( 'admin_footer', 'indgenio_personalizar_textos_admin_js' );