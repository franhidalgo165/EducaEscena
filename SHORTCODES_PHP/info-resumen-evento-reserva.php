// ==========================================
// RESUMEN DE EVENTO Y EXTRACCIÓN REAL DE PASES (PHP PURO)
// ==========================================

// 1. Shortcode para la tarjeta de resumen (muestra el pase elegido)
function get_evento_info_shortcode() {
    if ( ! function_exists( 'tribe_get_venue' ) ) {
        return 'Tribe Events inactivo.';
    }

    $evento_id = get_the_ID();
    
    if ( ! $evento_id || get_post_type( $evento_id ) !== 'tribe_events' ) {
        $evento_id = get_option( 'forminator_evento_activo_768', 0 );
    }

    if ( $evento_id && get_post_type( $evento_id ) === 'tribe_events' ) {
        $titulo         = get_the_title( $evento_id );
        $fecha          = tribe_get_start_date( $evento_id, false, 'd/m/Y' );
        $hora           = tribe_get_start_date( $evento_id, false, 'H:i' );
        
        $terminos_edad  = strip_tags( get_the_term_list( $evento_id, 'edad', '', ', ', '' ) );
        $terminos_sedes = strip_tags( get_the_term_list( $evento_id, 'sedes', '', ', ', '' ) );
        
        $lugar          = tribe_get_venue( $evento_id );
        $precio         = tribe_get_cost( $evento_id, true );

        // Capturar el pase seleccionado por el usuario
        $pase_elegido = '';
        if ( isset( $_POST['pase_seleccionado'] ) && ! empty( $_POST['pase_seleccionado'] ) ) {
            $pase_elegido = sanitize_text_field( $_POST['pase_seleccionado'] );
            set_transient( 'pase_usuario_' . get_current_user_id(), $pase_elegido, HOUR_IN_SECONDS );
        } else {
            $pase_elegido = get_transient( 'pase_usuario_' . get_current_user_id() );
            if ( empty( $pase_elegido ) && isset( $_POST['data'] ) ) {
                foreach ( $_POST as $key => $val ) {
                    if ( strpos( $key, 'pase_seleccionado' ) !== false ) {
                        $pase_elegido = sanitize_text_field( $val );
                    }
                }
            }
        }
        
        $output  = '<div class="evento-resumen-pro" style="font-family: Raleway, sans-serif; background:#f4f7f7; padding:20px; border-radius:12px; border: 1px solid #A0CED4; color: #1D1E1C;">';
        $output .= '<h3 style="margin-top:0; color: #198C9C; font-weight: 700; font-size: 20px;">' . esc_html( $titulo ) . '</h3>';
        $output .= '<p style="margin: 5px 0;"><strong>Fecha:</strong> ' . esc_html( $fecha ) . '</p>';
        $output .= '<p style="margin: 5px 0;"><strong>Hora:</strong> ' . esc_html( $hora ) . ' h.</p>';
        
        if ( ! empty( $pase_elegido ) ) {
            $output .= '<p style="margin: 8px 0; background: #e2f0f1; padding: 8px 12px; border-radius: 6px; color: #198C9C;"><strong>Pase Seleccionado:</strong> ' . esc_html( $pase_elegido ) . '</p>';
        }

        $output .= '<p style="margin: 5px 0;"><strong>Edad:</strong> ' . esc_html( $terminos_edad ) . '</p>';
        $output .= '<p style="margin: 5px 0;"><strong>Sede:</strong> ' . esc_html( $terminos_sedes ) . '</p>';
        $output .= '<p style="margin: 5px 0;"><strong>Lugar:</strong> ' . esc_html( $lugar ) . '</p>';
        $output .= '<p style="margin: 5px 0;"><strong>Precio:</strong> ' . esc_html( $precio ) . '</p>';
        $output .= '</div>';
        
        return $output;
    }
    
    return 'Resumen del evento disponible en la página del evento.';
}
add_shortcode( 'info_evento', 'get_evento_info_shortcode' );


// 2. Extraer pases usando la consulta real de pases hermanos por título
add_action( 'wp_footer', function() {
    if ( ! is_singular( 'tribe_events' ) ) {
        return;
    }

    $current_id = get_the_ID();
    $titulo = get_the_title( $current_id );
    $fecha_actual = current_time( 'Y-m-d H:i:s' );

    // Consulta exacta de pases hermanos por título
    $args = array(
        'post_type'      => 'tribe_events',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        's'              => $titulo,
        'orderby'        => 'meta_value',
        'meta_key'       => '_EventStartDate',
        'order'          => 'ASC',
        'meta_query'     => array(
            array(
                'key'     => '_EventEndDate',
                'value'   => $fecha_actual,
                'compare' => '>=',
                'type'    => 'DATETIME'
            )
        )
    );

    $query = new WP_Query( $args );
    $pases_array = array();

    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            $p_id = get_the_ID();
            $p_titulo = get_the_title();
            
            if ( sanitize_title( $p_titulo ) === sanitize_title( $titulo ) ) {
                $inicio_raw = get_post_meta( $p_id, '_EventStartDate', true );
                $f_texto = tribe_get_start_date( $p_id, false, 'd M Y' );
                $h_texto = tribe_get_start_date( $p_id, false, 'H:i' ) . 'h';
                
                $pases_array[] = array(
                    'raw'   => $inicio_raw,
                    'texto' => "Función: " . $f_texto . " a las " . $h_texto
                );
            }
        }
        wp_reset_postdata();
    }

    // Ordenar cronológicamente por fecha real
    if ( ! empty( $pases_array ) ) {
        usort( $pases_array, function($a, $b) {
            return strcmp($a['raw'], $b['raw']);
        });
    }

    // Extraer textos limpios y evitar duplicados
    $lista_final_pases = array();
    foreach ( $pases_array as $p ) {
        if ( ! in_array( $p['texto'], $lista_final_pases ) ) {
            $lista_final_pases[] = $p['texto'];
        }
    }

    // Si por lo que sea está vacío, cogemos al menos el actual
    if ( empty( $lista_final_pases ) ) {
        $lista_final_pases[] = "Función: " . tribe_get_start_date( $current_id, false, 'd M Y' ) . " a las " . tribe_get_start_date( $current_id, false, 'H:i' ) . "h";
    }
    ?>
    <script type="text/javascript">
        document.addEventListener("DOMContentLoaded", function() {
            const contenedorPases = document.getElementById("lista-pases-evento");
            if (!contenedorPases) return;

            contenedorPases.innerHTML = "";
            const pases = <?php echo json_encode( array_values( $lista_final_pases ) ); ?>;

            pases.forEach((paseTexto, index) => {
                const label = document.createElement("label");
                label.style.cssText = "display: flex; align-items: center; gap: 12px; background: #f8f9fa; border: 2px solid #A0CED4; padding: 12px 16px; border-radius: 12px; cursor: pointer; margin-bottom: 8px;";
                
                const input = document.createElement("input");
                input.type = "radio";
                input.name = "pase_seleccionado";
                input.value = paseTexto;
                if (index === 0) input.checked = true; // Selecciona el primero por defecto
                input.style.accentColor = "#198C9C";

                const texto = document.createElement("span");
                texto.innerHTML = "<strong>" + paseTexto + "</strong>";
                texto.style.cssText = "color: #1D1E1C; font-size: 14px; font-family: Raleway, sans-serif;";

                label.appendChild(input);
                label.appendChild(texto);
                contenedorPases.appendChild(label);
            });
        });
    </script>
    <?php
});