El problema es que si tú en Ionos tienes la web montada en un subdominio/dominio de prueba, Ionos necesita validar e ir asignando la carpeta de tu WordPress al dominio real.

Para que Ionos acepte ese dominio en su panel y le asigne el certificado SSL (HTTPS) y la ruta correcta, el dominio tiene que responder apuntando a Ionos. Si ellos lo han metido primero en Cloudflare sin haber configurado el dominio dentro de tu Ionos, la verificación de Ionos o de los certificados SSL automáticos falla porque se choca contra el "muro" de Cloudflare.

La solución: El orden correcto de los factores
Hay dos formas de resolver esto. La vía rápida sin cambiar nada en Cloudflare y la vía limpia (la que te pide Ionos).

Opción 1: La Vía Limpia (Lo que te pide Ionos) — La recomendada
Para hacer las cosas sobre seguro y sin dolores de cabeza con los certificados SSL:

Añade el dominio en tu Ionos: Entra a tu panel de Ionos y dale a Añadir dominio (o conectar dominio existente). Así Ionos sabrá que esa web le pertenece a ese dominio.

Pide a la otra empresa la dirección IP de tu Hosting en Ionos:

En tu panel de Ionos, busca la Dirección IP (IPv4) de tu servidor/web.

Que en Cloudflare pongan un registro "A":

Dile a la otra empresa: "Oye, en el panel de Cloudflare, no apuntéis con DNS de nombres. Cread un Registro A para @ (y para www) que apunte directamente a la IP [TU_IP_DE_IONOS] y poned el "nube naranja" (Proxy) desactivado (en gris) un momento".

Asigna el dominio a tu carpeta de WordPress: En Ionos, cambia la asignación del dominio para que apunte a la misma carpeta / donde instalaste tu WordPress de prueba.

Cambia las URLs de WordPress: En el panel de tu WP (o vía archivo wp-config.php), cambia la dirección del sitio de la URL de prueba al dominio definitivo.

Reactivar Cloudflare: Una vez todo cargue bien y tenga el SSL en Ionos, ellos pueden volver a encender la "nube naranja" (Proxy) en Cloudflare.

Opción 2: La Vía Directa desde Cloudflare (Sin tocar DNS de Ionos)
Si la otra empresa no quiere soltar las DNS de Cloudflare (porque ahí tengan configurado el correo corporativo, otros subdominios, etc.), no hace falta mover las DNS a Ionos. Cloudflare se puede quedar como gestor DNS perfectamente.

Lo único que tiene que hacer la otra empresa en su Cloudflare es:

Crear un Registro A apuntando a la IP de tu servidor en Ionos.

Desactivar temporalmente el Proxy de Cloudflare (dejar la nube en gris) para que Ionos pueda generar el certificado SSL sin interferencias.

En tu Ionos, añades ese dominio en modo "Dominio externo / Conectar dominio".

Una vez validado en Ionos, vuelven a activar la nube naranja de Cloudflare si quieren.