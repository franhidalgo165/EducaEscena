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

        add_menu_page(
            'Guía de Gestión',
            'Guía de gestión',
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

// 3. inyectar la tarjeta de guía profesional con cabecera y botones centrados
add_action( 'admin_notices', 'indgenio_mostrar_guia_bienvenida_directa' );
function indgenio_mostrar_guia_bienvenida_directa() {
    if ( current_user_can( 'administrator' ) ) {
        return;
    }

    global $pagenow;
    if ( $pagenow !== 'index.php' ) {
        return;
    }

    $url_eventos       = admin_url( 'edit.php?post_type=tribe_events' );
    $url_nuevo_evento  = admin_url( 'post-new.php?post_type=tribe_events' );
    $url_medios        = admin_url( 'upload.php' );
    $url_sedes         = admin_url( 'edit-tags.php?taxonomy=sedes&post_type=tribe_events' );
    $url_recintos      = admin_url( 'edit.php?post_type=tribe_venue' );
    $url_organizadores = admin_url( 'edit.php?post_type=tribe_organizer' );
    $url_idiomas       = admin_url( 'edit-tags.php?taxonomy=idioma&post_type=tribe_events' );
    $url_edades        = admin_url( 'edit-tags.php?taxonomy=edad&post_type=tribe_events' );
    $url_duracion      = admin_url( 'edit-tags.php?taxonomy=duracion&post_type=tribe_events' );
    $url_estado_pases  = admin_url( 'edit-tags.php?taxonomy=estado_pases&post_type=tribe_events' );
    $logo_url          = 'https://educaescena.es/wp-content/uploads/2026/07/LOGOTIPO-EDUCAESCENA_COLOR.png';

    echo '
    <div style="background: #ffffff; padding: 40px 50px; border-radius: 18px; font-family: \'Raleway\', sans-serif; max-width: 1300px; margin: 25px auto; box-shadow: 0 10px 30px rgba(0,0,0,0.08); border: 1px solid #cbd5e1; color: #1e293b;">
        
        <!-- cabecera centrada -->
        <div style="text-align: center; margin-bottom: 35px; border-bottom: 2px solid #f1f5f9; padding-bottom: 28px;">
            <img src="' . esc_url( $logo_url ) . '" alt="educaescena" style="max-width: 240px; height: auto; display: block; margin: 0 auto 15px auto;" />
            <div style="text-transform: uppercase; font-size: 11px; letter-spacing: 2.5px; color: #189c9c; font-weight: 800; font-family: \'Raleway\', sans-serif; margin-bottom: 6px;">Panel de control interno</div>
            <h1 style="margin: 0; color: #0f172a; font-size: 28px; font-weight: 800; font-family: \'Raleway\', sans-serif;">Centro de control y guía interactiva</h1>
            
            <!-- botones de acceso rápido centrados -->
            <div style="display: flex; justify-content: center; gap: 10px; flex-wrap: wrap; margin-top: 25px;">
                <a href="' . esc_url( $url_eventos ) . '" target="_blank" style="background: #189c9c; color: #ffffff; padding: 11px 20px; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 13px; font-family: \'Raleway\', sans-serif;">Eventos</a>
                <a href="' . esc_url( $url_nuevo_evento ) . '" target="_blank" style="background: #0d7a7a; color: #ffffff; padding: 11px 20px; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 13px; font-family: \'Raleway\', sans-serif;">+ Crear</a>
                <a href="' . esc_url( $url_sedes ) . '" target="_blank" style="background: #f8fafc; color: #189c9c; border: 2px solid #189c9c; padding: 9px 18px; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 13px; font-family: \'Raleway\', sans-serif;">Sedes</a>
                <a href="' . esc_url( $url_recintos ) . '" target="_blank" style="background: #f8fafc; color: #189c9c; border: 2px solid #189c9c; padding: 9px 18px; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 13px; font-family: \'Raleway\', sans-serif;">Recintos</a>
                <a href="' . esc_url( $url_organizadores ) . '" target="_blank" style="background: #f8fafc; color: #189c9c; border: 2px solid #189c9c; padding: 9px 18px; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 13px; font-family: \'Raleway\', sans-serif;">Organizadores</a>
                <a href="' . esc_url( $url_medios ) . '" target="_blank" style="background: #f8fafc; color: #189c9c; border: 2px solid #189c9c; padding: 9px 18px; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 13px; font-family: \'Raleway\', sans-serif;">Medios</a>
            </div>
        </div>

        <!-- cuadrícula de contenido cómoda y visible -->
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-bottom: 24px;">
            
            <!-- bloque 1: sedes -->
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 24px; display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div style="font-size: 17px; font-weight: 800; color: #0f172a; font-family: \'Raleway\', sans-serif; margin-bottom: 12px; display: flex; align-items: center; gap: 10px;">
                        <span style="background: #189c9c; color: #fff; width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; border-radius: 6px; font-size: 14px;">1</span> 
                        Gestión de sedes
                    </div>
                    <p style="font-size: 14px; color: #475569; line-height: 1.6; font-family: \'Raleway\', sans-serif; margin: 0 0 14px 0;">
                        Configura cada sede desde <a href="' . esc_url( $url_sedes ) . '" target="_blank" style="color: #189c9c; font-weight: 700; text-decoration: underline;">Sedes</a> definiendo su nombre, correo asociado y avisos específicos.
                    </p>
                    <div style="background: #e6f4f4; border-left: 4px solid #189c9c; padding: 10px 12px; border-radius: 0 8px 8px 0; font-size: 13px; color: #0f766e; font-weight: 700; font-family: \'Raleway\', sans-serif; margin-bottom: 16px;">
                        💡 Al crear una sede se genera automáticamente su <a href="' . esc_url( $url_organizadores ) . '" target="_blank" style="color: #0f766e; text-decoration: underline;">organizador</a> vinculado.
                    </div>
                </div>
                <a href="' . esc_url( $url_sedes ) . '" target="_blank" style="background: #ffffff; color: #189c9c; border: 2px solid #189c9c; text-align: center; padding: 10px; border-radius: 8px; font-size: 13px; font-weight: 700; font-family: \'Raleway\', sans-serif; text-decoration: none; display: block;">Gestionar sedes</a>
            </div>

            <!-- bloque 2: recintos -->
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 24px; display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div style="font-size: 17px; font-weight: 800; color: #0f172a; font-family: \'Raleway\', sans-serif; margin-bottom: 12px; display: flex; align-items: center; gap: 10px;">
                        <span style="background: #189c9c; color: #fff; width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; border-radius: 6px; font-size: 14px;">2</span> 
                        Creación de recintos
                    </div>
                    <p style="font-size: 14px; color: #475569; line-height: 1.6; font-family: \'Raleway\', sans-serif; margin: 0 0 14px 0;">
                        Da de alta los espacios físicos desde <a href="' . esc_url( $url_recintos ) . '" target="_blank" style="color: #189c9c; font-weight: 700; text-decoration: underline;">Recintos</a>.
                    </p>
                    <p style="font-size: 14px; color: #475569; line-height: 1.6; font-family: \'Raleway\', sans-serif; margin: 0 0 16px 0;">
                        Asocia cada recinto directamente con su respectiva sede y organizador correspondiente para mantener la coherencia en las reservas.
                    </p>
                </div>
                <a href="' . esc_url( $url_recintos ) . '" target="_blank" style="background: #ffffff; color: #189c9c; border: 2px solid #189c9c; text-align: center; padding: 10px; border-radius: 8px; font-size: 13px; font-weight: 700; font-family: \'Raleway\', sans-serif; text-decoration: none; display: block;">Gestionar recintos</a>
            </div>

            <!-- bloque 3: datos complementarios del espectáculo -->
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 24px; display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div style="font-size: 17px; font-weight: 800; color: #0f172a; font-family: \'Raleway\', sans-serif; margin-bottom: 12px; display: flex; align-items: center; gap: 10px;">
                        <span style="background: #189c9c; color: #fff; width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; border-radius: 6px; font-size: 14px;">3</span> 
                        Datos complementarios del espectáculo
                    </div>
                    <p style="font-size: 14px; color: #475569; line-height: 1.6; font-family: \'Raleway\', sans-serif; margin: 0 0 14px 0;">
                        Configura los valores complementarios obligatorios para los eventos:
                    </p>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                    <a href="' . esc_url( $url_idiomas ) . '" target="_blank" style="background: #ffffff; color: #189c9c; border: 1px solid #189c9c; text-align: center; padding: 10px; border-radius: 8px; font-size: 13px; font-weight: 700; font-family: \'Raleway\', sans-serif; text-decoration: none;">Idioma</a>
                    <a href="' . esc_url( $url_edades ) . '" target="_blank" style="background: #ffffff; color: #189c9c; border: 1px solid #189c9c; text-align: center; padding: 10px; border-radius: 8px; font-size: 13px; font-weight: 700; font-family: \'Raleway\', sans-serif; text-decoration: none;">Edades</a>
                    <a href="' . esc_url( $url_duracion ) . '" target="_blank" style="background: #ffffff; color: #189c9c; border: 1px solid #189c9c; text-align: center; padding: 10px; border-radius: 8px; font-size: 13px; font-weight: 700; font-family: \'Raleway\', sans-serif; text-decoration: none;">Duración</a>
                    <a href="' . esc_url( $url_estado_pases ) . '" target="_blank" style="background: #ffffff; color: #189c9c; border: 1px solid #189c9c; text-align: center; padding: 10px; border-radius: 8px; font-size: 13px; font-weight: 700; font-family: \'Raleway\', sans-serif; text-decoration: none;">Pases</a>
                </div>
            </div>

        </div>

        <!-- bloque 4: creación de eventos (ancho completo estilizado) -->
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 26px 30px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 20px;">
            <div style="flex: 1; min-width: 300px;">
                <div style="font-size: 18px; font-weight: 800; color: #0f172a; font-family: \'Raleway\', sans-serif; margin-bottom: 10px; display: flex; align-items: center; gap: 10px;">
                    <span style="background: #189c9c; color: #fff; width: 30px; height: 30px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; font-size: 15px;">4</span> 
                    Publicación y estructura final del evento
                </div>
                <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                    <span style="background: #ffffff; border: 1px solid #cbd5e1; padding: 6px 12px; border-radius: 6px; font-size: 13px; font-weight: 700; color: #334155; font-family: \'Raleway\', sans-serif;">Título y descripción</span>
                    <span style="background: #ffffff; border: 1px solid #cbd5e1; padding: 6px 12px; border-radius: 6px; font-size: 13px; font-weight: 700; color: #334155; font-family: \'Raleway\', sans-serif;">Fechas y pases</span>
                    <span style="background: #ffffff; border: 1px solid #cbd5e1; padding: 6px 12px; border-radius: 6px; font-size: 13px; font-weight: 700; color: #334155; font-family: \'Raleway\', sans-serif;">Sede / organizador</span>
                    <span style="background: #ffffff; border: 1px solid #cbd5e1; padding: 6px 12px; border-radius: 6px; font-size: 13px; font-weight: 700; color: #334155; font-family: \'Raleway\', sans-serif;">Recinto asociado</span>
                    <span style="background: #ffffff; border: 1px solid #cbd5e1; padding: 6px 12px; border-radius: 6px; font-size: 13px; font-weight: 700; color: #334155; font-family: \'Raleway\', sans-serif;">Edades y duración</span>
                    <span style="background: #ffffff; border: 1px solid #cbd5e1; padding: 6px 12px; border-radius: 6px; font-size: 13px; font-weight: 700; color: #334155; font-family: \'Raleway\', sans-serif;">Idioma y precio</span>
                    <span style="background: #ffffff; border: 1px solid #cbd5e1; padding: 6px 12px; border-radius: 6px; font-size: 13px; font-weight: 700; color: #334155; font-family: \'Raleway\', sans-serif;">Estado de pases</span>
                    <span style="background: #ffffff; border: 1px solid #cbd5e1; padding: 6px 12px; border-radius: 6px; font-size: 13px; font-weight: 700; color: #334155; font-family: \'Raleway\', sans-serif;">Imagen / galería</span>
                </div>
            </div>
            <div>
                <a href="' . esc_url( $url_nuevo_evento ) . '" target="_blank" style="display: inline-block; background: #189c9c; color: #fff; padding: 14px 28px; border-radius: 10px; font-size: 14px; font-weight: 800; font-family: \'Raleway\', sans-serif; text-decoration: none; box-shadow: 0 4px 14px rgba(24,156,156,0.3); white-space: nowrap;">+ Crear nuevo evento</a>
            </div>
        </div>

    </div>';
}

// 4. limpiar elementos sobrantes del escritorio estándar
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
        </style>';
    }
}