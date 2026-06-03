# Modelo de datos

Este documento describe el esquema de base de datos de la aplicación VideoBay.

> Los importes monetarios se almacenan como enteros en céntimos para evitar problemas de precisión con decimales.
> Ejemplo: 10,50 € → `1050`


Las entidades principales usan `uuid` como identificador público y `id` como identificador interno autoincremental. El sistema utiliza **Soft Deletes** (`deleted_at`) en las entidades gestionables desde administración. Las tablas puramente relacionales, de control de estado o de auditoría inmutable pueden no llevarlo; cuando es así se indica en la propia tabla.

Las tablas con `unique` sobre columnas que admiten soft delete incluyen una columna virtual `is_active` (`CASE WHEN deleted_at IS NULL THEN 1 ELSE NULL END`) dentro del unique. Esto permite reutilizar la combinación tras un borrado lógico sin colisión: los NULL no chocan entre sí en un índice único de MySQL.

Las integraciones externas se guardan con identificadores propios:

- `tmdb_id` para TMDB (películas, personas, géneros).
- `imdb_id` cuando TMDB lo proporcione.
- `stripe_session_id` y `stripe_payment_intent_id` para Stripe Checkout.
- `external_id` para subtítulos importados.


---

## Entidades

### `users`
Usuarios registrados en la aplicación (clientes y administradores).

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | BIGINT | Identificador interno |
| `uuid` | VARCHAR | Identificador público único |
| `name` | VARCHAR | Nombre público único |
| `email` | VARCHAR | Email único, usado para login |
| `email_verified_at` | TIMESTAMP (nullable) | Fecha de verificación de email |
| `password_hash` | VARCHAR | Hash de la contraseña |
| `avatar_url` | VARCHAR (nullable) | Imagen de perfil |
| `role` | VARCHAR | `customer` o `admin` (default `customer`) |
| `accessibility_settings` | JSON (nullable) | Tamaño de fuente, alto contraste, etc. |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |
| `deleted_at` | TIMESTAMP | Soft delete |

> Índices y restricciones:
> - `unique(uuid)` — el identificador público no se repite
> - `unique(name, is_active)` — `users_name_active_unique`. El nombre es único entre cuentas vivas; permite re-registrar tras `DeleteAccount`
> - `unique(email, is_active)` — `users_email_active_unique`. El email es único entre cuentas vivas; permite re-registrar tras `DeleteAccount`
>
> El usuario administrador se crea exclusivamente mediante seeder. No existe un endpoint público de registro de admin.

---

### `refresh_tokens`
Tokens de refresco de autenticación. Tabla de soporte del esquema de auth con JWT (access token stateless, refresh token persistido y rotado).

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | BIGINT | Identificador interno |
| `uuid` | VARCHAR | Identificador público único |
| `session_uuid` | VARCHAR | Identificador de la sesión a la que pertenece el token (varios refresh tokens de una misma sesión comparten este uuid si se rotan) |
| `user_id` | BIGINT | FK → `users` |
| `token_hash` | CHAR(64) | Hash del token (no se guarda el token en claro) |
| `expires_at` | TIMESTAMP | Fecha de expiración |
| `revoked_at` | TIMESTAMP | Fecha de revocación (nullable) |
| `replaced_by_id` | BIGINT | FK → `refresh_tokens` (nullable, auto-referencial). Apunta al nuevo token que sustituyó a este al rotar |
| `device_label` | VARCHAR (nullable) | Etiqueta opcional del dispositivo o cliente asociado a la sesión |
| `created_at` | TIMESTAMP | Fecha de emisión |
| `updated_at` | TIMESTAMP | Última modificación |

> Índices y restricciones:
> - `unique(uuid)` — el identificador público no se repite
> - `unique(token_hash)` — el hash de cada token es único en el sistema
> - `index(session_uuid, revoked_at)` — buscar tokens vivos de una sesión
> - `index(user_id, revoked_at)` — buscar tokens vivos de un usuario
> - `index(expires_at)` — limpieza de tokens caducados
>
> No tiene `deleted_at`: la revocación se marca con `revoked_at` (modelo de negocio propio, no soft delete genérico).
> `user_id` usa `ON DELETE CASCADE`: si se borrara físicamente un usuario, sus refresh tokens desaparecen.
 `replaced_by_id` se rellena al rotar el token y mantiene la cadena auditable de rotaciones; al expirar la sesión, todos los tokens con el mismo `session_uuid` se revocan en un único UPDATE. Si se detecta el uso de un token ya rotado (ataque de reuse), se revoca toda la cadena de esa `session_uuid`.

---

### `movies`
Películas del catálogo. Pueden venir de TMDB y existir solo como metadato (no necesariamente disponibles para streaming o compra).

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | BIGINT | Identificador interno |
| `uuid` | VARCHAR | Identificador público único |
| `tmdb_id` | BIGINT (nullable) | Identificador TMDB |
| `imdb_id` | VARCHAR(20) (nullable) | Identificador IMDb cuando TMDB lo proporcione |
| `title` | VARCHAR | Título localizado |
| `original_title` | VARCHAR (nullable) | Título original |
| `overview` | TEXT (nullable) | Sinopsis |
| `release_date` | DATE (nullable) | Fecha de estreno |
| `runtime_minutes` | SMALLINT (nullable) | Duración en minutos |
| `original_language` | VARCHAR(10) (nullable) | Código ISO del idioma original |
| `poster_path` | VARCHAR (nullable) | Póster |
| `backdrop_path` | VARCHAR (nullable) | Imagen de fondo |
| `tmdb_rating` | DECIMAL(4,2) (nullable) | Nota media en TMDB (0.00–10.00) |
| `cached_at` | TIMESTAMP (nullable) | Última actualización desde TMDB |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |
| `deleted_at` | TIMESTAMP | Soft delete |

> Índices y restricciones:
> - `unique(uuid)` — el identificador público no se repite
> - `unique(tmdb_id, is_active)` — `movies_tmdb_active_unique`. El `tmdb_id` no se repite entre películas vivas; permite re-importar tras un borrado lógico
> - `index(imdb_id)` — búsquedas cruzadas con OpenSubtitles y otros proveedores que usan IMDb
> - `index(title)` — búsqueda por título por prefijo
> - `fullText(title, original_title, overview)` — `movies_fulltext_index`. Búsqueda libre con `MATCH ... AGAINST` para el fallback local cuando TMDB no está disponible

---

### `people`
Actores, directores y miembros del equipo técnico. Cacheados desde TMDB.

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | BIGINT | Identificador interno |
| `uuid` | VARCHAR | Identificador público único |
| `tmdb_id` | BIGINT (nullable) | Identificador TMDB |
| `name` | VARCHAR | Nombre |
| `biography` | TEXT (nullable) | Biografía |
| `profile_path` | VARCHAR (nullable) | Foto |
| `birthday` | DATE (nullable) | Fecha de nacimiento |
| `deathday` | DATE (nullable) | Fecha de fallecimiento |
| `place_of_birth` | VARCHAR (nullable) | Lugar de nacimiento |
| `cached_at` | TIMESTAMP (nullable) | Última actualización desde TMDB |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |
| `deleted_at` | TIMESTAMP | Soft delete |

> Índices y restricciones:
> - `unique(uuid)` — el identificador público no se repite
> - `unique(tmdb_id, is_active)` — `people_tmdb_active_unique`. El `tmdb_id` no se repite entre personas vivas
> - `index(name)` — búsqueda por nombre por prefijo
> - `fullText(name)` — `people_fulltext_index`. Búsqueda libre con `MATCH ... AGAINST` para el fallback local cuando TMDB no está disponible

---

### `genres`
Géneros cinematográficos. Catálogo cerrado cargado mediante seeder desde TMDB.

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | BIGINT | Identificador interno |
| `tmdb_id` | BIGINT (nullable) | Identificador TMDB |
| `name` | VARCHAR | Nombre del género |

> Índices y restricciones:
> - `unique(tmdb_id)` — `genres_tmdb_unique`. El `tmdb_id` no se repite
> - `unique(name)` — `genres_name_unique`. El nombre no se repite
>
> No tiene `uuid`, `timestamps` ni `deleted_at`: es catálogo cerrado, no se audita ni se borra; los géneros se gestionan desde seeder.

---

### `movie_genre`
Relación N:M entre películas y géneros.

| Campo | Tipo | Descripción |
|---|---|---|
| `movie_id` | BIGINT | FK → `movies` |
| `genre_id` | BIGINT | FK → `genres` |

> Índices y restricciones:
> - `primary(movie_id, genre_id)` — PK compuesta, evita duplicados
>
> Tabla pivot pura: sin `id`, `timestamps` ni `deleted_at`. El borrado físico de la película o del género limpia la asociación (`ON DELETE CASCADE`).

---

### `movie_credits`
Créditos (reparto y equipo técnico) que relacionan películas con personas. Permite construir tanto el reparto de una película como la filmografía de una persona.

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | BIGINT | Identificador interno |
| `movie_id` | BIGINT | FK → `movies` |
| `person_id` | BIGINT | FK → `people` |
| `department` | VARCHAR | `cast`, `directing`, `writing`, `production`, `sound`, `art`, `camera`, `editing`, `crew` |
| `job` | VARCHAR (nullable) | Director, Writer, Producer, etc. |
| `character_name` | VARCHAR (nullable) | Personaje si es actor/actriz |
| `credit_order` | INT (nullable) | Orden en reparto/filmografía |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |

> Índices y restricciones:
> - `index(movie_id, department)` — cargar el reparto/equipo de una película filtrado por departamento
> - `index(person_id, department)` — cargar la filmografía de una persona filtrada por departamento (p. ej. "películas que dirigió")
>
> Sin `deleted_at`: la asociación se elimina físicamente al re-sincronizar con TMDB.

---

### `barcode_lookups`
Caché autoaprendida de la resolución barcode → película. La primera vez que un usuario identifica un barcode (por API externa o entrada manual), se guarda aquí para que el siguiente escaneo sea un acierto interno O(1).

| Campo | Tipo | Descripción |
|---|---|---|
| `barcode` | VARCHAR(32) | PK. Código de barras / EAN |
| `movie_id` | BIGINT (nullable) | FK → `movies` |
| `resolved_via` | VARCHAR | `manual`, `external_api`, `physical_copy` o `sale_proposal` |
| `confirmed` | BOOLEAN | Si la asociación está confirmada por el usuario (default `false`) |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |

> Índices y restricciones:
> - `primary(barcode)` — clave natural; el acceso siempre es por `barcode`
> - `index(movie_id)` — listar barcodes de una misma película (mismo título en distintas ediciones físicas)
>
> Sin `id` autoincremental: el `barcode` es la clave natural y único punto de acceso. Si un lookup resulta incorrecto se actualiza la fila (cambiando `movie_id` o `confirmed`) o se borra y se vuelve a aprender en la siguiente cascada.

---

### `physical_copies`
Copias físicas que la tienda vende. Cada copia es una unidad de inventario concreta con su formato, estado y precio.

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | BIGINT | Identificador interno |
| `uuid` | VARCHAR | Identificador público único |
| `movie_id` | BIGINT | FK → `movies` |
| `sku` | VARCHAR(64) | Stock Keeping Unit. Código interno de tienda asignado por el admin a cada copia física |
| `barcode` | VARCHAR(32) (nullable) | Código de barras de la edición |
| `format` | VARCHAR | `DVD`, `BLURAY`, `UHD_4K`, `VHS` |
| `region` | VARCHAR(16) (nullable) | Código de región / país |
| `condition` | VARCHAR | `new`, `like_new`, `good`, `fair` |
| `cover_photo_url` | VARCHAR (nullable) | Imagen real de la edición |
| `price_cents` | INT | Precio de venta en céntimos |
| `stock_available` | INT | Stock libre disponible (default `0`) |
| `stock_reserved` | INT | Stock reservado por checkouts pendientes (default `0`) |
| `active` | BOOLEAN | Visible en tienda (default `true`) |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |
| `deleted_at` | TIMESTAMP | Soft delete |

> Índices y restricciones:
> - `unique(uuid)` — el identificador público no se repite
> - `unique(sku, is_active)` — `physical_copies_sku_active_unique`. El SKU no se repite entre copias vivas
> - `index(barcode)` — el escáner de la APK busca por barcode
> - `index(movie_id, active)` — listar copias activas de una película
>
> `active = false` significa "descatalogada pero datos retenidos" (no se muestra en tienda, conserva histórico); `deleted_at != NULL` significa "eliminada por el admin". Ambos coexisten conceptualmente. El borrado de la película asociada se restringe (`ON DELETE RESTRICT`) para no destruir histórico de copias.

---

### `sale_proposals`
Propuestas de usuarios para vender una película física a la tienda. Flujo simple: propuesta → revisión admin → aceptada/rechazada/completada.

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | BIGINT | Identificador interno |
| `uuid` | VARCHAR | Identificador público único |
| `user_id` | BIGINT | FK → `users` |
| `movie_id` | BIGINT (nullable) | FK → `movies`. NULL si el usuario no identifica la película |
| `title_text` | VARCHAR (nullable) | Título introducido a mano si no se identifica la película |
| `barcode` | VARCHAR(32) (nullable) | Código de barras de la edición ofrecida |
| `format` | VARCHAR | Formato físico declarado |
| `condition` | VARCHAR | Estado declarado |
| `notes` | TEXT (nullable) | Comentarios del usuario |
| `offered_price_cents` | INT (nullable) | Precio que la tienda ofrece al usuario |
| `status` | VARCHAR | `proposed`, `accepted`, `rejected`, `completed` (default `proposed`) |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |
| `deleted_at` | TIMESTAMP | Soft delete |

> Índices y restricciones:
> - `unique(uuid)` — el identificador público no se repite
> - `index(barcode)` — buscar propuestas por código de barras
> - `index(user_id, status)` — "mis propuestas" filtradas por estado
> - `index(status)` — bandeja del admin de propuestas pendientes
>
> No hay pago automático al usuario: el admin gestiona el pago fuera del sistema.

---

### `pricing_settings`
Configuración global del motor de precios. Solo debe existir una fila.

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | BIGINT | Identificador interno (siempre `1` por convención) |
| `base_prices` | JSON | Precios base por formato en céntimos, p. ej. `{ "DVD": 600, "BLURAY": 1200, "UHD_4K": 2000, "VHS": 300 }` |
| `condition_multipliers` | JSON | Multiplicadores por estado, p. ej. `{ "new": 1.0, "like_new": 0.85, "good": 0.65, "fair": 0.45 }` |
| `buy_margin_percent` | TINYINT | Margen de compra respecto al precio de venta (p. ej. `40` = la tienda compra al 40% de lo que revende) |
| `updated_at` | TIMESTAMP | Última modificación |

> Índices y restricciones: ninguna específica más allá de la PK por defecto.
>
> La unicidad de la fila se garantiza por **convención en código** (no por constraint de BD): el modelo expone `PricingSettings::current()` que hace `firstOrCreate(['id' => 1], [...])` y ningún caso de uso crea filas adicionales. Una sola fila editable cubre las reglas de pricing del catálogo (precio base por formato + multiplicador por estado + margen de compra).
>
> Fórmula:
> ```
> precio_venta_estimado  = base_prices[formato] × condition_multipliers[estado]
> precio_compra_estimado = precio_venta_estimado × (buy_margin_percent / 100)
> ```

---

### `carts`
Un carrito por usuario, se borra al confirmar el pago.

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | BIGINT | Identificador interno |
| `uuid` | VARCHAR | Identificador público único |
| `user_id` | BIGINT | FK → `users` |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |

> Índices y restricciones:
> - `unique(uuid)` — el identificador público no se repite
> - `unique(user_id)` — un único carrito por usuario
>
> Sin soft delete el borrado del usuario propaga en cascada.

---

### `cart_items`
Líneas del carrito.

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | BIGINT | Identificador interno |
| `cart_id` | BIGINT | FK → `carts` |
| `physical_copy_id` | BIGINT | FK → `physical_copies` |
| `quantity` | INT | Cantidad (default `1`) |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |

> Índices y restricciones:
> - `unique(cart_id, physical_copy_id)` — `cart_items_cart_copy_unique`. Evita líneas duplicadas en el mismo carrito; añadir la misma copia incrementa la cantidad
>
> El borrado del carrito padre y de la `physical_copy` propagan en cascada. Si la copy está soft-deleted o `active = false`, la línea queda marcada como "no disponible" en la UI.

---

### `orders`
Pedidos de compra física con recogida en tienda.

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | BIGINT | Identificador interno |
| `uuid` | VARCHAR | Identificador público único |
| `user_id` | BIGINT | FK → `users` |
| `status` | VARCHAR | `pending_payment`, `paid`, `ready_for_pickup`, `picked_up`, `cancelled`, `refunded` (default `pending_payment`) |
| `total_cents` | INT | Total en céntimos |
| `stripe_session_id` | VARCHAR (nullable) | ID de sesión Stripe Checkout |
| `stripe_payment_intent_id` | VARCHAR (nullable) | Payment Intent asociado |
| `pickup_code` | VARCHAR(16) (nullable) | Código único de recogida (se genera al pagar) |
| `paid_at` | TIMESTAMP (nullable) | Fecha de pago confirmado |
| `ready_at` | TIMESTAMP (nullable) | Fecha en que el pedido queda listo para recogida |
| `picked_up_at` | TIMESTAMP (nullable) | Fecha de recogida en tienda |
| `cancelled_at` | TIMESTAMP (nullable) | Fecha de cancelación |
| `expires_at` | TIMESTAMP (nullable) | Caducidad de la reserva de stock para checkouts no completados |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |
| `deleted_at` | TIMESTAMP | Soft delete |

> Índices y restricciones:
> - `unique(uuid)` — el identificador público no se repite
> - `unique(pickup_code, is_active)` — `orders_pickup_code_active_unique`. El `pickup_code` no se repite entre pedidos vivos
> - `index(stripe_session_id)` — lookup del pedido al recibir webhook de Stripe
> - `index(stripe_payment_intent_id)` — lookup por Payment Intent
> - `index(user_id, status)` — "mis pedidos" filtrados por estado
> - `index(status, expires_at)` — `orders_status_expires_at_index`. Job de cancelación de pedidos expirados (`WHERE status = 'pending_payment' AND expires_at < NOW()`)
>
> El borrado físico de un usuario con pedidos se restringe (`ON DELETE RESTRICT`) para preservar el histórico contable.

---

### `order_items`
Líneas del pedido. Conservan un **snapshot inmutable** de los datos críticos en el momento de la compra (título, formato, estado, precio) para que cambios futuros en el catálogo no alteren los pedidos históricos.

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | BIGINT | Identificador interno |
| `order_id` | BIGINT | FK → `orders` |
| `physical_copy_id` | BIGINT | FK → `physical_copies` |
| `quantity` | INT | Cantidad |
| `unit_price_cents` | INT | Precio unitario congelado en el momento de la compra |
| `movie_title_snapshot` | VARCHAR | Título de la película en el momento de la compra |
| `format_snapshot` | VARCHAR | Formato físico en el momento de la compra |
| `condition_snapshot` | VARCHAR | Estado declarado en el momento de la compra |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |

> El borrado del pedido padre propaga en cascada. El borrado de la `physical_copy` referenciada se restringe (`ON DELETE RESTRICT`) para preservar la trazabilidad.

---

### `stripe_webhook_events`
Registro de webhooks recibidos de Stripe para garantizar **idempotencia**: si Stripe reentrega un evento, se detecta por su `id` (que es el propio `event.id` de Stripe) y no se procesa dos veces.

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | VARCHAR | PK. `event.id` de Stripe (`evt_xxxxxxxxxxxx`) |
| `type` | VARCHAR | Tipo de evento (`checkout.session.completed`, etc.) |
| `payload` | JSON | Payload completo del evento |
| `processing_error` | TEXT (nullable) | Mensaje de error si el procesamiento falla |
| `processed_at` | TIMESTAMP (nullable) | Fecha de procesamiento exitoso |
| `created_at` | TIMESTAMP | Fecha de recepción |

> Índices y restricciones:
> - `index(type)` — agrupar/filtrar por tipo de evento
>
> Sin `updated_at` ni `deleted_at`: es un log inmutable. La firma del webhook debe verificarse antes de guardar/procesar. `processing_error` se rellena solo cuando el handler lanza una excepción capturada.

---

### `video_files`
Archivos de vídeo asociados a películas streameables. 

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | BIGINT | Identificador interno |
| `uuid` | VARCHAR | Identificador público único |
| `movie_id` | BIGINT | FK → `movies` |
| `original_filename` | VARCHAR (nullable) | Nombre original del archivo subido (solo en ruta upload web) |
| `original_format` | VARCHAR(16) (nullable) | Formato original (MKV, AVI, MOV, MP4, etc.) (solo en ruta upload web) |
| `original_path` | VARCHAR (nullable) | Ruta del archivo original sin procesar (solo en ruta upload web; permite reintentar transcodificación) |
| `processed_path` | VARCHAR (nullable) | Ruta del MP4 final reproducible |
| `mime_type` | VARCHAR(64) | Normalmente `video/mp4` (default `video/mp4`) |
| `duration_seconds` | INT (nullable) | Duración en segundos |
| `file_size_bytes` | BIGINT (nullable) | Tamaño del archivo procesado |
| `audio_language` | VARCHAR(10) (nullable) | Código ISO del idioma original (audio único en VO) |
| `processing_status` | VARCHAR | `pending`, `processing`, `ready`, `failed` (default `pending`) |
| `processing_error` | TEXT (nullable) | Error de FFmpeg si falla la transcodificación |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |
| `deleted_at` | TIMESTAMP | Soft delete |

> Índices y restricciones:
> - `unique(uuid)` — el identificador público no se repite
> - `index(movie_id, processing_status)` — listar videos de una película filtrados por estado
> - `index(processing_status)` — worker de cola que busca pendientes (`WHERE processing_status = 'pending'`)
>
> Solo se reproduce si `processing_status = ready`. Range Requests sirve `processed_path`, nunca el `original_path`.

---

### `playback_progress`
Progreso de visualización por usuario y película. Permite "continuar viendo" desde donde se dejó.

| Campo | Tipo | Descripción |
|---|---|---|
| `user_id` | BIGINT | FK → `users` |
| `movie_id` | BIGINT | FK → `movies` |
| `position_seconds` | INT | Segundo actual de reproducción (default `0`) |
| `duration_seconds` | INT (nullable) | Duración total conocida |
| `completed` | BOOLEAN | Si se considera terminada (default `false`) |
| `updated_at` | TIMESTAMP | Última actualización |

> Índices y restricciones:
> - `primary(user_id, movie_id)` — PK compuesta; un único progreso por par usuario-película
> - `index(user_id, completed, updated_at)` — `playback_progress_continue_watching_index`. Carrusel "continuar viendo" (`WHERE user_id = X AND completed = false ORDER BY updated_at DESC`)
>
> Sin `id`, `uuid`, `created_at` ni `deleted_at`: es una tabla puramente relacional de estado actual.
---

### `subtitles`
Subtítulos disponibles para una película. Pueden venir de proveedor externo (OpenSubtitles), subida de admin o subida de usuario. Moderación reactiva vía reportes.

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | BIGINT | Identificador interno |
| `uuid` | VARCHAR | Identificador público único |
| `movie_id` | BIGINT | FK → `movies` |
| `language` | VARCHAR(10) | Código ISO del idioma |
| `label` | VARCHAR | Etiqueta visible (ej. "Español") |
| `source` | VARCHAR | `external`, `user_upload`, `admin_upload` |
| `provider` | VARCHAR (nullable) | Proveedor externo (ej. `opensubtitles`) |
| `external_id` | VARCHAR (nullable) | ID del proveedor externo |
| `file_path` | VARCHAR | Ruta local del `.vtt` |
| `original_format` | VARCHAR | `srt` o `vtt` (los SRT se convierten a VTT al importar) |
| `uploaded_by_user_id` | BIGINT (nullable) | FK → `users`. Quien subió el archivo |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |
| `deleted_at` | TIMESTAMP | Soft delete (retirada tras moderación) |

> Índices y restricciones:
> - `unique(uuid)` — el identificador público no se repite
> - `index(provider, external_id)` — buscar subtítulos ya importados del proveedor para no duplicar

---

### `subtitle_reports`
Reportes de subtítulos incorrectos por parte de los usuarios.

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | BIGINT | Identificador interno |
| `subtitle_id` | BIGINT | FK → `subtitles` |
| `reported_by_user_id` | BIGINT | FK → `users` |
| `reason` | VARCHAR | Motivo |
| `status` | VARCHAR | `pending`, `resolved`, `dismissed` (default `pending`) |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |

> Índices y restricciones:
> - `index(subtitle_id, status)` — listar reportes pendientes de un subtítulo concreto
> - `index(status)` — bandeja del admin con todos los reportes pendientes sin filtrar por subtítulo
>
> El borrado del subtítulo padre propaga en cascada. El borrado físico del usuario reportante también propaga en cascada (las cuentas borradas pierden sus reportes).

---

### `reviews`
Reseñas propias de los usuarios sobre películas (independientes de TMDB).

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | BIGINT | Identificador interno |
| `uuid` | VARCHAR | Identificador público único |
| `user_id` | BIGINT | FK → `users` |
| `movie_id` | BIGINT | FK → `movies` |
| `rating` | TINYINT | 1-10 (medios-estrellas, 1=½★, 10=5★) |
| `body` | TEXT (nullable) | Texto de la reseña puede ser solo rating sin texto|
| `contains_spoilers` | BOOLEAN | Si contiene spoilers (default `false`) |
| `likes_count` | INT | Contador desnormalizado de likes (default `0`) |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |
| `deleted_at` | TIMESTAMP | Soft delete |

> Índices y restricciones:
> - `unique(uuid)` — el identificador público no se repite
> - `unique(user_id, movie_id, is_active)` — `reviews_user_movie_active_unique`. Una reseña por usuario y película entre reseñas vivas; permite re-escribir tras borrar la propia
> - `index(movie_id)` — listar todas las reseñas de una película
>
> Solo el autor puede editar o borrar su reseña. El texto con `contains_spoilers = true` se oculta en UI hasta que el lector lo revele. El contador `likes_count` se mantiene sincronizado desde la lógica de aplicación al insertar/borrar en `review_likes`.

---

### `review_likes`
Likes sobre reseñas. Un like por usuario y reseña estilo Letterboxd.

| Campo | Tipo | Descripción |
|---|---|---|
| `review_id` | BIGINT | FK → `reviews` |
| `user_id` | BIGINT | FK → `users` |
| `created_at` | TIMESTAMP | Cuándo se dio el like |

> Índices y restricciones:
> - `primary(review_id, user_id)` — PK compuesta; un like por usuario y reseña
>
> Sin `id`, `uuid`, `updated_at` ni `deleted_at`: dar like es un INSERT, quitarlo es un DELETE físico.

---

### `review_reports`
Reportes de reseñas inadecuadas por parte de los usuarios.

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | BIGINT | Identificador interno |
| `review_id` | BIGINT | FK → `reviews` |
| `reported_by_user_id` | BIGINT | FK → `users` |
| `reason` | VARCHAR | `spam`, `offensive`, `hidden_spoiler`, `other` |
| `status` | VARCHAR | `pending`, `resolved`, `dismissed` (default `pending`) |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |

> Índices y restricciones:
> - `index(review_id, status)` — listar reportes pendientes de una reseña concreta
> - `index(status)` — bandeja del admin con todos los reportes pendientes sin filtrar por reseña

---

### `wishlist_items`
Películas guardadas en la lista de deseos del usuario.

| Campo | Tipo | Descripción |
|---|---|---|
| `user_id` | BIGINT | FK → `users` |
| `movie_id` | BIGINT | FK → `movies` |
| `created_at` | TIMESTAMP | Fecha de adición (default `CURRENT_TIMESTAMP`) |

> Índices y restricciones:
> - `primary(user_id, movie_id)` — PK compuesta; cada usuario añade cada película una sola vez
>
> Tabla puramente relacional: sin `id`, `uuid`, `updated_at` ni `deleted_at`. Quitar de la lista es un borrado físico. El borrado del usuario o de la película propaga en cascada.

---

### `watch_later_items`
Películas guardadas para ver más tarde. Conceptualmente separada de la wishlist (intención de consumo en streaming, no de compra).

| Campo | Tipo | Descripción |
|---|---|---|
| `user_id` | BIGINT | FK → `users` |
| `movie_id` | BIGINT | FK → `movies` |
| `created_at` | TIMESTAMP | Fecha de adición (default `CURRENT_TIMESTAMP`) |

> Índices y restricciones:
> - `primary(user_id, movie_id)` — PK compuesta; cada usuario añade cada película una sola vez
>
> Misma estructura que `wishlist_items`. Tiene sentido sobre todo para películas streameables, pero puede guardarse cualquier película.

---

### `pinned_favorites`
Películas favoritas en posiciones ordenadas (estilo Letterboxd).

Número de pines configurado en `WISHLIST_PINNED_MAX_SLOTS` (default 5). 

Independiente de `wishlist_items` y `watch_later_items`.

| Campo | Tipo | Descripción |
|---|---|---|
| `user_id` | BIGINT | FK → `users` |
| `position` | TINYINT | Posición del slot  |
| `movie_id` | BIGINT | FK → `movies` |

> Índices y restricciones:
> - `primary(user_id, position)` — PK compuesta; un slot, una peli
> - `unique(user_id, movie_id)` — `pinned_favorites_user_movie_unique`. La misma peli no puede pinnearse en dos slots a la vez
>
> Tabla  relacional
>
> El borrado físico del usuario o de la película propaga en cascada.

---

### `notifications`
Notificaciones in-app dirigidas a un usuario concreto. Generadas por otros dominios cuando ocurren eventos relevantes (pedido listo, propuesta aceptada, like en reseña, etc.).

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | BIGINT | Identificador interno |
| `uuid` | VARCHAR | Identificador público único |
| `user_id` | BIGINT | FK → `users`. Destinatario |
| `type` | VARCHAR(64) | Identificador del evento (`order.ready_for_pickup`, `proposal.accepted`, `review.liked`, etc.) |
| `title` | VARCHAR | Título breve mostrado en el feed |
| `body` | TEXT (nullable) | Cuerpo opcional con más detalle |
| `action_url` | VARCHAR (nullable) | URL relativa a la que navegar al clicar (ej: `/orders/{uuid}`) |
| `metadata` | JSON (nullable) | Snapshot opcional para render web: actor, película, reseña, subtítulo, pedido o propuesta |
| `read_at` | TIMESTAMP (nullable) | Cuándo el usuario la marcó como leída |
| `created_at` | TIMESTAMP | Cuándo se generó |

> Índices y restricciones:
> - `unique(uuid)` — el identificador público no se repite
> - `index(user_id, read_at)` — contar/listar no leídas (`WHERE user_id = X AND read_at IS NULL`)
> - `index(user_id, created_at)` — feed "mis notificaciones" ordenado por fecha
>
> Sin `updated_at` ni `deleted_at`: append-only. Marcar como leída es un UPDATE de `read_at`. El borrado físico del usuario propaga en cascada.

---

## Relaciones

```
users ──< refresh_tokens
users ──< carts
users ──< orders
users ──< sale_proposals
users ──< reviews
users ──< review_likes
users ──< review_reports
users ──< subtitle_reports
users ──< subtitles            (uploaded_by_user_id, nullable)
users ──< wishlist_items
users ──< watch_later_items
users ──< pinned_favorites
users ──< playback_progress
users ──< notifications

movies ──< physical_copies
movies ──< video_files
movies ──< subtitles
movies ──< reviews
movies ──< wishlist_items
movies ──< watch_later_items
movies ──< pinned_favorites
movies ──< playback_progress
movies ──< sale_proposals      (movie_id, nullable)
movies ──< barcode_lookups     (movie_id, nullable)
movies >──< genres             (vía movie_genre)
movies >──< people             (vía movie_credits)

physical_copies ──< cart_items
physical_copies ──< order_items

carts ──< cart_items
orders ──< order_items
reviews ──< review_likes
reviews ──< review_reports
subtitles ──< subtitle_reports

refresh_tokens ──< refresh_tokens   (replaced_by_id, auto-referencial)
```

---

## Reglas de negocio

### Catálogo

- Toda película externa se importa desde el backend usando TMDB. El frontend no conoce la clave de TMDB.
- Las películas se pueden buscar aunque no estén disponibles para compra o streaming.

  - Stream: existe un `video_files` `ready` no borrado asociado.
  - Compra: existe ≥1 `physical_copy` activa 


- Una película o persona se importa a su tabla local la primera vez que un usuario abre su ficha (`ImportMovieFromTmdb` / `ImportPersonFromTmdb`) con una llamada a `/movie/{id}` o `/person/{id}`. Los resultados de búsqueda no se guardan localmente hasta que alguien abre la ficha. `cached_at` permite refresco posterior si la fila queda desfasada.

### Inventario

- La tienda vende copias físicas propias. No hay marketplace P2P entre usuarios. No hay envío a domicilio: solo recogida.
- Las copias se identifican por formato, región, estado, precio y código de barras.
- El motor de precios usa una única fila en `pricing_settings` con `base_prices`, `condition_multipliers` y `buy_margin_percent`. La estimación es orientativa; el admin ajusta el precio final tanto de compra como de venta.

### Pedidos

- El usuario añade copias al carrito. Al iniciar checkout se crea un `order` en `pending_payment` y se reserva stock (`physical_copies.stock_reserved`).
- Stripe Checkout confirma el pago mediante webhook. La firma se verifica antes de procesar; la idempotencia se garantiza con `stripe_webhook_events.id`.
- Al confirmarse el pago, el webhook registra `paid_at`, guarda el `stripe_payment_intent_id`, genera el `pickup_code` único y deja el pedido en `ready_for_pickup`. El admin confirma la entrega marcándolo como `picked_up`.
- Las líneas (`order_items`) congelan snapshots del título, formato, estado y precio en el momento de la compra: el catálogo puede cambiar después sin alterar pedidos históricos.

### Streaming

- El streaming es gratuito para usuarios autenticados.
- Solo se reproducen vídeos legales alojados en el servidor (dominio público, Creative Commons, contenido del autor).
- El admin sube cualquier formato → se almacena en `original_path` y se encola `TranscodeVideoJob` → FFmpeg lo normaliza a MP4 H.264/AAC en `processed_path` → estado pasa a `ready`. Si falla, `processing_error` registra el motivo y el original sigue disponible para reintentar.
- Se sirve con Range Requests.


### Subtítulos

- Los subtítulos se cargan desde BD local. Si no existen, se pueden buscar e importar desde proveedor externo (OpenSubtitles o similar).
- Los usuarios pueden subir subtítulos propios.

- Los `.srt` se convierten a `.vtt` porque el `<track>` HTML5 solo acepta WebVTT. 


### Reseñas

- Las reseñas son propias de VideoBay, no de TMDB.
- Una reseña por usuario y película (`unique(user_id, movie_id, is_active)`).
- Los usuarios pueden dar like (`review_likes`) y reportar (`review_reports`).
- El texto con spoilers se oculta hasta que el lector lo revele.
- El contador `likes_count` se mantiene sincronizado desde la aplicación al insertar/borrar likes.

### Autenticación

- Esquema híbrido: **access token** JWT stateless de vida corta  + **refresh token** persistido en `refresh_tokens` con vida larga y rotación al renovar.
- "Cerrar todas las sesiones" equivale a revocar todos los `refresh_tokens` vivos del usuario (`UPDATE ... SET revoked_at = NOW() WHERE user_id = X AND revoked_at IS NULL`).
- El borrado físico de un usuario borra en cascade sus refresh tokens y el borrado lógico (soft delete) los invalida indirectamente porque las validaciones de uso comprueban el estado del usuario.

### Guardadas del usuario

- **Wishlist** y **watch later** son listas independientes.
- `pinned_favorites` son 5 slots ordenados (configurable por .env). Independientes de wishlist: una peli puede ser favorita sin estar en ella.

### Notificaciones

- Las notificaciones in-app son generadas por otros dominios cuando ocurren eventos relevantes (pedido listo, propuesta aceptada, like en reseña, etc.).
- Cada dominio dispara notificaciones vía `NotificationDispatcherInterface` (declarada en `Shared/Domain/Interfaces/`) sin acoplarse al dominio `Notification`.




### Convenciones transversales

- Los importes se almacenan en céntimos (`INT`) para evitar errores de precisión.

- Los hashes de tamaño fijo conocido se almacenan como `CHAR(n)` (p. ej. `CHAR(64)` para SHA-256 hex).

