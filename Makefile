# Docker

dc_build:
	docker compose -f ./docker/compose.yaml build

dc_start:
	docker compose -f ./docker/compose.yaml start

dc_stop:
	docker compose -f ./docker/compose.yaml stop

dc_up:
	docker compose -f ./docker/compose.yaml up -d

dc_ps:
	docker compose -f ./docker/compose.yaml ps -a

dc_restart:
	docker compose -f ./docker/compose.yaml stop
	docker compose -f ./docker/compose.yaml up -d

dc_logs:
	docker compose -f ./docker/compose.yaml logs --tail=100