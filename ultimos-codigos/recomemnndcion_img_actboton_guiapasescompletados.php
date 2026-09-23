// ==========================================
// METABOX INFORMATIVA Y AJUSTES DE INTERFAZ EN EL ADMIN DE EVENTOS
// ==========================================

// 1. Registrar la caja (Meta Box) en la pantalla de edición de eventos de The Events Calendar
add_action( 'add_meta_boxes', 'indgenio_agregar_metabox_instrucciones_pases' );
function indgenio_agregar_metabox_instrucciones_pases() {
    add_meta_box(
        'indgenio_info_pases_completos',                           // ID único de la caja
        'ℹ️ Guía rápida: Configuración de Pases Ocupados / Lista de Espera', // Título visible
        'indgenio_renderizar_contenido_metabox',                 // Función que dibuja el contenido
        'tribe_events',                                          // Post type (The Events Calendar)
        'normal',                                                // Posición ('normal' para que se pueda mover libremente)
        'high'                                                   // Prioridad alta para que aparezca arriba
    );
}

// 2. Contenido HTML explicativo con el formato estricto de dos dígitos en mes, día y hora
function indgenio_renderizar_contenido_metabox( $post ) {
    ?>
    <div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 13px; line-height: 1.5; color: #1d2327; padding: 4px;">
        <p style="margin-top: 0; font-weight: 600; color: #0f766e;">
            Instrucciones para marcar pases llenos y activar la Lista de Espera automática:
        </p>
        <p style="margin-bottom: 10px;">
            Puedes indicar qué pases se encuentran completos añadiendo una etiqueta especial en cualquier lugar de la descripción del evento. <strong>Este texto técnico es procesado por el sistema y no se mostrará de forma visible en la web pública.</strong>
        </p>
        
        <div style="background: #f0fdf4; border: 1px solid #bbf7d0; padding: 10px 12px; border-radius: 6px; margin-bottom: 12px;">
            <strong style="color: #166534; display: block; margin-bottom: 4px;">Sintaxis exacta obligatoria (2 dígitos en mes, día y hora):</strong>
            <code style="background: #dcfce7; color: #166534; padding: 2px 6px; border-radius: 4px; font-size: 12px;">[completado: MM/DD/YYYY HH:MM]</code>
        </div>

        <p style="margin-bottom: 6px; font-weight: 600;">Regla importante y ejemplos prácticos:</p>
        <ul style="margin: 0 0 12px 20px; padding: 0; list-style-type: disc;">
            <li style="margin-bottom: 4px;"><strong>¡Añade siempre ceros a la izquierda!</strong> Si el mes, el día o la hora son menores a 10 (como enero, los primeros días del mes o las 8 de la mañana), debes poner un 0 delante (ej: <code style="background: #f1f5f9; padding: 2px 4px; border-radius: 3px;">01</code> para enero o el día 3, y <code style="background: #f1f5f9; padding: 2px 4px; border-radius: 3px;">08:00</code> para las ocho).</li>
            <li style="margin-bottom: 4px;">Ejemplo con varios pases: <code style="background: #f1f5f9; padding: 2px 4px; border-radius: 3px;">[completado: 11/30/2026 08:00, 12/01/2026 09:00, 03/04/2026 10:00]</code></li>
        </ul>

        <div style="background: #fffbeb; border-left: 3px solid #d97706; padding: 8px 10px; font-size: 12px; color: #92400e; border-radius: 2px;">
            <strong>Nota importante:</strong> Asegúrate de que la fecha y hora coincidan exactamente con las configuradas en el evento.
        </div>
    </div>
    <?php
}

// 3. Script de JavaScript para la imagen destacada Y el aviso de actualizar 3 veces
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
                aviso.style.cssText = "background: #fff5f5; border-left: 3px solid #b91c1c; padding: 10px 12px; margin: 12px 12px 12px 12px; font-size: 12px; color: #991b1b; font-weight: 700; line-height: 1.4; border-radius: 4px;";
                aviso.innerHTML = "⚠️ Recuerda pulsar 3 veces el botón de actualizar al hacer cambios en el evento.";
                
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