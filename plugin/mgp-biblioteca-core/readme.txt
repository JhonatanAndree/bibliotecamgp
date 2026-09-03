=== Biblioteca Virtual MGP — Core ===
Contributors: ti-mgp
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 8.0
Stable tag: 0.5.13
License: GPLv2 or later

Motor de datos de la biblioteca virtual del IESTP Manuel Gonzales Prada.
No es un plugin genérico: está hecho a medida para el diseño ya aprobado
en biblioteca.mgp.edu.pe. Depende de DearFlip Lite (lector) y de Members
(login) como plugins complementarios, no como dependencias obligatorias
(el plugin verifica su presencia antes de usarlos).

== Registro de cambios ==

= 0.5.13 =
* Fix visual: los botones de pestañas de "Mis libros" (v0.5.12) se
  veían con borde rosado/magenta en vez del diseño esperado. Causa:
  el tema Hello Elementor trae en reset.css la regla
  "[type=button],[type=submit],button{border:1px solid #c36;...}",
  con la MISMA especificidad CSS (0,1,0) que ".mgp-tab-btn" — en un
  empate de especificidad gana el que carga después en el <head>, y
  el tema carga después que el plugin. El botón "Guardar" no tenía
  este problema porque ya usa 2 clases. Corregido escalando los
  selectores de pestañas a 2 clases (".mgp-tabs-nav .mgp-tab-btn"),
  que siempre gana sin depender del orden de carga. Ver MEMORIA.md §29.

= 0.5.12 =
* Rediseño UX de "Mis libros": nuevo shortcode [mgp_mis_libros] con
  pestañas — "En progreso" primero (vista por defecto), "Guardados" en
  una segunda pestaña, en vez de dos secciones apiladas. Pedido directo
  del usuario tras confirmar que el botón "Guardar" sí funcionaba
  (verificado en BD y en el shortcode [mgp_en_progreso]) pero el libro
  guardado-y-en-progreso solo aparecía en "En progreso" (§25) y el
  usuario no sabía dónde buscarlo. Nuevo CSS (.mgp-tabs-nav,
  .mgp-tab-btn) y JS de toggle (delegado en document, mismo patrón que
  el botón Guardar). Principio de UX/UI de todo el sitio pedido
  explícitamente por el usuario (ver MEMORIA.md §1/§28).

= 0.5.11 =
* Fix de legibilidad: la barra de progreso ("Vas por el X%") y los
  mensajes de estado/título/subtítulo (mgp-page-title, mgp-page-sub —
  saludo, "Aún no tienes libros...", etc.) nunca tuvieron CSS de respaldo
  (mismo hueco de Elementor atómico del v0.5.6 — §22, nadie lo notó hasta
  que hubo progreso real que mostrar). Ahora usan el token --mgp-ink
  (blanco real del sistema de diseño) en vez de heredar el color apagado
  por defecto. Pedido explícito del usuario. Ver MEMORIA.md §27.

= 0.5.10 =
* Fix: en Inicio, un libro recién agregado al catálogo Y ya en progreso
  aparecía duplicado (una vez en "Sigue leyendo", otra en "Recomendados
  para ti"). [mgp_recomendados] ahora excluye los libros con progreso
  1-99% vía post__not_in. Se extrajo obtener_libros_en_progreso() como
  método compartido entre ambos shortcodes para no duplicar la lógica.
  Ver MEMORIA.md §26.

= 0.5.9 =
* Fix: en "Mis libros", un libro guardado Y en progreso aparecía duplicado
  (una vez en "Guardados", otra en "En progreso"). [mgp_guardados] ahora
  excluye los libros con progreso 1-99%; esos viven solo en "En progreso".
  Los terminados (100%) siguen en Guardados (no hay sección Terminados
  todavía). Ver MEMORIA.md §25.

= 0.5.8 =
* Fix crítico: el botón "Leer" en TODAS las tarjetas del sitio (catálogo,
  inicio/sigue-leyendo, inicio/recomendados, mis-libros) apuntaba directo
  a la ruta cruda de streaming `/leer/{id}/` en vez de a la página
  individual del libro (`/libro/{slug}/`, plantilla single-libro.php).
  Efecto real: el navegador abría su visor de PDF nativo en vez del
  lector DearFlip embebido, así que el enganche de progreso de v0.5.7
  nunca podía dispararse (la instancia .df-element/df-app nunca existía
  en esa página). Corregido: los 4 lugares ahora usan get_permalink()
  para llevar siempre a la página del libro. Detectado y corregido tras
  reporte del usuario probando el flujo real. Ver MEMORIA.md §24.

= 0.5.7 =
* Nuevo: registro real del progreso de lectura. Se investigó el JS
  fuente de DearFlip Lite en el servidor y se confirmó que las opciones
  documentadas onPageChanged/onFlip/onReady nunca se ejecutan en la
  versión Lite (candado de la versión de pago: el método interno que las
  llamaría está vacío a propósito). assets/js/mgp-lector.js ahora sondea
  directamente las propiedades públicas currentPageNumber/pageCount de
  la instancia del lector (guardada en el elemento .df-element vía
  jQuery.data('df-app', ...)) y reporta a mgp_actualizar_progreso cuando
  la página cambia y se estabiliza. Se elimina el código anterior de
  este archivo, que escuchaba un evento 'dflip.pageFlip' inexistente y
  nunca pudo haber funcionado. Encolado y localizado (mgpLector) solo en
  la página individual del libro. Ver MEMORIA.md §23.

= 0.5.6 =
* Fix: la clase CSS del botón "Guardado" estaba mal escrita como
  "mgp-btn-guardado" — la clase global real del diseño Elementor es
  "mgp-btn-saved" (ver el listado original de clases en MEMORIA.md §3).
  Corregido en mgp-catalogo.js, class-mgp-inicio.php y
  class-mgp-mis-libros.php. La hoja de estilos de respaldo (ver punto
  siguiente) mantiene además una regla de compatibilidad para ambos
  nombres de clase por si quedó HTML viejo en caché.
* Fix importante de arquitectura: se detectó que el editor "atómico" de
  Elementor (widgets e-flexbox/clases atómicas) solo genera CSS para
  clases asignadas a widgets reales dentro del árbol del editor — las
  clases que nuestros shortcodes (mgp_categorias, mgp_recomendados,
  mgp_sigue_leyendo, mgp_guardados, mgp_en_progreso,
  template-tarjeta-libro.php) imprimen dentro de HTML generado por PHP
  son invisibles para ese escáner, así que Elementor nunca escribía su
  CSS — aunque la misma clase sí funciona en una página donde SÍ es un
  widget real (ej. los chips de categoría en Catálogo). En la práctica,
  las tarjetas y secciones generadas por shortcode podían verse sin
  ningún estilo. Solución: `assets/css/mgp-biblioteca.css` ahora declara
  a mano los valores exactos de cada clase global usada por los
  shortcodes (mgp-cat-card, mgp-row-wrap, mgp-book-card, mgp-tag*,
  mgp-btn*, etc.), usando las mismas variables CSS (--mgp-*) que
  Elementor ya declara en :root — así hereda automáticamente cualquier
  cambio de color/token que se haga en "Sistema de diseño" de Elementor,
  sin tocar este archivo. Es una copia de respaldo, NO la fuente de
  verdad: si el diseño visual cambia en Elementor, este archivo debe
  actualizarse a mano también. Ver MEMORIA.md §22.

= 0.5.5 =
* Nuevo: shortcodes [mgp_categorias] y [mgp_recomendados] para la página
  Inicio — reemplazan el contenido "Categorías" y "Recomendados para ti"
  que era 100% de muestra hardcodeado en Elementor (libros ficticios,
  conteos falsos). [mgp_categorias] imprime el conteo real de libros
  publicados por cada una de las 3 carreras técnicas. [mgp_recomendados]
  muestra los 4 libros publicados más recientes (con mensaje si aún no
  hay ninguno), reutilizando la misma tarjeta visual del catálogo,
  incluyendo el estado real "Guardado" si el usuario ya lo guardó. Ver
  MEMORIA.md §21.

= 0.5.4 =
* Fix importante: el rol "administrator" de WordPress nunca vio el
  menú "Libros" en wp-admin — el CPT usa capacidades propias
  (edit_libros, publish_libros, etc.) que solo se le habían concedido
  al rol "bibliotecario", nunca al administrador del sitio. Nuevo
  método MGP_Loader::reparar_capacidades_admin() se las da también al
  administrador (una sola vez, autoreparación en instalaciones ya
  activas + en la activación para instalaciones nuevas). Ver
  MEMORIA.md §20.

= 0.5.3 =
* Fix: el tag de color de la categoría "Mecánica de producción" nunca
  se pintaba porque el plugin mapeaba a la clase CSS "mgp-tag-mec",
  cuando la clase global real creada en el diseño Elementor original es
  "mgp-tag-meca" (con "a" final). Corregido el mapeo en
  MGP_Catalogo::clase_tag_categoria() — no requiere ningún cambio en
  Elementor. Ver MEMORIA.md §19.

= 0.5.2 =
* Nuevo: shortcodes [mgp_guardados] y [mgp_en_progreso] para la página
  Mis libros — el primero lista todos los libros que el usuario guardó
  (botón "Guardar" del catálogo), el segundo todos sus libros con
  progreso de lectura real entre 1% y 99%, ambos con lo más reciente
  primero. A diferencia del "Sigue leyendo" de Inicio (máximo 6), aquí
  se listan todos — es la página dedicada. Reutilizan las mismas
  clases visuales reales del catálogo (mgp-book-card, mgp-progress-*,
  etc.).
* Fix: se quitó un enqueue de "mgp-lector.js" en la página Mis libros
  que pedía un archivo que nunca llegó a crearse (404 silencioso en
  consola) y que además estaba en la página equivocada — el lector
  vive en la página individual del libro, no en Mis libros.
* Nota pendiente: el registro real de progreso de lectura
  (mgp_actualizar_progreso) todavía no tiene ningún JS enganchado a
  los eventos de paso de página de DearFlip en la página individual
  del libro — ver MEMORIA.md §18. Sin esto, [mgp_en_progreso] mostrará
  "no tienes libros en progreso" incluso con libros empezados, hasta
  que ese enganche se construya.

= 0.5.1 =
* Fix importante: las clases CSS que el plugin imprimía en las tarjetas
  del catálogo (g-mgp-book-card, g-mgp-tag, g-mgp-btn-row, etc.) tenían
  un prefijo "g-" que NUNCA existió en el diseño real de Elementor — se
  verificó contra el CSS generado en vivo y el prefijo correcto es
  "mgp-" a secas (mgp-book-card, mgp-tag...), desde la v0.1.0. Corregido
  en template-tarjeta-libro.php. El contenedor del catálogo
  (antes "g-mgp-row-wrap") y el buscador (antes "g-mgp-search-slot")
  también deben renombrarse a mgp-row-wrap / mgp-search-slot en
  Elementor — ver MEMORIA.md §17 para el detalle completo.
* Nuevo: shortcodes [mgp_saludo] y [mgp_sigue_leyendo] para la página
  Inicio — saludo con el nombre real del estudiante y su lista real de
  libros en progreso (antes: contenido de muestra hardcodeado en
  Elementor). Reutilizan las mismas clases visuales del catálogo más
  la barra de progreso (mgp-progress-*).
* Fix: el botón "Guardar" de las tarjetas de libro no tenía ningún
  JavaScript enganchado (no hacía nada al hacer clic) desde que se creó
  — ahora mgp-catalogo.js escucha clics delegados en document sobre
  .mgp-btn-guardar y llama al endpoint AJAX mgp_guardar_libro que ya
  existía en MGP_Usuario desde la v0.1.0 pero nunca se conectó al
  frontend.
* Cambio: mgp-catalogo.js ahora recibe el dato "pagina" (catálogo,
  inicio o mis-libros) vía wp_localize_script, porque catálogo e inicio
  comparten la misma clase mgp-row-wrap para su grilla — sin esto, el
  JS del catálogo activaría su AJAX de filtrado también en Inicio y
  pisaría la lista de "Sigue leyendo".

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
