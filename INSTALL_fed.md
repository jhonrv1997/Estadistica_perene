# Módulo Convenio FED v3.2 — IntelHIS

**Convenio FED - Fondo de Estimulo al Desempeno (Red de Salud Perené)**

Versión 3.2 del módulo: **el importador ahora acepta el `Resultados.txt` REAL del SQL Server (8 columnas, indicador identificado por su código, ej. `FED-04`)**, manteniendo además la gestión de indicadores, la visualización interactiva con filtros por establecimiento, año, mes (con opción "todos los meses") e indicador de la v3, y la corrección del error 500 de la v3.1.

## Novedades de la v3.2 (2026-11-04)

- **Importación del formato real de 8 columnas**: el export actual del SQL Server trae
  `codigo; renipress; establecimiento; anio; mes; valor_numerador; valor_denominador; porcentaje_avance`
  (ejemplo: `FED-04;319;BAJO MARANKIARI;2026;01;1;1;100`). El importador busca el
  `id_indicador` automáticamente por **código** en la *Gestión de Indicadores*.
- **Detección automática de formato, línea por línea**: 8 columnas (o 7, sin porcentaje → se recalcula) = formato nuevo; 13 columnas = formato antiguo (id numérico). Pueden mezclarse.
- Si el código del archivo no está registrado, la línea se omite y el mensaje indica el código exacto (ej. `FED-99`), para registrarlo y volver a importar.
- Compatible con **UTF-8 con BOM** (como trae el archivo), UTF-8 sin BOM y ANSI; saltos Windows o Unix; cabecera detectada y omitida.

## Novedades de la v3.1 (2026-11-04)

- **CORREGIDO el "500 Internal Server Error"** al entrar a `convenio_fed.php`. Causa: con `PDO::ATTR_EMULATE_PREPARES => false` (configuración de `config.php`), MySQL/MariaDB **no admite placeholders `?` dentro de sentencias SHOW** al prepararlas de forma nativa (error 1064). La v3 usaba `prepare("SHOW TABLES LIKE ?")` y caía nada más entrar. Ahora se consulta `information_schema` (100 % compatible) con respaldo `SHOW` + literal seguro.
- **Red de seguridad global**: si cualquier otra cosa fallara en el futuro, la página muestra un panel legible con el motivo técnico en lugar de un 500 en blanco.
- **Auto-reparación del esquema**: se detectó que en el volcado `Database/if0_42181393_his.sql` los ALTER de la tabla de avances apuntan al nombre en singular (`CONVENIO_FED_AVANCE`), de modo que en el servidor real la tabla quedó **sin PRIMARY KEY ni AUTO_INCREMENT**. Con la v3.1, la primera importación repara el esquema sola (y si el hosting no permite `ALTER`, asigna los id manualmente). Opcionalmente ejecute el SQL corregido.
- **`diagnostico_fed.php`**: página de diagnóstico (solo Administrador) que revisa PHP, extensiones, conexión, tablas, columnas y AUTO_INCREMENT, y confirma el estado del servidor.

## Flujo de trabajo (informático ↔ establecimientos)

1. **Informático**: evalúa los indicadores en su base local **SQL Server** y exporta los resultados a `Resultados.txt` (8 columnas separadas por `;`, con el **código** del indicador).
2. **Informático (rol Admin)**: entra a *Convenios → Convenio FED → Importar Avances* y sube el archivo. El sistema resuelve el código del indicador, inserta/actualiza los registros y guarda la **fecha y hora de importación**.
3. **Personal de los 12 establecimientos**: entra a *Convenios → Convenio FED* cualquier día y revisa con filtros cómo va el avance de los indicadores de su establecimiento.

## Archivos del módulo (v3.2)

| Archivo | Rol |
|---------|-----|
| `convenio_fed.php` | Página completa del módulo: visualización interactiva + panel admin (gestión de indicadores + importación). **v3.2: importa el Resultados.txt real de 8 columnas** |
| `includes/header.php` | Menú: agrega accesos directos en **Admin** → "FED: Importar Avances" y "FED: Gestion de Indicadores" |
| `Database/update_fed_v3.sql` | SQL de actualización (columna `fecha_importacion`, índices, PK/AUTO_INCREMENT y creación de tablas si faltan) |
| `diagnostico_fed.php` | **Nuevo (v3.1)**: diagnóstico del servidor en un clic (solo Administrador) |
| `INSTALL_fed.md` | Este archivo |

## Instalación (3 pasos)

### Paso 1 — Subir los archivos

Suba por FTP/File Manager (o `git push`) los 4 archivos, respetando las rutas:

```
convenio_fed.php            (reemplaza el existente — versión v3.2)
includes/header.php         (reemplaza el existente)
Database/update_fed_v3.sql  (nuevo)
diagnostico_fed.php         (nuevo, opcional pero recomendado)
```

> **IMPORTANTE (actualización desde la v3 / v3.1):** la v3.2 solo reemplaza `convenio_fed.php`; no hay cambios de base de datos. La v3.1 corregía el "500 Internal Server Error" al entrar a Convenio FED (uso de placeholders `?` en sentencias SHOW incompatible con `PDO::ATTR_EMULATE_PREPARES => false`).

### Paso 2 — Aplicar el SQL (recomendado, una sola vez)

1. Entre a phpMyAdmin (InfinityFree) → base **if0_42181393_his** → pestaña **SQL**.
2. Pegue el contenido completo de `Database/update_fed_v3.sql` y ejecute.
3. Si muestra `Duplicate column name` o `Duplicate key name`, ese paso ya estaba aplicado: es seguro continuar.

> **Nota**: aunque no aplique el SQL, el módulo funciona: al importar, el sistema intenta crear automáticamente la columna `fecha_importacion`; si no puede, usa `fecha_registro` (la fecha que viene en el archivo del SQL Server) como fecha de actualización mostrada.

### Paso 3 — Verificar

1. Entre al sitio como **Administrador** → *Convenios → Convenio FED*.
2. Debe ver el panel ámbar **"Gestion de Indicadores / Importar Avances"** (solo visible para admin).
3. En la pestaña **Importar Avances**, suba su `Resultados.txt` y pulse **Importar Ahora**.
4. Debe aparecer: *"Importacion completada: N insertados, M actualizados... Fecha y hora de importacion: ..."*.
5. La tabla **Detalle de Avances** debe mostrar las columnas en este orden:
   `Renipress | Establecimiento | Anio | Mes | Valor Numerador | Valor Denominador | Porcentaje Avance | Observaciones | Indicador | Fecha Importacion`

## Formato del archivo Resultados.txt

### Formato actual — 8 columnas separadas por `;` (el que genera su SQL Server)

```
codigo; renipress; establecimiento; anio; mes; valor_numerador; valor_denominador; porcentaje_avance
```

Ejemplo real (archivo de noviembre 2026, año 2026, indicador FED-04):

```
FED-04;319;BAJO MARANKIARI;2026;01;1;1;100
FED-04;318;VILLA PERENE;2026;02;12;14;85.71
FED-04;319;BAJO MARANKIARI;2026;04;0;1;0
```

Reglas que aplica el importador al formato nuevo:

- El **código** de la primera columna (ej. `FED-04`) se busca en *Gestión de Indicadores*; de ahí sale el `id_indicador` interno. Si no está registrado, la línea se omite con error informado.
- Si el archivo trae **7 columnas** (sin `porcentaje_avance`), se recalcula: `numerador / denominador * 100`.
- `fecha_registro` y `usuario` no vienen en este formato: se guarda como fecha del registro la **fecha y hora de la importación** y como usuario quien importó.
- `trimestre` y `observaciones` quedan NULL (este export no los trae).
- Codificación **UTF-8** (con o sin BOM, como el archivo real) o **ANSI/ISO-8859-1**; saltos de línea Windows o Unix.
- Si la primera línea es una cabecera de columnas, se detecta y se omite.

### Formato antiguo — 13 columnas (se acepta como alternativa)

```
id_avance; id_indicador; renipress; establecimiento; anio; trimestre; mes;
valor_numerador; valor_denominador; porcentaje_avance; observaciones;
fecha_registro; usuario
```

Ejemplo:

```
1;3;319;BAJO MARANKIARI;2026;NULL;01;1.00;1.00;100.00;NULL;2026-11-03 10:57:51.017;NULL
```

- El `id_avance` del archivo **se ignora** (la web asigna el suyo).
- El `id_indicador` numérico debe existir en *Gestión de Indicadores*.
- Texto `NULL` o campo vacío → se guarda como NULL.
- `fecha_registro` se acepta con milisegundos; se guarda sin milisegundos.
- Si `porcentaje_avance` viene `NULL`, se recalcula como `numerador / denominador * 100`.
- Ambos formatos pueden mezclarse en el mismo archivo: la detección es **línea por línea**.

## Modos de importación

| Modo | Comportamiento | Cuándo usarlo |
|------|----------------|---------------|
| **Actualizar** (recomendado) | Si ya existe la clave (indicador + renipress + año + mes) la **actualiza** con los valores nuevos; si no existe, **inserta** | Uso diario: solo re-envía el archivo y todo se refresca |
| **Reemplazar periodo** | Primero **elimina** los registros de los periodos (indicador + año + mes) que trae el archivo y luego los recarga | Cuando quitó establecimientos o meses en el SQL Server y no quiere dejar datos viejos |

## Gestión de indicadores (Admin)

- **Agregar**: botón "Nuevo Indicador" (código único, nombre, descripción, unidad, meta anual, peso ponderado, periodicidad, responsable, orden).
- **Modificar**: botón "Editar" en cada fila.
- **Desactivar/Activar**: botón de toggle. Desactivar **no borra nada** y conserva el historial de avances; simplemente deja de mostrarse en el reporte (útil cuando los indicadores cambian cada año).
- El **peso ponderado** alimenta el "Avance Global del Convenio FED (ponderado)".

## Visualización (todos los usuarios)

- **Filtros**: Año · Mes (con **"-- Todos los meses --"**) · Establecimiento (los 12 de la red) · Indicador. Se aplican automáticamente al cambiarlos.
- **Aviso de actualización**: "Datos actualizados al: dd/mm/yyyy hh:mm:ss" (fecha/hora de la última importación) para que todos sepan qué tan fresca está la información.
- **Tabla de detalle interactiva**: buscador en vivo, ordenamiento por cualquier columna (clic en el encabezado), contador de registros y **exportación CSV** (abre en Excel).
- **Resumen por indicador**: acumulado numerador/denominador y % de avance (calculado, no promediado), con barras y semáforo (Excelente ≥90, Bueno ≥75, Regular ≥60, Bajo ≥40, Crítico <40).
- **Resumen por establecimiento**: promedio de avance, **los que requieren atención aparecen primero**, con botón "Detalle" que filtra por ese establecimiento.
- **Vista de tarjetas** por indicador con barra de avance.

## Seguridad

- Las acciones de gestión e importación **solo funcionan con rol `admin`** (`verificarAdmin` + verificación CSRF en cada POST). Un usuario común que intente un POST recibe "Acceso denegado" y no se altera la base.
- Toda consulta usa sentencias preparadas (PDO); toda salida se escapó con `htmlspecialchars`.
- Cada importación queda registrada en `LOG_IMPORTACION` (historial reciente visible en la pestaña de importación) y también en el Log de Auditoría del sistema.

## Preguntas frecuentes

**¿Me sale "500 Internal Server Error" al entrar a convenio_fed.php?**

Ese fue un defecto de la versión v3, ya corregido en la v3.1: con la configuración `PDO::ATTR_EMULATE_PREPARES => false` de `config.php`, MySQL/MariaDB rechaza los placeholders `?` en sentencias `SHOW` (error 1064), y la página fallaba nada más entrar. **Solución: suba el `convenio_fed.php` de esta versión (v3.2).** Si después de eso volviera a ver un error, la página ahora mostrará un **panel rojo con el motivo técnico exacto** (en vez del 500 en blanco); además puede subir `diagnostico_fed.php` a la raíz y abrirlo como Administrador para un reporte completo del servidor.

**¿Puedo importar varios indicadores en el mismo archivo?**
Sí. El archivo puede mezclar códigos (`FED-04`, `FED-05`, ...) o ids distintos; cada línea se procesa de forma independiente. El archivo real de ejemplo trae solo `FED-04`.

**Mi archivo trae un código (ej. FED-99) que no está registrado.**
El mensaje de importación lo indica línea por línea con el código exacto (ej. *"el codigo de indicador FED-99 no esta registrado en la Gestion de Indicadores"*). Registre el indicador en *Gestion de Indicadores* con **exactamente ese código** y vuelva a importar; mientras tanto esa línea queda reportada como error y el resto del archivo sí se importa.

**Importé y no aparece nada en la vista.**
Verifique que el **año del filtro** coincida con el año de los datos (por defecto se muestra el más reciente con datos) y que los códigos del archivo existan en *Gestion de Indicadores*.

**¿Se duplican los registros si importo dos veces el mismo archivo?**
No. El modo "Actualizar" reconoce la misma clave (indicador + renipress + año + mes) y actualiza en lugar de duplicar.

**¿Y si mi archivo usa el formato antiguo de 13 columnas con id numérico?**
Tampoco hay problema: la detección es automática y por línea. Incluso puede mezclar ambos formatos en el mismo archivo.
