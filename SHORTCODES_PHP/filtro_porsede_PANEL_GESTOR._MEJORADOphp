// ==========================================
// 1. ESTILOS CSS (Filtros compactos, fluidos y alineados en una línea)
// ==========================================
add_action( 'admin_head', 'indgenio_estilos_filtros_admin' );
function indgenio_estilos_filtros_admin() {
    $screen = get_current_screen();
    if ( $screen && 'tribe_events' === $screen->post_type ) {
        ?>
        <style>
            /* Ocultar columnas rebeldes de Categorías y Etiquetas */
            .wp-list-table .column-event-categories,
            .wp-list-table .column-event_cat,
            .wp-list-table .column-event-cat,
            .wp-list-table .column-tags,
            .wp-list-table .column-tag {
                display: none !important;
            }

            /* Forzar barra superior compacta y en una sola línea */
            .tablenav.top {
                display: flex !important;
                flex-wrap: wrap !important;
                align-items: center !important;
                gap: 5px !important;
                margin-bottom: 15px !important;
            }
            .tablenav.top .actions:first-of-type {
                display: flex !important;
                flex-wrap: wrap !important;
                align-items: center !important;
                gap: 5px !important;
                float: none !important;
            }
            /* Selectores e inputs optimizados para ocupar el espacio justo */
            .tablenav.top select[name="action"],
            .tablenav.top select[name="action2"],
            .tablenav.top select[name="sedes"],
            .tablenav.top select[name="idioma"],
            .tablenav.top select[name="duracion"],
            .tablenav.top select[name="estado_pases"],
            .tablenav.top select[name="filtro_recinto"],
            .tablenav.top input[type="date"] {
                width: auto !important;
                max-width: none !important;
                border-radius: 8px !important;
                border: 2px solid #189c9c !important;
                padding: 4px 28px 4px 12px !important;
                height: 34px !important;
                vertical-align: middle !important;
                background-color: #fff !important;
                text-align: left !important;
                font-size: 13px !important;
                box-shadow: inset 0 1px 2px rgba(0,0,0,0.04);
            }
            /* Botones compactos */
            .tablenav.top input[id="doaction"],
            .tablenav.top input[id="doaction2"],
            .tablenav.top input[type="submit"] {
                width: auto !important;
                border-radius: 8px !important;
                border: 2px solid #189c9c !important;
                background-color: #189c9c !important;
                color: #fff !important;
                font-weight: 600 !important;
                padding: 0 14px !important;
                height: 34px !important;
                vertical-align: middle !important;
                text-align: center !important;
                font-size: 13px !important;
                cursor: pointer;
            }
            .tablenav.top input[id="doaction"]:hover,
            .tablenav.top input[id="doaction2"]:hover,
            .tablenav.top input[type="submit"]:hover {
                background-color: #147f7f !important;
                border-color: #147f7f !important;
            }
            .wp-list-table.posts tbody tr {
                font-size: 13.5px !important;
            }
        </style>
        <?php
    }
}


// ==========================================
// 2. FILTROS AVANZADOS Y BOTONES EN LA LISTA DE EVENTOS
// ==========================================
add_action( 'restrict_manage_posts', 'indgenio_filtros_avanzados_eventos' );
function indgenio_filtros_avanzados_eventos( $post_type ) {
    if ( 'tribe_events' === $post_type ) {
        
        // --- 1. Filtro por Sede ---
        $sede_actual = isset( $_GET['sedes'] ) ? $_GET['sedes'] : '';
        wp_dropdown_categories( array(
            'show_option_all' => 'Todas las sedes',
            'taxonomy'        => 'sedes',
            'name'            => 'sedes',
            'orderby'         => 'name',
            'selected'        => $sede_actual,
            'hierarchical'    => true,
            'hide_empty'      => false,
            'value_field'     => 'slug',
        ) );

        // --- 2. Filtro por Idioma ---
        $idioma_actual = isset( $_GET['idioma'] ) ? $_GET['idioma'] : '';
        wp_dropdown_categories( array(
            'show_option_all' => 'Todos los idiomas',
            'taxonomy'        => 'idioma',
            'name'            => 'idioma',
            'orderby'         => 'name',
            'selected'        => $idioma_actual,
            'hierarchical'    => true,
            'hide_empty'      => false,
            'value_field'     => 'slug',
        ) );

        // --- 3. Filtro por Duración ---
        $duracion_actual = isset( $_GET['duracion'] ) ? $_GET['duracion'] : '';
        wp_dropdown_categories( array(
            'show_option_all' => 'Todas las duraciones',
            'taxonomy'        => 'duracion',
            'name'            => 'duracion',
            'orderby'         => 'name',
            'selected'        => $duracion_actual,
            'hierarchical'    => true,
            'hide_empty'      => false,
            'value_field'     => 'slug',
        ) );

        // --- 4. Filtro por Estado de Pase ---
        $estado_pases_actual = isset( $_GET['estado_pases'] ) ? $_GET['estado_pases'] : '';
        wp_dropdown_categories( array(
            'show_option_all' => 'Todos los estados de pase',
            'taxonomy'        => 'estado_pases',
            'name'            => 'estado_pases',
            'orderby'         => 'name',
            'selected'        => $estado_pases_actual,
            'hierarchical'    => true,
            'hide_empty'      => false,
            'value_field'     => 'slug',
        ) );

        // --- 5. Filtro por Recinto (Venue) ---
        $recinto_actual = isset( $_GET['filtro_recinto'] ) ? sanitize_text_field( $_GET['filtro_recinto'] ) : '';
        $venues = get_posts( array( 'post_type' => 'tribe_venue', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC' ) );
        
        echo '<select name="filtro_recinto" id="filtro_recinto_select">';
        echo '<option value="">Todos los recintos</option>';
        foreach ( $venues as $venue ) {
            $venue_sedes = wp_get_post_terms( $venue->ID, 'sedes', array( 'fields' => 'slugs' ) );
            $venue_sede_slug = ! empty( $venue_sedes ) && ! is_wp_error( $venue_sedes ) ? $venue_sedes[0] : '';
            
            if ( empty( $venue_sede_slug ) ) {
                $ciudad_meta = get_post_meta( $venue->ID, '_VenueCity', true );
                if ( ! empty( $ciudad_meta ) ) {
                    $venue_sede_slug = sanitize_title( $ciudad_meta );
                }
            }

            echo '<option value="' . esc_attr( $venue->ID ) . '" data-sede="' . esc_attr( $venue_sede_slug ) . '" ' . selected( $recinto_actual, $venue->ID, false ) . '>' . esc_html( $venue->post_title ) . '</option>';
        }
        echo '</select>';

        // --- 6. Filtro por Rango de Fechas ---
        $fecha_desde = isset( $_GET['filtro_fecha_desde'] ) ? sanitize_text_field( $_GET['filtro_fecha_desde'] ) : '';
        $fecha_hasta = isset( $_GET['filtro_fecha_hasta'] ) ? sanitize_text_field( $_GET['filtro_fecha_hasta'] ) : '';
        
        echo '<input type="date" name="filtro_fecha_desde" value="' . esc_attr( $fecha_desde ) . '" placeholder="Desde">';
        echo '<input type="date" name="filtro_fecha_hasta" value="' . esc_attr( $fecha_hasta ) . '" placeholder="Hasta">';

        // --- Botón de Aplicar Filtros ---
        echo '<input type="submit" class="button" value="Filtrar eventos">';
    }
}


// ==========================================
// 3. SCRIPT JS PARA LA INTERACCIÓN CRUZADA (Sede ⇄ Recinto)
// ==========================================
add_action( 'admin_footer', 'indgenio_script_interaccion_filtros' );
function indgenio_script_interaccion_filtros() {
    $screen = get_current_screen();
    if ( $screen && 'tribe_events' === $screen->post_type ) {
        ?>
        <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function() {
            var sedeSelect = document.querySelector('select[name="sedes"]');
            var recintoSelect = document.querySelector('select[name="filtro_recinto"]');

            if (!sedeSelect || !recintoSelect) return;

            function filtrarRecintosPorSede(sedeSlug) {
                var opciones = recintoSelect.querySelectorAll('option');
                opciones.forEach(function(opt) {
                    var optSede = opt.getAttribute('data-sede');
                    if (opt.value === "" || !sedeSlug || sedeSlug === "0") {
                        opt.style.display = ""; 
                    } else if (optSede === sedeSlug) {
                        opt.style.display = "";
                    } else {
                        opt.style.display = "none";
                    }
                });
            }

            sedeSelect.addEventListener('change', function() {
                var sedeSlug = this.value;
                filtrarRecintosPorSede(sedeSlug);
                var recintoSeleccionado = recintoSelect.options[recintoSelect.selectedIndex];
                if (recintoSeleccionado && recintoSeleccionado.value !== "" && recintoSeleccionado.getAttribute('data-sede') !== sedeSlug && sedeSlug !== "0") {
                    recintoSelect.value = "";
                }
            });

            recintoSelect.addEventListener('change', function() {
                var recintoSeleccionado = this.options[this.selectedIndex];
                if (recintoSeleccionado && recintoSeleccionado.value !== "") {
                    var sedeSlug = recintoSeleccionado.getAttribute('data-sede');
                    if (sedeSlug) {
                        sedeSelect.value = sedeSlug;
                        filtrarRecintosPorSede(sedeSlug);
                    }
                }
            });

            if (sedeSelect.value && sedeSelect.value !== "0") {
                filtrarRecintosPorSede(sedeSelect.value);
            }
        });
        </script>
        <?php
    }
}


// ==========================================
// 4. INTERCEPTOR DE CONSULTAS PARA APLICAR LOS FILTROS
// ==========================================
add_action( 'pre_get_posts', 'indgenio_aplicar_filtros_avanzados_query' );
function indgenio_aplicar_filtros_avanzados_query( $query ) {
    global $pagenow;
    
    if ( is_admin() && 'edit.php' === $pagenow && $query->is_main_query() ) {
        if ( isset( $_GET['post_type'] ) && 'tribe_events' === $_GET['post_type'] ) {
            
            $tax_queries = array();

            // Sede
            if ( isset( $_GET['sedes'] ) && ! empty( $_GET['sedes'] ) && '0' !== $_GET['sedes'] ) {
                $tax_queries[] = array(
                    'taxonomy' => 'sedes',
                    'field'    => 'slug',
                    'terms'    => sanitize_text_field( $_GET['sedes'] ),
                );
            }

            // Idioma
            if ( isset( $_GET['idioma'] ) && ! empty( $_GET['idioma'] ) && '0' !== $_GET['idioma'] ) {
                $tax_queries[] = array(
                    'taxonomy' => 'idioma',
                    'field'    => 'slug',
                    'terms'    => sanitize_text_field( $_GET['idioma'] ),
                );
            }

            // Duración
            if ( isset( $_GET['duracion'] ) && ! empty( $_GET['duracion'] ) && '0' !== $_GET['duracion'] ) {
                $tax_queries[] = array(
                    'taxonomy' => 'duracion',
                    'field'    => 'slug',
                    'terms'    => sanitize_text_field( $_GET['duracion'] ),
                );
            }

            // Estado de Pases
            if ( isset( $_GET['estado_pases'] ) && ! empty( $_GET['estado_pases'] ) && '0' !== $_GET['estado_pases'] ) {
                $tax_queries[] = array(
                    'taxonomy' => 'estado_pases',
                    'field'    => 'slug',
                    'terms'    => sanitize_text_field( $_GET['estado_pases'] ),
                );
            }

            if ( ! empty( $tax_queries ) ) {
                $tax_queries['relation'] = 'AND';
                $query->set( 'tax_query', $tax_queries );
            }

            // Recinto (Venue)
            if ( isset( $_GET['filtro_recinto'] ) && ! empty( $_GET['filtro_recinto'] ) ) {
                $query->set( 'meta_query', array(
                    array(
                        'key'   => '_EventVenueID',
                        'value' => sanitize_text_field( $_GET['filtro_recinto'] ),
                    )
                ));
            }

            // Fechas (Desde / Hasta)
            if ( ( isset( $_GET['filtro_fecha_desde'] ) && ! empty( $_GET['filtro_fecha_desde'] ) ) || 
                 ( isset( $_GET['filtro_fecha_hasta'] ) && ! empty( $_GET['filtro_fecha_hasta'] ) ) ) {
                
                $meta_query = $query->get( 'meta_query' );
                if ( ! is_array( $meta_query ) ) {
                    $meta_query = array();
                }

                $desde = ! empty( $_GET['filtro_fecha_desde'] ) ? sanitize_text_field( $_GET['filtro_fecha_desde'] ) . ' 00:00:00' : '2020-01-01 00:00:00';
                $hasta = ! empty( $_GET['filtro_fecha_hasta'] ) ? sanitize_text_field( $_GET['filtro_fecha_hasta'] ) . ' 23:59:59' : '2030-12-31 23:59:59';

                $meta_query[] = array(
                    'key'     => '_EventStartDate',
                    'value'   => array( $desde, $hasta ),
                    'compare' => 'BETWEEN',
                    'type'    => 'DATETIME',
                );

                $query->set( 'meta_query', $meta_query );
            }
        }
    }
}


// ==========================================
// 5. LIMPIAR COLUMNAS Y AÑADIR LAS NUEVAS
// ==========================================
add_filter( 'manage_edit-tribe_events_columns', 'indgenio_modificar_columnas_eventos', 99 );
function indgenio_modificar_columnas_eventos( $columns ) {
    unset( $columns['event-cat'] );
    unset( $columns['event_cat'] );
    unset( $columns['event-categories'] );
    unset( $columns['event_categories'] );
    unset( $columns['event-tags'] );
    unset( $columns['event_tags'] );
    unset( $columns['tag'] );
    unset( $columns['tags'] );

    $nuevas_columnas = array();
    foreach ( $columns as $key => $title ) {
        $nuevas_columnas[$key] = $title;
        if ( 'title' === $key ) {
            $nuevas_columnas['sede_evento']        = 'Sede';
            $nuevas_columnas['idioma_evento']      = 'Idioma';
            $nuevas_columnas['duracion_evento']    = 'Duración';
            $nuevas_columnas['estado_pases_evento']= 'Estado de pase';
            $nuevas_columnas['recinto_evento']     = 'Recinto';
        }
    }
    
    return $nuevas_columnas;
}

add_action( 'manage_tribe_events_posts_custom_column', 'indgenio_mostrar_datos_en_columnas', 10, 2 );
function indgenio_mostrar_datos_en_columnas( $column_name, $post_id ) {
    if ( 'sede_evento' === $column_name ) {
        $sedes = wp_get_post_terms( $post_id, 'sedes', array( 'fields' => 'names' ) );
        echo ( ! empty( $sedes ) && ! is_wp_error( $sedes ) ) ? esc_html( implode( ', ', $sedes ) ) : '—';
    }
    if ( 'idioma_evento' === $column_name ) {
        $idiomas = wp_get_post_terms( $post_id, 'idioma', array( 'fields' => 'names' ) );
        echo ( ! empty( $idiomas ) && ! is_wp_error( $idiomas ) ) ? esc_html( implode( ', ', $idiomas ) ) : '—';
    }
    if ( 'duracion_evento' === $column_name ) {
        $duraciones = wp_get_post_terms( $post_id, 'duracion', array( 'fields' => 'names' ) );
        echo ( ! empty( $duraciones ) && ! is_wp_error( $duraciones ) ) ? esc_html( implode( ', ', $duraciones ) ) : '—';
    }
    if ( 'estado_pases_evento' === $column_name ) {
        $estados = wp_get_post_terms( $post_id, 'estado_pases', array( 'fields' => 'names' ) );
        echo ( ! empty( $estados ) && ! is_wp_error( $estados ) ) ? esc_html( implode( ', ', $estados ) ) : '—';
    }
    if ( 'recinto_evento' === $column_name ) {
        $venue_id = get_post_meta( $post_id, '_EventVenueID', true );
        if ( ! empty( $venue_id ) ) {
            $venue_title = get_the_title( $venue_id );
            echo ! empty( $venue_title ) ? esc_html( $venue_title ) : '—';
        } else {
            echo '—';
        }
    }
}