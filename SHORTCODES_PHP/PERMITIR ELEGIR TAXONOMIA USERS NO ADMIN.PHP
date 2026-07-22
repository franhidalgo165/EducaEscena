function personalizar_menu_y_permisos_gestor() {
    // 1. Asignar capacidades necesarias para que pueda usar eventos y subir imágenes
    $rol = get_role( 'gestor_de_eventos' ); // Cambia si el slug de tu rol es distinto
    if ( $rol ) {
        $rol->add_cap( 'manage_categories' );
        $rol->add_cap( 'assign_terms' );
        $rol->add_cap( 'upload_files' );
    }

    // 2. Ocultar menús del panel si NO es administrador
    if ( ! current_user_can( 'administrator' ) ) {
        // Menús principales
        remove_menu_page( 'index.php' );         // Escritorio
        remove_menu_page( 'profile.php' );       // Perfil
        remove_menu_page( 'edit.php' );          // Entradas (Blog)
        remove_menu_page( 'elementor' );         // Elementor
        remove_menu_page( 'edit-comments.php' ); // Comentarios
        remove_menu_page( 'tools.php' );         // Herramientas

        // Submenús sobrantes dentro de Eventos (The Events Calendar)
        remove_submenu_page( 'edit.php?post_type=tribe_events', 'edit-tags.php?taxonomy=tribe_events_cat&post_type=tribe_events' ); // Categorías de Evento
        remove_submenu_page( 'edit.php?post_type=tribe_events', 'edit-tags.php?taxonomy=post_tag&post_type=tribe_events' );         // Etiquetas
        remove_submenu_page( 'edit.php?post_type=tribe_events', 'tribe-common-gutenberg-rest-api' );                              // Incrustaciones / Ajustes
        remove_submenu_page( 'edit.php?post_type=tribe_events', 'aggregator' );                                                  // Importar
    }
}
add_action( 'admin_menu', 'personalizar_menu_y_permisos_gestor', 999 );