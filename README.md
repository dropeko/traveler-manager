# Traveler Manager — API de Pedidos de Viagem

Microsserviço em **Laravel** para gerenciar pedidos de viagem corporativa, com **API REST**, **autenticação JWT**, **MySQL via Docker** e **testes automatizados**.

---

## Decisões e caminhos adotados

- **Arquitetura**: API versionada em `/api/v1/*` com Controllers, Form Requests (validação), Resources (formatação), Eloquent Models e Notifications.
- **Autenticação**: JWT (guard `api`) para proteger endpoints.
- **Relacionamento**: `User (1) -> (N) TravelOrder`. Usuário comum vê apenas suas ordens; admin vê todas.
- **ID público da ordem**: o identificador usado nos endpoints é o `order_code` (ex.: `TO-2026-00000001`), gerado automaticamente após criação.
- **Regras de status**:
  - Apenas **admin** pode aprovar/cancelar via endpoint de status.
  - Cancelamento é bloqueado caso a ordem já esteja **aprovada**.
  - Transição **approved -> cancelled** é proibida (409).
- **Notificações**: ao aprovar/cancelar, grava notificação no banco (`notifications`) para o usuário solicitante.

---

## Requisitos

- Docker + Docker Compose
- Porta **8080** API e **8081** phpMyAdmin

---

## Como rodar localmente (Docker)

### 1) Clonar o repositório
```bash
git clone <URL_DO_REPOSITORIO>
cd traveler-manager
```

### 2) Subir os containers
```bash
docker compose up -d --build
```

### 3) Instalar dependências (se necessário)
> O Dockerfile já executa `composer install`, mas caso você altere dependências:
```bash
docker compose exec app composer install
```

### 4) Configurar ambiente
O projeto já inclui `.env.example`:

- `DB_CONNECTION=mysql`
- `DB_HOST=db`
- `DB_DATABASE=traveler_manager`
- `DB_USERNAME=traveler`
- `DB_PASSWORD=traveler`

Se você criar um `.env` novo:
```bash
copy .env.example .env
```

### 4.1) Gerar APP_KEY e JWT_SECRET (obrigatório em ambientes novos)
Ao baixar o repositório em uma máquina nova, gere as chaves dentro do container:

```bash
docker compose exec app php artisan key:generate
docker compose exec app php artisan jwt:secret --force
docker compose exec app php artisan optimize:clear
```

> O comando `jwt:secret` preenche a variável `JWT_SECRET` no `.env`. Sem isso, o login/validação de token JWT não funciona.

### 5) Rodar migrations + seed
```bash
docker compose exec app php artisan migrate:fresh --seed
```

**Seed cria:**
- 1 admin (`admin@example.com`)
- 3 usuários comuns (`test1@example.com`, `test2@example.com`, `test3@example.com`)
- 2 travel orders por usuário comum (uma delas com status `approved` ou `cancelled`)

---

## Endpoints principais

Base URL: `http://localhost:8080/api/v1`

### Health
- `GET /health`

### Auth
- `POST /register`
- `POST /login`
- `GET /me` (JWT)

### Travel Orders (JWT)
- `POST /travel-orders`
  - Body: `destination`, `departure_date`, `return_date`
  - `requester_name` é preenchido automaticamente a partir do usuário logado
- `GET /travel-orders`
  - Lista conforme regra: admin vê todas, user vê apenas as próprias
  - Filtros (query params):
    - `status`
    - `destination`
    - `created_from`, `created_to`
    - `travel_from`, `travel_to`
- `GET /travel-orders/{order_code}`
  - Retorna detalhes: `requester_name`, `destination`, `departure_date`, `return_date`, `status`
- `PATCH /travel-orders/{order_code}/status` (**admin-only**)
  - Body: `{ "status": "approved" | "cancelled" }`
  - Bloqueia `approved -> cancelled`
- `PATCH /travel-orders/{order_code}/cancel`
  - Bloqueia cancelamento se status já for `approved`

---

## Exemplo rápido (cURL)

### Login admin
```bash
curl -s -X POST "http://localhost:8080/api/v1/login" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  --data-raw "{\"email\":\"admin@example.com\",\"password\":\"123\"}"
```

Copie o `access_token` e use nas próximas chamadas:
```bash
TOKEN="COLE_AQUI"
```

### Listar travel orders
```bash
curl -s "http://localhost:8080/api/v1/travel-orders" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

---

## Executar testes

```bash
docker compose exec app php artisan test
```

---

## phpMyAdmin

- URL: `http://localhost:8081`
- Host: `db`
- User: `traveler`
- Password: `traveler`

---

## Observações
- O ID público usado pela API é o `order_code` (não o `id` numérico).
- Notificações de mudança de status são persistidas em `notifications` (canal `database`).