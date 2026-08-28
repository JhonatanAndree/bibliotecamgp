# Ruta de implementación — Biblioteca Virtual MGP

Estado: **plugin propio v0.4.0, puerta de login de todo el sitio en vivo
(código de estudiante = usuario + contraseña), portada estática = Inicio
(28/08/2026).** Ver MEMORIA.md §10-§13 para el detalle de bugs corregidos
y decisiones tomadas.

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
- [ ] **Página Login**: agregar en Elementor el widget "Shortcode" con
      `[mgp_login]` (única acción manual pendiente de esta página).
- [ ] **Página Catálogo**: contenedor `g-mgp-row-wrap`, chips con
      `data-mgp-categoria`, buscador en `.g-mgp-search-slot` — ver
      MEMORIA.md §13 para los detalles exactos por widget.
- [ ] **Página Inicio**: reemplazar el saludo y los "Sigue leyendo" de
      muestra (hardcodeados) por datos reales del usuario logueado.
- [ ] **Página Mis libros**: sin revisar todavía.
- [ ] Subir un primer libro real de prueba (Libros → Añadir nuevo) para
      validar el catálogo con datos reales en cuanto se cablee esa página.
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
