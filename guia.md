# Guía Técnica de Operaciones: Plataforma de Eventos

Esta guía documenta la arquitectura, el flujo de trabajo y el código de la plataforma de eventos desarrollada con WordPress, Elementor y The Events Calendar.

---

## 1. Arquitectura de Datos y Taxonomías (CPT UI)

El sistema utiliza el Custom Post Type nativo de *The Events Calendar* (`tribe_events`) enriquecido con taxonomías personalizadas gestionadas a través de **CPT UI**.

### Taxonomías Registradas
Para que los shortcodes funcionen correctamente, asegúrate de que las taxonomías estén vinculadas al Post Type `tribe_events`:

1.  **Sedes (`sede`):** Agrupa los eventos por localización (ej. *Almería, Cartagena*).
2.  **Idioma (`idioma`):** Permite renderizar la bandera correspondiente (ej. *Español, Inglés*).
3.  **Edad (`edad`):** Etiqueta el público objetivo (ej. *7-12 Años, +18*).

---

## 2. Flujo de Creación de Eventos

Para que el diseño automatizado reciba toda la información, cada evento debe cumplir con este checklist de publicación:

*   **Título y Descripción:** Rellenar con normalidad.
*   **Fecha y Hora:** Configurar en los metadatos nativos del calendario.
*   **Precio / Tickets:** Rellenar el coste. Si se deja en `0` o vacío, el sistema imprimirá "Gratuito".
*   **Imagen Destacada:** Se recomienda subir imágenes con relación de aspecto **1:1 (Cuadradas)** y un mínimo de **600x600 px** (< 150KB). El CSS `object-fit: cover` se encargará de recortarlas sin deformarlas.
*   **Asignación de Taxonomías:** Seleccionar la Sede, el Idioma y la Edad en la barra lateral derecha.

---

## 3. Plantillas de Elementor y Shortcodes

El sistema está descentralizado en 4 shortcodes principales ubicados en el `functions.php`.

### A. Título Dinámico para Plantillas de Sede
Se utiliza en la Plantilla de Archivo de Elementor para autocompletar el nombre de la sede sin arrastrar prefijos como "Archivo:" o "Sede:".

*   **Shortcode:** `[titulo_sede_dinamico]`
*   **Ubicación:** Widget de Shortcode (sustituye al widget de *Archive Title*).

```php
// Título Dinámico
function obtener_nombre_sede_dinamico() {
    $term = get_queried_object();
    if ($term instanceof WP_Term) {
        return '<h1 class="elementor-heading-title elementor-size-default">Programación en ' . esc_html($term->name) . '</h1>';
    }
    return '<h1 class="elementor-heading-title elementor-size-default">Programación</h1>';
}
add_shortcode('titulo_sede_dinamico', 'obtener_nombre_sede_dinamico');


lOS EVENTOS SON TRATADOS COMO POSTS