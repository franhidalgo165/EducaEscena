add_shortcode('mi_mapa_evento', function() {
    $venue_id = tribe_get_venue_id();
    if ($venue_id) {
        return tribe_get_embedded_map($venue_id);
    }
    return 'No hay ubicación definida.';
});