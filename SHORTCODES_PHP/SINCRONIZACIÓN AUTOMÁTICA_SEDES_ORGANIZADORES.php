// ==========================================
// SINCRONIZACIÓN AUTOMÁTICA: SEDES <-> ORGANIZADORES
// ==========================================

// 1. Crear o actualizar el organizador automáticamente al crear/editar una Sede
add_action( 'created_sedes', 'indgenio_sincronizar_organizador_sede', 10, 2 );
add_action( 'edited_sedes', 'indgenio_sincronizar_organizador_sede', 10, 2 );
function indgenio_sincronizar_organizador_sede( $term_id, $tt_id = '' ) {
    $term = get_term( $term_id, 'sedes' );
    if ( ! $term || is_wp_error( $term ) ) {
        return;
    }

    $nombre_sede = $term->name;
    
    // Comprobar si ya existe un organizador con este mismo nombre para no duplicarlo
    $organizador_existente = get_page_by_title( $nombre_sede, OBJECT, 'tribe_organizer' );

    if ( $organizador_existente ) {
        // Si ya existe, guardamos el ID asociado al término de la sede internamente
        update_term_meta( $term_id, 'tribe_organizer_id', $organizador_existente->ID );
    } else {
        // Si no existe, lo creamos automáticamente como organizador de The Events Calendar
        $organizador_data = array(
            'post_title'   => $nombre_sede,
            'post_type'    => 'tribe_organizer',
            'post_status'  => 'publish',
        );

        $organizador_id = wp_insert_post( $organizador_data );

        if ( $organizador_id && ! is_wp_error( $organizador_id ) ) {
            // Vinculamos el ID del organizador recién creado con la sede internamente mediante metadatos
            update_term_meta( $term_id, 'tribe_organizer_id', $organizador_id );
        }
    }
}

// 2. Borrar el organizador asociado automáticamente cuando se borra la Sede
add_action( 'pre_delete_term', 'indgenio_borrar_organizador_sede', 10, 2 );
function indgenio_borrar_organizador_sede( $term_id, $taxonomy ) {
    if ( $taxonomy !== 'sedes' ) {
        return;
    }

    // Recuperamos el ID del organizador vinculado internamente a esta sede
    $organizador_id = get_term_meta( $term_id, 'tribe_organizer_id', true );

    if ( $organizador_id ) {
        // Lo enviamos a la papelera automáticamente
        wp_trash_post( $organizador_id );
    }
}