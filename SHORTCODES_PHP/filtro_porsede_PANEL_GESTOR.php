// ==========================================
// 1. FILTRO DESPLEGABLE Y BOTÓN DE SEDE EN LA LISTA
// ==========================================
add_action( 'restrict_manage_posts', 'indgenio_filtrar_eventos_por_sede' );
function indgenio_filtrar_eventos_por_sede( $post_type ) {
    if ( 'tribe_events' === $post_type ) {
        $taxonomy = 'sedes';
        $selected = isset( $_GET[$taxonomy] ) ? $_GET[$taxonomy] : '';
        
        wp_dropdown_categories( array(
            'show_option_all' => 'Todas las sedes',
            'taxonomy'        => $taxonomy,
            'name'            => $taxonomy,
            'orderby'         => 'name',
            'selected'        => $selected,
            'hierarchical'    => true,
            'hide_empty'      => false,
            'value_field'     => 'slug',
        ) );
        
        // Botón de envío específico para el filtro
        echo '<input type="submit" id="filtrar_sede_btn" class="button" value="Filtrar por sede" style="margin-left: 5px;">';
    }
}

// 2. INTERCEPTOR DE CONSULTA PARA LAS SEDES
add_action( 'pre_get_posts', 'indgenio_aplicar_filtro_sedes_query' );
function indgenio_aplicar_filtro_sedes_query( $query ) {
    global $pagenow;
    
    if ( is_admin() && 'edit.php' === $pagenow && $query->is_main_query() ) {
        if ( isset( $_GET['post_type'] ) && 'tribe_events' === $_GET['post_type'] ) {
            if ( isset( $_GET['sedes'] ) && ! empty( $_GET['sedes'] ) && '0' !== $_GET['sedes'] ) {
                $query->set( 'tax_query', array(
                    array(
                        'taxonomy' => 'sedes',
                        'field'    => 'slug',
                        'terms'    => sanitize_text_field( $_GET['sedes'] ),
                    ),
                ) );
            }
        }
    }
}


// ==========================================
// 3. ELIMINAR COLUMNAS INÚTILES Y AÑADIR LA COLUMNA "SEDE"
// ==========================================
add_filter( 'manage_edit-tribe_events_columns', 'indgenio_modificar_columnas_eventos' );
function indgenio_modificar_columnas_eventos( $columns ) {
    unset( $columns['event-cat'] );
    unset( $columns['event_cat'] );
    unset( $columns['event-categories'] );
    unset( $columns['event-tags'] );
    unset( $columns['tag'] );

    $nuevas_columnas = array();
    foreach ( $columns as $key => $title ) {
        $nuevas_columnas[$key] = $title;
        if ( 'title' === $key ) {
            $nuevas_columnas['sede_evento'] = 'Sede';
        }
    }
    
    return $nuevas_columnas;
}

add_action( 'manage_tribe_events_posts_custom_column', 'indgenio_mostrar_sede_en_columna', 10, 2 );
function indgenio_mostrar_sede_en_columna( $column_name, $post_id ) {
    if ( 'sede_evento' === $column_name ) {
        $sedes = wp_get_post_terms( $post_id, 'sedes', array( 'fields' => 'names' ) );
        if ( ! empty( $sedes ) && ! is_wp_error( $sedes ) ) {
            echo esc_html( implode( ', ', $sedes ) );
        } else {
            echo '—';
        }
    }
}