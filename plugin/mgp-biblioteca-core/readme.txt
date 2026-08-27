=== Biblioteca Virtual MGP — Core ===
Contributors: ti-mgp
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 8.0
Stable tag: 0.2.0
License: GPLv2 or later

Motor de datos de la biblioteca virtual del IESTP Manuel Gonzales Prada.
No es un plugin genérico: está hecho a medida para el diseño ya aprobado
en biblioteca.mgp.edu.pe. Depende de DearFlip Lite (lector) y de Members
(login) como plugins complementarios, no como dependencias obligatorias
(el plugin verifica su presencia antes de usarlos).

== Registro de cambios ==

= 0.2.0 =
* Fix: la ruta del lector `/leer/{id}/` ahora se registra correctamente
  en la activación del plugin (antes daba 404 hasta un flush manual).
* Nuevo: selector visual de PDF (wp.media) en el meta box "Detalles del
  libro", en vez de pedir el ID de adjunto a mano — pensado para la carga
  masiva de hasta 200 libros. Incluye revalidación de tipo MIME en el
  servidor (solo application/pdf) antes de guardar.

= 0.1.0 =
* Primera versión: CPT `libro`, taxonomía `categoria_tecnica`, campos
  personalizados, lector protegido (/leer/{id}/), catálogo con filtro y
  búsqueda por AJAX con caché, guardado y progreso de lectura por usuario.
