// ==========================================
// 1. CAMPOS DE CORREO EN LA TAXONOMÍA DE SEDES
// ==========================================
add_action( 'sedes_add_form_fields', 'indgenio_agregar_correo_sede' );
function indgenio_agregar_correo_sede( $taxonomy ) {
    ?>
    <div class="form-field term-correo-wrap">
        <label for="correo_sede">Correo electrónico de la sede</label>
        <input type="email" name="correo_sede" id="correo_sede" value="">
        <p>Introduce el correo al que se enviarán las notificaciones de esta sede.</p>
    </div>
    <?php
}

add_action( 'sedes_edit_form_fields', 'indgenio_editar_correo_sede', 10, 2 );
function indgenio_editar_correo_sede( $term, $taxonomy ) {
    $correo_actual = get_term_meta( $term->term_id, 'correo_sede', true );
    ?>
    <tr class="form-field term-correo-wrap">
        <th scope="row"><label for="correo_sede">Correo electrónico de la sede</label></th>
        <td>
            <input type="email" name="correo_sede" id="correo_sede" value="<?php echo esc_attr( $correo_actual ); ?>">
            <p class="description">Introduce el correo al que se enviarán las notificaciones de esta sede.</p>
        </td>
    </tr>
    <?php
}

add_action( 'created_sedes', 'indgenio_guardar_correo_sede', 10, 2 );
add_action( 'edited_sedes', 'indgenio_guardar_correo_sede', 10, 2 );
function indgenio_guardar_correo_sede( $term_id ) {
    if ( isset( $_POST['correo_sede'] ) ) {
        update_term_meta( $term_id, 'correo_sede', sanitize_email( $_POST['correo_sede'] ) );
    }
}

// ==========================================
// 2. CAPTURA DEL EVENTO AL ENVIAR FORMULARIO
// ==========================================
add_action( 'forminator_custom_form_submit_before_set_fields', 'indgenio_capturar_evento_forminator', 10, 2 );
function indgenio_capturar_evento_forminator( $form_id, $response ) {
    $mi_formulario_id = 768;
    if ( intval( $form_id ) !== $mi_formulario_id ) {
        return;
    }

    $post_id = 0;
    if ( isset( $_SERVER['HTTP_REFERER'] ) && !empty( $_SERVER['HTTP_REFERER'] ) ) {
        $post_id = url_to_postid( esc_url_raw( $_SERVER['HTTP_REFERER'] ) );
    }

    if ( $post_id ) {
        update_option( 'forminator_evento_activo_768', $post_id );
    }
}

// ==========================================
// 3. INTERCEPTOR DE CORREOS: TARJETA HTML Y ENVÍO A SEDE (BCC)
// ==========================================
add_filter( 'wp_mail', 'indgenio_interceptar_correo_reserva_definitivo' );
function indgenio_interceptar_correo_reserva_definitivo( $args ) {
    if ( ( isset( $args['subject'] ) && strpos( $args['subject'], 'Nueva Reserva' ) !== false ) || 
         ( isset( $args['message'] ) && strpos( $args['message'], '¡Nueva Reserva Recibida!' ) !== false ) ) {

        $post_id = get_option( 'forminator_evento_activo_768', 0 );

        if ( !$post_id && isset( $_SERVER['HTTP_REFERER'] ) ) {
            $post_id = url_to_postid( esc_url_raw( $_SERVER['HTTP_REFERER'] ) );
        }

        // A. EXTRAER CORREO DE LA SEDE Y METERLO EN BCC
        if ( $post_id ) {
            $sedes = wp_get_post_terms( $post_id, 'sedes' );
            if ( ! is_wp_error( $sedes ) && ! empty( $sedes ) ) {
                $sede_id = $sedes[0]->term_id;
                $correo_sede = get_term_meta( $sede_id, 'correo_sede', true );

                if ( ! empty( $correo_sede ) && is_email( $correo_sede ) ) {
                    $headers = isset( $args['headers'] ) ? $args['headers'] : array();
                    if ( is_string( $headers ) ) {
                        $headers = explode( "\n", str_replace( "\r\n", "\n", $headers ) );
                    }
                    $headers[] = 'Bcc: ' . sanitize_email( $correo_sede );
                    $args['headers'] = $headers;
                }
            }
        }

        // B. PREPARAR LA TARJETA DEL EVENTO
        $html_evento = '';
        if ( $post_id && get_post_type( $post_id ) === 'tribe_events' ) {
            global $post;
            $post_original = $post;
            
            $post = get_post( $post_id );
            setup_postdata( $post );
            
            if ( function_exists( 'get_evento_info_shortcode' ) ) {
                $html_evento = get_evento_info_shortcode();
            } else {
                $html_evento = '<p><strong>Evento:</strong> ' . get_the_title($post_id) . '</p>';
            }
            
            wp_reset_postdata();
            $post = $post_original;
        } else {
            $html_evento = '<div style="background: #fff3cd; padding: 10px; border-radius: 6px; color: #856404; margin-bottom: 20px;">Aviso: No se pudo capturar el evento.</div>';
        }

        // C. INYECTAR LA TARJETA EN EL MENSAJE
        $html_evento_contenedor = '<div style="margin-bottom: 20px;">' . $html_evento . '</div>';
        $marcador = '<h1 style="margin: 0; font-size: 24px;">¡Nueva Reserva Recibida!</h1>';
        
        if ( strpos( $args['message'], $marcador ) !== false ) {
            $args['message'] = str_replace( $marcador, $marcador . '</div><div style="padding: 25px;">' . $html_evento_contenedor, $args['message'] );
        } else {
            $args['message'] = $html_evento_contenedor . $args['message'];
        }
    }

    return $args;
}