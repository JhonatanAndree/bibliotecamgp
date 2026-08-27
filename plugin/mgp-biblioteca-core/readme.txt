=== Biblioteca Virtual MGP — Core ===
Contributors: ti-mgp
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 8.0
Stable tag: 0.3.0
License: GPLv2 or later

Motor de datos de la biblioteca virtual del IESTP Manuel Gonzales Prada.
No es un plugin genérico: está hecho a medida para el diseño ya aprobado
en biblioteca.mgp.edu.pe. Depende de DearFlip Lite (lector) y de Members
(login) como plugins complementarios, no como dependencias obligatorias
(el plugin verifica su presencia antes de usarlos).

== Registro de cambios ==

= 0.3.0 =
* Nuevo: plantilla propia de página individual del libro (single-libro.php),
  con el mismo sistema de diseño del sitio ("Biblioteca MGP Nocturna") y el
  lector DearFlip embebido apuntando a la ruta protegida /leer/{id}/.
* Nuevo: aviso en el admin al crear/editar un libro recordando qué
  categorías técnicas existen hoy, para crear la categoría antes de subir
  un libro de una carrera nueva.
* Mejora: el lector de PDF (/leer/{id}/) ahora soporta peticiones HTTP
  Range, para que DearFlip cargue PDFs grandes en fragmentos en vez de
  descargar el archivo completo de una sola vez.

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
