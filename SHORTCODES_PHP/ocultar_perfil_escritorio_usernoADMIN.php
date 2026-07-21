function ocultar_menu_para_gestor() {
    // Si NO es Administrador
    if ( ! current_user_can( 'administrator' ) ) {
        // Ocultar Perfil
        remove_menu_page( 'profile.php' );

        // Ocultar Escritorio
        remove_menu_page( 'index.php' );
    }
}
add_action( 'admin_menu', 'ocultar_menu_para_gestor', 999 );