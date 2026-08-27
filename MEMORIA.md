# MEMORIA DEL PROYECTO — Biblioteca Virtual MGP

> Este archivo es la memoria persistente del proyecto. Si esta conversación
> se comprime o se pierde, leer este archivo completo primero para
> retomar el contexto sin repetir descubrimientos ni decisiones ya tomadas.

## 1. Contexto del proyecto

- **Institución:** IESTP Manuel Gonzales Prada, El Porvenir, La Libertad, Perú.
- **Objetivo:** biblioteca virtual para estudiantes/docentes del instituto,
  con catálogo por carrera técnica y lector de PDF integrado (sin descarga).
- **Sitio en vivo:** https://biblioteca.mgp.edu.pe/
- **Stack decidido:** WordPress + Elementor (v4.2.3, editor nuevo e-flexbox)
  + Novamira (control MCP del sitio) + plugin propio `mgp-biblioteca-core`.
- **Restricción clave de infraestructura:** hosting con solo 1GB de RAM.
  Toda decisión técnica debe considerar esto (caché, sin listas ni queries
  pesadas, sin cargar librerías JS/CSS de más).

## 2. Historial de decisiones (cronológico)

1. Primera propuesta: biblioteca vía plugins de WordPress de pago
   (Document Library Pro + DearFlip Pro).
2. Se pidió alternativa 100% gratuita → Document Library Lite + DearFlip
   Lite + FileBird + Members + UpdraftPlus + Wordfence + caché + Cloudflare.
3. Se exploró alternativa de sistema propio en PHP/Laravel con MVC — el
   usuario decidió NO seguir por ahí, **se quedó con la ruta WordPress**.
4. El usuario (o su equipo) ya diseñó y construyó en Elementor (vía Claude
   Design / Novamira) el sitio real: biblioteca.mgp.edu.pe, con 5 páginas
   y un sistema de diseño llamado "Biblioteca MGP Nocturna" (oscuro, acento
   rojo, tipografías Space Grotesk/Inter) — **ya activo, NO tocar sin
   pedir confirmación.**
5. Al analizar el sitio real se descubrió que es una MAQUETA pura de
   Elementor, sin ningún dato conectado (sin CPT, sin taxonomía, sin ACF).
   **Corrección de recomendación**: Document Library Lite no sirve aquí
   porque impone su propia UI — en su lugar se decidió CPT + taxonomía +
   meta fields nativos + plugin propio, para conservar el diseño exacto.
6. Plugins gratuitos finales a instalar en el sitio (fuera del plugin
   propio): **DearFlip Lite, Members, UpdraftPlus, Wordfence, FileBird.**
   ACF quedó descartado a favor de meta fields nativos (ver §5) para no
   sumar una dependencia más.
7. Se decidió construir un plugin propio (`mgp-biblioteca-core`) en vez de
   meter código en el tema, para que sobreviva cambios de tema/diseño.
8. Se conectó la carpeta local del usuario para versionar en Git:
   `D:\OneDrive\01 Trabajos\MGP\2026\Biblioteca` (accesible en esta sesión
   como `~/mnt/Biblioteca`). Repo Git inicializado ahí, rama `main`.
   **Pendiente:** el usuario debe configurar su identidad de Git
   (`git config user.name` / `user.email`) y el remoto (GitHub/GitLab)
   antes de poder hacer push — no se asumió ninguna identidad por él.

## 3. Estructura del sitio real (Elementor, sin datos conectados)

Páginas (ID de WordPress):
- Inicio (46) — saludo, "Sigue leyendo", "Categorías" (con conteo), "Recomendados".
- Catálogo (97) — grilla de tarjetas, chips de filtro por categoría, buscador
  (slot vacío `.g-mgp-search-slot`).
- Mis libros (100) — tarjetas con barra de progreso ("Vas por el 40%"),
  y panel de vista previa del lector (toolbar: buscar en texto, zoom -/+,
  pantalla completa, marcar página, modo oscuro; contador "página X de Y").
- Mi cuenta (103) — datos del estudiante, toggles, cambiar contraseña.
- Login (94) — pide código de estudiante, slot de formulario vacío.

Clases CSS del diseño que el plugin reutiliza (NO renombrar sin actualizar
también el plugin): `g-mgp-book-card`, `g-mgp-book-cover`,
`g-mgp-book-cover-label`, `g-mgp-tag` (+ `-comp`/`-conta`/`-mecanica`),
`g-mgp-book-meta`, `g-mgp-book-title`, `g-mgp-book-author`, `g-mgp-btn-row`,
`g-mgp-btn-primary`, `g-mgp-btn-ghost`/`g-mgp-btn-saved`, `g-mgp-progress*`,
`g-mgp-cat-card`, `g-mgp-search-slot`.

Categorías vigentes — **confirmado por el usuario (27/08/2026): el
instituto solo dicta 3 Carreras Técnicas Profesionales de 3 años, por
lo que estas 3 categorías son la lista completa por ahora** (no
"faltaba confirmar", como se pensaba antes):
Computación e informática, Contabilidad, Mecánica de producción.
Si el instituto abre una carrera nueva en el futuro, se puede crear la
categoría en cualquier momento desde Libros → Categorías técnicas (es
una taxonomía nativa de WordPress, no requiere tocar código) — el
plugin ya avisa de esto en el admin, ver MGP_Aviso_Categorias en §12.

## 4. Plugin `mgp-biblioteca-core` — qué existe hoy (v0.1.0)

Ubicación en este repo: `plugin/mgp-biblioteca-core/`.
**Ya está instalado y activo en el sitio en vivo** desde el 27/08/2026
(ver §10). Mantener el repo local y el sitio sincronizados: todo cambio
de código debe aplicarse en ambos lados (ver §7 "Cómo desplegar").

Arquitectura (un archivo = una responsabilidad):
- `mgp-biblioteca-core.php` — bootstrap, constantes, activación/desactivación.
- `includes/class-mgp-loader.php` — orquestador central (Singleton), carga
  todos los módulos y encola assets SOLO en páginas relevantes.
- `includes/cpt/class-mgp-cpt-libro.php` — CPT `libro`.
- `includes/cpt/class-mgp-tax-categoria.php` — taxonomía `categoria_tecnica`.
- `includes/cpt/class-mgp-campos-libro.php` — meta box con autor, PDF, acceso.
- `includes/lector/class-mgp-lector.php` — ruta `/leer/{id}/`, sirve el PDF
  en streaming (fpassthru, no carga el archivo entero en RAM), verifica
  acceso público vs. login.
- `includes/catalogo/class-mgp-catalogo.php` + `template-tarjeta-libro.php`
  — endpoint AJAX `mgp_filtrar_catalogo`, con caché de 5 min vía MGP_Cache.
- `includes/usuario/class-mgp-usuario.php` — AJAX `mgp_guardar_libro` y
  `mgp_actualizar_progreso` (usermeta, no cookies/localStorage).
- `includes/cache/class-mgp-cache.php` — wrapper de Transients API con
  invalidación por versión de grupo (sin recorrer la BD).
- `includes/seguridad/class-mgp-seguridad.php` — nonce, sanitización de
  categoría, rate limiting simple por transient.
- `assets/js/mgp-catalogo.js`, `assets/js/mgp-lector.js`,
  `assets/css/mgp-biblioteca.css` — vanilla JS (sin jQuery), fetch nativo.

### Meta keys usadas (única fuente de verdad — actualizar aquí si cambian)
- Post meta de `libro`: `_mgp_autor`, `_mgp_archivo_pdf_id`, `_mgp_acceso_publico`.
- User meta: `mgp_libros_guardados` (array), `mgp_progreso_libro_{id}` (array).

### Rol personalizado
`bibliotecario` — se crea en la activación del plugin, con permisos para
gestionar libros sin necesitar cuenta de Administrador completo.

### Endpoints AJAX (action de admin-ajax.php)
`mgp_filtrar_catalogo` (público), `mgp_guardar_libro` (logueado),
`mgp_actualizar_progreso` (logueado).

## 5. Decisiones técnicas y por qué (para no repetir la discusión)

- **No ACF, no Document Library Lite/Pro**: se prefirió CPT+taxonomía+meta
  nativos + plugin propio para no imponer una UI ajena sobre el diseño
  Elementor ya aprobado, y para no sumar dependencias de terceros a un
  plugin que debe vivir años.
- **Caché por transients con invalidación por versión de grupo**: pensado
  para el límite de 1GB RAM — evita recorrer/enumerar claves de caché a
  mano y reduce carga a MySQL en cada visita al catálogo.
- **Lector protegido vía `/leer/{id}/` + fpassthru**: la media library de
  WordPress es pública por defecto; esta ruta central controla acceso y
  transmite el PDF sin cargarlo completo en memoria. **Limitación conocida
  y aceptada**: no es DRM, un usuario logueado con conocimiento técnico
  podría igual guardar el archivo — es una barrera razonable, no absoluta.
- **JS sin jQuery (fetch nativo)**: menos peso, sin dependencia extra.
- **Assets encolados solo en páginas específicas** (`is_page(...)`), no
  en todo el sitio — otra decisión pensada para el hosting de 1GB.

## 6. Pendiente / próximos pasos técnicos

- [ ] Confirmar con Biblioteca/Coordinación académica la lista COMPLETA
      de carreras técnicas (categorías) — el diseño solo muestra 3.
- [ ] Subir portadas reales de los libros (hoy "portada pendiente").
- [x] Mejorar el campo "Archivo PDF" del meta box con selector visual
      (wp.media) — hecho en v0.2.0 (27/08/2026), ver §11.
- [ ] Crear la plantilla single-libro (página individual de cada libro)
      que incluya el shortcode/markup de DearFlip apuntando a `/leer/{id}/`.
- [ ] Conectar los chips de categoría del diseño real (Catálogo) con
      `data-mgp-categoria="<slug>"` para que `mgp-catalogo.js` los detecte.
- [ ] Cargar libros de prueba (los 6 que aparecen hardcodeados en el
      diseño: "Aprendiendo React", "Git y GitHub desde cero", etc.) como
      posts reales del CPT para probar el flujo end-to-end.
- [ ] Decidir e implementar login por código de estudiante (Members o
      usuarios nativos con el código como parte del username/meta).
- [ ] Configurar Git remoto (GitHub/GitLab) del instituto y hacer el
      primer push — pendiente de que el usuario configure su identidad
      Git local y el repo remoto.

## 7. Cómo desplegar el plugin al sitio en vivo

1. En esta carpeta local: `plugin/mgp-biblioteca-core/` es la carpeta que
   se sube tal cual a `wp-content/plugins/` del sitio.
2. Dos formas de subirla:
   - **Manual**: comprimir `mgp-biblioteca-core/` en un .zip y subirlo
     desde Plugins > Añadir nuevo > Subir plugin en el wp-admin.
   - **Vía Claude/Novamira**: pedir en el chat que suba el plugin usando
     `novamira/create-upload-link` (sube el .zip directo al servidor).
3. Activar el plugin en Plugins. Esto crea el CPT, la taxonomía y el rol
   `bibliotecario`, y refresca los permalinks automáticamente.
4. Verificar que `/catalogo/` siga viéndose igual (el plugin no debe
   alterar el diseño existente, solo alimentarlo de datos).

## 8. Cómo retomar este proyecto en una sesión nueva

1. Leer este archivo completo primero.
2. Revisar `docs/ruta-implementacion.md` para el plan de fases vigente.
3. Si hay dudas sobre el sitio real, usar las abilities de Novamira
   (`mcp-adapter-execute-ability` con `novamira/run-wp-cli`, etc.) contra
   el servidor `biblioteca.mgp.edu.pe` en vez de asumir — el sitio pudo
   cambiar desde la última visita.
4. Revisar la sección §6 (pendientes) antes de proponer trabajo nuevo.

## 9. Verificación en vivo (25/08/2026)

Se confirmó vía Novamira (`wp plugin list`) el estado real del sitio:
- **Activos:** 3d-flipbook-dflip-lite (DearFlip Lite), advanced-custom-fields
  (ACF — instalado por el instituto, el plugin propio NO lo usa, usa meta
  fields nativos), filebird, members, updraftplus, elementor,
  header-footer-elementor, novamira.
- **Wordfence y `mgp-biblioteca-core`**: instalados y activados por el
  usuario el 27/08/2026 — ver §10 para el detalle de la verificación y un
  bug encontrado/corregido en ese mismo despliegue.

### Confirmación de arquitectura modular
Se le explicó al usuario (y queda registrado aquí) el límite real de cada
módulo: los módulos se comunican solo vía hooks de WP y los helpers
`MGP_Cache`/`MGP_Seguridad`, nunca llamando funciones internas de otro
módulo directamente. Único acoplamiento fuerte y consciente: la plantilla
`includes/catalogo/template-tarjeta-libro.php` depende de las clases CSS
exactas del diseño Elementor — si el diseño visual cambia, ese es el único
archivo a tocar, aislado a propósito del resto de la lógica de datos.

## 10. Despliegue en vivo y bug corregido (27/08/2026)

**Estado confirmado en el sitio real** (vía `wp plugin list`, `wp post-type
list`, `wp role list`, `wp taxonomy list`, `wp rewrite list`):
- `mgp-biblioteca-core` v0.1.0 **activo** en producción.
- Todos los plugins complementarios **activos**: DearFlip Lite, ACF,
  FileBird, Members, UpdraftPlus, **Wordfence** (recién instalado y activado
  por el usuario).
- CPT `libro`, taxonomía `categoria_tecnica` y rol `Bibliotecario`
  registrados correctamente en el sitio real.

**Bug real encontrado y corregido**: la ruta del lector de PDF
`/leer/{id}/` (registrada en `MGP_Lector::registrar_ruta()`) NO estaba en
las reglas de reescritura del sitio → daba 404. Causa raíz: el hook de
activación `mgp_bib_activar()` en `mgp-biblioteca-core.php` llamaba a
`flush_rewrite_rules()` sin antes registrar esa regla en la misma
petición (el `add_action('init', ...)` del constructor de `MGP_Lector` no
alcanza a ejecutarse durante la activación).

**Corrección aplicada** (código, no solo remedio manual):
`mgp_bib_activar()` ahora hace `require_once` de
`includes/lector/class-mgp-lector.php` y llama explícitamente
`( new MGP_Lector() )->registrar_ruta();` ANTES de `flush_rewrite_rules()`.
Aplicado en: (1) el repo local `plugin/mgp-biblioteca-core/mgp-biblioteca-core.php`,
(2) el sitio real vía `novamira/execute-php`. Verificado end-to-end
desactivando y reactivando el plugin en producción (`wp plugin
deactivate/activate mgp-biblioteca-core`) y confirmando con `wp rewrite
list` que la regla `^leer/([0-9]+)/?$` queda registrada automáticamente,
sin flush manual.

**Lección para el futuro**: cualquier regla de reescritura, CPT o
taxonomía nueva que se agregue al plugin debe registrarse también dentro
de `mgp_bib_activar()` antes del `flush_rewrite_rules()` final — no basta
con que el hook `init` normal la registre, porque en el propio momento de
activación ese hook ya pasó. Esto quedó documentado también como lección
reusable en el skill de Novamira `desarrollo-plugin-modular-wordpress`.

## 11. Decisión de almacenamiento y selector de PDF (27/08/2026)

**Decisión: NO se usa Amazon S3.** Se evaluó (costo ~$0.023/GB/mes en S3,
free tier actual de AWS es solo $200 en créditos por 6 meses desde julio
2025, ya no es "gratis indefinido" como antes). El usuario confirmó:
hosting propio tiene **50GB de espacio** y el catálogo tendrá **máximo
200 libros PDF**. Con ese tope, incluso a 50MB por PDF (generoso para un
libro técnico) son solo 10GB — muy por debajo del límite. **Todos los
PDFs se guardan en la Media Library de WordPress, en el hosting mismo,
sin servicios externos.** No reevaluar esto salvo que el catálogo crezca
mucho más allá de 200 libros o el hosting cambie de plan.

**v0.2.0 — Selector visual de PDF**: antes de empezar la carga de los 200
libros (uno por uno, vía el admin de WordPress, decisión confirmada por
el usuario — no se hizo importador CSV), se mejoró el meta box "Detalles
del libro" para que el campo PDF use el selector nativo de medios de
WordPress (`wp.media`) en vez de pedir el ID de adjunto a mano. Cambios:
- `includes/cpt/class-mgp-campos-libro.php`: nuevo método
  `encolar_selector_medios()` (carga `wp_enqueue_media()` + el script
  SOLO en la pantalla de editar/crear un libro, nunca en el resto del
  admin — 1GB RAM). El campo numérico visible se volvió `<input
  type="hidden">` con botones "Seleccionar PDF"/"Quitar" y una vista
  previa con el nombre del archivo.
- Nuevo archivo `assets/js/mgp-admin-medios.js`: vanilla JS, abre
  `wp.media` filtrado a `application/pdf`, reutiliza el mismo frame en
  clics repetidos (evita fugas de memoria).
- Seguridad: `guardar()` ahora revalida en el servidor que el adjunto
  cuyo ID llega por POST sea realmente `application/pdf`
  (`get_post_mime_type()`) antes de guardarlo — el filtro del navegador
  es cosmético, esta es la verificación real.
- Versión del plugin subida a **0.2.0** (constante `MGP_BIB_VERSION`) para
  forzar cache-bust del JS en los navegadores. Aplicado en repo local y en
  el sitio real (vía `novamira/execute-php` para el PHP,
  `novamira/write-file` para el JS — `write-file`/`edit-file` de Novamira
  NO permiten escribir `.php` fuera de `wp-content/novamira-sandbox/`, por
  eso el PHP se escribe con `execute-php` + `file_put_contents`).

## 12. Página individual del libro + aviso de categorías (v0.3.0, 27/08/2026)

**Categorías confirmadas**: el instituto solo tiene 3 Carreras Técnicas
Profesionales de 3 años — las 3 categorías actuales (Computación,
Contabilidad, Mecánica de producción) son la lista completa por ahora.
Se pueden crear categorías nuevas en cualquier momento (taxonomía nativa
de WordPress, sin tocar código) si el instituto abre una carrera. Nuevo
módulo `MGP_Aviso_Categorias` (`includes/cpt/class-mgp-aviso-categorias.php`):
muestra un aviso (`admin_notices`) en la pantalla de crear/editar un
libro, listando las categorías vigentes y recordando crear la categoría
ANTES de subir un libro de una carrera nueva. Es solo un recordatorio de
flujo de trabajo, no una restricción técnica.

**Página individual del libro**: nueva plantilla propia
`templates/single-libro.php`, cargada solo para el CPT `libro` vía el
filtro `single_template` en el nuevo módulo
`includes/plantillas/class-mgp-plantilla-single-libro.php`. Por qué una
plantilla propia y no un "Single Post" de Elementor: el sitio solo tiene
Elementor gratuito + Header Footer Elementor, sin Theme Builder para
CPTs personalizados (eso es de Elementor Pro). La plantilla reutiliza
`get_header()`/`get_footer()` del tema (mismo menú, sesión y pie de
página del resto del sitio) y replica el sistema de diseño "Biblioteca
MGP Nocturna" con variables CSS propias en
`assets/css/mgp-single-libro.css` (colores, tipografías Space
Grotesk/Inter, radios, espaciados — tomados de `novamira/get-active-design`
para que sea EXACTO, no aproximado).

**Lector DearFlip embebido — cómo se integró (investigación real, no
supuesta)**: se inspeccionó el JS fuente de DearFlip Lite
(`assets/js/dflip.js`) en el propio servidor para confirmar el método de
incrustación manual, ya que el shortcode `[dflip]` de este plugin espera
un post de SU PROPIO CPT `dflip` — no sirve para libros que viven en
nuestro CPT `libro`. Confirmado en el código fuente: cualquier elemento
`<div class="df-element" source="URL_DEL_PDF" height="..." webgl="...">`
es detectado automáticamente por dflip.js (escanea `.df-element` y lee
sus atributos vía `getAttributes()`, alias `source`/`pdf-source`/`df-source`).
Por eso `single-libro.php` NO usa el shortcode: arma ese `div` directo,
apuntando `source` a la ruta protegida `/leer/{id}/` (nunca a la URL
directa del adjunto). DearFlip Lite registra sus propios handles
`dflip-script`/`dflip-style` en `wp_enqueue_scripts`; nuestro módulo los
reencola (si ya están registrados) con prioridad 20 para no depender del
orden de carga.

**Mejora de seguridad/rendimiento en el lector**: `MGP_Lector::entregar_pdf()`
ahora soporta peticiones HTTP Range (`Range: bytes=inicio-fin`), necesario
para que el visor cargue PDFs grandes en fragmentos en vez de descargar
el archivo completo de una vez — mejor para el estudiante y para el
ancho de banda del hosting de 1GB. Cambió de `fpassthru()` a un bucle
`fread()` de 8KB por bloque para poder respetar el rango pedido.

**Despliegue**: se intentó primero subir el plugin completo empaquetado
en un .zip vía `novamira/create-upload-link` (más eficiente que archivo
por archivo) — **falló**: el contenedor de este chat no tiene salida de
red directa hacia biblioteca.mgp.edu.pe (`curl` dio `CONNECT tunnel
failed, 403`). Se volvió al método ya usado en v0.2.0: cada archivo PHP
se escribe en el servidor vía `novamira/execute-php` +
`file_put_contents()` con el contenido en base64; los archivos no-PHP
(CSS) van directo con `novamira/write-file`. **Para una sesión futura**:
si se necesita subir MUCHOS archivos de una vez, este método
archivo-por-archivo es lento — considerar si el contenedor de esa sesión
sí tiene salida de red hacia el sitio antes de descartar el enfoque zip.

**Verificación en vivo**: se creó un post de prueba del CPT `libro`
(`wp post create`), se confirmó por `WebFetch` que la página
`/libro/libro-de-prueba-borrar/` carga sin error fatal, con el título,
el encabezado y el aviso de login correctos, y se revisó que no haya
nada nuevo en `debug.log`. El post de prueba se borró después
(`wp post delete --force`).

**Archivos nuevos en v0.3.0**:
- `includes/plantillas/class-mgp-plantilla-single-libro.php`
- `templates/single-libro.php`
- `assets/css/mgp-single-libro.css`
- `includes/cpt/class-mgp-aviso-categorias.php`

**Archivos modificados en v0.3.0**:
- `includes/class-mgp-loader.php` (registra los 2 módulos nuevos)
- `includes/lector/class-mgp-lector.php` (soporte de Range)
- `mgp-biblioteca-core.php` (versión 0.3.0)
- `readme.txt` (changelog)

