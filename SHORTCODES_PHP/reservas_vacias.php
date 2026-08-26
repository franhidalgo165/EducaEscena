// ==========================================
// 4. VALIDACIÓN: AVISO DIRECTO EN LOS CURSOS SI ESTÁN A 0
// ==========================================
add_filter( 'forminator_custom_form_submit_errors', function( $submit_errors, $form_id, $field_data_array ) {
    $mi_formulario_id = 768;
    if ( intval( $form_id ) !== $mi_formulario_id ) {
        return $submit_errors;
    }

    $total_plazas = 0;
    foreach ( $field_data_array as $field ) {
        if ( isset( $field['value'] ) && is_numeric( $field['value'] ) ) {
            $total_plazas += intval( $field['value'] );
        }
    }

    if ( $total_plazas <= 0 ) {
        // Asignamos el error directamente a uno de los campos de tu grupo
        // (puedes cambiar 'currency-1' o el ID del campo si lo sabes, o dejarlo así para que salte)
        $submit_errors[] = array(
            'name'  => 'cursos', 
            'error' => '¡Atención! Debes seleccionar al menos una plaza en los cursos para poder continuar.'
        );
    }

    return $submit_errors;
}, 10, 3 );