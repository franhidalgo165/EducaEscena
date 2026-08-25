add_filter( 'forminator_field_hidden_field_value', 'inyectar_correo_sede_al_formulario', 10, 3 );
function inyectar_correo_sede_al_formulario( $value, $field, $form_id ) {
    
    // Solo actuamos en tu formulario ID 768
    if ( $form_id == 768 ) {
        
        // 1. Detectamos que estamos en un evento de The Events Calendar
        if ( is_singular( 'tribe_events' ) ) {
            
            // 2. Buscamos qué sede (taxonomía) tiene marcada este evento
            $terms = get_the_terms( get_the_ID(), 'sedes' );
            
            if ( $terms && ! is_wp_error( $terms ) ) {
                $term = $terms[0]; // Cogemos la sede seleccionada
                
                // 3. Sacamos el correo que guardamos en esa sede
                $correo_sede = get_term_meta( $term->term_id, 'correo_sede', true );
                
                // 4. Si el campo oculto es hidden-1 y tenemos un correo, lo inyectamos
                if ( $field['element_id'] === 'hidden-1' && ! empty( $correo_sede ) ) {
                    return $correo_sede;
                }
            }
        }
    }
    return $value;
}