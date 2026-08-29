# Ruta de implementación — Biblioteca Virtual MGP

Estado: **plugin propio v0.5.1. Página Login terminada y verificada en
vivo. Página Catálogo cableada, pero con 2 clases pendientes de
corregir en Elementor (ver Fase 2). Página Inicio: backend listo
(shortcodes `[mgp_saludo]` y `[mgp_sigue_leyendo]`), falta que el
usuario los coloque en Elementor. Falta subir el primer libro real.
Bug real corregido: el prefijo "g-" en las clases CSS del catálogo
nunca existió en el diseño real de Elementor (era "mgp-" a secas desde
la v0.1.0) — ver MEMORIA.md §17.** Ver MEMORIA.md §10-§17 para el
detalle de bugs corregidos y decisiones tomadas.

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
- [ ] **Página Catálogo — pendiente en Elementor**: renombrar 2 clases
      CSS que quedaron con el prefijo "g-" incorrecto — contenedor de
      tarjetas `g-mgp-row-wrap` → `mgp-row-wrap`, envoltorio del
      buscador `g-mgp-search-slot` → `mgp-search-slot`. Los chips
      (atributo `data-mgp-categoria`) NO se tocan, están bien. Ver
      MEMORIA.md §17.
- [x] **Página Inicio — backend (v0.5.1)**: shortcodes
      `[mgp_saludo]` (nombre real del usuario logueado) y
      `[mgp_sigue_leyendo]` (libros en progreso reales, con barra de
      avance) reemplazan el "Hola, Renzo" y las tarjetas de muestra
      hardcodeadas — ver MEMORIA.md §17.
- [ ] **Página Inicio — pendiente en Elementor**: borrar el saludo de
      muestra y la fila "Sigue leyendo" de muestra; agregar dos widgets
      "Shortcode" con `[mgp_saludo]` y `[mgp_sigue_leyendo]`.
- [ ] Opcional/cosmético: crear la clase global de Elementor
      `mgp-tag-mec` (con su propio color, igual que `mgp-tag-comp` /
      `mgp-tag-conta`) para que la etiqueta de Mecánica de producción
      tenga color — sin esto el libro se ve igual, solo sin color en el
      tag.
- [ ] **Página Mis libros**: sin revisar todavía. Nota: `class-mgp-loader.php`
      ya referencia un archivo `mgp-lector.js` para esta página que
      todavía no existe en `assets/js/` — pendiente crearlo cuando se
      trabaje esta página.
- [ ] Subir un primer libro real de prueba (Libros → Añadir nuevo) para
      validar el catálogo con datos reales (ya cableado, solo falta
      contenido).
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
