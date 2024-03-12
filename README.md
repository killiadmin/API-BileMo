# API_BileMo

BileMo is a company offering a whole selection of high-end mobile phones. You are in charge of the development of the
mobile phone showcase of the BileMo company. BileMo's business model is not to sell its products directly on the
website, but to provide all platforms that wish access to the catalog via an API (Application Programming Interface).
This is therefore exclusively B2B (business to business) sales. You will need to expose a certain number of APIs so that
applications from other web platforms can perform operations.

## Required

    * PHP >=8.1
    * Composer   
    * Symfony CLI
    * Docker
    * Docker-compose

## Create your directory for installing project, follow the steps

```bash
mkdir P7_API-BileMo_KF

cd P7_API-BileMo_KF

mkdir database
```

## Clone the project API-BileMo

```bash
git clone https://github.com/killiadmin/API-BileMo.git
```

## Launch the development environment

```bash
cd API-BileMo
```

```bash
composer update 
```

```bash
docker compose up -d
```

```bash
symfony serve -d
```

## Launch the migrations

```bash
symfony console d:m:m
```

## Add fakes datas

```bash
symfony console d:f:l
```

## Open the API documentation and view the available routes

```bash
path: ^/api/doc
```
