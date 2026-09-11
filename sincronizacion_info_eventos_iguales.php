// ==========================================
// SINCRONIZACIÓN AUTOMÁTICA DE EVENTOS POR GRUPO (Incluyendo cambio de título)
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
    // Comprobamos si el evento ya tiene un código de grupo asignado. Si no lo tiene, se lo creamos.
    $grupo_id = get_post_meta( $post_id, 'indgenio_grupo_id', true );
    
    if ( empty( $grupo_id ) ) {
        // Generamos un identificador único basado en el título inicial limpio y un número aleatorio
        $grupo_id = sanitize_title( $titulo_actual ) . '-' . uniqid();
        update_post_meta( $post_id, 'indgenio_grupo_id', $grupo_id );
    }

    // 4. Buscar otros eventos que pertenezcan exactamente al mismo GRUPO (independientemente de su título)
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

    // Si aún hay eventos antiguos que no tienen el metadato pero se llaman igual, los cazamos por el título actual para unificarlos
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
    $contenido    = $post->post_content; // Descripción y vídeo de YouTube
    $thumbnail_id = get_post_thumbnail_id( $post_id ); // Imagen destacada principal
    
    // Taxonomías
    $terms_edad     = wp_get_post_terms( $post_id, 'edad', array( 'fields' => 'ids' ) );
    $terms_idioma   = wp_get_post_terms( $post_id, 'idioma', array( 'fields' => 'ids' ) );
    $terms_duracion = wp_get_post_terms( $post_id, 'duracion', array( 'fields' => 'ids' ) );
    
    // Galería de ACF
    $galeria_acf = get_field('galeria_de_imagenes', $post_id);

    // --- APLICAR LOS DATOS A TODOS LOS HERMANOS ---
    if ( ! empty( $eventos_hermanos ) ) {
        foreach ( $eventos_hermanos as $hermano_id ) {
            
            // Asegurarnos de que el hermano también tenga grabado el mismo identificador de grupo
            update_post_meta( $hermano_id, 'indgenio_grupo_id', $grupo_id );

            // 1. Actualizar Título y Contenido (Descripción y YouTube)
            wp_update_post( array(
                'ID'           => $hermano_id,
                'post_title'   => $titulo_actual,
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

            // 4. Actualizar la Galería de ACF adaptando el formato array de imágenes
            if ( function_exists('update_field') ) {
                $galeria_para_guardar = array();
                if ( ! empty( $galeria_acf ) && is_array( $galeria_acf ) ) {
                    foreach ( $galeria_acf as $imagen ) {
                        // Como devuelve un array de imágenes, extraemos el ID numérico de cada una
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