
/**
 * INYECTOR GLOBAL V6 - ACTUALIZADO
 * Reemplaza el inyector de orden exacto con lectura directa de atributos data-*
 * 
 * CAMBIOS:
 * - No busca por título (frágil)
 * - Lee data-pase-id y data-estado que proporciona la V6
 * - Compatible con .app-hora-enlace y .forminator-radio
 * - Mucho más limpio y robusto
 */

add_action( 'wp_footer', 'indgenio_inyector_global_v6_actualizado', 99 );
function indgenio_inyector_global_v6_actualizado() {
    ?>
    <script type="text/javascript">
    (function () {
        // ========== APLICAR ESTILOS GLOBALES A TODOS LOS PASES ==========
        function aplicarEstilosGlobales() {
            // 1. .app-hora-enlace con data-pase-id (tarjetas de programación)
            var horasCompletas = document.querySelectorAll('[data-pase-id][data-estado="completo"]');
            horasCompletas.forEach(function(el) {
                if (!el.classList.contains('pase-completo')) {
                    el.classList.add('pase-completo');
                }
            });

            // 2. Radios de Forminator con data-pase-id
            var radiosForminator = document.querySelectorAll('.forminator-radio input[type="radio"][data-pase-id]');
            radiosForminator.forEach(function(radio) {
                var paseId = radio.getAttribute('data-pase-id');
                if (!paseId) return;

                var wrapper = radio.closest('.forminator-radio');
                var estadoMeta = radio.getAttribute('data-estado');
                var esCompleto = estadoMeta === 'completo';

                if (wrapper) {
                    if (esCompleto && !wrapper.classList.contains('pase-completo')) {
                        wrapper.classList.add('pase-completo');
                    } else if (!esCompleto) {
                        wrapper.classList.remove('pase-completo');
                    }
                }

                var label = wrapper ? wrapper.querySelector('.forminator-radio-label') : null;
                if (label && esCompleto && label.innerText.indexOf('Lista de espera') === -1) {
                    label.innerHTML += '<span class="texto-lista-espera"> (Lista de espera)</span>';
                }

                radio.setAttribute('data-estado-pase', esCompleto ? 'completo' : 'disponible');
            });
        }

        // ========== EVENTO CHANGE EN RADIOS PARA GUARDAR EN OCULTOS ==========
        function capturarCambioRadios() {
            document.addEventListener('change', function(e) {
                if (e.target && e.target.type === 'radio' && e.target.closest('.forminator-custom-form')) {
                    var radio = e.target;
                    var form = radio.closest('form');
                    if (!form) return;

                    var paseId = radio.getAttribute('data-pase-id');
                    var estado = radio.getAttribute('data-estado-pase') || 'disponible';
                    var valor = (radio.value || '').trim();

                    // Input oculto: valor del pase (texto)
                    var inputPase = form.querySelector('input[type="hidden"][name="pase_seleccionado_real"]');
                    if (!inputPase) {
                        inputPase = document.createElement('input');
                        inputPase.type = 'hidden';
                        inputPase.name = 'pase_seleccionado_real';
                        form.appendChild(inputPase);
                    }
                    inputPase.value = valor;

                    // Input oculto: ID del pase
                    var inputId = form.querySelector('input[type="hidden"][name="pase_evento_id_real"]');
                    if (!inputId) {
                        inputId = document.createElement('input');
                        inputId.type = 'hidden';
                        inputId.name = 'pase_evento_id_real';
                        form.appendChild(inputId);
                    }
                    inputId.value = paseId || '';

                    // Input oculto: estado (para compatibilidad con interceptor de correos)
                    var inputEstado = form.querySelector('input[type="hidden"][name="estado_lista_espera_oculto"]');
                    if (!inputEstado) {
                        inputEstado = document.createElement('input');
                        inputEstado.type = 'hidden';
                        inputEstado.name = 'estado_lista_espera_oculto';
                        form.appendChild(inputEstado);
                    }
                    inputEstado.value = (estado === 'completo') ? '1' : '0';
                }
            }, true);
        }

        // ========== EJECUCIÓN ==========
        document.addEventListener('DOMContentLoaded', function() {
            aplicarEstilosGlobales();
            capturarCambioRadios();
        });

        // Ejecuciones temporizadas (para DOM dinámico)
        setTimeout(aplicarEstilosGlobales, 300);
        setTimeout(aplicarEstilosGlobales, 1000);
        setTimeout(aplicarEstilosGlobales, 2500);

        // Re-aplicar al navegar en modal de Forminator
        document.addEventListener('click', function(e) {
            if (e.target && (e.target.closest('.forminator-button-next') || e.target.closest('.forminator-button-prev'))) {
                setTimeout(aplicarEstilosGlobales, 500);
            }
        });
    })();
    </script>
    <?php
}