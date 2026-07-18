# API de Pedidos de Viagem — Contrato

Este documento é a especificação (entradas/saídas) usada como base para os testes (TDD). Nenhum controller/rota foi implementado ainda.

## Convenções gerais

- Base path: `/api`
- Autenticação: `Authorization: Bearer <token>` (JWT via `php-open-source-saver/jwt-auth`), via middleware `auth:api` (guard `api` configurado com driver `jwt`).
- Autorização por papel: `users.role` (`user` | `admin`), verificada em `App\Policies\TravelOrderPolicy`.
- Content-Type: `application/json` em todas as requisições e respostas.
- Envelope de recurso único: `{ "data": { ... } }`.
- Envelope de coleção paginada (padrão `apiResource`/`AnonymousResourceCollection` do Laravel): `{ "data": [...], "links": {...}, "meta": {...} }`.
- Erros de validação (422): formato padrão do Laravel `{ "message": "...", "errors": { "campo": ["..."] } }`.
- Erro de autorização (403): `{ "message": "This action is unauthorized." }`.
- Erro de não encontrado (404): `{ "message": "No query results for model [App\\Models\\TravelOrder] {id}" }` (padrão do `ModelNotFoundException`).
- Erro de regra de negócio (409): `{ "message": "<motivo>" }`.

## Rotas

| Método | URI | Ação | Quem pode acessar |
|---|---|---|---|
| POST | `/api/travel-orders` | Criar pedido | Qualquer usuário autenticado |
| GET | `/api/travel-orders` | Listar pedidos (com filtros) | Autenticado — `user` vê só os próprios; `admin` vê todos |
| GET | `/api/travel-orders/{travelOrder}` | Detalhar pedido | Dono do pedido ou `admin` |
| PATCH | `/api/travel-orders/{travelOrder}/status` | Aprovar/cancelar pedido | Somente `admin` (e nunca o próprio solicitante) |

`routes/api.php` (esboço, ainda não criado):
```php
Route::middleware('auth:api')->group(function () {
    Route::apiResource('travel-orders', TravelOrderController::class)
        ->only(['store', 'show', 'index']);

    Route::patch('travel-orders/{travelOrder}/status', [TravelOrderStatusController::class, 'update'])
        ->name('travel-orders.status.update');
});
```

---

### `POST /api/travel-orders` — Criar pedido de viagem

**Request body**
```json
{
  "destination_country": "Brasil",
  "destination_state": "SP",
  "destination_city": "São Paulo",
  "departure_date": "2026-08-10",
  "return_date": "2026-08-15"
}
```
O solicitante (`user_id`) é sempre o usuário autenticado — não é um campo de entrada. `destination_state` é opcional (nem todo país tem o conceito de estado/província).

**Validação (`StoreTravelOrderRequest`)**
| Campo | Regras |
|---|---|
| `destination_country` | `required`, `string`, `max:255` |
| `destination_state` | `nullable`, `string`, `max:255` |
| `destination_city` | `required`, `string`, `max:255` |
| `departure_date` | `required`, `date`, `after_or_equal:today` |
| `return_date` | `required`, `date`, `after:departure_date` |

**Resposta de sucesso — `201 Created`**
```json
{
  "data": {
    "id": 1,
    "requester": { "id": 5, "name": "Rafael Magno" },
    "destination_country": "Brasil",
    "destination_state": "SP",
    "destination_city": "São Paulo",
    "departure_date": "2026-08-10",
    "return_date": "2026-08-15",
    "status": "requested",
    "created_at": "2026-07-17T21:40:00Z",
    "updated_at": "2026-07-17T21:40:00Z"
  }
}
```

**Erros**
- `401` — sem token / token inválido.
- `422` — payload inválido (ex.: `return_date` antes de `departure_date`).

---

### `GET /api/travel-orders` — Listar pedidos

**Query params (filtros, todos opcionais)**
| Param | Tipo | Descrição |
|---|---|---|
| `status` | `requested\|approved\|cancelled` | Filtra por status |
| `destination_country` | string | Busca parcial (`LIKE %valor%`) |
| `destination_state` | string | Busca parcial (`LIKE %valor%`) |
| `destination_city` | string | Busca parcial (`LIKE %valor%`) |
| `departure_from` / `departure_to` | `date` | Faixa de data de ida |
| `return_from` / `return_to` | `date` | Faixa de data de volta |
| `per_page` | int (1–100, padrão 15) | Tamanho de página |
| `page` | int | Página atual |

Usuário `user` sempre recebe apenas seus próprios pedidos (filtro `user_id` fixado no backend, não é um input). Usuário `admin` recebe todos, podendo opcionalmente filtrar por `user_id` também.

**Resposta de sucesso — `200 OK`**
```json
{
  "data": [
    {
      "id": 1,
      "requester": { "id": 5, "name": "Rafael Magno" },
      "destination_country": "Brasil",
      "destination_state": "SP",
      "destination_city": "São Paulo",
      "departure_date": "2026-08-10",
      "return_date": "2026-08-15",
      "status": "requested",
      "created_at": "2026-07-17T21:40:00Z",
      "updated_at": "2026-07-17T21:40:00Z"
    }
  ],
  "links": {
    "first": "/api/travel-orders?page=1",
    "last": "/api/travel-orders?page=3",
    "prev": null,
    "next": "/api/travel-orders?page=2"
  },
  "meta": {
    "current_page": 1,
    "last_page": 3,
    "per_page": 15,
    "total": 42
  }
}
```

**Erros**
- `401` — sem autenticação.
- `422` — filtro inválido (ex.: `status` fora do enum, `departure_from` maior que `departure_to`).

---

### `GET /api/travel-orders/{travelOrder}` — Detalhar pedido

**Resposta de sucesso — `200 OK`**
```json
{
  "data": {
    "id": 1,
    "requester": { "id": 5, "name": "Rafael Magno" },
    "destination_country": "Brasil",
    "destination_state": "SP",
    "destination_city": "São Paulo",
    "departure_date": "2026-08-10",
    "return_date": "2026-08-15",
    "status": "approved",
    "created_at": "2026-07-17T21:40:00Z",
    "updated_at": "2026-07-17T22:00:00Z",
    "status_histories": [
      {
        "status": "requested",
        "user_id": { "id": 5, "name": "Rafael Magno" },
        "reason": null,
        "created_at": "2026-07-17T22:00:00Z"
      },
      {
        "status": "approved",
        "user_id": { "id": 1, "name": "Admin" },
        "reason": null,
        "created_at": "2026-07-17T23:00:00Z"
      }
    ]
  }
}
```

**Erros**
- `401` — sem autenticação.
- `403` — usuário `user` tentando ver pedido de outro solicitante.
- `404` — pedido inexistente.

---

### `PATCH /api/travel-orders/{travelOrder}/status` — Atualizar status

**Request body**
```json
{
  "status": "approved"
}
```
ou
```json
{
  "status": "cancelled",
  "reason": "Viagem não é mais necessária"
}
```

**Validação (`UpdateTravelOrderStatusRequest`)**
| Campo | Regras |
|---|---|
| `status` | `required`, `in:approved,cancelled` |
| `reason` | `required_if:status,cancelled`, `string`, `max:255` |

**Regras de negócio (`TravelOrderPolicy` + service)**
1. Somente `admin` pode chamar este endpoint — inclusive o próprio solicitante do pedido, mesmo sendo admin de outra conta, não pode alterar o status do seu próprio pedido. → `403` caso viole.
2. Transições permitidas: `requested → approved`, `requested → cancelled`.
3. `approved → cancelled` **não é permitido** (regra "só cancela se ainda não foi aprovado") → `409`.
4. `cancelled` é estado terminal (qualquer transição a partir dele) → `409`.
5. Toda transição bem-sucedida grava uma linha em `travel_order_status_histories` (`changed_by` = admin autenticado).

**Resposta de sucesso — `200 OK`**
```json
{
  "data": {
    "id": 1,
    "requester": { "id": 5, "name": "Rafael Magno" },
    "destination_country": "Brasil",
    "destination_state": "SP",
    "destination_city": "São Paulo",
    "departure_date": "2026-08-10",
    "return_date": "2026-08-15",
    "status": "approved",
    "created_at": "2026-07-17T21:40:00Z",
    "updated_at": "2026-07-17T22:00:00Z"
  }
}
```

**Erros**
- `401` — sem autenticação.
- `403` — solicitante tentando alterar o próprio pedido, ou usuário `user` tentando alterar qualquer pedido.
  ```json
  { "message": "This action is unauthorized." }
  ```
- `404` — pedido inexistente.
- `409` — transição de status inválida.
  ```json
  { "message": "Não é possível cancelar um pedido que já foi aprovado." }
  ```
- `422` — `status` fora do enum permitido (ex.: tentar setar `requested` diretamente).

## Casos de teste sugeridos (TDD)

- **Store**: cria com sucesso; falha sem campos obrigatórios (`destination_country`, `destination_city`, `departure_date`, `return_date`); `destination_state` é opcional; falha com `return_date` antes de `departure_date`; falha com `departure_date` no passado; `user_id` sempre é o autenticado, ignorando qualquer valor enviado no payload.
- **Show**: dono vê o próprio pedido; admin vê pedido de qualquer um; outro `user` recebe `403`; id inexistente recebe `404`.
- **Index**: filtra por `status`; filtra por `destination_country`/`destination_state`/`destination_city` (parcial); filtra por faixa de datas; `user` só vê os próprios; `admin` vê todos; paginação respeita `per_page`.
- **Status update**: admin aprova pedido `requested` com sucesso; admin cancela pedido `requested` com sucesso; admin tenta cancelar pedido `approved` → `409`; `user` (não-admin) tenta mudar status → `403`; solicitante tenta mudar status do próprio pedido → `403`; tentativa de transição para `requested` → `422`; pedido `cancelled` recebe nova tentativa de transição → `409`; cada transição bem-sucedida cria registro em `travel_order_status_histories`.
