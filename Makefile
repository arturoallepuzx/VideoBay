start:
	docker compose up -d

stop:
	docker compose down

restart: stop start

recreate:
	docker compose down
	docker compose up -d --build

status:
	docker compose ps

install:
	docker compose run --rm api composer install
	docker compose run --rm api php artisan migrate --seed

install-frontend:
	docker compose run --rm frontend npm install

db-migrate:
	docker compose exec api php artisan migrate

db-seed:
	docker compose exec api php artisan db:seed

test:
	docker compose exec api php artisan test

test-frontend:
	docker compose exec frontend npx ng test --watch=false --browsers=ChromeHeadlessCI

build-frontend:
	docker compose exec frontend npx ng build

# Servidor de desarrollo con live reload (watch) en primer plano. Abre http://localhost:4200 en el navegador.
# Si el frontend ya está en marcha (make start), lo detiene antes para liberar el puerto 4200.
# Para abrir el navegador automáticamente: cd frontend && npm start -- --open
serve-frontend:
	docker compose stop frontend 2>/dev/null || true
	docker compose run --rm -p 4200:4200 frontend sh -c "npm install && npx ng serve --host 0.0.0.0"

lint:
	docker compose exec api vendor/bin/pint
	docker compose exec api vendor/bin/pint --config=pint_strict.json app tests

logs-backend:
	docker compose exec -it api sh -c "touch storage/logs/laravel.log && less +F storage/logs/laravel.log"
