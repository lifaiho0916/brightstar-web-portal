# Makefile to simplify commands

PROJECT_ID ?= brightstar-driver-app-381315
REGION ?= asia-southeast1
SERVICE_IMAGE ?= brightstar-driver-web
SERVICE_TAG ?= latest
SERVICE_PORT ?= 80

# CI_DB_HOSTNAME=/cloudsql/brightstar-driver-app-381315:asia-southeast1:driver-app-db
CI_DB_HOSTNAME=35.240.149.132
CI_DB_USERNAME=brightst_driverappuser
CI_DB_PASSWORD=XM@tB%Bo&gp*Mvn_U
CI_DB_DATABASE=brightst_driverappdb
CI_DB_PREFIX=bs_
CI_ENVIRONMENT=development
CI_BASE_URL=http://localhost
# CI_BASE_URL=https://driver-app.brightstar.com.my


default: build

.PHONY: dist

build:
	# Build docker ...
	# docker build -t $(SERVICE_IMAGE):$(SERVICE_TAG) .
	gcloud config set project $(PROJECT_ID)
	gcloud builds submit --tag gcr.io/$(PROJECT_ID)/$(SERVICE_IMAGE) --ignore-file .dockerignore

build-local:
	# Build docker ...
	docker build -t $(SERVICE_IMAGE):$(SERVICE_TAG) .

run-docker:
	# Run docker (remote)...
	# Run docker... CMD: apache2-foreground
	docker run --privileged -it -p $(SERVICE_PORT):$(SERVICE_PORT) \
	  -v $(PWD):/var/www/html/ \
	  -e CI_DB_HOSTNAME="$(CI_DB_HOSTNAME)" \
	  -e CI_DB_USERNAME="$(CI_DB_USERNAME)" \
	  -e CI_DB_PASSWORD="$(CI_DB_PASSWORD)" \
	  -e CI_DB_DATABASE="$(CI_DB_DATABASE)" \
	  -e CI_DB_PREFIX="$(CI_DB_PREFIX)" \
	  -e CI_BASE_URL="$(CI_BASE_URL)" \
	  -e CI_ENVIRONMENT="$(CI_ENVIRONMENT)" \
	  $(SERVICE_IMAGE):$(SERVICE_TAG) /bin/bash

run-it:
	# Run docker... CMD: apache2-foreground
	docker run --privileged -it --name $(SERVICE_IMAGE) -p $(SERVICE_PORT):$(SERVICE_PORT) \
	  $(SERVICE_IMAGE):$(SERVICE_TAG) /bin/bash

docker-cleanup:
	docker volume rm $$(docker volume ls -qf dangling=true) && docker volume prune && docker system prune -a

sync-uploads:
	gsutil -m rsync -r ./writable gs://brightstar-driver-static/writable

