// ==========================================
// 1. SHORTCODE DE RESUMEN Y TARJETA DEL EVENTO (Con Precio Numérico Oculto)
// ==========================================
function get_evento_info_shortcode() {
    if ( ! function_exists( 'tribe_get_venue' ) ) {
        return 'Tribe Events inactivo.';
    }

    $evento_id = get_the_ID();
    
    if ( ! $evento_id || get_post_type( $evento_id ) !== 'tribe_events' ) {
        $evento_id = get_option( 'forminator_evento_activo_768', 0 );
    }

    if ( $evento_id && get_post_type( $evento_id ) === 'tribe_events' ) {
        $titulo          = get_the_title( $evento_id );
        $fecha           = tribe_get_start_date( $evento_id, false, 'd/m/Y' );
        $hora            = tribe_get_start_date( $evento_id, false, 'H:i' );
        
        $terminos_edad   = strip_tags( get_the_term_list( $evento_id, 'edad', '', ', ', '' ) );
        $terminos_idioma = strip_tags( get_the_term_list( $evento_id, 'idioma', '', ', ', '' ) );
        $terminos_sedes  = strip_tags( get_the_term_list( $evento_id, 'sedes', '', ', ', '' ) );
        
        $lugar = tribe_get_venue( $evento_id );
        if ( empty( $lugar ) ) {
            $lugar = ! empty( $terminos_sedes ) ? $terminos_sedes : get_post_meta( $evento_id, '_EventVenue', true );
        }

        // Gestión inteligente y limpia del precio
        $precio_raw = tribe_get_cost( $evento_id, true );
        $precio_limpio_str = trim( str_replace( array('€', '$', '£', 'EUR'), '', $precio_raw ) );

        $precio_num = 0;
        if ( empty( $precio_raw ) || $precio_limpio_str === '' || stripos( $precio_raw, 'gratis' ) !== false || stripos( $precio_raw, 'free' ) !== false ) {
            $precio = 'Gratuito';
            $precio_num = 0;
        } else {
            $precio = $precio_raw;
            $precio_num = floatval( str_replace(',', '.', $precio_limpio_str) );
        }

        // Capturar de forma segura si el usuario envió el dato por POST
        $pase_elegido = '';
        if ( isset( $_POST['pase_seleccionado'] ) && ! empty( $_POST['pase_seleccionado'] ) ) {
            $pase_elegido = sanitize_text_field( $_POST['pase_seleccionado'] );
            set_transient( 'pase_usuario_' . get_current_user_id(), $pase_elegido, HOUR_IN_SECONDS );
        } else {
            $pase_elegido = get_transient( 'pase_usuario_' . get_current_user_id() );
        }
        
        // Renderizamos la tarjeta incluyendo un atributo oculto `data-precio` con el valor exacto para JavaScript
        $output  = '<div class="evento-resumen-pro" data-precio-num="' . esc_attr( $precio_num ) . '" style="font-family: Raleway, sans-serif; background:#f4f7f7; padding:20px; border-radius:12px; border: 1px solid #A0CED4; color: #1D1E1C;">';
        $output .= '<h3 style="margin-top:0; color: #198C9C; font-weight: 700; font-size: 20px;">' . esc_html( $titulo ) . '</h3>';
        
        // 1. Pase Seleccionado
        $output .= '<p style="margin: 8px 0; background: #e2f0f1; padding: 8px 12px; border-radius: 6px; color: #198C9C;"><strong>Pase Seleccionado:</strong> <span id="resumen-pase-real" style="font-weight:700;">' . ( !empty($pase_elegido) ? esc_html($pase_elegido) : 'Seleccionando pase...' ) . '</span></p>';

        // 2. Fecha, Edad, Idioma, Sede, Lugar, Precio
        $output .= '<p style="margin: 5px 0;"><strong>Fecha:</strong> ' . esc_html( $fecha ) . '</p>';
        $output .= '<p style="margin: 5px 0;"><strong>Edad:</strong> ' . esc_html( $terminos_edad ) . '</p>';
        if ( ! empty( $terminos_idioma ) ) {
            $output .= '<p style="margin: 5px 0;"><strong>Idioma:</strong> ' . esc_html( $terminos_idioma ) . '</p>';
        }
        $output .= '<p style="margin: 5px 0;"><strong>Sede:</strong> ' . esc_html( $terminos_sedes ) . '</p>';
        $output .= '<p style="margin: 5px 0;"><strong>Lugar:</strong> ' . esc_html( $lugar ) . '</p>';
        $output .= '<p style="margin: 5px 0;"><strong>Precio:</strong> ' . esc_html( $precio ) . '</p>';
        $output .= '</div>';
        
        return $output;
    }
    
    return 'Resumen del evento disponible en la página del evento.';
}
add_shortcode( 'info_evento', 'get_evento_info_shortcode' );


// ==========================================
// 2. EXTRACCIÓN REAL DE PASES Y CÁLCULO DE IMPORTE EN EL FOOTER
// ==========================================
add_action( 'wp_footer', function() {
    if ( ! is_singular( 'tribe_events' ) ) {
        return;
    }

    $current_id = get_the_ID();
    $titulo = get_the_title( $current_id );
    $fecha_actual = current_time( 'Y-m-d H:i:s' );

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
                    'texto' => $f_texto . " a las " . $h_texto
                );
            }
        }
        wp_reset_postdata();
    }

    if ( ! empty( $pases_array ) ) {
        usort( $pases_array, function($a, $b) {
            return strcmp($a['raw'], $b['raw']);
        });
    }

    $lista_final_pases = array();
    foreach ( $pases_array as $p ) {
        if ( ! in_array( $p['texto'], $lista_final_pases ) ) {
            $lista_final_pases[] = $p['texto'];
        }
    }

    if ( empty( $lista_final_pases ) ) {
        $lista_final_pases[] = tribe_get_start_date( $current_id, false, 'd M Y' ) . " a las " . tribe_get_start_date( $current_id, false, 'H:i' ) . "h";
    }
    ?>
    <script type="text/javascript">
        (function() {
            const pasesDisponibles = <?php echo json_encode( array_values( $lista_final_pases ) ); ?>;

            function sincronizarTodo() {
                // 1. Pintar los pases en el contenedor si existe
                const contenedorPases = document.getElementById("lista-pases-evento");
                if (contenedorPases && (contenedorPases.innerHTML.indexOf("Cargando") !== -1 || contenedorPases.children.length === 0)) {
                    let paseGuardado = localStorage.getItem("pase_seleccionado_form");
                    contenedorPases.innerHTML = "";

                    pasesDisponibles.forEach((paseTexto, index) => {
                        const label = document.createElement("label");
                        label.style.cssText = "display: flex; align-items: center; gap: 12px; background: #f8f9fa; border: 2px solid #A0CED4; padding: 12px 16px; border-radius: 12px; cursor: pointer; margin-bottom: 8px;";
                        
                        const input = document.createElement("input");
                        input.type = "radio";
                        input.name = "pase_seleccionado";
                        input.value = paseTexto;
                        input.style.accentColor = "#198C9C";

                        if (paseGuardado ? paseGuardado === paseTexto : index === 0) {
                            input.checked = true;
                            if (!paseGuardado) {
                                localStorage.setItem("pase_seleccionado_form", paseTexto);
                            }
                        }

                        input.addEventListener("change", function() {
                            if (this.checked) {
                                localStorage.setItem("pase_seleccionado_form", this.value);
                            }
                        });

                        label.addEventListener("click", function() {
                            input.checked = true;
                            localStorage.setItem("pase_seleccionado_form", input.value);
                        });

                        const texto = document.createElement("span");
                        texto.innerHTML = "<strong>" + paseTexto + "</strong>";
                        texto.style.cssText = "color: #1D1E1C; font-size: 14px; font-family: Raleway, sans-serif;";

                        label.appendChild(input);
                        label.appendChild(texto);
                        contenedorPases.appendChild(label);
                    });
                }

                // 2. Sincronizar texto del pase en el resumen
                const spanResumen = document.getElementById("resumen-pase-real");
                if (spanResumen) {
                    let seleccionadoDOM = document.querySelector('input[name="pase_seleccionado"]:checked');
                    let valorGuardado = localStorage.getItem("pase_seleccionado_form");

                    if (seleccionadoDOM) {
                        spanResumen.innerText = seleccionadoDOM.value;
                        localStorage.setItem("pase_seleccionado_form", seleccionadoDOM.value);
                    } else if (valorGuardado) {
                        spanResumen.innerText = valorGuardado;
                    } else if (pasesDisponibles.length > 0) {
                        spanResumen.innerText = pasesDisponibles[0];
                    }
                }

                // 3. CÁLCULO PRECISO DEL IMPORTE TOTAL (Leyendo el atributo data-precio-num directo de PHP)
                let precioNum = 0;
                let elemResumenPro = document.querySelector(".evento-resumen-pro");
                if (elemResumenPro && elemResumenPro.hasAttribute("data-precio-num")) {
                    precioNum = parseFloat(elemResumenPro.getAttribute("data-precio-num")) || 0;
                }

                // Sumar estrictamente las plazas de los alumnos (excluyendo docentes {number-14})
                let idsAlumnos = ['number-1', 'number-2', 'number-5', 'number-4', 'number-3', 'number-7', 'number-8', 'number-9', 'number-10', 'number-11'];
                let totalAlumnos = 0;

                idsAlumnos.forEach(function(id) {
                    let input = document.querySelector('input[name="' + id + '"]');
                    if (input) {
                        totalAlumnos += parseInt(input.value) || 0;
                    }
                });

                let importeFinal = totalAlumnos * precioNum;

                let spanImporte = document.getElementById("calculo-importe-total");
                if (spanImporte) {
                    spanImporte.innerText = importeFinal.toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + " €";
                }
            }

            setInterval(sincronizarTodo, 200);
        })();
    </script>
    <?php
});