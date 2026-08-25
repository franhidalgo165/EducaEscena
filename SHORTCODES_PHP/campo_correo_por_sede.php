// 1. Añadir el campo de correo cuando creas una nueva sede
add_action( 'sedes_add_form_fields', 'agregar_correo_sede' );
function agregar_correo_sede( $taxonomy ) {
    ?>
    <div class="form-field term-correo-wrap">
        <label for="correo_sede">Correo electrónico de la sede</label>
        <input type="email" name="correo_sede" id="correo_sede" value="">
        <p>Introduce el correo al que se enviarán las notificaciones de esta sede.</p>
    </div>
    <?php
}

// 2. Añadir el campo de correo cuando editas una sede existente
add_action( 'sedes_edit_form_fields', 'editar_correo_sede', 10, 2 );
function editar_correo_sede( $term, $taxonomy ) {
    $correo_actual = get_term_meta( $term->term_id, 'correo_sede', true );
    ?>
    <tr class="form-field term-correo-wrap">
        <th scope="row"><label for="correo_sede">Correo electrónico de la sede</label></th>
        <td>
            <input type="email" name="correo_sede" id="correo_sede" value="<?php echo esc_attr( $correo_actual ); ?>">
            <p class="description">Introduce el correo al que se enviarán las notificaciones de esta sede.</p>
        </td>
    </tr>
    <?php
}

// 3. Guardar el correo cuando guardas o actualizas la sede
add_action( 'created_sedes', 'guardar_correo_sede', 10, 2 );
add_action( 'edited_sedes', 'guardar_correo_sede', 10, 2 );
function guardar_correo_sede( $term_id ) {
    if ( isset( $_POST['correo_sede'] ) ) {
        update_term_meta( $term_id, 'correo_sede', sanitize_email( $_POST['correo_sede'] ) );
    }
}