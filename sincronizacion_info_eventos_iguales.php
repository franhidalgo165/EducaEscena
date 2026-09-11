// ==========================================
// 1. CREAR LA CAJA "CÓDIGO DE SINCRONIZACIÓN" EN EL EDITOR
// ==========================================
add_action( 'add_meta_boxes', 'indgenio_agregar_caja_sincronizacion' );
function indgenio_agregar_caja_sincronizacion() {
    add_meta_box( 
        'indgenio_sync_box', 
        '🔄 Sincronización de Eventos', 
        'indgenio_renderizar_caja_sincronizacion', 
        'tribe_events', 
        'side', 
        'high' 
    );
}

function indgenio_renderizar_caja_sincronizacion( $post ) {
    $codigo_actual = get_post_meta( $post->ID, 'indgenio_codigo_sync', true );
    echo '<p style="font-size:13px; color:#666; margin-bottom:8px;">Mismo código para compartir contenido y galería entre sedes independientes.</p>';
    echo '<input type="text" name="indgenio_codigo_sync" value="' . esc_attr( $codigo_actual ) . '" style="width:100%; border: 2px solid #189c9c; border-radius: 4px; padding: 6px;" placeholder="Ej: catrina-2026" />';
}


// ==========================================
// 2. MOTOR DE SINCRONIZACIÓN AUTOMÁTICA (Seguro y ligero)
// ==========================================
add_action( 'save_post_tribe_events', 'indgenio_sincronizar_eventos_por_codigo', 99, 3 );
function indgenio_sincronizar_eventos_por_codigo( $post_id, $post, $update ) {
    
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( wp_is_post_revision( $post_id ) ) return;
    if ( $post->post_status !== 'publish' && $post->post_status !== 'draft' ) return;

    if ( isset( $_POST['indgenio_codigo_sync'] ) ) {
        $codigo_sync = sanitize_text_field( $_POST['indgenio_codigo_sync'] );
        update_post_meta( $post_id, 'indgenio_codigo_sync', $codigo_sync );
    } else {
        $codigo_sync = get_post_meta( $post_id, 'indgenio_codigo_sync', true );
    }

    if ( empty( $codigo_sync ) ) return;

    static $sincronizando = false;
    if ( $sincronizando ) return;
    $sincronizando = true;

    $contenido    = $post->post_content; 
    $thumbnail_id = get_post_thumbnail_id( $post_id ); 
    
    // Taxonomías permitidas
    $terms_edad     = wp_get_post_terms( $post_id, 'edad', array( 'fields' => 'ids' ) );
    $terms_idioma   = wp_get_post_terms( $post_id, 'idioma', array( 'fields' => 'ids' ) );
    $terms_duracion = wp_get_post_terms( $post_id, 'duracion', array( 'fields' => 'ids' ) );
    
    // Galería ACF
    $galeria_acf      = get_field('galeria_de_imagenes', $post_id);
    $galeria_meta_key = get_post_meta( $post_id, '_galeria_de_imagenes', true );

    // Buscar hermanos con el mismo código de sincronización
    $args = array(
        'post_type'      => 'tribe_events',
        'posts_per_page' => -1,
        'post_status'    => 'any',
        'meta_query'     => array(
            array(
                'key'     => 'indgenio_codigo_sync',
                'value'   => $codigo_sync,
                'compare' => '='
            )
        ),
        'post__not_in'   => array( $post_id ),
        'fields'         => 'ids'
    );

    $eventos_hermanos = get_posts( $args );

    if ( ! empty( $eventos_hermanos ) ) {
        remove_action( 'save_post_tribe_events', 'indgenio_sincronizar_eventos_por_codigo', 99 );

        foreach ( $eventos_hermanos as $hermano_id ) {
            
            // Actualizar SOLO el Contenido
            wp_update_post( array(
                'ID'           => $hermano_id,
                'post_content' => $contenido
            ) );

            // Actualizar Taxonomías generales
            wp_set_post_terms( $hermano_id, $terms_edad, 'edad' );
            wp_set_post_terms( $hermano_id, $terms_idioma, 'idioma' );
            wp_set_post_terms( $hermano_id, $terms_duracion, 'duracion' );

            // Imagen destacada
            if ( $thumbnail_id ) {
                set_post_thumbnail( $hermano_id, $thumbnail_id );
            } else {
                delete_post_thumbnail( $hermano_id );
            }

            // Sincronizar galería ACF de forma segura
            if ( function_exists('update_field') ) {
                update_field('galeria_de_imagenes', $galeria_acf, $hermano_id);
            }
            if ( ! empty( $galeria_meta_key ) ) {
                update_post_meta( $hermano_id, '_galeria_de_imagenes', $galeria_meta_key );
            }
        }

        add_action( 'save_post_tribe_events', 'indgenio_sincronizar_eventos_por_codigo', 99, 3 );
    }

    $sincronizando = false;
}


// ==========================================
// 3. COMPATIBILIDAD DE GALERÍA EN VISTA INDIVIDUAL
// ==========================================
add_filter( 'post_thumbnail_html', 'indgenio_mostrar_galeria_en_detalle_evento', 20, 5 );
function indgenio_mostrar_galeria_en_detalle_evento( $html, $post_id, $post_thumbnail_id, $size, $attr ) {
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
            return $salida_galeria;
        }
    }
    return $html;
}