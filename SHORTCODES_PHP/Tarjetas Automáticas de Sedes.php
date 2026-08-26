// ==========================================
// 1. CAMPOS DE IMAGEN EN LA TAXONOMÍA DE SEDES
// ==========================================
add_action( 'sedes_add_form_fields', 'indgenio_agregar_campo_imagen_sede' );
function indgenio_agregar_campo_imagen_sede( $taxonomy ) {
    ?>
    <div class="form-field term-imagen-wrap" style="margin-top: 15px;">
        <label for="imagen_sede">URL del Logo / Imagen de la Sede</label>
        <input type="text" name="imagen_sede" id="imagen_sede" value="">
        <p>Sube la imagen a la Biblioteca de Medios y pega aquí su URL completa.</p>
    </div>
    <?php
}

add_action( 'sedes_edit_form_fields', 'indgenio_editar_campo_imagen_sede', 10, 2 );
function indgenio_editar_campo_imagen_sede( $term, $taxonomy ) {
    $imagen_actual = get_term_meta( $term->term_id, 'imagen_sede', true );
    ?>
    <tr class="form-field term-imagen-wrap">
        <th scope="row"><label for="imagen_sede">URL del Logo / Imagen de la Sede</label></th>
        <td>
            <input type="text" name="imagen_sede" id="imagen_sede" value="<?php echo esc_attr( $imagen_actual ); ?>">
            <p class="description">Sube la imagen a la Biblioteca de Medios y pega aquí su URL.</p>
        </td>
    </tr>
    <?php
}

add_action( 'created_sedes', 'indgenio_guardar_imagen_sede', 10, 2 );
add_action( 'edited_sedes', 'indgenio_guardar_imagen_sede', 10, 2 );
function indgenio_guardar_imagen_sede( $term_id ) {
    if ( isset( $_POST['imagen_sede'] ) ) {
        update_term_meta( $term_id, 'imagen_sede', esc_url_raw( $_POST['imagen_sede'] ) );
    }
}


// ==========================================
// 2. SHORTCODE DE TARJETAS DE SEDES CON ANIMACIÓN ULTRA SUAVE Y LENTA
// ==========================================
add_shortcode( 'tarjetas_sedes_educaescena', function() {
    $sedes = get_terms( array(
        'taxonomy'   => 'sedes',
        'hide_empty' => false,
    ) );

    if ( empty( $sedes ) || is_wp_error( $sedes ) ) {
        return '<p style="font-family: Raleway, sans-serif;">No hay sedes creadas todavía.</p>';
    }

    $hoy = current_time( 'Y-m-d H:i:s' );

    // Estilos CSS con transición ultra suave, 1.6s de duración y desplazamiento corto
    $output = '
    <style>
        @keyframes ultraSmoothFadeInUp {
            0% {
                opacity: 0;
                transform: translateY(20px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .indgenio-sede-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 220px;
            opacity: 0;
            animation: ultraSmoothFadeInUp 1.6s cubic-bezier(0.25, 1, 0.5, 1) forwards;
        }

        /* Retraso escalonado sutil y gradual entre tarjetas */
        .indgenio-sede-card:nth-child(1) { animation-delay: 0.25s; }
        .indgenio-sede-card:nth-child(2) { animation-delay: 0.5s; }
        .indgenio-sede-card:nth-child(3) { animation-delay: 0.75s; }
        .indgenio-sede-card:nth-child(4) { animation-delay: 1.0s; }
        .indgenio-sede-card:nth-child(5) { animation-delay: 1.25s; }
        .indgenio-sede-card:nth-child(n+6) { animation-delay: 1.5s; }

        @media (max-width: 1024px) {
            .indgenio-sede-card-header,
            .indgenio-sede-card-header div {
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                text-align: center !important;
            }
        }
    </style>';

    $output .= '<div style="font-family: Raleway, sans-serif; display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 20px;">';

    foreach ( $sedes as $sede ) {
        $sede_url = get_term_link( $sede );
        $sede_imagen = get_term_meta( $sede->term_id, 'imagen_sede', true );

        // Consulta de eventos para esta sede (sin duplicar pases)
        $args_eventos = array(
            'post_type'      => 'tribe_events',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'tax_query'      => array(
                array(
                    'taxonomy' => 'sedes',
                    'field'    => 'term_id',
                    'terms'    => $sede->term_id,
                ),
            ),
        );
        $query_eventos = new WP_Query( $args_eventos );
        
        $eventos_unicos = array();
        if ( $query_eventos->have_posts() ) {
            while ( $query_eventos->have_posts() ) {
                $query_eventos->the_post();
                $id = get_the_ID();
                $titulo = get_the_title();
                $clave_grupo = sanitize_title( $titulo );
                $inicio_raw = get_post_meta( $id, '_EventStartDate', true );

                if ( $inicio_raw >= $hoy ) {
                    if ( ! isset( $eventos_unicos[$clave_grupo] ) ) {
                        $eventos_unicos[$clave_grupo] = true;
                    }
                }
            }
            wp_reset_postdata();
        }

        $num_eventos = count( $eventos_unicos );
        $texto_funcion = ( $num_eventos === 1 ) ? 'Función' : 'Funciones';

        // Estructura de la tarjeta
        $output .= '<div class="indgenio-sede-card">';
            
            // Cabecera: Logo y Título
            $output .= '<div class="indgenio-sede-card-header">';
                if ( ! empty( $sede_imagen ) ) {
                    $output .= '<div style="margin-bottom: 15px; height: 50px; display: flex; align-items: center;"><a href="' . esc_url( $sede_url ) . '" target="_blank" style="display: block;"><img src="' . esc_url( $sede_imagen ) . '" alt="' . esc_attr( $sede->name . ' Logo' ) . '" style="max-height: 50px; width: auto; max-width: 100%; object-fit: contain;"></a></div>';
                }
                $output .= '<h3 style="margin: 0; font-size: 22px; font-weight: 700; text-transform: uppercase; font-family: Raleway, sans-serif;">';
                $output .= '<a href="' . esc_url( $sede_url ) . '" target="_blank" style="color: #198C9C; text-decoration: none;">' . esc_html( $sede->name ) . '</a>';
                $output .= '</h3>';
            $output .= '</div>';

            // Pie de tarjeta: Píldora de funciones y enlace Ver
            $output .= '<div style="display: flex; align-items: center; justify-content: space-between; margin-top: 20px; border-top: 1px solid #f0f0f0; padding-top: 15px;">';
                
                // Píldora del contador real de espectáculos
                $output .= '<a href="' . esc_url( $sede_url ) . '" target="_blank" style="background: #e2f0f1; color: #198C9C; padding: 8px 20px; border-radius: 20px; font-size: 14px; text-align: center; text-decoration: none; display: inline-block; font-family: Raleway, sans-serif;">';
                    $output .= '<strong style="font-size: 16px; font-weight: 800; color: #198C9C;">' . $num_eventos . '</strong> <span style="font-size: 12px; font-weight: 500;">' . $texto_funcion . '</span>';
                $output .= '</a>';
                
                // Enlace Ver ->
                $output .= '<a href="' . esc_url( $sede_url ) . '" target="_blank" style="color: #198C9C; text-decoration: none; font-weight: 700; font-size: 15px; display: flex; align-items: center; gap: 5px; font-family: Raleway, sans-serif;">Ver &rarr;</a>';
            
            $output .= '</div>';

        $output .= '</div>';
    }

    $output .= '</div>';
    return $output;
});