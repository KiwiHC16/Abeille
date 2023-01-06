.PHONY: vendor coverage sonarrun sonarlog help

MAKEPATH := $(abspath $(lastword $(MAKEFILE_LIST)))
PWD := $(dir $(MAKEPATH))
CONTAINERS := $(shell docker ps -a -q -f "name=slim4api*")
SONARQUBE_URL := "sonarqube:9000"
SONAR_NET := "abeille_sonarnet"
SONAR_PROP := "sonar-project-local.properties"

help:
		@$(MAKE) -pRrq -f $(lastword $(MAKEFILE_LIST)) : 2>/dev/null | awk -v RS= -F: '/^# Fichiers/,/^# Base/ {if ($$1 !~ "^[#.]") {print $$1}}' | sort | egrep -v -e '^[^[:alnum:]]' -e '^$@$$'

coverage:
		docker compose -f docker-compose-nginx.yml exec php-fpm sh -c "./vendor/bin/phpunit --coverage-text --coverage-html coverage"

sonarlog:
		docker compose -f docker-compose-sonarsvr.yaml logs -f

sonarrun:
		docker compose -f docker-compose-sonarsvr.yaml up -d
		docker run --rm \
			-e SONAR_HOST_URL=http://${SONARQUBE_URL} \
            -v "${PWD}:/usr/src" \
            --network="${SONAR_NET}" \
            sonarsource/sonar-scanner-cli \
            -Dproject.settings=${SONAR_PROP}