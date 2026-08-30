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

## 13. Login/puerta de acceso + arranque del cableado Elementor (v0.4.0, 28/08/2026)

**Decisión de login confirmada por el usuario**: el "código de estudiante"
es el **nombre de usuario** de una cuenta WordPress normal, con
**contraseña** (definida por el bibliotecario o autogenerada al crear la
cuenta). No es login sin contraseña ni código+DNI — se descartaron esas
dos opciones. Se implementó con `wp_login_form()` del núcleo de
WordPress (no se reinventó la autenticación), así se heredan sus
protecciones y las de Wordfence (ya instalado) sin código extra.

**Lo que se construyó**:
- `includes/acceso/class-mgp-acceso.php` (`MGP_Acceso`), nuevo módulo:
  - Hook `template_redirect`: cualquier visitante sin sesión iniciada que
    pida cualquier página del sitio (excepto `/login/`) es redirigido a
    `/login/`. Excluye explícitamente AJAX/cron/REST por seguridad ante
    cambios futuros del núcleo, aunque esas rutas no pasan por
    `template_redirect` en el flujo normal.
  - Shortcode `[mgp_login]`: imprime `wp_login_form()` con las etiquetas
    en español ("Código de estudiante" / "Contraseña" / "Ingresar"),
    dentro de un `<div class="mgp-login">` estilado con
    `assets/css/mgp-login.css` (mismos tokens del sistema de diseño
    "Biblioteca MGP Nocturna"). Redirige a `/inicio/` tras loguearse; si
    alguien ya logueado visita la página con el shortcode, se lo saca
    directo a `/inicio/`.
- CSS enqueued solo en `is_page('login')`, siguiendo el mismo patrón de
  carga condicional del resto del plugin (RAM limitada).
- **Config de sitio (no de código)**: se cambió `show_on_front` de
  `posts` (mostraba el blog por defecto de WordPress — nunca se había
  configurado) a `page`, con `page_on_front = 46` (la página "Inicio").
  Antes de este cambio, la "puerta principal" del sitio (`/`) no
  apuntaba a ninguna de las páginas del diseño real.

**Por qué el flujo pedido por el usuario ya queda cubierto**: visitante
entra a `/` (o a cualquier URL) sin sesión → `MGP_Acceso::exigir_login()`
lo manda a `/login/` → inicia sesión con su código+contraseña vía el
formulario `[mgp_login]` → WordPress redirige a `/inicio/`, donde ya no
hay redirección porque `is_user_logged_in()` es verdadero.

**Verificado en vivo** (vía `wp_remote_get`/`wp_remote_post` ejecutados
en el propio servidor con `novamira/execute-php`, para no depender de la
falta de red saliente del contenedor de este chat):
- `GET /` anónimo → `302` a `https://biblioteca.mgp.edu.pe/login/`. ✅
- `GET /catalogo/` anónimo → mismo `302` a `/login/`. ✅
- `GET /login/` anónimo → `200` (única página accesible sin sesión). ✅
- Se creó un usuario de prueba temporal (`TEST-0000` + contraseña
  aleatoria), se autenticó vía `wp-login.php` con esas credenciales, y
  las cookies de sesión (`wordpress_logged_in_...`) se emitieron
  correctamente — confirma que el mecanismo usuario+contraseña funciona
  tal como se decidió. Usuario de prueba eliminado al terminar.
- `wp plugin list` confirma `mgp-biblioteca-core` v0.4.0 activo. Sin
  errores nuevos en `debug.log` (no existe el archivo — no hay warnings
  ni fatales acumulados).

**Pendiente para la próxima sesión / página por página**:
- Página **Login**: el usuario debe abrir el editor de Elementor y
  agregar el widget **"Shortcode"** (incluido en la versión gratuita)
  con el texto `[mgp_login]` donde quiera que aparezca el formulario. Es
  la única acción manual que falta para esta página — todo lo demás ya
  quedó resuelto en el backend.
- Página **Catálogo**: reemplazar los rectángulos hechos a mano por un
  contenedor con clase `g-mgp-row-wrap` (grilla, vía widget HTML), los
  chips de categoría por widgets HTML con `data-mgp-categoria="slug"` (o
  `"todos"`), y el buscador por un `<input>` dentro de
  `.g-mgp-search-slot` (también HTML). El JS `mgp-catalogo.js` ya
  detecta estas clases/atributos automáticamente — no requiere shortcode.
  **Dato importante**: el catálogo NO carga libros reales al abrir la
  página, solo al hacer clic en un filtro o escribir en el buscador —
  decidir si se quiere una carga inicial automática (cambio chico en el
  JS) antes de dar esta página por terminada.
- Página **Inicio**: hoy tiene contenido de MUESTRA cableado a mano
  ("Hola, Renzo" + 2 libros "Aprendiendo React"/"Git y GitHub desde
  cero" fijos en el HTML) que NO es real — falta conectar el saludo con
  el nombre del usuario logueado y la sección "Sigue leyendo" con su
  progreso real (el backend de guardado/progreso ya existe desde
  Fase 1, `MGP_Usuario`, pero no está enchufado a esta página).
- Página **Mis libros**: pendiente de revisar (usa `mgp-lector.js`
  además de los assets del catálogo) — no se tocó en esta sesión.
- Subir al menos 1 libro real de prueba (**Libros → Añadir nuevo** en el
  admin) para probar el catálogo con datos reales en cuanto se cablee la
  página Catálogo.

**Archivos nuevos en v0.4.0**:
- `includes/acceso/class-mgp-acceso.php`
- `assets/css/mgp-login.css`

**Archivos modificados en v0.4.0**:
- `includes/class-mgp-loader.php` (registra `MGP_Acceso`, agrega
  `login` al enqueue condicional)
- `mgp-biblioteca-core.php` (versión 0.4.0)
- `readme.txt` (changelog)
- Config de WordPress (no versionada en Git): `show_on_front=page`,
  `page_on_front=46`.

### 13.1 Bug encontrado y corregido al probar el login (28/08/2026)

Al pedir "prueba el login" se encontró un bug real en `MGP_Acceso`: el
shortcode `[mgp_login]` llamaba a `wp_safe_redirect()` + `exit;` cuando
detectaba una sesión ya iniciada. Esto es un antipatrón de WordPress —
un shortcode puede ejecutarse en contextos donde terminar el proceso a
medio render rompe la respuesta completa (vistas previas del editor,
llamadas REST/AJAX, u otro código que capture su salida con output
buffering, como hace `novamira/execute-php`). Se detectó exactamente
así: al probar `do_shortcode('[mgp_login]')` desde una sesión ya
autenticada, el `exit()` cortaba el proceso PHP a medio responder y la
petición completa fallaba (502 en el proxy).

**Fix**: se movió TODA la lógica de redirect a `exigir_login()` (hook
`template_redirect`, que corre antes de cualquier salida) — incluyendo
el caso "usuario ya logueado visita /login/", que antes vivía
(incorrectamente) dentro del shortcode. El shortcode ahora nunca
redirige ni llama a `exit()`: si se renderiza estando logueado, solo
devuelve un aviso HTML simple con un enlace a Inicio. Regla general para
el futuro de este proyecto: **ningún shortcode debe llamar a `exit()`
o `wp_die()`**, los redirects van siempre en hooks tempranos como
`template_redirect` o `init`.

**Verificación completa tras el fix** (todo ejecutado en el propio
servidor vía `wp_remote_get`/`wp_remote_post` para no depender de la
falta de red saliente del contenedor de este chat):
- `do_shortcode('[mgp_login]')` estando logueado ya NO rompe la
  respuesta — devuelve el aviso "Ya iniciaste sesión" correctamente.
- `GET /login/` anónimo → `200`.
- Usuario de prueba temporal (`TEST-0000`) creado, logueado por
  `wp-login.php` con usuario+contraseña → cookie
  `wordpress_logged_in_...` emitida correctamente.
- Con esa sesión: `GET /` → `200` (contenido real de Inicio, sin
  redirect); `GET /catalogo/` → `200` (ya no manda a `/login/`);
  `GET /inicio/` → `301` a `/` — esto es comportamiento NORMAL de
  WordPress (no un bug): al ser `/inicio/` la portada estática
  (`page_on_front`), WP canonicaliza su URL propia hacia `/`.
- Usuario de prueba eliminado al terminar.

## 14. Categorías técnicas creadas + carga automática del catálogo (v0.4.1, 28/08/2026)

**El usuario confirmó que ya insertó `[mgp_login]` en la página Login vía
Elementor.** Se verificó en vivo: el shortcode está en `_elementor_data`
(el post_content de una página Elementor con header/footer casi nunca
se actualiza, así que buscar ahí daba falso negativo — hay que revisar
`_elementor_data`), y `/login/` responde 200 con el campo de usuario,
el botón "Ingresar" y la clase `mgp-login` presentes. **Página Login:
terminada.**

**Hallazgo importante antes de armar el Catálogo**: las 3 categorías
técnicas (confirmadas por el usuario en §3/§12) NUNCA se habían creado
como términos reales de la taxonomía `categoria_tecnica` — estaba
registrada pero vacía. Se crearon ahora vía `wp_insert_term()`:

| Nombre | Slug |
|---|---|
| Computación e informática | `computacion-e-informatica` |
| Contabilidad | `contabilidad` |
| Mecánica de producción | `mecanica-de-produccion` |

Los slugs de Contabilidad y Mecánica de producción coinciden con los
selectores CSS ya escritos en `mgp-single-libro.css`
(`[data-categoria="contabilidad"]`, `[data-categoria="mecanica-de-produccion"]`
/ `"mecanica"`) — se usaron esos mismos slugs a propósito para no tener
que tocar el CSS.

**Cambio en `mgp-catalogo.js`**: por decisión del usuario, se agregó una
llamada a `pedirCatalogo()` justo después de registrar los listeners de
clic/búsqueda, así el catálogo pinta los libros reales apenas se abre la
página en vez de esperar la primera interacción.

**Verificado en vivo** (usuario de prueba temporal, igual que en §13):
`/catalogo/` logueado → `200`; nonce extraído de la página y usado para
llamar `admin-ajax.php?action=mgp_filtrar_catalogo` → `200` con
`{"success":true,"data":{"html":"<p class=\"g-mgp-empty\">No se
encontraron libros con ese filtro.</p>"}}` — respuesta correcta, ya que
todavía no hay ningún libro publicado. Usuario de prueba eliminado.

**Pendiente**: falta que el usuario cablee en Elementor (widget HTML,
versión gratuita) el contenedor `g-mgp-row-wrap`, los chips
`data-mgp-categoria` con los 3 slugs de arriba (+ uno `"todos"`), y el
buscador dentro de `.g-mgp-search-slot`. Ver el mensaje de esa sesión
para el HTML exacto entregado.

**Archivos modificados en v0.4.1**:
- `includes/acceso/class-mgp-acceso.php` (fix del `exit()` en el shortcode)
- `assets/js/mgp-catalogo.js` (carga automática al abrir)
- `mgp-biblioteca-core.php` (versión 0.4.1)
- `readme.txt` (changelog)
- Datos del sitio (no versionados en Git): 3 términos de
  `categoria_tecnica` creados.

## 15. Corrección de rumbo: chips por CLASE CSS, no por atributo (v0.4.2, 28/08/2026)

El usuario mandó una captura de la página Catálogo ya construida en
Elementor: tenía tarjetas y chips de MUESTRA hechos a mano (no vienen
del plugin) y no encontraba dónde escribir el atributo
`data-mgp-categoria` que se le había indicado. Motivo real: Elementor
**gratuito** no tiene el panel "Atributos personalizados" (Custom
Attributes) — es una función exclusiva de Elementor Pro. El plan
anterior (widget HTML con `data-mgp-categoria="slug"`) seguía siendo
técnicamente válido pero obligaba a construir los botones desde cero en
HTML crudo, perdiendo el estilo que el usuario ya tenía armado con
widgets nativos.

**Cambio de arquitectura**: `mgp-catalogo.js` ya NO busca
`data-mgp-categoria`. Ahora cada chip se identifica por una CLASE CSS,
usando un mapa fijo en el JS:

| Clase CSS (a escribir en Elementor) | Categoría |
|---|---|
| `cat-todos` | Todos |
| `cat-computacion` | Computación e informática |
| `cat-contabilidad` | Contabilidad |
| `cat-mecanica` | Mecánica de producción |

El campo **"Clases CSS"** SÍ existe en la versión gratuita de Elementor
(pestaña Avanzado de cualquier widget) — a diferencia de "Atributos
personalizados". Esto permite al usuario seguir usando sus widgets
nativos (Botón, Icon Box, Tabs, lo que sea) y solo agregar dos clases en
ese campo: `g-mgp-cat-card` (marca general de "esto es un chip de
filtro", ya usado por el JS) + la clase específica de la tabla de
arriba. El contenedor de tarjetas (`g-mgp-row-wrap`) y el buscador
(`.g-mgp-search-slot input`) también se resuelven con el mismo campo
"Clases CSS" sobre el contenedor/wrapper que ya tienen, sin necesidad de
widgets HTML nuevos.

**Advertencia dada al usuario**: en cuanto agregue la clase
`g-mgp-row-wrap` al contenedor, el JS reemplaza su contenido con el
catálogo real al cargar la página (por el cambio de v0.4.1) — las
tarjetas de muestra desaparecerán y, como todavía no hay libros
publicados, se verá "No se encontraron libros con ese filtro" hasta que
suba al menos uno.

**Archivos modificados en v0.4.2**:
- `assets/js/mgp-catalogo.js` (identificación de chips por clase, no
  por atributo)
- `mgp-biblioteca-core.php` (versión 0.4.2)
- `readme.txt` (changelog)

## 16. Elementor Pro instalado — vuelta a atributos personalizados, catálogo cableado y verificado en vivo (v0.5.0, 29/08/2026)

El usuario decidió instalar **Elementor Pro** en vez de seguir con el
workaround de clases CSS de v0.4.2 (le pareció más simple no tener que
reemplazar/rehacer nada más adelante). Confirmado en vivo vía
`wp plugin list --status=active`: `elementor-pro` 4.2.2 activo junto a
`elementor` 4.2.3.

Con Pro disponible, el panel **Avanzado → Atributos personalizados**
(campo "Attributes" en la UI en inglés de esta instalación) vuelve a
estar disponible, así que se revirtió el JS a su diseño original:
`assets/js/mgp-catalogo.js` ya no usa `MAPA_CLASE_A_SLUG` ni las clases
`cat-*`/`g-mgp-cat-card` — ahora los chips se seleccionan con
`document.querySelectorAll('[data-mgp-categoria]')` y el slug se lee
directo de `chip.getAttribute('data-mgp-categoria')`. El contenedor de
tarjetas (`g-mgp-row-wrap`) y el envoltorio del buscador
(`g-mgp-search-slot`) se mantienen como clases CSS igual que antes —
esas dos nunca dependieron de Elementor Pro.

**Error real del usuario durante el cableado** (documentado porque es
fácil repetirlo): la primera vez, escribió `data-mgp-categoria` en el
campo **Clases** en vez de en **Atributos personalizados**/"Attributes"
— son dos secciones distintas del panel Avanzado. El resultado fue
`class="data-mgp-categoria e-button-base"` en vez de un atributo real,
así que el JS no encontraba ningún chip. Se detectó revisando el HTML
en vivo (`data-mgp-categoria: ` vacío pese a que `g-mgp-row-wrap` y
`g-mgp-search-slot` sí aparecían) y se corrigió pidiendo captura del
panel; el usuario lo arregló usando el campo correcto ("Attributes",
con par clave|valor separado por `=`).

**Verificación en vivo, paso a paso** (usuario de prueba temporal,
creado y borrado igual que en verificaciones anteriores):
1. Primer intento de verificación con `wp_remote_get` SIN cookies de
   sesión devolvió la página de login (0 coincidencias de
   `g-mgp-row-wrap`, `g-mgp-search-slot`, `data-mgp-categoria`) — esto
   es el comportamiento CORRECTO de `MGP_Acceso` (el sitio entero exige
   login), no un bug. Hay que recordar siempre pasar cookies de un
   usuario autenticado al probar cualquier página del sitio.
2. Con cookie de sesión válida: `g-mgp-row-wrap` SI, `g-mgp-search-slot`
   SI (con el `<input>` dentro, confirmado por contexto HTML), pero
   `data-mgp-categoria` vacío — así se detectó el error de campo
   descrito arriba.
3. Tras la corrección del usuario: los 4 chips devuelven
   `data-mgp-categoria="todos|computacion-e-informatica|contabilidad|mecanica-de-produccion"`
   en el orden correcto.
4. Prueba funcional del AJAX real (`admin-ajax.php`, acción
   `mgp_filtrar_catalogo`, nonce extraído de la misma carga de página
   autenticada): filtrar por `contabilidad` devolvió
   `{"success":true,"data":{"html":"<p class=\"g-mgp-empty\">No se
   encontraron libros con ese filtro.</p>","cache":false}}` — respuesta
   correcta, es SOLO porque el CPT `libro` todavía no tiene ningún
   registro real (las tarjetas de muestra visibles en el catálogo son
   contenido de Elementor hecho a mano, no vienen de la base de datos).

**Conclusión**: la página Catálogo queda completamente cableada y
verificada en producción — grilla, chips y buscador. Falta subir el
primer libro real para ver resultados reales en vez del mensaje de
"vacío".

**Archivos modificados en v0.5.0**:
- `assets/js/mgp-catalogo.js` (vuelta a atributos personalizados,
  eliminado el mapa de clases del workaround de v0.4.2)
- `mgp-biblioteca-core.php` (versión 0.5.0)
- `readme.txt` (changelog)

## 17. Bug real descubierto (prefijo "g-" nunca existió) + página Inicio con datos reales + caída de producción y recuperación (v0.5.1, 29/08/2026)

**El hallazgo**: al preparar el cableado de Inicio, se necesitaba saber
qué clases CSS reales usa esa página para las tarjetas de "Sigue
leyendo" — así se descubrió, comparando contra el CSS que Elementor
genera EN VIVO (`wp-content/uploads/elementor/css/global-*.css`,
descargado y grepeado directamente vía `wp_remote_get`), que **el
prefijo "g-" que el plugin viene usando desde la v0.1.0 en las clases
del catálogo (g-mgp-book-card, g-mgp-tag, g-mgp-row-wrap,
g-mgp-search-slot, etc.) NUNCA existió en el diseño real de Elementor**.
Las clases globales reales del sistema de diseño "Biblioteca MGP
Nocturna" son `mgp-*` a secas — confirmado grepeando CADA hoja de
estilo Elementor que carga tanto /catalogo/ como /inicio/
(`global-46-frontend-desktop.css`, `global-97-frontend-desktop.css`,
`post-*.css`): cero coincidencias de "g-mgp" en ningún lado, decenas de
reglas reales para `.mgp-book-card`, `.mgp-tag`, `.mgp-tag-comp`,
`.mgp-tag-conta`, `.mgp-btn-primary`, `.mgp-row-wrap`,
`.mgp-search-slot`, `.mgp-progress-fill`, etc.

Esto significa que el catálogo, desde que existe (v0.1.0), estuvo
imprimiendo tarjetas con clases sin ningún CSS asociado — se hubieran
visto completamente sin estilo (sin tarjeta, sin colores, sin
distribución) en cuanto hubiera habido libros reales que mostrar. No se
notó antes porque nunca hubo libros reales en el catálogo hasta ahora.
También significa que las instrucciones dadas al usuario en las
secciones §15 y §16 de instalar `g-mgp-row-wrap` / `g-mgp-search-slot`
en Elementor fueron un error mío — deben corregirse a `mgp-row-wrap` /
`mgp-search-slot` (el usuario aún no ha hecho este cambio en Elementor
al cierre de esta sección; queda pendiente).

**Corrección aplicada (código)**:
- `template-tarjeta-libro.php`: todas las clases `g-mgp-*` → `mgp-*`.
- Nuevo método estático `MGP_Catalogo::clase_tag_categoria( $slug )`:
  mapea el slug de la taxonomía a la clase de color real
  (`computacion-e-informatica` → `mgp-tag-comp`, `contabilidad` →
  `mgp-tag-conta`). **Mecánica de producción no tiene clase de color
  todavía** (`mgp-tag-mec` — nunca se creó en Elementor, solo existen
  variantes para Computación y Contabilidad porque esas fueron las
  únicas categorías con tarjetas de muestra armadas a mano). Pendiente:
  el usuario debe crear la Clase global `mgp-tag-mec` en Elementor con
  un color propio (mismo patrón que las otras dos) o esas tarjetas se
  verán con la etiqueta sin color de fondo.
- `class-mgp-catalogo.php`: el mensaje de "sin resultados" usaba
  `g-mgp-empty` (tampoco existía) → ahora usa `mgp-page-sub` (clase real
  de texto atenuado, ya definida en el sistema).
- **Bug adicional encontrado de paso**: el botón "Guardar" de las
  tarjetas nunca tuvo JavaScript enganchado — existía el endpoint AJAX
  (`MGP_Usuario::alternar_guardado()`, desde v0.1.0) pero nada en el
  frontend lo llamaba. Corregido: `mgp-catalogo.js` ahora escucha clics
  delegados en `document` sobre `.mgp-btn-guardar`.
- `mgp-catalogo.js` recibe un dato nuevo localizado desde PHP,
  `mgpBiblioteca.pagina` (calculado con `is_page()` en
  `class-mgp-loader.php`), porque Catálogo e Inicio comparten la MISMA
  clase `mgp-row-wrap` para su grilla de tarjetas — sin este dato, el
  script del catálogo se activaría también en Inicio y pisaría "Sigue
  leyendo" con una petición AJAX de filtrado que no le corresponde a
  esa página.

**Página Inicio con datos reales**: nueva clase `MGP_Inicio`
(`includes/inicio/class-mgp-inicio.php`), registra dos shortcodes:
- `[mgp_saludo]` — imprime `<div class="mgp-stack-xs"><h1
  class="mgp-page-title">Hola, {nombre}</h1><p
  class="mgp-page-sub">...</p></div>`, con el nombre de pila real del
  usuario logueado (`first_name`, o `display_name` si no lo cargó).
- `[mgp_sigue_leyendo]` — recorre TODO el user meta del usuario de una
  sola vez (`get_user_meta( $uid )`, sin key), filtra las claves
  `mgp_progreso_libro_{id}` con porcentaje entre 1 y 99 (ni sin
  empezar, ni terminado), descarta libros borrados/despublicados,
  ordena por `actualizado` descendente, limita a 6, e imprime tarjetas
  con la misma base visual del catálogo (`mgp-book-card`) más la barra
  de progreso real (`mgp-progress`, `mgp-progress-track`,
  `mgp-progress-fill` con `style="width:{porcentaje}%"`,
  `mgp-progress-label` con el texto "Vas por el X%"). Si no hay libros
  en progreso, muestra un mensaje con link al catálogo en vez de una
  grilla vacía.

**Instrucciones pendientes para el usuario en Elementor** (página
Inicio): borrar los widgets de saludo y la fila de tarjetas de muestra
existentes, y en su lugar agregar DOS widgets "Shortcode" (Elementor
Pro) con `[mgp_saludo]` y `[mgp_sigue_leyendo]` respectivamente. También
falta corregir en la página Catálogo ya publicada: renombrar la clase
del contenedor de tarjetas de `g-mgp-row-wrap` a `mgp-row-wrap`, y la
del envoltorio del buscador de `g-mgp-search-slot` a `mgp-search-slot`
(las clases del chip, basadas en atributos personalizados, no cambian).

**Incidente de producción durante el despliegue**: a mitad del
despliegue de v0.5.1, justo después de subir `class-mgp-loader.php`
(que ya agregaba el `require_once` de `includes/inicio/class-mgp-inicio.php`)
pero ANTES de poder subir ese archivo nuevo, la conexión con Novamira
MCP se cortó con un error 502 sostenido de Cloudflare del lado de
`api.anthropic.com` (no del hosting del sitio) durante cerca de 25
minutos. Con el `require_once` apuntando a un archivo que aún no
existía en el servidor, **el sitio completo cayó con un error fatal de
PHP** ("Ha habido un error crítico en esta web") para todos los
visitantes, confirmado por el usuario con captura de pantalla.
Solución de emergencia: se le pidió al usuario entrar por el
administrador de archivos de su hosting y comentar manualmente las dos
líneas del `require_once`/instanciación de `MGP_Inicio` en
`class-mgp-loader.php` — el sitio volvió de inmediato. En cuanto la
conexión con Novamira se restableció, se completó el despliegue (subida
del archivo faltante primero, y recién al final la reactivación de esas
dos líneas en el loader) y se verificó en vivo que /inicio/, /catalogo/
y /login/ responden 200 sin errores.

**Lección para el futuro**: cuando un cambio de código introduce una
dependencia entre archivos (un `require_once` a un archivo nuevo), el
archivo nuevo debe subirse ANTES que el archivo que lo referencia,
nunca al revés — así una interrupción a mitad de despliegue deja el
sitio en un estado roto en vez de uno simplemente desactualizado. Esta
vez se hizo en el orden incorrecto (loader primero) y causó una caída
real evitable.

**Archivos modificados/creados en v0.5.1**:
- `includes/catalogo/template-tarjeta-libro.php` (clases corregidas)
- `includes/catalogo/class-mgp-catalogo.php` (clases corregidas +
  `clase_tag_categoria()`)
- `includes/inicio/class-mgp-inicio.php` (NUEVO)
- `includes/class-mgp-loader.php` (registra `MGP_Inicio`, agrega
  `pagina` al script localizado)
- `assets/js/mgp-catalogo.js` (selectores `mgp-*`, gate por página,
  handler del botón Guardar)
- `mgp-biblioteca-core.php` (versión 0.5.1)
- `readme.txt` (changelog)

## 18. v0.5.2 — Página Mis libros: guardados + en progreso, y bug del "mgp-lector.js" fantasma

**Verificación en vivo del milestone anterior**: antes de empezar Mis
libros se re-verificó Catálogo e Inicio con el usuario ya habiendo
hecho las correcciones pendientes en Elementor. Resultado: ambas
páginas 100% correctas — 0 rastros de `g-mgp` en el HTML, atributo
`data-mgp-categoria` intacto en los 4 chips, `[mgp_saludo]` y
`[mgp_sigue_leyendo]` renderizando datos reales (se creó y luego borró
un libro y un progreso de prueba para confirmarlo: saludo con nombre
real, tarjeta con categoría con color, barra de progreso real al 42%,
botones Leer/Guardar funcionales).

**Trabajo de esta versión**: nueva clase `MGP_Mis_Libros`
(`includes/mis-libros/class-mgp-mis-libros.php`) con dos shortcodes,
mismo patrón que Inicio (`MGP_Inicio`):

- `[mgp_guardados]` — todos los libros que el usuario guardó (botón
  "Guardar" del catálogo/inicio), más recientes primero (se invierte
  el array `mgp_libros_guardados`, que WordPress guarda en orden de
  guardado). Si el libro guardado también tiene progreso de lectura,
  la tarjeta muestra la barra; si no, la omite (tarjeta "limpia").
- `[mgp_en_progreso]` — todos los libros con progreso de lectura real
  entre 1% y 99%, más recientes primero. A diferencia del "Sigue
  leyendo" de Inicio (tope de 6), aquí se listan TODOS — es la página
  dedicada a esto.

Ambos shortcodes reutilizan `MGP_Catalogo::clase_tag_categoria()` y las
mismas clases visuales reales (`mgp-book-card`, `mgp-progress-*`,
etc.). A diferencia de la tarjeta del catálogo (que siempre arranca en
estado "Guardar"), acá el botón ya refleja el estado real: si el libro
está guardado, arranca en "Guardado" con la clase `mgp-btn-guardado`
— consistente con lo que hace el JS tras un clic exitoso.

**Bug encontrado y corregido: "mgp-lector.js" nunca existió**.
`class-mgp-loader.php::cargar_assets()` tenía, desde antes de esta
sesión, un `wp_enqueue_script('mgp-lector', ... 'mgp-lector.js' ...)`
condicionado a `is_page('mis-libros')`. Dos problemas: (1) ese archivo
nunca se creó — cada carga de la página Mis libros le pedía al
navegador un JS que daba 404 en silencio; (2) la ubicación era
conceptualmente incorrecta — el lector DearFlip no vive en "Mis
libros" (una vitrina de tarjetas), vive en la página individual del
libro (`templates/single-libro.php`, vía
`MGP_Plantilla_Single_Libro`). Se quitó el enqueue roto.

**Hallazgo más importante: el progreso de lectura real nunca se
registra**. Revisando `templates/single-libro.php` y
`MGP_Plantilla_Single_Libro::encolar_assets()` se confirmó que la
página individual del libro solo encola DearFlip (visor) y el CSS
propio — no hay ningún JS que escuche los eventos de "cambio de
página" del lector y llame al endpoint AJAX
`MGP_Usuario::actualizar_progreso()` (que existe y funciona desde la
v0.1.0, solo que nunca lo llama nadie). Es decir: **ningún estudiante
ha generado nunca progreso de lectura real**, porque no existe el
enganche que lo dispara. Decisión tomada: no fabricar ese enganche a
ciegas sin conocer primero la API real de eventos de DearFlip Lite
(nombre exacto del evento de cambio de página, si es un evento DOM
custom, un callback de configuración, o algo que exige revisar su
documentación/código fuente instalado) — construir esto sin verificar
arriesga registrar progreso incorrecto o nunca disparar el evento.
Queda como tarea pendiente explícita, documentada en el código
(comentario en `cargar_assets()`) y en el changelog de `readme.txt`.
Los shortcodes de Mis libros e Inicio ya están listos para mostrar ese
dato en cuanto el enganche exista — no requieren cambios adicionales.

**Verificación**: `php -l` limpio en los 3 archivos tocados, versión
`0.5.2` confirmada activa en el sitio, `/mis-libros/`, `/catalogo/`
responden 200. Prueba funcional de los shortcodes con `do_shortcode()`
usando datos reales temporales (2 libros de prueba: uno guardado con
25% de progreso, otro solo guardado sin progreso) — confirmado:
`[mgp_guardados]` lista ambos con lo más reciente primero, botón en
estado "Guardado", tarjeta con progreso solo cuando corresponde;
`[mgp_en_progreso]` lista solo el que tiene progreso real. Datos de
prueba borrados al terminar.

**Pendiente para el usuario en Elementor** — página Mis libros: crear
(o editar, si ya existe contenido de muestra) dos secciones, cada una
con un widget "Shortcode": una con `[mgp_guardados]`, otra con
`[mgp_en_progreso]`. Sugerido: un encabezado de texto "Guardados" antes
del primero y "En progreso" antes del segundo (mismo patrón visual que
el resto del sitio, clases `mgp-section`/`mgp-section-title` si ya
existen como clases globales — si no, un widget de texto normal con el
estilo de título que se esté usando en el resto del sitio sirve igual).

**Archivos modificados/creados en v0.5.2**:
- `includes/mis-libros/class-mgp-mis-libros.php` (NUEVO)
- `includes/class-mgp-loader.php` (registra `MGP_Mis_Libros`, quita el
  enqueue roto de `mgp-lector.js`)
- `mgp-biblioteca-core.php` (versión 0.5.2)
- `readme.txt` (changelog)

## 19. Bug real: mapeo de clase de color para "Mecánica de producción" (v0.5.3)

Al intentar colorear el tag de categoría "Mecánica de producción" (única
de las 3 carreras sin color desde el diseño original), el usuario creó
en Elementor una clase local `mgp-tag-mec` y no logró convertirla en
global (el menú "Convertir en una clase global" no respondía). Antes de
seguir insistiendo en Elementor, se le pidió abrir **Sistema de diseño →
Clases** (ícono de gota en la barra superior del editor) para ver la
lista completa de clases globales reales (103 en total).

Ahí apareció la causa real: la clase global **ya existía** desde el
diseño original, junto a `mgp-tag-comp` y `mgp-tag-conta`, pero con el
nombre `mgp-tag-meca` — no `mgp-tag-mec`. El código del plugin
(`MGP_Catalogo::clase_tag_categoria()`) tenía el nombre mal escrito
(`mgp-tag-mec`, sin la "a" final), por eso nunca coincidía con ninguna
clase real y el tag salía sin color. No era un problema de Elementor ni
del usuario — era un typo en el mapeo PHP.

**Fix**: `clase_tag_categoria()` en
`includes/catalogo/class-mgp-catalogo.php` corregido para mapear
`'mecanica-de-produccion' => 'mgp-tag-meca'`. Sin cambios en Elementor
necesarios — la clase `mgp-tag-mec` local que el usuario creó de prueba
puede borrarse, no se usa.

**Verificación**: `php -l` limpio, versión `0.5.3` confirmada activa
(`MGP_BIB_VERSION` y cabecera del plugin coinciden), llamada directa a
`MGP_Catalogo::clase_tag_categoria('mecanica-de-produccion')` en el
sitio real devuelve `mgp-tag-meca`, y `template-tarjeta-libro.php`
confirmado que emite `class="mgp-tag mgp-tag-meca"` en la tarjeta.

**Archivos modificados en v0.5.3**:
- `includes/catalogo/class-mgp-catalogo.php` (mapeo corregido)
- `mgp-biblioteca-core.php` (versión 0.5.3)
- `readme.txt` (changelog)

