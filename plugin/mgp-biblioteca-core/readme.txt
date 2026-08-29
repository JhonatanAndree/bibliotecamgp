=== Biblioteca Virtual MGP — Core ===
Contributors: ti-mgp
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 8.0
Stable tag: 0.5.0
License: GPLv2 or later

Motor de datos de la biblioteca virtual del IESTP Manuel Gonzales Prada.
No es un plugin genérico: está hecho a medida para el diseño ya aprobado
en biblioteca.mgp.edu.pe. Depende de DearFlip Lite (lector) y de Members
(login) como plugins complementarios, no como dependencias obligatorias
(el plugin verifica su presencia antes de usarlos).

== Registro de cambios ==

= 0.5.0 =
* Cambio: con Elementor Pro ya instalado en el sitio, los chips de
  categoría del catálogo vuelven a identificarse por el atributo
  data-mgp-categoria="slug" (panel Avanzado > Atributos personalizados),
  en vez de las clases CSS cat-* usadas como workaround para Elementor
  Free. Selector JS: [data-mgp-categoria]. El contenedor de tarjetas
  (g-mgp-row-wrap) y el envoltorio del buscador (g-mgp-search-slot)
  siguen usando clases CSS igual que antes.

= 0.4.2 =
* Cambio: los chips de categoría del catálogo ya no se identifican por
  el atributo data-mgp-categoria (requiere "Atributos personalizados",
  función de Elementor Pro), sino por clases CSS (cat-todos,
  cat-computacion, cat-contabilidad, cat-mecanica) — el campo "Clases
  CSS" del panel Avanzado sí existe en Elementor gratuito, en cualquier
  widget. Así se puede cablear el catálogo sin licencia Pro.

= 0.4.1 =
* Fix: el shortcode [mgp_login] ya no llama a exit() al detectar una
  sesión iniciada (antipatrón que podía romper la respuesta en
  contextos como previews o llamadas REST/AJAX) — ese caso se movió al
  hook template_redirect, donde vive el resto de los redirects.
* Mejora: el catálogo (/catalogo/) ahora carga los libros reales al
  abrir la página, en vez de esperar el primer clic en un filtro o una
  búsqueda.

= 0.4.0 =
* Nuevo: puerta de acceso de todo el sitio (MGP_Acceso). Cualquier visitante
  sin sesión iniciada es redirigido a /login/; solo esa página es visible
  sin loguearse. Login = código de estudiante como usuario + contraseña
  normal (autenticación estándar de WordPress vía wp_login_form()).
* Nuevo: shortcode [mgp_login] — formulario de acceso con el mismo
  lenguaje visual del sitio, listo para insertarse en la página Login con
  el widget "Shortcode" de Elementor (disponible en la versión gratuita).
  Redirige a /inicio/ tras loguearse.
* Config: la página "Inicio" queda como portada estática del sitio
  (antes mostraba el listado de entradas del blog por defecto de
  WordPress).

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
