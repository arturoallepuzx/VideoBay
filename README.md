# VideoBay

Plataforma web y móvil para la compraventa y streaming de películas.
Backend: **Laravel 12** arquitectura **DDD + Hexagonal**.
Frontend: **Angular** (standalone components) + **Ionic** + **Capacitor** para empaquetar APK Android.

---

## Índice

- [Prerrequisitos](#prerrequisitos)
- [Cómo empezar](#cómo-empezar)
- [Estructura del proyecto](#estructura-del-proyecto)
  - [Backend (`backend/`)](#backend-backend)
  - [Frontend (`frontend/`)](#frontend-frontend)
  - [Docker y servicios](#docker-y-servicios)
- [Convenciones de desarrollo](#convenciones-de-desarrollo)
- [Nomenclatura](#nomenclatura)
- [Flujo de ejemplo: creación de usuario](#flujo-de-ejemplo-creación-de-usuario)
- [Buenas prácticas](#buenas-prácticas)
- [Estilo de código](#estilo-de-código)
- [Errores frecuentes a evitar](#errores-frecuentes-a-evitar)

---

## Prerrequisitos

Para trabajar en este proyecto necesitas tener instalado en tu máquina:

- **Docker** (y Docker Compose), para levantar la API, el frontend, la base de datos y DbGate. Sin Docker no podrás ejecutar `make start` ni el resto de comandos que dependen de los contenedores.
- **Make** (GNU Make), para usar los objetivos del `Makefile` (`make start`, `make install`, `make db-migrate`, etc.).
- **Git**, para clonar el repositorio.

---

## Cómo empezar

1. **Clonar el repositorio:**

   ```bash
   git clone <repo-url>
   cd VideoBay
   ```

2. **Configurar entorno backend (solo la primera vez):** copiar el archivo de ejemplo:

   ```bash
   cp backend/.env.example backend/.env
   ```

3. **Levantar los contenedores Docker:**

   ```bash
   make start
   ```

4. **Instalar dependencias backend, migrar la base de datos y generar clave de aplicación:**

   ```bash
   make install   # composer install + APP_KEY + migraciones + seeders (requiere que los contenedores estén levantados: make start)
   ```

   Si el contenedor `api` no quedó en marcha, vuelve a levantar: `make start`.

5. **Frontend (Angular):** El repositorio ya incluye el proyecto en `frontend/`. Con `make start` el contenedor levanta la app automáticamente. Para desarrollo en primer plano con live reload: `make serve-frontend`.

Tras seguir estos pasos tendrás:

- **API (Laravel):** [http://localhost:8000](http://localhost:8000)
- **Frontend (Angular):** [http://localhost:4200](http://localhost:4200)
- **DbGate (MySQL):** [http://localhost:9051](http://localhost:9051) (conexión **VideoBay MySQL** preconfigurada)

---

## Estructura del proyecto

### Backend (`backend/`)

El backend sigue una arquitectura **DDD + Hexagonal**, con cada dominio encapsulado bajo su propio namespace y una capa compartida:

```text
backend/app/
├── Shared/
│   └── Domain/
│       └── ValueObject/              # VOs reutilizables entre dominios
│           ├── DomainDateTime.php
│           ├── Email.php
│           └── Uuid.php
└── <Dominio>/                        # Ej: User, Catalog, Order
    ├── Domain/
    │   ├── Entity/
    │   ├── ValueObject/
    │   └── Interfaces/
    ├── Application/
    │   └── <CasoDeUso>/
    │       ├── <CasoDeUso>.php
    │       └── <CasoDeUso>Response.php
    └── Infrastructure/
        ├── Persistence/
        │   ├── Models/               # Modelos Eloquent específicos de cada dominio
        │   └── Repositories/         # Implementaciones concretas de repositorios
        ├── Services/                 # Implementaciones de servicios (hashers, clientes HTTP, etc.)
        └── Entrypoint/
            └── Http/                 # Controladores / entrypoints HTTP
```

| Carpeta | Descripción |
|---------|-------------|
| **Shared/Domain/ValueObject** | Value Objects reutilizables entre dominios (`Email`, `DomainDateTime`, `Uuid`, etc.). |
| **Domain/** | Lógica de negocio pura: entidades, VOs específicos del dominio e interfaces (contratos). |
| **Domain/Interfaces/** | Contratos de repositorios y servicios del dominio (p. ej. `UserRepositoryInterface`, `PasswordHasherInterface`). |
| **Application/** | Cada caso de uso vive en `Application/<CasoDeUso>/` con `<CasoDeUso>.php` y `<CasoDeUso>Response.php`. La Response es el DTO de salida del caso de uso; el controlador la usa para devolver JSON u otro formato. |
| **Infrastructure/** | Adaptadores externos: persistencia (Eloquent, etc.), entrypoints HTTP/CLI, colas, eventos. Los servicios externos (hashers, clientes HTTP) tienen su interfaz en `Domain/Interfaces/` y la implementación en `Infrastructure/Services/` (p. ej. `LaravelPasswordHasher`), registrada en el contenedor (p. ej. `AppServiceProvider`). |

### Frontend (`frontend/`)

Proyecto **Angular + Ionic** (standalone components) que consume la API del backend mediante los endpoints definidos en los entrypoints de cada dominio. Servido en `http://localhost:4200`.

```text
frontend/src/app/
├── components/        # Componentes reutilizables (botones, cards, modals…)
├── pages/             # Páginas de la aplicación
│   └── core/          # Páginas principales
├── pipes/             # Pipes personalizados
├── providers/         # Interceptores y providers (HTTP interceptor)
└── services/          # Servicios (llamadas API, lógica compartida)
```

- **Interceptor HTTP** (`providers/interceptor.ts`): prefija la URL base de la API (`environment.apiUrl`) y añade headers por defecto (`Accept`, `Accept-Language`).
- **Providers registrados en `main.ts`** con `withInterceptorsFromDi()` y `withFetch()`.

### Docker y servicios

| Servicio   | Contenedor        | Descripción                            |
|-----------|-------------------|----------------------------------------|
| `api`     | videobay_api      | Backend Laravel (puerto 8000)          |
| `frontend`| videobay_frontend | Angular (puerto 4200)                  |
| `db`      | videobay_db       | MySQL 8                                |
| `dbgate`  | videobay_dbgate   | Cliente web MySQL DbGate (puerto 9051) |

Con `make start` se levantan la API (Laravel en 8000), el frontend (Angular en 4200), MySQL y DbGate (9051). DbGate (http://localhost:9051) permite explorar la base MySQL con la conexión preconfigurada "VideoBay MySQL".

Comandos base (desde la raíz del repo):

```bash
make start            # Levantar contenedores (API, frontend, db, dbgate)
make install          # composer install + APP_KEY + migraciones + seeders
# Copiar backend/.env.example a backend/.env y generar APP_KEY si aplica
make install-frontend # Instalar dependencias npm del frontend
make db-migrate       # Migraciones en api
make restart          # stop + start
make test             # Tests PHP
make lint             # Laravel Pint
```

---

## Convenciones de desarrollo

1. **Dominio autocontenido.** Cada dominio encapsula sus entidades, VOs, interfaces, casos de uso e entrypoints. No depender de otros dominios para la lógica de negocio; compartir solo a través de `Shared` cuando sea necesario.

2. **Creación de entidades.**
   - Usar **métodos estáticos de fábrica** en la entidad: `Entity::dddCreate(...)`.
   - No instanciar la entidad con `new` desde casos de uso ni desde Infrastructure.

3. **Value Objects (VOs).**
   - **Constructor privado y método estático `create(...)`** como único punto de entrada. No instanciar VOs con `new` desde fuera del VO; usar siempre `VO::create(...)` (y métodos estáticos específicos como `Uuid::generate()` o `DomainDateTime::now()` cuando existan).
   - **Reutilizables**: en `Shared/Domain/ValueObject/` si aplican a varios dominios.
   - **Inmutables**: usar `\DateTimeImmutable` para fechas (`DomainDateTime`) y validar bien los inputs.
   - **Nombres semánticos**: `CreatedAt`, `UpdatedAt`, `DomainDateTime`, `Email`.

4. **Application / Casos de uso.**
   - Casos de uso **puros**: solo orquestan entidades y repositorios (inyectados por interfaz), sin lógica de framework.
   - Pueden devolver **objetos de respuesta** (p. ej. `CreateUserResponse`) en lugar de la entidad, para que el controlador no dependa del dominio en la serialización. La clase Response vive en la misma carpeta que el caso de uso (`Application/<CasoDeUso>/<CasoDeUso>Response.php`).
   - Pueden tener **Handlers** desacoplados para comandos o colas.
   - Servicios como el hasher de contraseñas se definen por **interfaz en Domain/Interfaces/** y se implementan en **Infrastructure/Services/**. El caso de uso los recibe por constructor; el controlador no usa facades (p. ej. `Hash`) para esa lógica.

5. **Infrastructure / Entrypoints.**
   - Controladores HTTP, comandos CLI, suscriptores de eventos.
   - Solo **adaptadores**: transforman entrada/salida y delegan en Application. Sin lógica de negocio.
   - Validaciones de entrada pueden usar Form Requests en `Infrastructure/Entrypoint/Http/Requests` del dominio.

6. **Repositorios.**
   - Implementar las **interfaces** definidas en `Domain/Interfaces/`.
   - Implementaciones concretas (Eloquent, etc.) en `Infrastructure/Persistence/Repositories/`.
   - Modelos Eloquent en `Infrastructure/Persistence/Models/`, usados solo desde los repositorios.
   - Tipar métodos con **entidades del dominio y VOs**, nunca con arrays planos ni tipos genéricos de framework.

7. **Rutas.** Registrar rutas apuntando a controladores dentro de los dominios, por ejemplo:

   ```php
   use App\User\Infrastructure\Entrypoint\Http\PostController;

   Route::post('/users', PostController::class);
   ```

8. **Testing.**
   - **Tests unitarios**: dominio y casos de uso (entidades, VOs, use cases con repositorios mockeados).
   - **Tests de integración**: validar endpoints HTTP contra los entrypoints (API).
   - No escribir tests que dependan de la implementación concreta de los repositorios (p. ej. detalles de Eloquent).

---

## Nomenclatura

| Elemento     | Convención          | Ejemplo                                       |
|-------------|---------------------|-----------------------------------------------|
| Clases      | PascalCase          | `User`, `CreateUserHandler`, `DomainDateTime` |
| Métodos     | camelCase           | `dddCreate`, `execute`, `handle`              |
| Variables   | camelCase           | `userRepository`, `createdAt`                 |
| Namespaces  | Reflejar carpetas   | `App\User\Domain\Entity`                      |
| Repositorios| Sufijo `Repository` | `EloquentUserRepository`                      |
| Interfaces  | Sufijo `Interface`  | `UserRepositoryInterface`                     |

---

## Flujo de ejemplo: creación de usuario

1. `PostController::__invoke()` recibe `POST /users`, valida con `$request->validate([...])` y llama al caso de uso pasando **contraseña en claro** (no hace hash en el controlador).
2. El caso de uso recibe `PasswordHasherInterface` y `UserRepositoryInterface`. Llama a `$this->passwordHasher->hash($plainPassword)`, construye VOs con `Email::create($email)` (y `Uuid::generate()`, `DomainDateTime::now()` donde aplique) y crea la entidad con `User::dddCreate(...)`.
3. Persiste con `UserRepositoryInterface::create($user)` (implementación `EloquentUserRepository` + modelo `EloquentUser`).
4. Devuelve `CreateUserResponse::create($user)` con los datos a exponer.
5. El controlador devuelve `new JsonResponse($response->toArray(), 201)` (sin acceder a la entidad para montar el JSON).

---

## Buenas prácticas

- Evitar lógica de negocio en Controllers o Eloquent Models.
- Mantener los dominios **autocontenidos**, siguiendo la convención: `App/<Dominio>/{Domain, Application, Infrastructure}`.
- Escribir **tests** que dependan de la interfaz del dominio, no de la implementación concreta.
- **No mezclar** lógica de dominio con código de framework (HTTP, Eloquent, etc.). El dominio no debe depender de Laravel.
- **Validaciones y reglas de negocio** en Domain: entidades, VOs y, si aplica, servicios de dominio. No en controladores ni en modelos de persistencia.
- **Infrastructure** solo transforma y conecta: HTTP, persistencia, colas, eventos. Sin reglas de negocio.
- **Reutilizar** VOs de `Shared` siempre que sea posible.
- Mantener **consistencia semántica** en nombres y tipos en todo el dominio.
- **Inmutabilidad**: cada cambio debe preservar la inmutabilidad de los VOs (y de los atributos que deban serlo).
- **Crear entidades** solo mediante el método de fábrica: `Entity::dddCreate(...)`.
- **Crear VOs** solo mediante su método estático de entrada (`Email::create(...)`, `Uuid::create(...)`, etc.), nunca con `new` desde fuera del VO.
- **No usar facades de Laravel** (p. ej. `Hash`) en controladores para lógica de dominio; usar servicios inyectados por interfaz en el caso de uso.
- **Programar contra interfaces** (repositorios, servicios externos), no contra implementaciones concretas en Domain o Application.

---

## Estilo de código

Para mantener un código consistente entre todos los colaboradores se siguen estas pautas:

- **Backend (PHP):** PSR-12 y las recomendaciones de [Symfony Coding Standards](https://symfony.com/doc/current/contributing/code/standards.html).
- **Frontend (Angular):** [Angular Style Guide](https://angular.dev/style-guide).
- **Tipado estricto en dominio y tests**: todos los archivos PHP bajo `backend/app/` y `backend/tests/` deben declarar `declare(strict_types=1);` inmediatamente tras `<?php`. `make lint` lo impone automáticamente (segunda pasada con `pint_strict.json` sobre `app/` y `tests/`).

**Convenciones básicas:**

- Una **clase por archivo** (sin clases auxiliares anidadas).
- **Imports (`use`) explícitos** para todas las clases que no estén en el espacio de nombres global.
- Propiedades **antes** de los métodos; primero métodos públicos, luego protegidos, luego privados (constructor al inicio).
- `camelCase` para variables y métodos, `PascalCase` para clases, `SCREAMING_SNAKE_CASE` para constantes.
- Paréntesis al instanciar clases aunque no haya argumentos (`new Foo()`).
- Añadir una **línea en blanco antes de `return`** cuando mejora la legibilidad.
- En arrays multilínea, usar **coma final** en cada elemento, incluido el último.
- Evitar lógica compleja en una sola línea; preferir bloques claros con llaves siempre presentes.

Antes de subir cambios, se recomienda:

```bash
make test   # tests del backend (PHPUnit)
make lint   # formatear código PHP (Laravel Pint) y Strict Types
```

---

## Errores frecuentes a evitar

- Colocar interfaces de repositorio fuera de `Domain/Interfaces/`.
- Instanciar entidades con `new` desde un caso de uso o controlador.
- Instanciar VOs con `new` desde casos de uso o controladores; usar siempre el método estático del VO (p. ej. `Email::create(...)`).
- Hashear contraseñas (u otra lógica de dominio) en el controlador con `Hash::make` u otras facades; debe hacerse en el caso de uso mediante un servicio inyectado por interfaz.
- Introducir tipos de Laravel (Request, Model, etc.) o arrays planos en firmas de métodos del dominio.
- Añadir lógica de negocio en controladores o en modelos Eloquent.
- Exponer estado mutable en VOs o permitir que las entidades devuelvan arrays/DTOs sin tipo en APIs públicas del dominio.
