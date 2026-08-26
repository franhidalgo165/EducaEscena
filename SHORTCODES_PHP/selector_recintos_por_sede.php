
// ==========================================
// 1. AÑADIR CAMPO EN RECINTOS PARA ASIGNAR SEDE (ORGANIZADOR)
// ==========================================
add_action( 'add_meta_boxes', 'indgenio_vincular_recinto_organizador' );
function indgenio_vincular_recinto_organizador() {
    add_meta_box(
        'recinto_organizador_meta',
        'Sede (Organizador)',
        'indgenio_recinto_organizador_callback',
        'tribe_venue',
        'side',
        'high'
    );
}

function indgenio_recinto_organizador_callback( $post ) {
    $organizador_actual = get_post_meta( $post->ID, '_recinto_padre_organizador', true );
    $organizadores = get_posts( array(
        'post_type'      => 'tribe_organizer',
        'posts_per_page' => -1,
        'post_status'    => 'publish'
    ) );
    
    echo '<p style="margin-bottom:10px;">Selecciona a qué Sede pertenece este recinto:</p>';
    echo '<select name="_recinto_padre_organizador" style="width:100%;">';
    echo '<option value="">-- Selecciona la sede --</option>';
    foreach ( $organizadores as $org ) {
        echo '<option value="' . esc_attr($org->ID) . '" ' . selected( $organizador_actual, $org->ID, false ) . '>' . esc_html($org->post_title) . '</option>';
    }
    echo '</select>';
    echo '<p class="description">Permite relacionar el recinto con su Sede.</p>';
}

add_action( 'save_post_tribe_venue', 'indgenio_guardar_vinculo_recinto' );
function indgenio_guardar_vinculo_recinto( $post_id ) {
    if ( isset( $_POST['_recinto_padre_organizador'] ) ) {
        update_post_meta( $post_id, '_recinto_padre_organizador', sanitize_text_field( $_POST['_recinto_padre_organizador'] ) );
    }
}

// ==========================================
// 2. INYECTAR SELECTOR A ANCHO COMPLETO Y ADAPTAR TEXTOS A SEDE
// ==========================================
add_action( 'admin_footer-post.php', 'indgenio_selector_pro_js' );
add_action( 'admin_footer-post-new.php', 'indgenio_selector_pro_js' );
function indgenio_selector_pro_js() {
    global $post;
    if ( ! $post || $post->post_type !== 'tribe_events' ) return;

    $recintos_objs = get_posts( array( 'post_type' => 'tribe_venue', 'posts_per_page' => -1, 'post_status' => 'publish' ) );
    $mapa_relaciones = array();
    $lista_recintos = array();
    
    foreach ( $recintos_objs as $r ) {
        $org_id = get_post_meta( $r->ID, '_recinto_padre_organizador', true );
        $mapa_relaciones[$r->ID] = $org_id;
        $lista_recintos[] = array(
            'id'     => $r->ID,
            'title'  => $r->post_title,
            'org_id' => $org_id
        );
    }
    ?>
    <style>
        #indgenio-recinto-box {
            margin-top: 15px;
            padding: 16px 20px;
            background: #ffffff;
            border: 1px solid #c3c4c7;
            border-left: 4px solid #2271b1;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            border-radius: 4px;
            width: 100%;
            box-sizing: border-box;
        }
        #indgenio-recinto-box label {
            font-size: 13px;
            font-weight: 600;
            color: #1d2327;
            display: block;
            margin-bottom: 8px;
        }
        #indgenio_recinto_select {
            width: 100%;
            padding: 6px 12px;
            font-size: 14px;
            height: 36px;
            border: 1px solid #8c8f94;
            border-radius: 4px;
            background-color: #fff;
            color: #2c3338;
            box-sizing: border-box;
        }
        #indgenio_recinto_select:focus {
            border-color: #2271b1;
            box-shadow: 0 0 0 1px #2271b1;
            outline: none;
        }
        #indgenio-recinto-box .description {
            margin-top: 8px;
            font-size: 12px;
            color: #646970;
            font-style: italic;
        }
    </style>
    <script>
    jQuery(document).ready(function($) {
        var mapaRecintos = <?php echo json_encode($mapa_relaciones); ?>;
        var todosRecintos = <?php echo json_encode($lista_recintos); ?>;
        
        var $campoOrgOficial = $('select#saved_organizer, [name^="organizer[OrganizerID]"]');
        var $campoRecOficial = $('select#saved_venue, [name^="venue[VenueID]"]');

        if (!$campoOrgOficial.length) return;

        // Ocultar el selector nativo conflictivo
        $campoRecOficial.closest('.tribe-section, tr, div').hide();

        // Cambiar los textos visuales de "ORGANIZADORES" a "SEDES (ORGANIZADOR)"
        var $tituloSeccionOrg = $campoOrgOficial.closest('.tribe-section, tr, div, .postbox').find('h2, h3, legend').filter(function() {
            return $(this).text().toUpperCase().includes('ORGANIZADORES');
        });
        if ($tituloSeccionOrg.length) {
            $tituloSeccionOrg.text('SEDES (ORGANIZADOR)');
        }

        // Cambiar etiqueta de "Organizador:" a "Sede:" si la encuentra cerca
        $campoOrgOficial.closest('.tribe-section, tr, div').find('label').filter(function() {
            return $(this).text().includes('Organizador:');
        }).text('Sede:');

        // Crear nuestro selector a ancho completo
        var htmlSelectPropio = '<div id="indgenio-recinto-box">';
        htmlSelectPropio += '<label for="indgenio_recinto_select">Recinto asociado (Filtrado automático por Sede):</label>';
        htmlSelectPropio += '<select id="indgenio_recinto_select">';
        htmlSelectPropio += '<option value="">-- Selecciona un recinto --</option>';
        for (var i = 0; i < todosRecintos.length; i++) {
            htmlSelectPropio += '<option value="' + todosRecintos[i].id + '" data-org="' + todosRecintos[i].org_id + '">' + todosRecintos[i].title + '</option>';
        }
        htmlSelectPropio += '</select>';
        htmlSelectPropio += '<p class="description">Este campo se sincroniza de forma inteligente con la Sede seleccionada arriba.</p>';
        htmlSelectPropio += '</div>';

        // Insertarlo justo debajo del bloque de organizadores
        var $bloqueOrg = $campoOrgOficial.closest('.tribe-section, tr, div, .misc-pub-section').last();
        if ($bloqueOrg.length) {
            $bloqueOrg.after(htmlSelectPropio);
        } else {
            $campoOrgOficial.parent().after(htmlSelectPropio);
        }

        var $miSelectRecinto = $('#indgenio_recinto_select');

        var recintoActualOficial = $campoRecOficial.val();
        if (recintoActualOficial) {
            $miSelectRecinto.val(recintoActualOficial);
        }

        function actualizarOpcionesRecintos() {
            var orgSeleccionado = $campoOrgOficial.val();

            $miSelectRecinto.find('option').each(function() {
                var orgDelRecinto = $(this).attr('data-org');
                var valOpt = $(this).val();

                if (!valOpt) {
                    $(this).show();
                    return;
                }

                if (orgSeleccionado && orgSeleccionado !== '' && orgSeleccionado !== '0') {
                    if (orgDelRecinto == orgSeleccionado) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                } else {
                    $(this).show();
                }
            });

            var recSelActual = $miSelectRecinto.val();
            if (orgSeleccionado && recSelActual && mapaRecintos[recSelActual] && mapaRecintos[recSelActual] != orgSeleccionado) {
                $miSelectRecinto.val('');
                $campoRecOficial.val('').trigger('change');
            }
        }

        $miSelectRecinto.on('change', function() {
            var recintoId = $(this).val();
            $campoRecOficial.val(recintoId).trigger('change');

            if (recintoId && mapaRecintos[recintoId]) {
                var orgDestino = mapaRecintos[recintoId];
                if ($campoOrgOficial.val() != orgDestino) {
                    $campoOrgOficial.val(orgDestino).trigger('change');
                }
            }
        });

        $(document).on('change', 'select#saved_organizer, [name^="organizer[OrganizerID]"]', function() {
            actualizarOpcionesRecintos();
        });

        setTimeout(actualizarOpcionesRecintos, 400);
    });
    </script>
    <?php
}