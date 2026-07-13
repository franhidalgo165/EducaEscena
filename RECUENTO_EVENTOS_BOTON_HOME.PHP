function shortcode_contador_funciones_sede($atts) {
    $a = shortcode_atts(array(
        'sede' => '',
        'tax'  => 'sedes', 
    ), $atts);

    if (empty($a['sede'])) return '<span>-</span>';

    $term = get_term_by('slug', $a['sede'], $a['tax']);
    $url_sede = $term ? get_term_link($term) : '#';

    global $wpdb;
    $query = "SELECT COUNT(p.ID) FROM {$wpdb->posts} p 
              INNER JOIN {$wpdb->term_relationships} tr ON (p.ID = tr.object_id)
              INNER JOIN {$wpdb->term_taxonomy} tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id)
              INNER JOIN {$wpdb->terms} t ON (tt.term_id = t.term_id)
              WHERE p.post_type = 'tribe_events' AND p.post_status = 'publish' 
              AND t.slug = %s AND tt.taxonomy = %s";
    
    $total = (int)$wpdb->get_var($wpdb->prepare($query, $a['sede'], $a['tax']));
    $texto = ($total === 1) ? 'Función' : 'Funciones';

    $html = '
    <style>
        .btn-contador-sede { 
            display: inline-flex !important; flex-direction: column !important; 
            align-items: center !important; justify-content: center !important; 
            background-color: #dbeceb !important; border-radius: 40px !important; 
            padding: 6px 20px !important; 
            width: 110px !important; height: 50px !important; 
            flex-grow: 0 !important; flex-shrink: 0 !important;
            text-align: center !important; line-height: 1 !important; box-sizing: border-box !important;
            transition: all 0.3s ease !important; cursor: pointer !important; text-decoration: none !important;
        }
        .btn-contador-sede:hover { background-color: #198C9C !important; }
        .texto-numero { 
            color: #198C9C !important; font-family: "Raleway", sans-serif !important; 
            font-size: 18px !important; font-weight: 700 !important; 
            font-variant-numeric: tabular-nums !important;
            display: block !important; margin: 0 !important;
        }
        .texto-label { 
            color: #198C9C !important; font-family: "Raleway", sans-serif !important; 
            font-size: 12px !important; font-weight: 500 !important; 
            display: block !important; margin: 0 !important;
        }
        .btn-contador-sede:hover .texto-numero, .btn-contador-sede:hover .texto-label { color: #ffffff !important; }
    </style>

    <a href="' . esc_url($url_sede) . '" class="btn-contador-sede" target="_blank" rel="noopener">
        <span class="texto-numero">' . $total . '</span>
        <span class="texto-label">' . esc_html($texto) . '</span>
    </a>
    ';

    return $html;
}
add_shortcode('contador_sede', 'shortcode_contador_funciones_sede');