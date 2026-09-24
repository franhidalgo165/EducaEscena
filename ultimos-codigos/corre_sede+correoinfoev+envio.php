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

                $headers = isset( $args['headers'] ) ? $args['headers'] : array();
                if ( is_string( $headers ) ) {
                    $headers = explode( "\n", str_replace( "\r\n", "\n", $headers ) );
                }

                if ( ! empty( $correo_sede ) && is_email( $correo_sede ) ) {
                    $headers[] = 'Bcc: ' . sanitize_email( $correo_sede );
                }

                $headers[] = 'Bcc: desarrollo@indgenio.es';
                $headers[] = 'Bcc: info@indgenio.es';

                $args['headers'] = $headers;
            }
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

        // Comprobación de lista de espera
        $es_lista_espera = false;
        if ( isset( $_POST['es_lista_espera_real'] ) && $_POST['es_lista_espera_real'] === '1' ) {
            $es_lista_espera = true;
        } else {
            $post_obj = $post_id ? get_post($post_id) : null;
            $contenido_evento = $post_obj ? $post_obj->post_content : '';
            if ( !empty( $pase_final_correo ) && preg_match( '/\[completado:\s*([^\]]+)\]/i', $contenido_evento, $matches ) ) {
                $array_completadas = array_map( 'trim', explode( ',', $matches[1] ) );
                foreach ($array_completadas as $hc) {
                    if (!empty($hc) && (stripos($pase_final_correo, $hc) !== false || stripos($hc, $pase_final_correo) !== false)) {
                        $es_lista_espera = true;
                        break;
                    }
                }
            }
        }

        // Modificar asunto si está lleno
        $sufijo_asunto = $es_lista_espera ? ' [PASE LLENO - LISTA DE ESPERA]' : '';
        if ( ! empty( $nombre_sede ) ) {
            $args['subject'] = 'Solicitud de asistencia ' . $nombre_sede . ' - Educaescena' . $sufijo_asunto;
        } else {
            $args['subject'] = 'Solicitud de asistencia - Educaescena' . $sufijo_asunto;
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

            // Texto adaptado para el correo
            $extra_aviso_correo = '';
            if ( $es_lista_espera ) {
                $extra_aviso_correo = '<div style="background-color: #fff5f5; border: 1px solid #c53030; color: #c53030; padding: 8px 12px; border-radius: 6px; margin-top: 8px; font-weight: 700; font-size: 12px;">Pase lleno, entra a lista de espera</div>';
            }

            // TARJETA HTML DEL EVENTO
            $html_evento  = '<div style="text-align: center; margin-bottom: 20px;"><img src="https://educaescena.es/wp-content/uploads/2026/07/LOGOTIPO-EDUCAESCENA_COLOR.png" alt="Educaescena" style="max-width: 200px; height: auto; display: inline-block;" /></div>';
            $html_evento .= '<div style="font-family: Raleway, sans-serif; background:#f4f7f7; padding:20px; border-radius:12px; border: 1px solid #189c9c; color: #1D1E1C; margin-bottom: 20px;">';
            $html_evento .= '<div style="font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; color: #189c9c; font-weight: 700; margin-bottom: 2px;">Título de la obra:</div>';
            $html_evento .= '<h3 style="margin-top:0; margin-bottom: 8px; color: #189c9c; font-weight: 700; font-size: 20px;">' . esc_html( $titulo ) . '</h3>';
            $html_evento .= '<p style="margin: 0 0 8px 0; color: #189c9c;"><strong>Pase solicitado:</strong> <span id="texto-pase-en-resumen" style="font-weight:700;">' . esc_html( $fecha_hora_pase ) . '</span></p>';
            
            $html_evento .= $extra_aviso_correo;

            if ( $es_lista_espera ) {
                $html_evento .= '<div style="background-color: #fff5f5; border-left: 4px solid #c53030; padding: 8px 10px; margin: 8px 0; font-size: 12px; color: #c53030; border-radius: 4px;"><strong>Aviso:</strong> Este pase está completo y la solicitud ha entrado a lista de espera.</div>';
            }

            if ( ! empty( $terminos_edad ) ) { $html_evento .= '<p style="margin: 5px 0;"><strong>Edad:</strong> ' . esc_html( $terminos_edad ) . '</p>'; }
            $html_evento .= '<p style="margin: 5px 0;"><strong>Sede:</strong> ' . esc_html( $terminos_sedes ) . '</p>';
            $html_evento .= '<p style="margin: 5px 0;"><strong>Lugar:</strong> ' . esc_html( $lugar ) . '</p>';
            $html_evento .= '<p style="margin: 5px 0;"><strong>Precio por plaza:</strong> ' . esc_html( $precio_texto ) . '</p>';
            $html_evento .= '</div>';
        }

        $texto_general_aviso = 'Para modificaciones o anulaciones de las solicitudes de asistencia ha de contactar con la oficina de gestión en el teléfono 622 007 355. Las sesiones son orientativas, la organización se reserva el derecho a cancelar el evento en el caso de no disponer de suficientes solicitudes para cubrir el aforo mínimo necesario. El precio por alumno/a indicado en la presente solicitud incluye el I.V.A.';

        $html_aviso = '';
        if ( ! empty( $aviso_texto ) ) {
            $html_aviso = '
            <div style="background-color: #ffffff; color: #333333; text-align: left; padding: 18px 22px; border-radius: 12px; border: 1px solid #dcdcdc; font-family: Raleway, sans-serif; margin-top: 25px; margin-bottom: 20px; width: 100%; box-sizing: border-box;">
                <strong style="font-size: 15px; font-weight: 700; display: block; text-align: left; margin-bottom: 5px; color: #189c9c;">Aviso importante</strong>
                <span style="font-size: 14px; font-weight: 400; display: block; text-align: left; line-height: 1.4; margin-bottom: 12px; color: #444444;">' . esc_html( $aviso_texto ) . '</span>
                <hr style="border: none; border-top: 1px solid #dcdcdc; margin: 12px 0;">
                <span style="font-size: 11px; font-weight: 400; display: block; text-align: left; line-height: 1.4; color: #666666;">' . esc_html( $texto_general_aviso ) . '</span>
            </div>';
        } else {
            $html_aviso = '
            <div style="background-color: #ffffff; color: #333333; text-align: left; padding: 15px 20px; border-radius: 12px; border: 1px solid #dcdcdc; font-family: Raleway, sans-serif; margin-top: 25px; margin-bottom: 20px; width: 100%; box-sizing: border-box;">
                <span style="font-size: 11px; font-weight: 400; display: block; text-align: left; line-height: 1.4; color: #666666;">' . esc_html( $texto_general_aviso ) . '</span>
            </div>';
        }

        if ( strpos( $args['message'], '[info_evento]' ) !== false ) {
            $args['message'] = str_replace( '[info_evento]', $html_evento, $args['message'] );
        } else {
            $args['message'] = $html_evento . $args['message'];
        }

        $coletilla = 'Este mensaje ha sido generado automáticamente por el sistema de gestión de Educaescena.';
        if ( strpos( $args['message'], $coletilla ) !== false ) {
            $args['message'] = str_replace( $coletilla, $html_aviso . '<br>' . $coletilla, $args['message'] );
        } else {
            $args['message'] .= $html_aviso;
        }
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
// 5. SCRIPT JS DE SELECCIÓN, VALIDACIÓN Y LISTA DE ESPERA (CONCORDANCIA FECHA + HORA)
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

    // Leer los pases que están realmente en rojo en la tarjeta del evento (Fecha exacta + Hora)
    function sincronizarPasesRojosForminator() {
        var pasesOcupadosExactos = [];
        
        // Buscar las filas de días en la tarjeta del evento que contienen pases marcados en rojo
        document.querySelectorAll('.app-dia-fila').forEach(function(fila) {
            var textoDia = fila.querySelector('.app-dia-texto');
            if (textoDia) {
                var fechaStr = textoDia.innerText.replace(':', '').trim(); // Ej: "21 Abr"
                
                fila.querySelectorAll('.estado-ocupado').forEach(function(elHora) {
                    var horaStr = elHora.innerText.replace('h', '').trim(); // Ej: "10:00"
                    pasesOcupadosExactos.push({
                        fecha: fechaStr,
                        hora: horaStr
                    });
                });
            }
        });

        if (pasesOcupadosExactos.length === 0) return;

        var formulariosForminator = document.querySelectorAll('.forminator-custom-form, form.forminator-ui');
        formulariosForminator.forEach(function(form) {
            var labelsOpciones = form.querySelectorAll('label, .forminator-radio');
            
            labelsOpciones.forEach(function(label) {
                var textoLabel = label.innerText || ''; // Ej: "21 Abr 2027 a las 10:00h"
                
                var coincide = pasesOcupadosExactos.some(function(item) {
                    return textoLabel.includes(item.fecha) && textoLabel.includes(item.hora);
                });

                if (coincide && !label.querySelector('.etiqueta-lista-espera')) {
                    label.style.color = '#c53030';
                    label.style.fontWeight = 'bold';
                    
                    var spanExtra = document.createElement('span');
                    spanExtra.className = 'etiqueta-lista-espera';
                    spanExtra.style.display = 'inline-block';
                    spanExtra.style.marginLeft = '10px';
                    spanExtra.style.fontSize = '13px';
                    spanExtra.style.color = '#c53030';
                    spanExtra.style.fontWeight = '700';
                    spanExtra.innerText = '(Pase lleno, entra a lista de espera)';
                    label.appendChild(spanExtra);
                }
            });
        });
    }

    // Gestionar la tarjeta roja y el checkbox en el resumen del formulario usando fecha + hora
    function actualizarResumenForminator() {
        var pasesOcupadosExactos = [];
        document.querySelectorAll('.app-dia-fila').forEach(function(fila) {
            var textoDia = fila.querySelector('.app-dia-texto');
            if (textoDia) {
                var fechaStr = textoDia.innerText.replace(':', '').trim();
                fila.querySelectorAll('.estado-ocupado').forEach(function(elHora) {
                    var horaStr = elHora.innerText.replace('h', '').trim();
                    pasesOcupadosExactos.push({
                        fecha: fechaStr,
                        hora: horaStr
                    });
                });
            }
        });

        var paseSeleccionado = localStorage.getItem('forminator_ultimo_pase_768') || '';
        
        var estaOcupado = pasesOcupadosExactos.some(function(item) {
            return paseSeleccionado.includes(item.fecha) && paseSeleccionado.includes(item.hora);
        });

        var form = document.querySelector('.forminator-custom-form, form.forminator-ui');
        if (form) {
            var inputListaEspera = form.querySelector('#es_lista_espera_real');
            if (!inputListaEspera) {
                inputListaEspera = document.createElement('input');
                inputListaEspera.type = 'hidden';
                inputListaEspera.id = 'es_lista_espera_real';
                inputListaEspera.name = 'es_lista_espera_real';
                form.appendChild(inputListaEspera);
            }
            inputListaEspera.value = estaOcupado ? '1' : '0';
        }

        var tarjetaExistente = document.querySelector('.tarjeta-alerta-roja-exclusiva');
        if (!estaOcupado) {
            if (tarjetaExistente) { tarjetaExistente.remove(); }
            return;
        }

        var checkboxExiste = tarjetaExistente ? tarjetaExistente.querySelector('#check_consciente_lista_espera') : null;
        if (estaOcupado && (!tarjetaExistente || !checkboxExiste)) {
            if (tarjetaExistente) { tarjetaExistente.remove(); }

            var elementos = document.querySelectorAll('p, div, strong, span');
            for (var i = 0; i < elementos.length; i++) {
                var el = elementos[i];
                if (el.innerText && el.innerText.trim().indexOf('Edad:') === 0 && el.children.length === 0) {
                    
                    var contenedorAlerta = document.createElement('div');
                    contenedorAlerta.className = 'tarjeta-alerta-roja-exclusiva';
                    contenedorAlerta.style.backgroundColor = '#fff5f5';
                    contenedorAlerta.style.border = '1px solid #c53030';
                    contenedorAlerta.style.color = '#c53030';
                    contenedorAlerta.style.padding = '12px 14px';
                    contenedorAlerta.style.borderRadius = '6px';
                    contenedorAlerta.style.marginBottom = '12px';
                    contenedorAlerta.style.fontSize = '13px';
                    
                    var textoRojo = document.createElement('div');
                    textoRojo.style.fontWeight = '700';
                    textoRojo.style.marginBottom = '8px';
                    textoRojo.innerText = 'Pase lleno, entra a lista de espera';
                    contenedorAlerta.appendChild(textoRojo);

                    var labelCheck = document.createElement('label');
                    labelCheck.style.display = 'flex';
                    labelCheck.style.alignItems = 'center';
                    labelCheck.style.gap = '8px';
                    labelCheck.style.fontWeight = '600';
                    labelCheck.style.cursor = 'pointer';
                    labelCheck.style.color = '#c53030';

                    var checkbox = document.createElement('input');
                    checkbox.type = 'checkbox';
                    checkbox.id = 'check_consciente_lista_espera';
                    checkbox.name = 'check_consciente_lista_espera';
                    checkbox.required = true;

                    var spanCheck = document.createElement('span');
                    spanCheck.innerText = 'Soy consciente de que mi solicitud queda bajo lista de espera.';

                    labelCheck.appendChild(checkbox);
                    labelCheck.appendChild(spanCheck);
                    contenedorAlerta.appendChild(labelCheck);

                    el.parentNode.insertBefore(contenedorAlerta, el);
                    break;
                }
            }
        }
    }

    setTimeout(sincronizarPasesRojosForminator, 600);
    document.addEventListener('click', function() {
        setTimeout(sincronizarPasesRojosForminator, 300);
        setTimeout(actualizarResumenForminator, 400);
        setTimeout(actualizarResumenForminator, 800);
    });

    document.addEventListener('submit', function(e) {
        var checkbox = document.getElementById('check_consciente_lista_espera');
        if (checkbox && !checkbox.checked) {
            e.preventDefault();
            alert('Debes marcar la casilla para confirmar que eres consciente de que la solicitud queda en lista de espera.');
            return false;
        }
    }, true);
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
// ==========================================
// 7. SCRIPT EXCLUSIVO PARA INYECTAR AVISO DE LISTA DE ESPERA EN RESUMEN
// ==========================================
add_action( 'wp_footer', 'indgenio_alerta_resumen_exclusiva_js' );
function indgenio_alerta_resumen_exclusiva_js() {
    ?>
    <script type="text/javascript">
    function gestionarAlertaResumen() {
        // Comprobamos si estamos visualmente en la pestaña de Resumen (buscando su indicador activo)
        var tabResumenActivo = Array.from(document.querySelectorAll('.forminator-current, span, div, a')).some(function(el) {
            return el.innerText && el.innerText.trim() === 'Resumen' && (el.classList.contains('forminator-current') || el.style.color !== '' || el.getAttribute('aria-selected') === 'true' || el.closest('.forminator-current'));
        });

        // Alternativa robusta: comprobar si existe el botón "Enviar" o el título "Resumen de la Reserva"
        var tituloResumen = Array.from(document.querySelectorAll('h3, h4, div, span')).some(function(el) {
            return el.innerText && el.innerText.includes('Resumen de la Reserva');
        });

        if (!tituloResumen) {
            // Si no estamos en la pantalla de resumen, borramos cualquier tarjeta por si acaso y salimos
            document.querySelectorAll('.tarjeta-alerta-roja-exclusiva').forEach(function(t) { t.remove(); });
            return;
        }

        // Recopilar pases ocupados de la web
        var pasesOcupados = [];
        document.querySelectorAll('.app-hora-texto.estado-ocupado, .app-hora-enlace.estado-ocupado').forEach(function(el) {
            var textoHora = el.innerText.trim();
            var fila = el.closest('.app-dia-fila');
            if (fila) {
                var textoFecha = fila.querySelector('.app-dia-texto');
                if (textoFecha) {
                    var fechaLimpia = textoFecha.innerText.replace(':', '').trim();
                    pasesOcupados.push(fechaLimpia + ' ' + textoHora);
                }
            }
            pasesOcupados.push(textoHora);
        });

        var paseSeleccionado = localStorage.getItem('forminator_ultimo_pase_768') || '';
        
        var estaOcupado = pasesOcupados.some(function(paseOcupado) {
            var partes = paseOcupado.split(' ');
            var horaBuscada = partes[partes.length - 1];
            return paseSeleccionado.includes(horaBuscada) && (paseSeleccionado.includes('30 Nov') || paseSeleccionado.includes('01 Dic') || paseSeleccionado.includes(partes[0]));
        });

        var tarjetaExistente = document.querySelector('.tarjeta-alerta-roja-exclusiva');

        if (estaOcupado && !tarjetaExistente) {
            // Buscamos exclusivamente la línea que contiene "Edad:" dentro de la tarjeta de resumen
            var elementos = document.querySelectorAll('p, div, strong, span');
            for (var i = 0; i < elementos.length; i++) {
                var el = elementos[i];
                if (el.innerText && el.innerText.trim().indexOf('Edad:') === 0 && el.children.length === 0) {
                    var tarjetaRoja = document.createElement('div');
                    tarjetaRoja.className = 'tarjeta-alerta-roja-exclusiva';
                    tarjetaRoja.style.backgroundColor = '#fff5f5';
                    tarjetaRoja.style.border = '1px solid #c53030';
                    tarjetaRoja.style.color = '#c53030';
                    tarjetaRoja.style.padding = '10px 14px';
                    tarjetaRoja.style.borderRadius = '6px';
                    tarjetaRoja.style.marginBottom = '12px';
                    tarjetaRoja.style.fontWeight = '700';
                    tarjetaRoja.style.fontSize = '13px';
                    tarjetaRoja.innerText = 'Pase lleno, admite lista de espera';
                    
                    // Insertar la tarjeta roja justo encima de la Edad
                    el.parentNode.insertBefore(tarjetaRoja, el);
                    break;
                }
            }
        } else if (!estaOcupado && tarjetaExistente) {
            tarjetaExistente.remove();
        }
    }

    // Comprobar constantemente clics en el formulario para asegurar que el bloque 7 actúe al cambiar a Resumen
    document.addEventListener('click', function() {
        setTimeout(gestionarAlertaResumen, 350);
    });
    
    // Ejecutar al cargar por si se accede directamente
    setTimeout(gestionarAlertaResumen, 800);
    </script>
    <?php
}
// ==========================================
// 8. AVISO CLARO EN EL ASUNTO DEL CORREO SI EL PASE ESTÁ LLENO
// ==========================================
add_filter( 'wp_mail', 'indgenio_modificar_asunto_correo_lista_espera', 25, 1 );
function indgenio_modificar_asunto_correo_lista_espera( $args ) {
    if ( ! is_array( $args ) || ! isset( $args['subject'] ) ) {
        return $args;
    }

    // Verificar que sea un correo de reserva de Forminator
    if ( strpos( $args['subject'], 'Solicitud de asistencia' ) !== false || strpos( $args['subject'], 'Nueva Reserva' ) !== false ) {
        
        $post_id = get_option( 'forminator_evento_activo_768', 0 );
        if ( !$post_id && isset( $_SERVER['HTTP_REFERER'] ) ) {
            $post_id = url_to_postid( esc_url_raw( $_SERVER['HTTP_REFERER'] ) );
        }

        if ( $post_id && get_post_type( $post_id ) === 'tribe_events' ) {
            // Recuperar el pase seleccionado
            $pase_final = '';
            if ( isset( $_POST['pase_seleccionado_real'] ) && !empty( $_POST['pase_seleccionado_real'] ) ) {
                $pase_final = sanitize_text_field( $_POST['pase_seleccionado_real'] );
            } elseif ( isset( $_POST['pase_seleccionado'] ) && !empty( $_POST['pase_seleccionado'] ) ) {
                $pase_final = sanitize_text_field( $_POST['pase_seleccionado'] );
            } else {
                $pase_final = get_option( 'forminator_pase_activo_768', '' );
            }

            // Comprobar si está en lista de espera (completado)
            $post_obj = get_post( $post_id );
            $contenido_evento = $post_obj ? $post_obj->post_content : '';
            $es_lista_espera = false;

            if ( !empty( $pase_final ) && preg_match( '/\[completado:\s*([^\]]+)\]/i', $contenido_evento, $matches ) ) {
                $array_completadas = array_map( 'trim', explode( ',', $matches[1] ) );
                foreach ( $array_completadas as $hc ) {
                    if ( !empty( $hc ) && ( stripos( $pase_final, $hc ) !== false || stripos( $hc, $pase_final ) !== false ) ) {
                        $es_lista_espera = true;
                        break;
                    }
                }
            }

            // Si está lleno, modificamos el asunto del correo para que avise de inmediato
            if ( $es_lista_espera ) {
                // Si el asunto ya contiene la sede, inyectamos el aviso justo antes o al final
                if ( strpos( $args['subject'], ' - Educaescena' ) !== false ) {
                    $args['subject'] = str_replace( ' - Educaescena', ' [PASE LLENO - LISTA DE ESPERA] - Educaescena', $args['subject'] );
                } else {
                    $args['subject'] .= ' [PASE LLENO - LISTA DE ESPERA]';
                }
            }
        }
    }

    return $args;
}