/**
 * Inyector Global de Pases por Posición Visual (Independiente)
 */
add_action( 'wp_footer', 'indgenio_inyector_global_por_posicion' );
function indgenio_inyector_global_por_posicion() {
    // 1. Recopilamos todos los eventos padre y sus metadatos de estado del admin
    $args = array(
        'post_type'      => 'tribe_events',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'post_parent'    => 0 // Solo eventos principales/padres
    );
    $query = new WP_Query( $args );
    $mapa_estados_padres = array();

    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            $id = get_the_ID();
            $estados = get_post_meta( $id, '_estado_pases_custom', true );
            
            if ( ! empty( $estados ) && is_array( $estados ) ) {
                // Guardamos el mapa indexado por el título limpio del evento y también por su ID
                $titulo_key = sanitize_title( get_the_title() );
                $mapa_estados_padres[ $titulo_key ] = $estados;
                $mapa_estados_padres[ 'id_' . $id ] = $estados;
            }
        }
        wp_reset_postdata();
    }
    ?>
    <style>
        .app-hora-enlace.pase-completo,
        .pase-completo {
            color: #c53030 !important;
            background-color: #fff5f5 !important;
            padding: 2px 6px !important;
            border-radius: 4px !important;
            border: 1px solid #fed7d7 !important;
            display: inline-block !important;
        }
    </style>

    <script type="text/javascript">
    document.addEventListener("DOMContentLoaded", function() {
        var mapaPadres = <?php echo json_encode( $mapa_estados_padres ); ?>;

        // Buscamos todas las tarjetas de eventos de la programación o sedes
        var tarjetas = document.querySelectorAll('.app-card-evento, article.type-tribe_events, div[class*="evento"]');

        tarjetas.forEach(function(tarjeta) {
            // Buscamos el título de la tarjeta para asociarlo con el evento padre
            var tituloElemento = tarjeta.querySelector('.app-card-titulo, h3, h2, .tribe-event-title');
            if (!tituloElemento) return;

            var tituloTexto = tituloElemento.innerText.trim().toLowerCase();
            // Limpiamos el título igual que en PHP (sanitize_title aproximado)
            var tituloKey = tituloTexto.normalize("NFD").replace(/[\u0300-\u036f]/g, "").replace(/[^a-z0-9]+/g, "-").replace(/^-+|-+$/g, "");

            // Buscamos los estados de este evento en el mapa global
            var estadosEvento = mapaPadres[tituloKey];
            
            // Si no lo encuentra exacto, buscamos coincidencia parcial
            if (!estadosEvento) {
                for (var key in mapaPadres) {
                    if (tituloKey.includes(key) || key.includes(tituloKey)) {
                        estadosEvento = mapaPadres[key];
                        break;
                    }
                }
            }

            if (estadosEvento) {
                // Buscamos todas las horas/pases listados dentro de esta tarjeta específica
                var pasesHoras = tarjeta.querySelectorAll('.app-hora-enlace, span[class*="hora"], time, .tribe-event-duration');

                pasesHoras.forEach(function(paseHora, index) {
                    // El primer pase es '0', el segundo 'rec_1', el tercero 'rec_2', etc.
                    var claveMeta = (index === 0) ? "0" : "rec_" + index;
                    var estadoPase = "disponible";

                    if (estadosEvento[claveMeta] !== undefined) {
                        estadoPase = estadosEvento[claveMeta];
                    } else if (estadosEvento[index] !== undefined) {
                        estadoPase = estadosEvento[index];
                    }

                    // Si está completo según el admin, forzamos la clase en rojo
                    if (estadoPase.toLowerCase() === "completo") {
                        paseHora.classList.add("pase-completo");
                    }
                });
            }
        });
    });
    </script>
    <?php
}