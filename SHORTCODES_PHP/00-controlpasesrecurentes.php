
/**
 * Control de Pases Individuales - The Events Calendar (V4 Final Sync)
 */

// 1. Inyectar el selector y sincronizar su valor real con la BD en el admin
add_action( 'admin_footer', 'indgenio_admin_pases_v4' );
function indgenio_admin_pases_v4() {
    $screen = get_current_screen();
    if ( ! $screen || $screen->post_type !== 'tribe_events' ) {
        return;
    }

    global $post;
    $estados_guardados = $post ? get_post_meta( $post->ID, '_estado_pases_custom', true ) : array();
    if ( ! is_array( $estados_guardados ) ) {
        $estados_guardados = array();
    }
    ?>
    <script type="text/javascript">
    document.addEventListener("DOMContentLoaded", function() {
        var estadosGuardados = <?php echo json_encode( $estados_guardados ); ?>;

        function crearSelectoresPases() {
            // Evento principal superior
            var inputFin = document.querySelector('input[name*="EventEndDate"]');
            if (inputFin) {
                var contenedor = inputFin.closest('div[class*="field"], .inside, div');
                if (contenedor && !contenedor.querySelector('.estado-pase-principal-v4')) {
                    var wrap = document.createElement('div');
                    wrap.className = 'estado-pase-principal-v4';
                    wrap.style.cssText = "display: block; margin-top: 8px; clear: both;";
                    
                    var valPrinc = (estadosGuardados['0'] !== undefined) ? estadosGuardados['0'] : 'disponible';

                    wrap.innerHTML = '<div style="display: inline-flex; align-items: center; gap: 6px; background: #f8fafc; padding: 4px 10px; border-radius: 4px; border: 1px solid #cbd5e1;"><span style="font-size: 11px; font-weight: 700; color: #475569; font-family: \'Raleway\', sans-serif;">Estado del pase:</span><select name="estado_pase_custom[0]" style="font-size: 11px; border-radius: 4px; border-color: #cbd5e1; padding: 2px 6px; height: 26px; background: #fff;"><option value="disponible" ' + (valPrinc === 'disponible' ? 'selected' : '') + '>🟢 Disponible</option><option value="completo" ' + (valPrinc === 'completo' ? 'selected' : '') + '>🔴 Completo (Lista espera)</option></select></div>';
                    
                    inputFin.parentNode.insertBefore(wrap, inputFin.nextSibling);
                }
            }

            // Eventos recurrentes inferiores
            var filas = document.querySelectorAll('.tribe-datetime-wrapper, .tribe-recurrence-rule, div[class*="recurrence"], .event-schedule-details, .inside');
            
            for (var i = 0; i < filas.length; i++) {
                var fila = filas[i];
                if ((fila.innerText.indexOf('Desde') !== -1 || fila.querySelector('input[name*="start"]')) && !fila.querySelector('.estado-pase-recurrente-v4')) {
                    
                    var elementosHora = fila.querySelectorAll('input[name*="end"], select[name*="end"], select[name*="recurrence"]');
                    var anclaje = elementosHora[elementosHora.length - 1] || fila.querySelector('input[type="text"]');
                    
                    if (anclaje) {
                        var wrapper = document.createElement('div');
                        wrapper.className = 'estado-pase-recurrente-v4';
                        wrapper.style.cssText = "display: block; margin-top: 8px; clear: both;";
                        
                        var keyRec = 'rec_' + (i + 1);
                        var valRec = (estadosGuardados[keyRec] !== undefined) ? estadosGuardados[keyRec] : 'disponible';

                        wrapper.innerHTML = '<div style="display: inline-flex; align-items: center; gap: 6px; background: #f1f5f9; padding: 4px 10px; border-radius: 4px; border: 1px solid #cbd5e1;"><span style="font-size: 11px; font-weight: 700; color: #475569; font-family: \'Raleway\', sans-serif;">Estado del pase:</span><select name="estado_pase_custom[' + keyRec + ']" style="font-size: 11px; border-radius: 4px; border-color: #cbd5e1; padding: 2px 6px; height: 26px; background: #fff;"><option value="disponible" ' + (valRec === 'disponible' ? 'selected' : '') + '>🟢 Disponible</option><option value="completo" ' + (valRec === 'completo' ? 'selected' : '') + '>🔴 Completo (Lista espera)</option></select></div>';
                        
                        anclaje.parentNode.insertBefore(wrapper, anclaje.nextSibling);
                    }
                }
            }
        }

        // Ejecuciones múltiples para capturar la carga asíncrona completa de TEC
        setTimeout(crearSelectoresPases, 300);
        setTimeout(crearSelectoresPases, 1000);
        setTimeout(crearSelectoresPases, 2500);

        document.body.addEventListener('click', function() {
            setTimeout(crearSelectoresPases, 300);
        });
    });
    </script>
    <?php
}

// 2. Guardar con seguridad los estados individuales en el post_meta
add_action( 'save_post_tribe_events', 'indgenio_guardar_pases_v4', 10, 2 );
function indgenio_guardar_pases_v4( $post_id, $post ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    if ( isset( $_POST['estado_pase_custom'] ) && is_array( $_POST['estado_pase_custom'] ) ) {
        $limpio = array();
        foreach ( $_POST['estado_pase_custom'] as $k => $v ) {
            $limpio[ sanitize_key( $k ) ] = sanitize_text_field( $v );
        }
        update_post_meta( $post_id, '_estado_pases_custom', $limpio );
    }
}

// 3. Estilos públicos para el texto en rojo suave de los pases completos
add_action( 'wp_head', 'indgenio_estilos_pases_v4' );
function indgenio_estilos_pases_v4() {
    echo '<style>
        .app-hora-enlace.pase-completo {
            color: #c53030 !important;
            background-color: #fff5f5;
            padding: 2px 6px;
            border-radius: 4px;
            border: 1px solid #fed7d7;
        }
        .app-hora-enlace.pase-completo:hover {
            color: #9b2c2c !important;
            text-decoration: underline !important;
        }
    </style>';
}