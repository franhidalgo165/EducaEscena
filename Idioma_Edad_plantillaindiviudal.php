function shortcode_etiquetas_idioma_edad() {
    // Detecta el ID del evento actual
    $id = get_the_ID();
    if (!$id || get_post_type($id) !== 'tribe_events') return '';

    // Obtener términos
    $idiomas = get_the_terms($id, 'idioma');
    $edades = get_the_terms($id, 'edad');
    
    $idioma_texto = (!empty($idiomas) && !is_wp_error($idiomas)) ? $idiomas[0]->name : '';
    $edad_texto = (!empty($edades) && !is_wp_error($edades)) ? $edades[0]->name : '';

    // Lógica banderas
    $svg_bandera = '';
    if ($idioma_texto) {
        $i = strtolower(trim($idioma_texto));
        if (strpos($i, 'español') !== false || strpos($i, 'esp') !== false) {
            $svg_bandera = '<svg viewBox="0 0 750 500" style="width:14px; height:14px; margin-right:6px; border-radius:2px;"><rect width="750" height="500" fill="#c60b1e"/><rect width="750" height="250" y="125" fill="#ffc400"/></svg>';
        } elseif (strpos($i, 'inglés') !== false || strpos($i, 'eng') !== false) {
            $svg_bandera = '<svg viewBox="0 0 60 30" style="width:14px; height:14px; margin-right:6px; border-radius:2px;"><g clip-path="url(#s)"><path d="M0,0 v30 h60 v-30 z" fill="#012169"/><path d="M0,0 L60,30 M60,0 L0,30" stroke="#fff" stroke-width="6"/><path d="M30,0 v30 M0,15 h60" stroke="#fff" stroke-width="10"/></g></svg>';
        }
    }

    // Estilos y HTML
    $html = '
    <style>
        .app-etiquetas-box { display: flex; gap: 10px; flex-wrap: wrap; }
        .app-badge { display: inline-flex; align-items: center; font-family: "Raleway", sans-serif !important; font-size: 11px !important; font-weight: 700 !important; padding: 4px 10px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.5px; }
        
        /* Idioma: Fondo claro, texto color azul oscuro */
        .badge-idioma { background: #ebf6f7; color: #067988 !important; }
        
        /* Edad: Fondo oscuro, texto blanco */
        .badge-edad { background: #4a5568; color: #ffffff !important; padding-top:6px; padding-bottom: 6px; }
    </style>
    <div class="app-etiquetas-box">';
    
    if ($idioma_texto) $html .= '<span class="app-badge badge-idioma">' . $svg_bandera . esc_html($idioma_texto) . '</span>';
    if ($edad_texto) $html .= '<span class="app-badge badge-edad">' . esc_html($edad_texto) . '</span>';
    
    $html .= '</div>';
    
    return $html;
}
add_shortcode('etiquetas_evento_actual', 'shortcode_etiquetas_idioma_edad');