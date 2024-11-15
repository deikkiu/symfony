# Docker

dc_build:
	docker compose -f docker-compose.yml build

dc_start:
	docker compose -f docker-compose.yml start

dc_stop:
	docker compose -f docker-compose.yml stop

dc_up:
	docker compose -f docker-compose.yml up -d

dc_ps:
	docker compose -f docker-compose.yml ps -a

dc_restart:
	docker compose -f docker-compose.yml stop
	docker compose -f docker-compose.yml up -d

dc_logs:
	docker compose -f docker-compose.yml logs --tail=100