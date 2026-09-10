// ==========================================
// control total de acceso, menús y guía educaescena
// ==========================================

// 1. ocultar menús sobrantes y añadir la opción de "guía de gestión" en el panel lateral
add_action( 'admin_menu', 'indgenio_ajustar_menu_lateral_gestor', 9999 );
function indgenio_ajustar_menu_lateral_gestor() {
    if ( ! current_user_can( 'administrator' ) ) {
        remove_menu_page( 'profile.php' );
        remove_menu_page( 'index.php' ); 
        remove_menu_page( 'elementor' );
        remove_menu_page( 'edit.php?post_type=elementor_library' ); 
        
        remove_submenu_page( 'elementor', 'elementor' );
        remove_submenu_page( 'elementor', 'elementor-role-manager' );
        remove_submenu_page( 'elementor', 'elementor-tools' );
        remove_submenu_page( 'elementor', 'elementor-system-info' );
        remove_submenu_page( 'elementor', 'elementor-license' );
        remove_submenu_page( 'elementor', 'elementor-getting-started' );
        remove_submenu_page( 'edit.php?post_type=elementor_library', 'elementor-library' );

        add_menu_page(
            'Guía de gestión',
            '📖 Guía de gestión',
            'read',
            'index.php',
            '',
            'dashicons-welcome-learn-more',
            2
        );
    }
}

// 2. bloquear accesos por url directa a elementor
add_action( 'admin_init', 'indgenio_bloquear_acceso_elementor' );
function indgenio_bloquear_acceso_elementor() {
    if ( is_admin() && ! current_user_can( 'administrator' ) ) {
        global $pagenow;
        if ( $pagenow === 'admin.php' && isset( $_GET['page'] ) && strpos( $_GET['page'], 'elementor' ) !== false ) {
            wp_die( 'No tienes permisos para acceder a esta sección.' );
        }
        if ( $pagenow === 'edit.php' && isset( $_GET['post_type'] ) && $_GET['post_type'] === 'elementor_library' ) {
            wp_die( 'No tienes permisos para acceder a las plantillas.' );
        }
    }
}

// 3. inyectar únicamente el logotipo corporativo centrado a petición de la dirección
add_action( 'admin_notices', 'indgenio_mostrar_guia_bienvenida_directa' );
function indgenio_mostrar_guia_bienvenida_directa() {
    if ( current_user_can( 'administrator' ) ) {
        return;
    }

    global $pagenow;
    if ( $pagenow !== 'index.php' ) {
        return;
    }

    $logo_url = 'https://educaescena.es/wp-content/uploads/2026/07/LOGOTIPO-EDUCAESCENA_COLOR.png';

    echo '
    <div style="background: #ffffff; padding: 60px 40px; border-radius: 18px; font-family: \'Raleway\', sans-serif; max-width: 600px; margin: 80px auto; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.08); border: 1px solid #cbd5e1;">
        <img src="' . esc_url( $logo_url ) . '" alt="educaescena" style="max-width: 280px; height: auto; display: block; margin: 0 auto;" />
    </div>';
}

// 4. limpiar elementos sobrantes del escritorio estándar y ocultar rastro visual de elementor
add_action( 'admin_head', 'indgenio_estilos_limpieza_escritorio' );
function indgenio_estilos_limpieza_escritorio() {
    if ( current_user_can( 'administrator' ) ) {
        return;
    }
    
    global $pagenow;
    if ( $pagenow === 'index.php' ) {
        echo '
        <style>
            #wp-dashboard-widget-notice, .welcome-panel, #dashboard-widgets-wrap, #screen-meta-links, .update-nag, #wpbody-content > .wrap > h1 { display: none !important; }
            #wpbody-content { background: transparent !important; }
            li#menu-posts-elementor_library, li.toplevel_page_elementor { display: none !important; }
        </style>';
    }
}