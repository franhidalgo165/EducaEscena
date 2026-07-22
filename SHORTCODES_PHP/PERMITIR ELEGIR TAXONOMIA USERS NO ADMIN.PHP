function forzar_permiso_gestor_eventos() {
    // Obtenemos el rol exacto tal y como lo muestra WordPress
    $rol = get_role( 'gestor_de_eventos' ); // Si al guardarlo da error, prueba también con 'gestor-educaescena'
    if ( $rol ) {
        $rol->add_cap( 'manage_categories' );
        $rol->add_cap( 'assign_terms' );
        $rol->add_cap( 'edit_posts' );
    }
}
add_action( 'init', 'forzar_permiso_gestor_eventos' );