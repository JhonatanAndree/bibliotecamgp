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

Categorías vistas en el diseño (solo 3, **falta confirmar la lista
completa de carreras del instituto con Biblioteca/Coordinación
académica** — el diseño no incluye Enfermería, por ejemplo):
Computación e informática, Contabilidad, Mecánica de producción.

## 4. Plugin `mgp-biblioteca-core` — qué existe hoy (v0.1.0)

Ubicación en este repo: `plugin/mgp-biblioteca-core/`.
**Todavía NO está instalado en el sitio en vivo** — está desarrollado
localmente, pendiente de subir (ver §7 "Cómo desplegar").

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
- [ ] Mejorar el campo "Archivo PDF" del meta box: hoy pide el ID de
      adjunto a mano — falta un selector visual de medios (wp.media).
- [ ] Instalar en el sitio real: DearFlip Lite, Members, UpdraftPlus,
      Wordfence, FileBird (ninguno instalado todavía, ver §3 de discover-abilities).
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
