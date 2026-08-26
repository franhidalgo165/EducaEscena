add_shortcode('mi_mapa_evento', function() {
    $venue_id = tribe_get_venue_id();
    if ($venue_id) {
        
        // Filtro temporal para modificar el zoom que pide The Events Calendar a Google
        add_filter('tribe_get_basic_gmap_embed_url_args', function($args) {
            $args['zoom'] = 17; 
            return $args;
        });

        $map = tribe_get_embedded_map($venue_id);
        
        return '<div class="mi-mapa-personalizado">' . $map . '</div>';
    }
    return 'No hay ubicación definida.';
});