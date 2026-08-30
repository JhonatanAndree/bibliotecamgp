# Ruta de implementación — Biblioteca Virtual MGP

Estado: **plugin propio v0.5.4. Login, Catálogo e Inicio terminadas y
verificadas en vivo. Mis libros: backend listo, falta que el usuario
coloque los 2 shortcodes en Elementor. Bug real corregido: el rol
administrador nunca tuvo permiso para ver el menú "Libros" en
wp-admin (solo el rol bibliotecario lo tenía) — corregido y aplicado
en caliente — ver MEMORIA.md §20. El usuario ya empezó a subir libros
reales (30/08/2026), el primero se subió por el CPT equivocado
(DearFlip en vez de Libros) y debe recrearse desde el menú correcto.
Pendiente real detectado: el progreso de lectura nunca se registra
(falta enganchar el lector DearFlip al endpoint de progreso) — ver
MEMORIA.md §18.** Ver MEMORIA.md §10-§20 para el detalle de bugs
corregidos y decisiones tomadas.

## Fase 0 — Hecho
- Sitio Elementor diseñado y publicado en biblioteca.mgp.edu.pe (fuera de
  este repo, vive en WordPress).
- Análisis del diseño real: páginas, campos de tarjeta, panel del lector,
  clases CSS a reutilizar (ver MEMORIA.md §3).
- Repo Git inicializado, estructura del plugin creada.

## Fase 1 — Backend de datos (este repo, v0.1.0)
- [x] CPT `libro` + taxonomía `categoria_tecnica`.
- [x] Campos personalizados (autor, PDF, acceso público).
- [x] Lector protegido `/leer/{id}/` en streaming.
- [x] Catálogo AJAX con filtro + búsqueda + caché.
- [x] Guardado y progreso de lectura por usuario.
- [x] Rol `bibliotecario`.

## Fase 2 — Conexión con el sitio real
- [x] Subir `mgp-biblioteca-core` al sitio (ver MEMORIA.md §7) y activar.
- [x] Instalar DearFlip Lite, Members, UpdraftPlus, Wordfence, FileBird.
- [x] Corregir bug del hook de activación (regla `/leer/{id}/` no se
      registraba antes del flush) — ver MEMORIA.md §10.
- [x] Crear plantilla single-libro con DearFlip apuntando a `/leer/{id}/`
      — ver MEMORIA.md §12.
- [x] Aviso en el admin recordando las categorías técnicas vigentes antes
      de subir un libro de una carrera nueva — ver MEMORIA.md §12.
- [x] Puerta de login de todo el sitio (`MGP_Acceso`) + shortcode
      `[mgp_login]` + portada estática = página Inicio — ver MEMORIA.md
      §13.
- [x] **Página Login**: shortcode `[mgp_login]` agregado por el usuario
      en Elementor y verificado en vivo (200, formulario visible) — ver
      MEMORIA.md §14.
- [x] Categorías técnicas creadas como términos reales (antes solo
      existían como decisión, no como datos) — ver MEMORIA.md §14.
- [x] Catálogo: carga automática de libros reales al abrir la página
      (antes solo cargaba tras el primer filtro/búsqueda) — v0.4.1.
- [x] **Página Catálogo**: cableada y verificada en vivo con Elementor
      Pro — chips con atributo personalizado `data-mgp-categoria`
      (slugs: `todos`, `computacion-e-informatica`, `contabilidad`,
      `mecanica-de-produccion`) — ver MEMORIA.md §16.
- [x] **Página Catálogo — clases corregidas en Elementor**: contenedor
      de tarjetas `mgp-row-wrap` y envoltorio del buscador
      `mgp-search-slot`, ambas asignadas como clases GLOBALES reales
      (ya no como clases locales con el prefijo "g-" incorrecto).
      Verificado en vivo: 0 rastros de `g-mgp`, atributo
      `data-mgp-categoria` intacto en los 4 chips. Ver MEMORIA.md §17.
- [x] **Página Inicio — backend (v0.5.1)**: shortcodes
      `[mgp_saludo]` (nombre real del usuario logueado) y
      `[mgp_sigue_leyendo]` (libros en progreso reales, con barra de
      avance) reemplazan el "Hola, Renzo" y las tarjetas de muestra
      hardcodeadas — ver MEMORIA.md §17.
- [x] **Página Inicio — colocada en Elementor y verificada en vivo**:
      widgets Shortcode con `[mgp_saludo]` y `[mgp_sigue_leyendo]`
      reemplazan el saludo y la fila de muestra. Prueba con libro y
      progreso reales (creados y borrados como datos temporales):
      saludo con nombre real, tarjeta con categoría con color, barra
      de progreso real (42%), botones Leer/Guardar funcionales — sin
      rastro de `g-mgp`. 29/08/2026.
- [x] **Fix (v0.5.3)**: tag de color de "Mecánica de producción" —
      la clase global `mgp-tag-meca` ya existía en Elementor desde el
      diseño original (junto a `mgp-tag-comp`/`mgp-tag-conta`); el bug
      era un typo en el plugin (`clase_tag_categoria()` mapeaba a
      `mgp-tag-mec`, sin la "a" final). Corregido en código, sin
      cambios en Elementor. Ver MEMORIA.md §19.
- [x] **Página Mis libros — backend (v0.5.2)**: shortcodes
      `[mgp_guardados]` (todos los libros guardados, más recientes
      primero) y `[mgp_en_progreso]` (todos los libros con progreso
      real 1-99%, más recientes primero, sin el tope de 6 que tiene
      Inicio) — probados con `do_shortcode()` y datos reales
      temporales. Ver MEMORIA.md §18.
- [x] Fix: se quitó el enqueue roto de `mgp-lector.js` (archivo que
      nunca se creó, en la página equivocada) — ver MEMORIA.md §18.
- [ ] **Página Mis libros — pendiente en Elementor**: agregar dos
      widgets "Shortcode" con `[mgp_guardados]` y `[mgp_en_progreso]`
      (sugerido: un encabezado de texto antes de cada uno, "Guardados"
      / "En progreso").
- [ ] **Pendiente real de backend (no bloquea Mis libros)**: el
      progreso de lectura nunca se registra — falta el JS que escuche
      los eventos de cambio de página del lector DearFlip en la página
      individual del libro y llame al endpoint AJAX ya existente
      (`mgp_actualizar_progreso`). Requiere revisar primero la API de
      eventos real de DearFlip Lite antes de construirlo. Ver
      MEMORIA.md §18.
- [x] **Fix (v0.5.4)**: el rol `administrator` nunca tuvo las
      capacidades del CPT `libro` (`edit_libros`, etc.) — solo el rol
      `bibliotecario` las tenía. Por eso el menú "Libros" nunca
      apareció en wp-admin, ni para el propio administrador. Nuevo
      `MGP_Loader::reparar_capacidades_admin()`, autoreparación
      aplicada en caliente. Ver MEMORIA.md §20.
- [ ] Subir un primer libro real de prueba (Libros → Añadir nuevo) para
      validar el catálogo con datos reales (ya cableado, solo falta
      contenido). El primer intento del usuario (30/08/2026) se subió
      por error desde el menú "DearFlip Books" (CPT `dflip`, no
      `libro`) — pendiente recrearlo desde "Libros → Añadir nuevo"
      ahora que el menú es visible. Ver MEMORIA.md §20.
- [ ] **Empezar la carga real de hasta 200 libros**, uno por uno desde el
      admin (decisión confirmada — sin importador CSV).

## Fase 3 — Pulido y lanzamiento
- [x] Confirmar lista completa de carreras técnicas: solo 3, confirmado
      por el usuario (27/08/2026) — ver MEMORIA.md §3 y §12.
- [ ] Subir portadas reales.
- [x] Selector visual de medios para el PDF (mejora del meta box) — v0.2.0.
- [x] Definir mecanismo de login por código de estudiante: usuario =
      código, con contraseña (confirmado y construido, v0.4.0) — ver
      MEMORIA.md §13.
- [ ] Pruebas de carga con varios usuarios simultáneos (recordar límite
      de 1GB de RAM del hosting).
- [ ] Capacitar al personal de biblioteca (rol `bibliotecario`).

## Fase 4 — Operación continua
- [ ] Backups automáticos verificados (UpdraftPlus).
- [ ] Revisión periódica de seguridad (Wordfence) y actualizaciones.
- [ ] Mantener MEMORIA.md al día con cada decisión nueva.
