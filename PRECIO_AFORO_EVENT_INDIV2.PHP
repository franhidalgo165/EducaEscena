function shortcode_info_evento_mini() {
    global $wpdb;
    $evento_id = get_the_ID();
    
    $precio = tribe_get_cost($evento_id, false);
    $precio_final = (!empty($precio)) ? $precio . '€' : 'Gratuito';

    // Buscamos el aforo buscando en toda la tabla postmeta 
    // donde el valor esté vinculado al ID del evento.
    // Esto evita errores de clases y funciones no encontradas.
    $capacidad = $wpdb->get_var($wpdb->prepare("
        SELECT meta_value 
        FROM {$wpdb->postmeta} 
        WHERE meta_key = '_tribe_ticket_capacity' 
        AND post_id IN (
            SELECT post_id FROM {$wpdb->postmeta} 
            WHERE meta_key = '_tribe_wooticket_for_event' 
            AND meta_value = %d
        )
        LIMIT 1
    ", $evento_id));

    $capacidad_final = ($capacidad > 0) ? $capacidad . ' plazas' : 'Consultar';

    ob_start(); ?>
    <style>
        .container-info-evento { display: flex; gap: 15px; margin: 20px 0; font-family: 'Raleway', sans-serif; }
        .btn-evento { background-color: #ffffff; color: #198C9C; border: 2px solid #198C9C; padding: 8px 20px; border-radius: 10px; font-weight: 800; font-size: 0.9rem; text-transform: uppercase; display: inline-block; }
    </style>
    
    <div class="container-info-evento">
        <div class="btn-evento">Precio: <?php echo esc_html($precio_final); ?></div>
        <div class="btn-evento">Aforo: <?php echo esc_html($capacidad_final); ?></div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('info_evento_mini', 'shortcode_info_evento_mini');