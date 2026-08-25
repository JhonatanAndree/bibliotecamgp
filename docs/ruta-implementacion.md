# Ruta de implementación — Biblioteca Virtual MGP

Estado: **en desarrollo, plugin no instalado aún en el sitio en vivo.**

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

## Fase 2 — Conexión con el sitio real (siguiente paso)
- [ ] Subir `mgp-biblioteca-core` al sitio (ver MEMORIA.md §7) y activar.
- [ ] Instalar DearFlip Lite, Members, UpdraftPlus, Wordfence, FileBird.
- [ ] Crear plantilla single-libro con DearFlip apuntando a `/leer/{id}/`.
- [ ] Conectar los chips de categoría y el buscador del diseño real con
      los atributos `data-mgp-categoria` que espera `mgp-catalogo.js`.
- [ ] Cargar el fondo bibliográfico real (o al menos los 6 libros de
      prueba que ya aparecen en el diseño) como posts del CPT.

## Fase 3 — Pulido y lanzamiento
- [ ] Confirmar lista completa de carreras técnicas con Biblioteca.
- [ ] Subir portadas reales.
- [ ] Selector visual de medios para el PDF (mejora del meta box).
- [ ] Definir mecanismo de login por código de estudiante.
- [ ] Pruebas de carga con varios usuarios simultáneos (recordar límite
      de 1GB de RAM del hosting).
- [ ] Capacitar al personal de biblioteca (rol `bibliotecario`).

## Fase 4 — Operación continua
- [ ] Backups automáticos verificados (UpdraftPlus).
- [ ] Revisión periódica de seguridad (Wordfence) y actualizaciones.
- [ ] Mantener MEMORIA.md al día con cada decisión nueva.
