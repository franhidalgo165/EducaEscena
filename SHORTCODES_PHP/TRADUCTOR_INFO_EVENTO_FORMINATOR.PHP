// ==========================================
// 1. CAPTURA DEL EVENTO ANTES DE ENVIAR EL FORMULARIO
// ==========================================
add_action( 'forminator_custom_form_submit_before_set_fields', function( $form_id, $response ) {
    $mi_formulario_id = 768;
    if ( intval( $form_id ) !== $mi_formulario_id ) {
        return;
    }

    if ( isset( $_SERVER['HTTP_REFERER'] ) && !empty( $_SERVER['HTTP_REFERER'] ) ) {
        $post_id = url_to_postid( esc_url_raw( $_SERVER['HTTP_REFERER'] ) );
        if ( $post_id && get_post_type( $post_id ) === 'tribe_events' ) {
            update_option( 'forminator_evento_activo_768', $post_id );
        }
    }
}, 10, 2 );


// ==========================================
// 2. INTERCEPTOR GLOBAL DE CORREOS DE WORDPRESS (WP_MAIL)
// ==========================================
function interceptar_correo_reserva_educaescena( $args ) {
    if ( ( isset( $args['subject'] ) && strpos( $args['subject'], 'Nueva Reserva' ) !== false ) || 
         ( isset( $args['message'] ) && strpos( $args['message'], '¡Nueva Reserva Recibida!' ) !== false ) ) {

        $post_id = get_option( 'forminator_evento_activo_768', 0 );

        if ( !$post_id && isset( $_SERVER['HTTP_REFERER'] ) ) {
            $post_id = url_to_postid( esc_url_raw( $_SERVER['HTTP_REFERER'] ) );
        }

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

        // Envolvemos la tarjeta del evento para que tenga un margen bonito y quede integrada
        $html_evento_contenedor = '<div style="margin-bottom: 20px;">' . $html_evento . '</div>';

        // Buscamos justo después del título "¡Nueva Reserva Recibida!" para meter la tarjeta dentro
        $marcador = '<h1 style="margin: 0; font-size: 24px;">¡Nueva Reserva Recibida!</h1>';
        
        if ( strpos( $args['message'], $marcador ) !== false ) {
            // Metemos la tarjeta justo debajo de la cabecera verde/título
            $args['message'] = str_replace( $marcador, $marcador . '</div><div style="padding: 25px;">' . $html_evento_contenedor, $args['message'] );
        } else {
            $args['message'] = $html_evento_contenedor . $args['message'];
        }
    }

    return $args;
}
add_filter( 'wp_mail', 'interceptar_correo_reserva_educaescena' );