function get_evento_info_shortcode() {
    if ( !function_exists( 'tribe_get_venue' ) ) return 'Tribe Events inactivo.';

    $evento_id = get_the_ID();
    
    if ( get_post_type($evento_id) === 'tribe_events' ) {
        // Datos con respaldo por si falla la función básica
        $titulo = get_the_title($evento_id);
        $fecha = tribe_get_start_date($evento_id, false, 'd/m/Y');
        $lugar = tribe_get_venue($evento_id);
        $precio = tribe_get_cost($evento_id, true);
        
        $output = '<div class="evento-resumen-pro" style="font-family: Raleway, sans-serif; background:#f4f7f7; padding:20px; border-radius:12px; border: 1px solid #A0CED4; color: #1D1E1C;">';
        $output .= '<h3 style="margin-top:0; color: #198C9C; font-weight: 700; font-size: 20px;">' . $titulo . '</h3>';
        $output .= '<p style="margin: 5px 0;"><strong>Fecha:</strong> ' . $fecha . '</p>';
        $output .= '<p style="margin: 5px 0;"><strong>Lugar:</strong> ' . ($lugar ? $lugar : 'Consultar ubicación') . '</p>';
        $output .= '<p style="margin: 5px 0;"><strong>Precio:</strong> ' . ($precio ? $precio : 'Gratuito / Consultar') . '</p>';
        $output .= '</div>';
        
        return $output;
    }
    return 'Resumen del evento disponible en la página del evento.';
}
add_shortcode( 'info_evento', 'get_evento_info_shortcode' );