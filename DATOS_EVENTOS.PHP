function shortcode_eventos_sede_horizontal_completo() {
    // 1. OBTENER EL OBJETO
    $term = get_queried_object();
    
    if ( ! ( $term instanceof WP_Term ) ) {
        return '<p style="font-family:\'Raleway\',sans-serif; color:#718096; text-align:center; padding:20px;">Atención: Este shortcode está diseñado para usarse dentro de una Plantilla de Archivo de Elementor.</p>';
    }

    $sede_slug = $term->slug;
    $sede_tax  = $term->taxonomy;
    
    // 2. CONSULTA BLINDADA (Ordenada ASC para que los meses fluyan hacia el futuro)
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
        'order'          => 'ASC' // <-- Cambiado para agrupar los meses correctamente
    );

    $query = new WP_Query($args);
    
    // 3. DISEÑO CON SEPARADOR DE MESES
    $html = '
    <style>
        .app-eventos-container { display: flex; flex-direction: column; width: 100%; box-sizing: border-box; }
        
        /* Estilos para el titular del mes */
        .app-mes-header { font-family: "Raleway", sans-serif !important; font-size: 26px !important; font-weight: 800 !important; color: #1a202c !important; margin: 30px 0 15px 0 !important; padding-bottom: 8px; border-bottom: 2px solid #edf2f7; text-transform: capitalize; }
        .app-mes-header:first-child { margin-top: 0 !important; }

        .app-card-evento { display: flex; flex-direction: row; align-items: stretch; background: #ffffff; border-radius: 16px; border: 1px solid #edf2f7; box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.03); width: 100%; box-sizing: border-box; transition: transform 0.2s ease, box-shadow 0.2s ease; overflow: hidden; margin-bottom: 15px; }
        .app-card-evento:hover { transform: translateY(-2px); box-shadow: 0px 6px 25px rgba(0, 0, 0, 0.06); }
        
        .app-card-img-box { width: 30%; flex-shrink: 0; display: flex; }
        .app-card-img { width: 100%; height: 100%; object-fit: cover; display: block; background-color: #0e7490; }
        
        .app-card-content-box { width: 70%; display: flex; justify-content: space-between; align-items: center; gap: 20px; padding: 20px 24px; box-sizing: border-box; }
        
        .app-card-textos { display: flex; flex-direction: column; gap: 8px; flex-grow: 1; }
        .app-header-titulo-box { display: flex; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 4px; }
        .app-card-titulo { font-family: "Raleway", sans-serif !important; font-size: 20px !important; font-weight: 700 !important; color: #1a202c !important; margin: 0 !important; }
        
        .app-card-badge { display: inline-flex; align-items: center; font-family: "Raleway", sans-serif !important; font-size: 11px !important; font-weight: 700 !important; color: #ffffff !important; padding: 4px 10px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.5px; }
        .app-card-badge svg { width: 14px; height: 10px; margin-right: 6px; border-radius: 2px; object-fit: cover; }
        
        .badge-idioma { background: #0e7490; }
        .badge-edad { background: #4a5568; }
        
        .app-card-detalles { display: flex; flex-wrap: wrap; gap: 15px; font-family: "Raleway", sans-serif !important; font-size: 14px !important; color: #4a5568 !important; margin: 0 !important; }
        .app-detalle-item { display: flex; align-items: center; gap: 6px; }
        .app-detalle-item svg { color: #198C9C !important; stroke-width: 2.5; }
        
        .app-detalle-precio { font-weight: 700 !important; color: #0e7490 !important; }
        
        .app-card-boton-box { flex-shrink: 0; display: flex; align-items: center; }
        .app-card-btn { display: inline-block; background-color: #0e7490; color: #ffffff !important; padding: 12px 24px; border-radius: 10px; font-family: "Raleway", sans-serif !important; font-size: 14px !important; font-weight: 600 !important; text-decoration: none !important; white-space: nowrap; transition: background-color 0.2s ease; }
        .app-card-btn:hover { background-color: #0891b2; }
        
        @media (max-width: 768px) { 
            .app-card-evento { flex-direction: column; } 
            .app-card-img-box { width: 100%; height: 200px; } 
            .app-card-content-box { width: 100%; flex-direction: column; align-items: flex-start; gap: 16px; padding: 20px; } 
            .app-card-btn { width: 100%; text-align: center; box-sizing: border-box; } 
        }
    </style>
    ';

    $html .= '<div class="app-eventos-container">';

    if ($query->have_posts()) {
        $mes_actual_tracker = ''; // Variable para controlar el cambio de mes

        while ($query->have_posts()) {
            $query->the_post();
            $id = get_the_ID();
            
            // --- LÓGICA DE MESES ---
            $fecha_raw = get_post_meta($id, '_EventStartDate', true);
            $mes_anio_key = date('Y-m', strtotime($fecha_raw));
            
            if ($mes_anio_key !== $mes_actual_tracker) {
                $nombre_mes = wp_date('F Y', strtotime($fecha_raw));
                $html .= '<h2 class="app-mes-header">' . esc_html($nombre_mes) . '</h2>';
                $mes_actual_tracker = $mes_anio_key;
            }
            // -----------------------

            $titulo = get_the_title();
            $enlace = get_permalink();
            
            $imagen = has_post_thumbnail($id) ? get_the_post_thumbnail_url($id, 'medium') : 'https://ui-avatars.com/api/?name=' . urlencode($titulo) . '&background=0e7490&color=fff&size=150';
            
            $idiomas_lista = get_the_terms($id, 'idioma');
            $idioma_texto = (!empty($idiomas_lista) && !is_wp_error($idiomas_lista)) ? $idiomas_lista[0]->name : '';
            
            $edades_lista = get_the_terms($id, 'edad');
            $edad_texto = (!empty($edades_lista) && !is_wp_error($edades_lista)) ? $edades_lista[0]->name : '';
            
            $svg_bandera = '';
            if ($idioma_texto) {
                $idioma_limpio = strtolower(trim($idioma_texto));
                if (strpos($idioma_limpio, 'español') !== false || strpos($idioma_limpio, 'esp') !== false) {
                    $svg_bandera = '<svg viewBox="0 0 750 500" xmlns="http://www.w3.org/2000/svg"><rect width="750" height="500" fill="#c60b1e"/><rect width="750" height="250" y="125" fill="#ffc400"/></svg>';
                } elseif (strpos($idioma_limpio, 'inglés') !== false || strpos($idioma_limpio, 'ingles') !== false || strpos($idioma_limpio, 'eng') !== false) {
                    $svg_bandera = '<svg viewBox="0 0 60 30" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#s)"><path d="M0,0 v30 h60 v-30 z" fill="#012169"/><path d="M0,0 L60,30 M60,0 L0,30" stroke="#fff" stroke-width="6"/><path d="M30,0 v30 M0,15 h60" stroke="#fff" stroke-width="10"/></g></svg>';
                }
            }

            $fecha = tribe_get_start_date($id, false, 'd \d\e F');
            $hora = tribe_get_start_date($id, false, 'H:i');
            $lugar = tribe_get_venue($id);
            
            $precio = tribe_get_cost($id);
            $precio = (!empty($precio) && is_numeric($precio)) ? $precio . '€' : (empty($precio) || $precio == '0' ? 'Gratuito' : $precio);

            $html .= '<div class="app-card-evento">';
            $html .= '  <div class="app-card-img-box"><img src="' . esc_url($imagen) . '" alt="' . esc_attr($titulo) . '" class="app-card-img"></div>';
            $html .= '  <div class="app-card-content-box">';
            $html .= '      <div class="app-card-textos">';
            $html .= '          <div class="app-header-titulo-box"><h3 class="app-card-titulo">' . esc_html($titulo) . '</h3>';
            if ($idioma_texto) { $html .= '          <span class="app-card-badge badge-idioma">' . $svg_bandera . esc_html($idioma_texto) . '</span>'; }
            if ($edad_texto) { $html .= '          <span class="app-card-badge badge-edad">' . esc_html($edad_texto) . '</span>'; }
            $html .= '          </div>';
            $html .= '          <div class="app-card-detalles">';
            $html .= '              <span class="app-detalle-item"><svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg> ' . esc_html($fecha) . ' • ' . esc_html($hora) . 'h</span>';
            if ($lugar) { $html .= '          <span class="app-detalle-item"><svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg> ' . esc_html($lugar) . '</span>'; }
            $html .= '              <span class="app-detalle-item app-detalle-precio">Precio: ' . esc_html($precio) . '</span>';
            $html .= '          </div>';
            $html .= '      </div>';
            $html .= '      <div class="app-card-boton-box"><a href="' . esc_url($enlace) . '" class="app-card-btn">Reservar</a></div>';
            $html .= '  </div>';
            $html .= '</div>';
        }
        wp_reset_postdata();
    } else {
        $html .= '<p style="font-family:\'Raleway\',sans-serif; color:#718096; text-align:center; padding:30px; background:#f7fafc; border-radius:8px; border:1px dashed #e2e8f0;">No hay eventos programados en esta sede.</p>';
    }
    $html .= '</div>';
    return $html;
}
add_shortcode('mis_eventos_sedes', 'shortcode_eventos_sede_horizontal_completo');