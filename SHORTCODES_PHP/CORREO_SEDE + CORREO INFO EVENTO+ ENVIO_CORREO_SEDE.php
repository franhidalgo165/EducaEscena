// ==========================================
// 1. CAMPOS DE CORREO Y AVISO EN LA TAXONOMÍA DE SEDES
// ==========================================
add_action( 'sedes_add_form_fields', 'indgenio_agregar_correo_sede' );
function indgenio_agregar_correo_sede( $taxonomy ) {
    ?>
    <div class="form-field term-correo-wrap">
        <label for="correo_sede">Correo electrónico de la sede</label>
        <input type="email" name="correo_sede" id="correo_sede" value="">
        <p>Introduce el correo al que se enviarán las notificaciones de esta sede.</p>
    </div>
    <div class="form-field term-aviso-wrap" style="margin-top: 15px;">
        <label for="aviso_sede">Texto de Aviso Importante para esta Sede</label>
        <textarea name="aviso_sede" id="aviso_sede" rows="3" style="width:100%;"></textarea>
        <p>Escribe el texto de aviso que aparecerá al final del correo.</p>
    </div>
    <?php
}

add_action( 'sedes_edit_form_fields', 'indgenio_editar_correo_sede', 10, 2 );
function indgenio_editar_correo_sede( $term, $taxonomy ) {
    $correo_actual = get_term_meta( $term->term_id, 'correo_sede', true );
    $aviso_actual  = get_term_meta( $term->term_id, 'aviso_sede', true );
    ?>
    <tr class="form-field term-correo-wrap">
        <th scope="row"><label for="correo_sede">Correo electrónico de la sede</label></th>
        <td>
            <input type="email" name="correo_sede" id="correo_sede" value="<?php echo esc_attr( $correo_actual ); ?>">
            <p class="description">Introduce el correo al que se enviarán las notificaciones de esta sede.</p>
        </td>
    </tr>
    <tr class="form-field term-aviso-wrap">
        <th scope="row"><label for="aviso_sede">Aviso Importante de la Sede</label></th>
        <td>
            <textarea name="aviso_sede" id="aviso_sede" rows="3" style="width:100%;"><?php echo esc_textarea( $aviso_actual ); ?></textarea>
            <p class="description">Texto que se mostrará al final del correo para esta sede.</p>
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
    if ( isset( $_POST['aviso_sede'] ) ) {
        update_term_meta( $term_id, 'aviso_sede', sanitize_textarea_field( $_POST['aviso_sede'] ) );
    }
}


// ==========================================
// 2. CAPTURA BLINDADA DEL PASE Y DEL EVENTO AL ENVIAR
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

    $pase_detectado = '';
    
    if ( isset( $_POST['pase_seleccionado_real'] ) && ! empty( $_POST['pase_seleccionado_real'] ) ) {
        $pase_detectado = trim( sanitize_text_field( $_POST['pase_seleccionado_real'] ) );
    }

    if ( empty( $pase_detectado ) ) {
        $posibles_claves = array( 'pase_seleccionado', 'radio-1', 'radio', 'select-1' );
        foreach ( $posibles_claves as $clave ) {
            if ( isset( $_POST[$clave] ) && ! empty( $_POST[$clave] ) ) {
                $pase_detectado = trim( sanitize_text_field( $_POST[$clave] ) );
                break;
            } elseif ( isset( $_POST['data'][$clave] ) && ! empty( $_POST['data'][$clave] ) ) {
                $pase_detectado = trim( sanitize_text_field( $_POST['data'][$clave] ) );
                break;
            }
        }
    }

    if ( ! empty( $pase_detectado ) ) {
        update_option( 'forminator_pase_activo_768', $pase_detectado );
    }
}


// ==========================================
// 3. INTERCEPTOR DE CORREOS Y GENERADOR DEL SHORTCODE [info_evento]
// ==========================================
add_filter( 'wp_mail', 'indgenio_interceptar_correo_reserva_definitivo' );
function indgenio_interceptar_correo_reserva_definitivo( $args ) {
    if ( ! is_array( $args ) || ! isset( $args['subject'], $args['message'] ) ) {
        return $args;
    }

    if ( ( strpos( $args['subject'], 'Nueva Reserva' ) !== false || strpos( $args['subject'], 'Solicitud de asistencia' ) !== false ) ||  
         ( strpos( $args['message'], '¡Nueva Reserva Recibida!' ) !== false || strpos( $args['message'], 'Solicitud de asistencia' ) !== false ) ) {

        $post_id = get_option( 'forminator_evento_activo_768', 0 );
        if ( !$post_id && isset( $_SERVER['HTTP_REFERER'] ) ) {
            $post_id = url_to_postid( esc_url_raw( $_SERVER['HTTP_REFERER'] ) );
        }

        $nombre_sede = '';
        $aviso_texto = '';
        if ( $post_id ) {
            $sedes = wp_get_post_terms( $post_id, 'sedes' );
            if ( ! is_wp_error( $sedes ) && ! empty( $sedes ) ) {
                $nombre_sede = $sedes[0]->name;
                $sede_id = $sedes[0]->term_id;
                $correo_sede = get_term_meta( $sede_id, 'correo_sede', true );
                $aviso_texto = get_term_meta( $sede_id, 'aviso_sede', true );

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

        if ( ! empty( $nombre_sede ) ) {
            $args['subject'] = 'Solicitud de asistencia ' . $nombre_sede . ' - Educaescena';
        } else {
            $args['subject'] = 'Solicitud de asistencia - Educaescena';
        }

        $pase_final_correo = '';
        if ( isset( $_POST['pase_seleccionado_real'] ) && !empty( $_POST['pase_seleccionado_real'] ) ) {
            $pase_final_correo = sanitize_text_field( $_POST['pase_seleccionado_real'] );
        }

        if ( empty( $pase_final_correo ) ) {
            $posibles_claves = array( 'pase_seleccionado', 'radio-1', 'radio', 'select-1' );
            foreach ( $posibles_claves as $clave ) {
                if ( isset( $_POST[$clave] ) && !empty( $_POST[$clave] ) ) {
                    $pase_final_correo = sanitize_text_field( $_POST[$clave] );
                    break;
                } elseif ( isset( $_POST['data'][$clave] ) && !empty( $_POST['data'][$clave] ) ) {
                    $pase_final_correo = sanitize_text_field( $_POST['data'][$clave] );
                    break;
                }
            }
        }

        if ( empty( $pase_final_correo ) ) {
            $pase_final_correo = get_option( 'forminator_pase_activo_768', 'Pase general del evento' );
        }
        
        $html_evento = '';
        if ( $post_id && get_post_type( $post_id ) === 'tribe_events' ) {
            $titulo = get_the_title( $post_id );
            $fecha_hora_pase = $pase_final_correo;
            $terminos_edad = strip_tags( get_the_term_list( $post_id, 'edad', '', ', ', '' ) );
            $terminos_sedes = ! empty( $nombre_sede ) ? $nombre_sede : strip_tags( get_the_term_list( $post_id, 'sedes', '', ', ', '' ) );
            $lugar = tribe_get_venue( $post_id );
            if ( empty( $lugar ) ) { $lugar = ! empty( $terminos_sedes ) ? $terminos_sedes : get_post_meta( $post_id, '_EventVenue', true ); }
            
            $precio_raw = function_exists( 'tribe_get_cost' ) ? tribe_get_cost( $post_id, true ) : '';
            if ( empty( $precio_raw ) ) {
                $precio_raw = get_post_meta( $post_id, '_EventCost', true );
            }
            
            if ( is_numeric( $precio_raw ) ) {
                $precio_texto = number_format( floatval( $precio_raw ), 2, ',', '.' ) . ' €';
            } elseif ( ! empty( $precio_raw ) ) {
                $precio_texto = $precio_raw;
            } else {
                $precio_texto = 'Gratuito';
            }

			// TARJETA HTML EXACTA CON DISEÑO LIMPIO (SIN FECHA GENERAL Y CON ID DE CONTROL PARA AJAX)
						$html_evento  = '<div style="text-align: center; margin-bottom: 20px;"><img src="https://educaescena.es/wp-content/uploads/2026/07/LOGOTIPO-EDUCAESCENA_COLOR.png" alt="Educaescena" style="max-width: 200px; height: auto; display: inline-block;" /></div>';
						$html_evento .= '<div style="font-family: Raleway, sans-serif; background:#f4f7f7; padding:20px; border-radius:12px; border: 1px solid #189c9c; color: #1D1E1C; margin-bottom: 20px;">';
						$html_evento .= '<div style="font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; color: #189c9c; font-weight: 700; margin-bottom: 2px;">Título de la obra:</div>';
						$html_evento .= '<h3 style="margin-top:0; margin-bottom: 8px; color: #189c9c; font-weight: 700; font-size: 20px;">' . esc_html( $titulo ) . '</h3>';
						$html_evento .= '<p style="margin: 0 0 8px 0; color: #189c9c;"><strong>Pase solicitado:</strong> <span id="texto-pase-en-resumen" style="font-weight:700;">' . esc_html( $fecha_hora_pase ) . '</span></p>';
						if ( ! empty( $terminos_edad ) ) { $html_evento .= '<p style="margin: 5px 0;"><strong>Edad:</strong> ' . esc_html( $terminos_edad ) . '</p>'; }
            $html_evento .= '<p style="margin: 5px 0;"><strong>Sede:</strong> ' . esc_html( $terminos_sedes ) . '</p>';
            $html_evento .= '<p style="margin: 5px 0;"><strong>Lugar:</strong> ' . esc_html( $lugar ) . '</p>';
            $html_evento .= '<p style="margin: 5px 0;"><strong>Precio por plaza:</strong> ' . esc_html( $precio_texto ) . '</p>';
            $html_evento .= '</div>';
        }

        // Texto legal y de avisos generales solicitados
        $texto_general_aviso = 'Para modificaciones o anulaciones de las solicitudes de asistencia ha de contactar con la oficina de gestión en el teléfono 622 007 355. Las sesiones son orientativas, la organización se reserva el derecho a cancelar el evento en el caso de no disponer de suficientes solicitudes para cubrir el aforo mínimo necesario. El precio por alumno/a indicado en la presente solicitud incluye el I.V.A.';

        // Bloque de avisos con FONDO BLANCO (#ffffff), borde sutil y texto elegante
        $html_aviso = '';
        if ( ! empty( $aviso_texto ) ) {
            $html_aviso = '
            <div style="background-color: #ffffff; color: #333333; padding: 18px 22px; border-radius: 12px; border: 1px solid #dcdcdc; font-family: Raleway, sans-serif; margin-top: 25px; margin-bottom: 20px; width: 100%; box-sizing: border-box;">
                <strong style="font-size: 15px; font-weight: 700; display: block; margin-bottom: 5px; color: #189c9c;">Aviso importante</strong>
                <span style="font-size: 14px; font-weight: 400; display: block; line-height: 1.4; margin-bottom: 12px; color: #444444;">' . esc_html( $aviso_texto ) . '</span>
                <hr style="border: none; border-top: 1px solid #dcdcdc; margin: 12px 0;">
                <span style="font-size: 11px; font-weight: 400; color: #666666; display: block; line-height: 1.4;">' . esc_html( $texto_general_aviso ) . '</span>
            </div>';
        } else {
            $html_aviso = '
            <div style="background-color: #ffffff; color: #333333; padding: 15px 20px; border-radius: 12px; border: 1px solid #dcdcdc; font-family: Raleway, sans-serif; margin-top: 25px; margin-bottom: 20px; width: 100%; box-sizing: border-box;">
                <span style="font-size: 11px; font-weight: 400; color: #666666; display: block; line-height: 1.4;">' . esc_html( $texto_general_aviso ) . '</span>
            </div>';
        }

        if ( strpos( $args['message'], '[info_evento]' ) !== false ) {
            $args['message'] = str_replace( '[info_evento]', $html_evento, $args['message'] );
        } else {
            $args['message'] = $html_evento . $args['message'];
        }

        $args['message'] .= $html_aviso;
    }

    return $args;
}


// ==========================================
// 4. SHORTCODE [aviso_sede] PARA PINTAR EL RECUADRO EN LA WEB
// ==========================================
add_shortcode( 'aviso_sede', function() {
    $term = get_queried_object();

    if ( ! $term || ! isset( $term->taxonomy ) || $term->taxonomy !== 'sedes' ) {
        return ''; 
    }

    $aviso_texto = get_term_meta( $term->term_id, 'aviso_sede', true );

    if ( empty( $aviso_texto ) ) {
        return '';
    }

    $output = '
    <div style="background-color: #198C9C; color: #ffffff; padding: 20px 25px; border-radius: 16px; font-family: Raleway, sans-serif; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 15px rgba(0,0,0,0.08); margin: 20px 0; width: 100%; box-sizing: border-box;">
        <div style="display: flex; flex-direction: column; gap: 5px;">
            <strong style="font-size: 18px; font-weight: 700; letter-spacing: 0.3px;">Aviso importante</strong>
            <span style="font-size: 15px; font-weight: 400; opacity: 0.95;">' . esc_html( $aviso_texto ) . '</span>
        </div>
        <div style="flex-shrink: 0; margin-left: 15px;">
            <svg width="26" height="26" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="opacity: 0.9;"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
        </div>
    </div>';

    return $output;
});


// ==========================================
// 5. SCRIPT JS EN TIEMPO REAL (RESISTENTE A AJAX Y PASOS MULTIPASO)
// ==========================================
add_action( 'wp_footer', 'indgenio_capturar_radio_real_js' );
function indgenio_capturar_radio_real_js() {
    ?>
    <script type="text/javascript">
    document.addEventListener('change', function(e) {
        if (e.target && e.target.type === 'radio') {
            var form = e.target.closest('form');
            if (form) {
                var valorPase = e.target.value ? e.target.value.trim() : '';
                if (valorPase) {
                    localStorage.setItem('forminator_ultimo_pase_768', valorPase);

                    var inputOculto = form.querySelector('#pase_seleccionado_real');
                    if (!inputOculto) {
                        inputOculto = document.createElement('input');
                        inputOculto.type = 'hidden';
                        inputOculto.id = 'pase_seleccionado_real';
                        inputOculto.name = 'pase_seleccionado_real';
                        form.appendChild(inputOculto);
                    }
                    inputOculto.value = valorPase;
                }
            }
        }
    }, true);

    document.addEventListener('click', function() {
        var spanPase = document.getElementById('texto-pase-en-resumen');
        if (spanPase && (spanPase.innerText.includes('Seleccionado en el paso anterior') || spanPase.innerText.trim() === '')) {
            var paseGuardado = localStorage.getItem('forminator_ultimo_pase_768');
            if (paseGuardado) {
                spanPase.innerText = paseGuardado;
            }
        }
    });
    </script>
    <?php
}


// ==========================================
// 6. FORZAR FORMATO DE PRECIO CON DECIMALES AUTOMÁTICAMENTE
// ==========================================
add_filter( 'tribe_get_cost', 'indgenio_forzar_precio_con_decimales', 10, 3 );
function indgenio_forzar_precio_con_decimales( $cost, $post_id, $with_currency_symbol ) {
    $clean_cost = preg_replace( '/[^0-9.]/', '', $cost );
    
    if ( is_numeric( $clean_cost ) && floatval( $clean_cost ) > 0 ) {
        $formatted = number_format( floatval( $clean_cost ), 2, ',', '.' ) . ' €';
        return $formatted;
    } elseif ( empty( $clean_cost ) || floatval( $clean_cost ) == 0 ) {
        return 'Gratuito';
    }
    
    return $cost;
}