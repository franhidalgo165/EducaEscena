function shortcode_detalle_evento_unificado() {
    if ( ! is_singular( 'tribe_events' ) ) {
        return '';
    }

    $current_id = get_the_ID();
    $titulo = get_the_title( $current_id );
    $fecha_actual = current_time( 'Y-m-d H:i:s' );

    // PASO CLAVE: Si el evento usa eventos recurrentes o hijos, buscamos por ID principal o por similitud exacta de título sin filtros restrictivos de 's'
    $args = array(
        'post_type'      => 'tribe_events',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
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
    $todos_los_pases = array();

    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            $p_id = get_the_ID();
            $p_titulo = get_the_title();
            
            // Comprobación estricta de título limpio para incluir el evento actual y todos sus pases hermanos
            if ( sanitize_title( $p_titulo ) === sanitize_title( $titulo ) ) {
                $inicio_raw = get_post_meta( $p_id, '_EventStartDate', true );
                $todos_los_pases[] = array(
                    'id'        => $p_id,
                    'fecha_key' => date('Y-m-d', strtotime($inicio_raw)),
                    'fecha'     => tribe_get_start_date($p_id, false, 'd M'),
                    'hora'      => tribe_get_start_date($p_id, false, 'H:i') . 'h',
                    'raw'       => $inicio_raw
                );
            }
        }
        wp_reset_postdata();
    }

    // Asegurarnos de que el propio evento actual esté siempre incluido aunque la consulta general falle por caprichos de WP
    $current_inicio_raw = get_post_meta( $current_id, '_EventStartDate', true );
    $current_fecha_key = date('Y-m-d', strtotime($current_inicio_raw));
    $current_fecha_fmt = tribe_get_start_date($current_id, false, 'd M');
    $current_hora_fmt = tribe_get_start_date($current_id, false, 'H:i') . 'h';

    $existe_actual = false;
    foreach ($todos_los_pases as $p) {
        if ($p['raw'] === $current_inicio_raw) {
            $existe_actual = true;
            break;
        }
    }
    if (!$existe_actual) {
        $todos_los_pases[] = array(
            'id'        => $current_id,
            'fecha_key' => $current_fecha_key,
            'fecha'     => $current_fecha_fmt,
            'hora'      => $current_hora_fmt,
            'raw'       => $current_inicio_raw
        );
    }

    if ( ! empty( $todos_los_pases ) ) {
        usort( $todos_los_pases, function($a, $b) {
            return strcmp($a['raw'], $b['raw']);
        });
    }

    // Obtener detalles del evento
    $lugar = tribe_get_venue( $current_id );
    $organizador = tribe_get_organizer( $current_id );
    $duracion_texto = (($t = get_the_terms($current_id, 'duracion')) && !is_wp_error($t)) ? $t[0]->name : '';

    // Agrupar recopilando absolutamente todas las horas sin exclusiones
    $pases_por_dia = array();
    foreach ( $todos_los_pases as $p ) {
        $key = $p['fecha_key'];
        if ( ! isset( $pases_por_dia[$key] ) ) {
            $pases_por_dia[$key] = array(
                'fecha_formateada' => $p['fecha'],
                'horas'            => array()
            );
        }
        if ( ! in_array( $p['hora'], $pases_por_dia[$key]['horas'] ) ) {
            $pases_por_dia[$key]['horas'][] = $p['hora'];
        }
    }

    $html = '
    <style>
        .app-detalle-container { display: flex; flex-direction: column; gap: 12px; font-family: "Raleway", sans-serif !important; font-size: 15px !important; color: #ffffff !important; width: 100%; box-sizing: border-box; }
        .app-detalle-linea { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; color: #ffffff !important; }
        .app-detalle-linea svg { color: #ffffff !important; flex-shrink: 0; }
        
        .app-pases-lista-vertical { display: flex; flex-direction: column; gap: 4px; width: 100%; margin-top: 2px; }
        .app-dia-fila { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .app-dia-texto { font-weight: 600; color: #ffffff !important; }
        .app-hora-texto { background: rgba(255, 255, 255, 0.15); border: 1px solid rgba(255, 255, 255, 0.3); color: #ffffff !important; padding: 2px 8px; border-radius: 5px; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; }
        .app-separador-pipe { color: rgba(255, 255, 255, 0.6); font-weight: 300; margin: 0 2px; }

        .app-info-horizontal { display: flex; flex-wrap: wrap; gap: 20px; align-items: center; margin-top: 2px; }
    </style>';

    $html .= '<div class="app-detalle-container">';

    if ( ! empty( $pases_por_dia ) ) {
        $html .= '<div class="app-detalle-linea" style="align-items: flex-start;">';
        $html .= '  <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-top: 3px;"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>';
        $html .= '  <div><strong>Pases:</strong>';
        $html .= '      <div class="app-pases-lista-vertical">';
        
        foreach ( $pases_por_dia as $dia ) {
            $html .= '          <div class="app-dia-fila">';
            $html .= '              <span class="app-dia-texto">' . esc_html( $dia['fecha_formateada'] ) . ':</span>';
            
            $total_horas = count( $dia['horas'] );
            foreach ( $dia['horas'] as $idx => $hora_val ) {
                $html .= '          <span class="app-hora-texto">' . esc_html( $hora_val ) . '</span>';
                if ( $idx < $total_horas - 1 ) {
                    $html .= '      <span class="app-separador-pipe">|</span>';
                }
            }
            $html .= '          </div>';
        }
        
        $html .= '      </div>';
        $html .= '  </div>';
        $html .= '</div>';
    }

    $html .= '<div class="app-info-horizontal">';
    if ( $lugar ) {
        $html .= '<div class="app-detalle-linea"><svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg> ' . esc_html( $lugar ) . '</div>';
    }
    if ( $organizador ) {
        $html .= '<div class="app-detalle-linea"><svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg> ' . esc_html( $organizador ) . '</div>';
    }
    if ( $duracion_texto ) {
        $html .= '<div class="app-detalle-linea"><svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg> Duración: ' . esc_html( $duracion_texto ) . '</div>';
    }
    $html .= '</div>';

    $html .= '</div>';

    return $html;
}
add_shortcode( 'detalle_evento_unificado', 'shortcode_detalle_evento_unificado' );