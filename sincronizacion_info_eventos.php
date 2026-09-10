// ==========================================
// SINCRONIZACIÓN AUTOMÁTICA DE EVENTOS CON EL MISMO TÍTULO
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

    // 3. Buscar otros eventos que tengan exactamente el MISMO título
    $args = array(
        'post_type'      => 'tribe_events',
        'posts_per_page' => -1,
        'post_status'    => 'any',
        'title'          => $titulo_actual,
        'post__not_in'   => array( $post_id ),
        'fields'         => 'ids'
    );

    $eventos_hermanos = get_posts( $args );

    if ( ! empty( $eventos_hermanos ) ) {
        
        // --- RECOGER LOS DATOS DEL EVENTO EDITADO ---
        
        $contenido    = $post->post_content; // Descripción y vídeo de YouTube
        $thumbnail_id = get_post_thumbnail_id( $post_id ); // Imagen destacada principal
        
        // Taxonomías
        $terms_edad     = wp_get_post_terms( $post_id, 'edad', array( 'fields' => 'ids' ) );
        $terms_idioma   = wp_get_post_terms( $post_id, 'idioma', array( 'fields' => 'ids' ) );
        $terms_duracion = wp_get_post_terms( $post_id, 'duracion', array( 'fields' => 'ids' ) );
        
        // Galería de ACF con su nombre exacto
        $galeria_acf = get_field('galeria_de_imagenes', $post_id);

        // --- APLICAR LOS DATOS A TODOS LOS HERMANOS ---
        
        foreach ( $eventos_hermanos as $hermano_id ) {
            
            // 1. Actualizar Contenido (Descripción y YouTube)
            wp_update_post( array(
                'ID'           => $hermano_id,
                'post_content' => $contenido
            ) );

            // 2. Actualizar Taxonomías (Edad, Idioma, Duración)
            wp_set_post_terms( $hermano_id, $terms_edad, 'edad' );
            wp_set_post_terms( $hermano_id, $terms_idioma, 'idioma' );
            wp_set_post_terms( $hermano_id, $terms_duracion, 'duracion' );

            // 3. Actualizar Imagen Destacada Principal
            if ( $thumbnail_id ) {
                set_post_thumbnail( $hermano_id, $thumbnail_id );
            } else {
                delete_post_thumbnail( $hermano_id );
            }

            // 4. Actualizar la Galería de ACF en los eventos hermanos
            if ( function_exists('update_field') ) {
                update_field('galeria_de_imagenes', $galeria_acf, $hermano_id);
            }
        }
    }

    $sincronizando = false;
}