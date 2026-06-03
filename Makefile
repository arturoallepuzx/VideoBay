COMPOSE = docker compose --env-file backend/.env
PROD_COMPOSE = docker compose --env-file backend/.env -f docker-compose.yml -f docker-compose.prod.yml

start:
	$(COMPOSE) up -d

stop:
	$(COMPOSE) down

restart: stop start

recreate:
	$(COMPOSE) down
	$(COMPOSE) up -d --build

status:
	$(COMPOSE) ps

install:
	$(COMPOSE) run --rm api composer install
	$(COMPOSE) run --rm api php artisan key:generate
	$(COMPOSE) run --rm api php artisan migrate --seed

install-frontend:
	$(COMPOSE) run --rm frontend npm install

db-migrate:
	$(COMPOSE) exec api php artisan migrate

db-seed:
	$(COMPOSE) exec api php artisan db:seed

db-fresh:
	$(COMPOSE) exec api php artisan migrate:fresh --seed

db-reset:
	$(COMPOSE) down -v
	$(COMPOSE) up -d

test:
	$(COMPOSE) exec -T db sh -c 'mysql -uroot -p"$$MYSQL_ROOT_PASSWORD" -e "CREATE DATABASE IF NOT EXISTS videobay_test; GRANT ALL PRIVILEGES ON videobay_test.* TO \"videobay\"@\"%\";"'
	$(COMPOSE) exec -e APP_ENV=testing -e DB_DATABASE=videobay_test -e QUEUE_CONNECTION=sync -e CACHE_STORE=array -e SESSION_DRIVER=array -e MAIL_MAILER=array api php artisan test

test-frontend:
	$(COMPOSE) exec frontend npx ng test --watch=false --browsers=ChromeHeadlessCI

build-frontend:
	$(COMPOSE) exec frontend npx ng build

serve-frontend:
	$(COMPOSE) stop frontend 2>/dev/null || true
	$(COMPOSE) run --rm -p 4200:4200 frontend sh -c "npm install && npx ng serve --host 0.0.0.0"

lint:
	$(COMPOSE) exec api vendor/bin/pint
	$(COMPOSE) exec api vendor/bin/pint --config=pint_strict.json app tests

logs-backend:
	$(COMPOSE) exec -it api sh -c "touch storage/logs/laravel.log && less +F storage/logs/laravel.log"

logs-worker:
	$(COMPOSE) logs -f worker

logs-scheduler:
	$(COMPOSE) logs -f scheduler

# ---- Producción ----

prod-start:
	$(PROD_COMPOSE) up -d

prod-stop:
	$(PROD_COMPOSE) down

prod-build:
	$(PROD_COMPOSE) up -d --build

prod-migrate:
	$(PROD_COMPOSE) exec api php artisan migrate --force

prod-logs-caddy:
	$(PROD_COMPOSE) logs -f caddy