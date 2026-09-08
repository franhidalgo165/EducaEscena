// ==========================================
// 1. CAMPO DE AVISO EN LA TAXONOMÍA DE SEDES
// ==========================================
add_action( 'sedes_add_form_fields', 'indgenio_agregar_campo_aviso_sede' );
function indgenio_agregar_campo_aviso_sede( $taxonomy ) {
    ?>
    <div class="form-field term-aviso-wrap" style="margin-top: 15px;">
        <label for="aviso_sede">Texto de Aviso Importante para esta Sede</label>
        <textarea name="aviso_sede" id="aviso_sede" rows="3" style="width:100%;"></textarea>
        <p>Escribe el texto de aviso que aparecerá en el recuadro turquesa de esta sede.</p>
    </div>
    <?php
}

add_action( 'sedes_edit_form_fields', 'indgenio_editar_campo_aviso_sede', 10, 2 );
function indgenio_editar_campo_aviso_sede( $term, $taxonomy ) {
    $aviso_actual = get_term_meta( $term->term_id, 'aviso_sede', true );
    ?>
    <tr class="form-field term-aviso-wrap">
        <th scope="row"><label for="aviso_sede">Aviso Importante de la Sede</label></th>
        <td>
            <textarea name="aviso_sede" id="aviso_sede" rows="3" style="width:100%;"><?php echo esc_textarea( $aviso_actual ); ?></textarea>
            <p class="description">Texto que se mostrará en el recuadro turquesa para esta sede.</p>
        </td>
    </tr>
    <?php
}

add_action( 'created_sedes', 'indgenio_guardar_campo_aviso_sede', 10, 2 );
add_action( 'edited_sedes', 'indgenio_guardar_campo_aviso_sede', 10, 2 );
function indgenio_guardar_campo_aviso_sede( $term_id ) {
    if ( isset( $_POST['aviso_sede'] ) ) {
        update_term_meta( $term_id, 'aviso_sede', sanitize_textarea_field( $_POST['aviso_sede'] ) );
    }
}


// ==========================================
// 2. SHORTCODE [aviso_sede] PARA PINTAR EL RECUADRO AUTOMÁTICAMENTE (Con Margen Lateral de 20px)
// ==========================================
add_shortcode( 'aviso_sede', function() {
    // Detectamos si estamos en la página de taxonomía (archivo) de la sede actual
    $term = get_queried_object();

    if ( ! $term || ! isset( $term->taxonomy ) || $term->taxonomy !== 'sedes' ) {
        return ''; // Si no es una página de sede, no muestra nada
    }

    $aviso_texto = get_term_meta( $term->term_id, 'aviso_sede', true );

    // Si la sede no tiene texto escrito, no pinta nada
    if ( empty( $aviso_texto ) ) {
        return '';
    }

    // Estructura con márgenes laterales de 20px exactos y ancho adaptado (100% - 40px)
    $output = '
    <div style="background-color: #198C9C; color: #ffffff; padding: 20px 25px; border-radius: 16px; font-family: Raleway, sans-serif; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 15px rgba(0,0,0,0.08); margin: 20px 20px; width: calc(100% - 40px); box-sizing: border-box;">
        <div style="display: flex; flex-direction: column; gap: 5px;">
            <strong style="font-size: 18px; font-weight: 700; letter-spacing: 0.3px;">Aviso importante</strong>
            <span style="font-size: 15px; font-weight: 400; opacity: 0.95;">' . esc_html( $aviso_texto ) . '</span>
        </div>
        <div style="flex-shrink: 0; margin-left: 15px;">
            <svg width="26" height="26" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="opacity: 0.9;"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
        </div>
    </div>';

    return $output;
});