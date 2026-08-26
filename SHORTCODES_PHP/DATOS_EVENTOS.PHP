function shortcode_eventos_sede_horizontal_completo() {
    // 1. OBTENER EL OBJETO DE LA SEDE (Comprobación de Elementor)
    $term = get_queried_object();
    
    if ( ! ( $term instanceof WP_Term ) ) {
        return '<p style="font-family:\'Raleway\',sans-serif; color:#718096; text-align:center; padding:20px;">Atención: Este shortcode está diseñado para usarse dentro de una Plantilla de Archivo de Elementor.</p>';
    }

    $sede_slug = $term->slug;
    $sede_tax  = $term->taxonomy;
    $fecha_actual = current_time('Y-m-d H:i:s');
    
    // 2. CONSULTA BLINDADA (Filtra por sede y eventos futuros)
    $args = array(
        'post_type'      => 'tribe_events',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'tax_query'      => array(
            array(
                'taxonomy' => $sede_tax,
                'field'    => 'slug',
                'terms'    => $sede_slug,
            ),
        ),
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

    $query = new WP_Query($args);
    
    // 3. DISEÑO CSS UNIFICADO
    $html = '
    <style>
        .app-eventos-container { display: flex; flex-direction: column; width: 100%; box-sizing: border-box; }
        .app-mes-header { font-family: "Raleway", sans-serif !important; font-size: 32px !important; font-weight: 700 !important; color: #198C9C !important; margin: 40px 0 20px 0 !important; padding-bottom: 10px; border-bottom: 2px solid #edf2f7; text-transform: capitalize; }
        .app-mes-header:first-child { margin-top: 0 !important; }
        .app-card-evento { display: flex; flex-direction: row; align-items: stretch; background: #ffffff; border-radius: 16px; border: 1px solid #edf2f7; box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.03); width: 100%; box-sizing: border-box; transition: transform 0.2s ease, box-shadow 0.2s ease; overflow: hidden; margin-bottom: 35px; }
        .app-card-evento:hover { transform: translateY(-2px); box-shadow: 0px 6px 25px rgba(0, 0, 0, 0.06); }
        
        /* CONTENEDOR Y RECORTE FIJO DE LA IMAGEN */
        .app-card-img-box { width: 300px; min-height: 100%; flex-shrink: 0; display: flex; overflow: hidden; background-color: #0e7490; }
        .app-card-img { width: 100%; height: 100%; object-fit: cover; object-position: center; display: block; }
        
        .app-card-content-box { width: 70%; display: flex; justify-content: space-between; align-items: center; gap: 20px; padding: 20px 24px; box-sizing: border-box; }
        .app-card-textos { display: flex; flex-direction: column; gap: 8px; flex-grow: 1; }
        .app-header-titulo-box { display: flex; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 4px; }
        
        .app-titulo-link { text-decoration: none !important; color: inherit; transition: color 0.3s ease; }
        .app-titulo-link:hover .app-card-titulo { color: #0e7490 !important; }
        .app-card-titulo { font-family: "Raleway", sans-serif !important; font-size: 24px !important; font-weight: 600 !important; color: #198C9C !important; margin: 0 !important; }
        
        .app-card-badge { display: inline-flex; align-items: center; font-family: "Raleway", sans-serif !important; font-size: 11px !important; font-weight: 700 !important; color: #ffffff !important; padding: 4px 10px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.5px; line-height: 1; }
        .app-card-badge svg { margin-right: 6px; width: 14px; height: 14px; border-radius: 2px; }
        .badge-idioma { background: #0e7490; }
        .badge-edad { background: #4a5568; padding-top:6px; padding-bottom: 6px;}
        
        .app-card-detalles { display: flex; flex-direction: column; gap: 8px; font-family: "Raleway", sans-serif !important; font-size: 15px !important; color: #4a5568 !important; margin: 0 !important; }
        .app-detalle-linea { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
        .app-detalle-linea svg { color: #A0CED4; flex-shrink: 0; }
        .app-detalle-precio { font-weight: 700 !important; color: #0e7490 !important; }
        
        /* Lista vertical de pases sin fondo */
        .app-pases-lista-vertical { display: flex; flex-direction: column; gap: 4px; width: 100%; margin-top: 2px; }
        .app-dia-fila { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
        .app-dia-texto { font-weight: 600; color: #4a5568; }
        .app-hora-enlace { color: #198C9C !important; font-weight: 600; text-decoration: none !important; transition: color 0.2s; }
        .app-hora-enlace:hover { color: #0e7490 !important; text-decoration: underline !important; }
        .app-separador-pipe { color: #cbd5e1; font-weight: 300; margin: 0 2px; }

        .app-card-boton-box { flex-shrink: 0; display: flex; flex-direction: column; align-items: center; gap: 5px; }
        .app-card-btn { display: inline-block; background-color: #0e7490; color: #ffffff !important; padding: 12px 24px; border-radius: 30px; font-family: "Raleway", sans-serif !important; font-size: 16px !important; font-weight: 700 !important; text-decoration: none !important; white-space: nowrap; transition: background-color 0.2s ease; }
        .app-card-btn:hover { background-color: #0891b2; }
        
        .app-estado-container { font-family: "Raleway", sans-serif !important; font-size: 13px !important; color: #4a5568; margin-top: 5px; }
        .estado-disponible { color: #2f855a !important; font-weight: 800 !important; }
        .estado-ocupado { color: #c53030 !important; font-weight: 800 !important; }

        @media (max-width: 768px) { 
            .app-card-evento { flex-direction: column; } 
            .app-card-img-box { width: 100%; height: 200px; min-height: 200px; } 
            .app-card-content-box { width: 100%; flex-direction: column; align-items: flex-start; gap: 16px; padding: 20px; } 
            .app-card-btn { width: 100%; text-align: center; box-sizing: border-box; } 
        }
    </style>
    ';

    $html .= '<div class="app-eventos-container">';

    if ($query->have_posts()) {
        $eventos_agrupados = array();

        while ($query->have_posts()) {
            $query->the_post();
            $id = get_the_ID();
            $titulo = get_the_title();
            $clave_grupo = sanitize_title($titulo);

            $inicio_raw = get_post_meta($id, '_EventStartDate', true);
            
            if (!isset($eventos_agrupados[$clave_grupo])) {
                $eventos_agrupados[$clave_grupo] = array(
                    'titulo'      => $titulo,
                    'enlace'      => get_permalink($id),
                    'imagen'      => has_post_thumbnail($id) ? get_the_post_thumbnail_url($id, 'large') : 'https://ui-avatars.com/api/?name=' . urlencode($titulo) . '&background=0e7490&color=fff&size=600',
                    'idioma'      => (($t = get_the_terms($id, 'idioma')) && !is_wp_error($t)) ? $t[0]->name : '',
                    'edad'        => (($t = get_the_terms($id, 'edad')) && !is_wp_error($t)) ? $t[0]->name : '',
                    'duracion'    => (($t = get_the_terms($id, 'duracion')) && !is_wp_error($t)) ? $t[0]->name : '',
                    'estado'      => (($t = get_the_terms($id, 'estado_pases')) && !is_wp_error($t)) ? $t[0]->name : '',
                    'organizador' => tribe_get_organizer($id),
                    'lugar'       => tribe_get_venue($id), // Actualizado al sistema nativo de TEC
                    'precio'      => tribe_get_cost($id),
                    'pases'       => array()
                );
            }

            if ($inicio_raw >= $fecha_actual) {
                $eventos_agrupados[$clave_grupo]['pases'][] = array(
                    'id'        => $id,
                    'fecha_key' => date('Y-m-d', strtotime($inicio_raw)),
                    'fecha'     => tribe_get_start_date($id, false, 'd M'),
                    'hora'      => tribe_get_start_date($id, false, 'H:i') . 'h',
                    'enlace'    => get_permalink($id),
                    'raw'       => $inicio_raw
                );
            }
        }
        wp_reset_postdata();

        foreach ($eventos_agrupados as $clave => $datos) {
            if (empty($datos['pases'])) {
                unset($eventos_agrupados[$clave]);
                continue;
            }
            usort($eventos_agrupados[$clave]['pases'], function($a, $b) {
                return strcmp($a['raw'], $b['raw']);
            });
        }

        if (!empty($eventos_agrupados)) {
            uasort($eventos_agrupados, function($a, $b) {
                return strcmp($a['pases'][0]['raw'], $b['pases'][0]['raw']);
            });

            $mes_actual_tracker = '';

            foreach ($eventos_agrupados as $evento) {
                $primer_pase_raw = $evento['pases'][0]['raw'];
                $mes_anio_key = date('Y-m', strtotime($primer_pase_raw));
                
                if ($mes_anio_key !== $mes_actual_tracker) {
                    $nombre_mes = wp_date('F Y', strtotime($primer_pase_raw));
                    $html .= '<h2 class="app-mes-header">' . esc_html($nombre_mes) . '</h2>';
                    $mes_actual_tracker = $mes_anio_key;
                }

                $precio_raw = $evento['precio'];
                $precio = (!empty($precio_raw) && is_numeric($precio_raw)) ? $precio_raw . '€' : (empty($precio_raw) || $precio_raw == '0' ? 'Gratuito' : $precio_raw);
                $clase_estado = (strtolower($evento['estado']) == 'ocupado') ? 'estado-ocupado' : 'estado-disponible';

                // Banderas SVG
                $svg_bandera = '';
                if ($evento['idioma']) {
                    $i = strtolower(trim($evento['idioma']));
                    if (strpos($i, 'español') !== false || strpos($i, 'esp') !== false) {
                        $svg_bandera = '<svg viewBox="0 0 750 500" xmlns="http://www.w3.org/2000/svg"><rect width="750" height="500" fill="#c60b1e"/><rect width="750" height="250" y="125" fill="#ffc400"/></svg>';
                    } elseif (strpos($i, 'inglés') !== false || strpos($i, 'eng') !== false) {
                        $svg_bandera = '<svg viewBox="0 0 60 30" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#s)"><path d="M0,0 v30 h60 v-30 z" fill="#012169"/><path d="M0,0 L60,30 M60,0 L0,30" stroke="#fff" stroke-width="6"/><path d="M30,0 v30 M0,15 h60" stroke="#fff" stroke-width="10"/></g></svg>';
                    }
                }

                // Agrupar los pases por día
                $pases_por_dia = array();
                foreach ($evento['pases'] as $p) {
                    $pases_por_dia[$p['fecha_key']]['fecha_formateada'] = $p['fecha'];
                    $pases_por_dia[$p['fecha_key']]['horas'][] = array(
                        'hora'   => $p['hora'],
                        'enlace' => $p['enlace']
                    );
                }

                // HTML TARJETA
                $html .= '<div class="app-card-evento">';
                $html .= '  <div class="app-card-img-box"><img src="' . esc_url($evento['imagen']) . '" alt="' . esc_attr($evento['titulo']) . '" class="app-card-img"></div>';
                $html .= '  <div class="app-card-content-box">';
                $html .= '      <div class="app-card-textos">';
                $html .= '          <div class="app-header-titulo-box"><a href="' . esc_url($evento['enlace']) . '" target="_blank" class="app-titulo-link"><h3 class="app-card-titulo">' . esc_html($evento['titulo']) . '</h3></a>';
                if ($evento['idioma']) $html .= '<span class="app-card-badge badge-idioma">' . $svg_bandera . esc_html($evento['idioma']) . '</span>'; 
                if ($evento['edad']) $html .= '<span class="app-card-badge badge-edad">' . esc_html($evento['edad']) . '</span>'; 
                $html .= '          </div>';
                
                $html .= '          <div class="app-card-detalles">';
                
                if (!empty($pases_por_dia)) {
                    $html .= '          <div class="app-detalle-linea"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg> Pases:';
                    $html .= '          <div class="app-pases-lista-vertical">';
                    
                    foreach ($pases_por_dia as $dia) {
                        $html .= '          <div class="app-dia-fila">';
                        $html .= '              <span class="app-dia-texto">' . esc_html($dia['fecha_formateada']) . ':</span>';
                        
                        $total_horas = count($dia['horas']);
                        foreach ($dia['horas'] as $idx => $h) {
                            $html .= '          <a href="' . esc_url($h['enlace']) . '" target="_blank" class="app-hora-enlace">' . esc_html($h['hora']) . '</a>';
                            if ($idx < $total_horas - 1) {
                                $html .= '      <span class="app-separador-pipe">|</span>';
                            }
                        }
                        
                        $html .= '          </div>';
                    }
                    
                    $html .= '          </div></div>';
                }

                if ($evento['lugar']) $html .= '<div class="app-detalle-linea" style="margin-top: 4px;"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg> ' . esc_html($evento['lugar']) . '</div>'; 
                if ($evento['organizador']) $html .= '<div class="app-detalle-linea"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg> ' . esc_html($evento['organizador']) . '</div>';
                if ($evento['duracion']) $html .= '<div class="app-detalle-linea"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg> Duración: ' . esc_html($evento['duracion']) . '</div>';
                
                $html .= '              <div class="app-detalle-linea app-detalle-precio" style="margin-top: 4px;">Precio: ' . esc_html($precio) . '</div>';
                $html .= '          </div>';
                $html .= '      </div>';
                
                $html .= '      <div class="app-card-boton-box">';
                $html .= '          <a href="' . esc_url($evento['enlace']) . '" target="_blank" class="app-card-btn">Reservar</a>';
                if ($evento['estado']) {
                    $html .= '      <div class="app-estado-container">Estado: <span class="' . $clase_estado . '">' . esc_html($evento['estado']) . '</span></div>';
                }
                $html .= '      </div>';
                $html .= '  </div>';
                $html .= '</div>';
            }
        } else {
            $html .= '<p style="font-family:\'Raleway\',sans-serif; text-align:center;">No hay eventos próximos programados en esta sede.</p>';
        }
    } else {
        $html .= '<p style="font-family:\'Raleway\',sans-serif; text-align:center;">No hay eventos próximos programados en esta sede.</p>';
    }

    $html .= '</div>';
    return $html;
}
add_shortcode('mis_eventos_sedes', 'shortcode_eventos_sede_horizontal_completo');