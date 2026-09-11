// ==========================================
// 1. SINCRONIZACIÓN AUTOMÁTICA DE EVENTOS POR GRUPO (Incluyendo cambio de título)
// ==========================================
add_action( 'save_post_tribe_events', 'indgenio_sincronizar_eventos_mismo_nombre', 20, 3 );
function indgenio_sincronizar_eventos_mismo_nombre( $post_id, $post, $update ) {
    
    // 1. Evitar autoguardados, revisiones o estados no válidos
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( wp_is_post_revision( $post_id ) ) return;
    if ( $post->post_status !== 'publish' && $post->post_status !== 'draft' ) return;

    // 2. Prevenir bucle infinito
    static $sincronizando = false;
    if ( $sincronizando ) return;

    $titulo_actual = $post->post_title;
    if ( empty( $titulo_actual ) ) return;

    $sincronizando = true;

    // 3. Sistema de Identificador Único de Grupo (Metadato Oculto)
    $grupo_id = get_post_meta( $post_id, 'indgenio_grupo_id', true );
    
    if ( empty( $grupo_id ) ) {
        $grupo_id = sanitize_title( $titulo_actual ) . '-' . uniqid();
        update_post_meta( $post_id, 'indgenio_grupo_id', $grupo_id );
    }

    // 4. Buscar otros eventos que pertenezcan exactamente al mismo GRUPO
    $args = array(
        'post_type'      => 'tribe_events',
        'posts_per_page' => -1,
        'post_status'    => 'any',
        'meta_query'     => array(
            array(
                'key'     => 'indgenio_grupo_id',
                'value'   => $grupo_id,
                'compare' => '='
            )
        ),
        'post__not_in'   => array( $post_id ),
        'fields'         => 'ids'
    );

    $eventos_hermanos = get_posts( $args );

    if ( empty( $eventos_hermanos ) ) {
        $args_titulo = array(
            'post_type'      => 'tribe_events',
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'title'          => $titulo_actual,
            'post__not_in'   => array( $post_id ),
            'fields'         => 'ids'
        );
        $eventos_hermanos = get_posts( $args_titulo );
    }

    // --- RECOGER LOS DATOS DEL EVENTO EDITADO ---
    $contenido    = $post->post_content; 
    $thumbnail_id = get_post_thumbnail_id( $post_id ); 
    
    // Taxonomías
    $terms_edad     = wp_get_post_terms( $post_id, 'edad', array( 'fields' => 'ids' ) );
    $terms_idioma   = wp_get_post_terms( $post_id, 'idioma', array( 'fields' => 'ids' ) );
    $terms_duracion = wp_get_post_terms( $post_id, 'duracion', array( 'fields' => 'ids' ) );
    
    // Galería de ACF
    $galeria_acf = get_field('galeria_de_imagenes', $post_id);

    // --- APLICAR LOS DATOS A TODOS LOS HERMANOS ---
    if ( ! empty( $eventos_hermanos ) ) {
        foreach ( $eventos_hermanos as $hermano_id ) {
            
            update_post_meta( $hermano_id, 'indgenio_grupo_id', $grupo_id );

            // 1. Actualizar Título y Contenido
            wp_update_post( array(
                'ID'           => $hermano_id,
                'post_title'   => $titulo_actual,
                'post_content' => $contenido
            ) );

            // 2. Actualizar Taxonomías
            wp_set_post_terms( $hermano_id, $terms_edad, 'edad' );
            wp_set_post_terms( $hermano_id, $terms_idioma, 'idioma' );
            wp_set_post_terms( $hermano_id, $terms_duracion, 'duracion' );

            // 3. Actualizar Imagen Destacada Principal
            if ( $thumbnail_id ) {
                set_post_thumbnail( $hermano_id, $thumbnail_id );
            } else {
                delete_post_thumbnail( $hermano_id );
            }

            // 4. Actualizar la Galería de ACF
            if ( function_exists('update_field') ) {
                $galeria_para_guardar = array();
                if ( ! empty( $galeria_acf ) && is_array( $galeria_acf ) ) {
                    foreach ( $galeria_acf as $imagen ) {
                        if ( is_array( $imagen ) && isset( $imagen['ID'] ) ) {
                            $galeria_para_guardar[] = $imagen['ID'];
                        } elseif ( is_numeric( $imagen ) ) {
                            $galeria_para_guardar[] = $imagen;
                        }
                    }
                }
                update_field('galeria_de_imagenes', $galeria_para_guardar, $hermano_id);
            }
        }
    }

    $sincronizando = false;
}


// ==========================================
// 2. COMPATIBILIDAD INTELIGENTE: DESTACADA EN SEDES/PROGRAMACIÓN Y GALERÍA EN EVENTO INDIVIDUAL
// ==========================================
add_filter( 'post_thumbnail_html', 'indgenio_mostrar_galeria_en_detalle_evento', 20, 5 );
function indgenio_mostrar_galeria_en_detalle_evento( $html, $post_id, $post_thumbnail_id, $size, $attr ) {
    // Si estamos en la página individual de un evento, comprobamos si hay galería ACF
    if ( is_singular( 'tribe_events' ) && in_the_loop() && is_main_query() ) {
        $galeria_acf = get_field( 'galeria_de_imagenes', $post_id );
        
        if ( ! empty( $galeria_acf ) && is_array( $galeria_acf ) ) {
            $salida_galeria = '<div class="indgenio-evento-galeria-dinamica" style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 20px;">';
            
            foreach ( $galeria_acf as $imagen ) {
                $img_url = '';
                if ( is_array( $imagen ) && isset( $imagen['url'] ) ) {
                    $img_url = $imagen['url'];
                } elseif ( is_numeric( $imagen ) ) {
                    $img_url = wp_get_attachment_url( $imagen );
                }
                
                if ( ! empty( $img_url ) ) {
                    $salida_galeria .= '<img src="' . esc_url( $img_url ) . '" alt="Galería del evento" style="max-width: 100%; height: auto; border-radius: 8px; flex: 1 1 calc(50% - 10px);">';
                }
            }
            
            $salida_galeria .= '</div>';
            return $salida_galeria; // Muestra la galería en la vista de detalle
        }
    }
    
    // En cualquier otro sitio (Página de Sedes, Programación, widgets, etc.), devuelve la imagen destacada con total normalidad
    return $html;
}