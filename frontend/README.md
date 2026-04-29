# Frontend — Angular + Ionic

Proyecto frontend basado en **Angular 20** con **Ionic 8**.

---

## Requisitos previos

El frontend se ejecuta dentro de un contenedor Docker, por lo que **no necesitas instalar Node ni Angular en tu máquina**. Solo necesitas:

- **Docker** y **Docker Compose**
- **Make** (viene preinstalado en macOS y Linux)

> Si prefieres trabajar sin Docker, necesitarás **Node.js >= 20** y **npm**.

---

## Primeros pasos

Todos los comandos se ejecutan desde la **raíz del proyecto** (no desde la carpeta `frontend/`).

```bash
# 1. Levantar todos los contenedores (API + frontend + DB + DbGate)
make start

# 2. Instalar dependencias del frontend
make install-frontend

# 3. Arrancar el servidor de desarrollo con live reload
make serve-frontend
```

Abre [http://localhost:4200](http://localhost:4200) en el navegador para ver la aplicación.

---

## Comandos del Makefile

| Comando                 | Descripción                                         |
|-------------------------|-----------------------------------------------------|
| `make start`            | Levanta todos los contenedores                      |
| `make stop`             | Para todos los contenedores                         |
| `make restart`          | Reinicia todos los contenedores                     |
| `make install-frontend` | Instala las dependencias (`npm install`)            |
| `make serve-frontend`   | Arranca el servidor de desarrollo con live reload   |
| `make build-frontend`   | Genera el build de producción                       |
| `make test-frontend`    | Ejecuta los tests unitarios (headless)              |

---

## Trabajar dentro del contenedor

Para ejecutar comandos de Ionic o Angular directamente, primero entra al contenedor:

```bash
docker compose exec frontend sh
```

Una vez dentro, todos los comandos que se muestran a continuación están disponibles.

---

## Comandos de Ionic CLI

### Servir la aplicación

```bash
npx ionic serve
```

Arranca un servidor local de desarrollo con recarga automática.

### Generar elementos nuevos

Ionic usa `ionic generate` (o `ionic g` como atajo) para crear componentes, páginas, servicios, etc.

```bash
# Generar una nueva página
npx ionic g page pages/mi-pagina

# Generar un componente
npx ionic g component components/mi-componente

# Generar un servicio
npx ionic g service services/mi-servicio

# Generar un pipe
npx ionic g pipe pipes/mi-pipe

# Generar un guard
npx ionic g guard guards/mi-guard
```

> **Nota:** `ionic generate` es un wrapper de `ng generate` que añade configuración específica de Ionic.

### Build de producción

```bash
npx ionic build --prod
```

Genera los archivos optimizados para producción en la carpeta `www/`.

---

## Comandos de Angular CLI

Puedes usar `ng` directamente para tareas más específicas de Angular.

```bash
# Generar un componente standalone
npx ng generate component components/mi-componente --standalone

# Generar un servicio
npx ng generate service services/mi-servicio

# Ejecutar tests en modo watch (se re-ejecutan al guardar)
npx ng test

# Ejecutar tests una sola vez (útil para CI)
npx ng test --watch=false --browsers=ChromeHeadlessCI

# Ejecutar el linter
npx ng lint
```

---

## Estructura del proyecto

```
src/app/
├── components/        # Componentes reutilizables (botones, cards, modals...)
├── pages/             # Páginas de la aplicación
├── pipes/             # Pipes personalizados
├── providers/         # Interceptores HTTP y providers
└── services/          # Servicios (llamadas a la API, lógica compartida)
```

---

## Configuración del entorno

Los archivos de entorno están en `src/environments/`:

| Archivo               | Uso                                               |
|-----------------------|---------------------------------------------------|
| `environment.ts`      | Desarrollo (usado por defecto con `ng serve`)     |
| `environment.prod.ts` | Producción (usado con `ng build --configuration production`) |

Para cambiar la URL de la API, edita la propiedad `apiUrl` en el archivo correspondiente.

---

## Recursos útiles

- [Documentación de Ionic](https://ionicframework.com/docs)
- [Documentación de Angular](https://angular.dev)
- [Documentación de Capacitor](https://capacitorjs.com/docs)
