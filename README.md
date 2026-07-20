## Decisões de implementação

- **Autenticação JWT** (`php-open-source-saver/jwt-auth`) com guard `api`, em vez de Sanctum — tokens stateless, com `login`, `refresh` e `logout` (blacklist).
- **Autorização por papel** (`users.role`: `user` | `admin`) via `TravelOrderPolicy` e `scopeVisibleTo` na model. Admin altera status e enxerga todos os pedidos; usuário comum só cria e enxerga os próprios pedidos.
- **Camada de serviço separada por responsabilidade**: `TravelOrderWriteService` (criação e transições de status, dentro de transação) e `TravelOrderReadService` (listagem, filtros e detalhamento). Leitura e escrita mudam por motivos diferentes, então vivem em classes diferentes.
- **`status` como enum PHP** (`App\Enums\TravelOrderStatus`) persistido como `string`, em vez de `ENUM` nativo do MySQL — evita `ALTER TABLE` custoso ao evoluir os estados.
- **Histórico**: cada transição (e a própria criação) grava uma linha em `travel_order_status_histories` registrando quem fez, qual status e por quê. `travel_orders` usa *soft deletes*.
- **Regras de negócio no service**: transições válidas são `requested → approved` e `requested → cancelled`; cancelar um pedido já aprovado (ou alterar um já cancelado) retorna `409`.
- **Notificação por e-mail assíncrona**: a mudança de status dispara um *event* (`ShouldDispatchAfterCommit`) → *listener* (`ShouldQueue`) → `Notification`, processada por um worker de fila dedicado. O e-mail só sai depois do commit da transação.
- **Validação em Form Requests**, serialização em **API Resources** (envelope `{ "data": ... }`), respostas de erro padronizadas.
- **Documentação da API** gerada automaticamente com Scribe, a partir de anotações nos controllers.

---

## Requisitos

- [Docker](https://www.docker.com/) e Docker Compose (v2)

Não é necessário ter PHP, Composer ou MySQL instalados localmente — tudo roda dentro dos containers.

---

## Instalação e execução (local, via Docker)

Clone o repositório e rode o script de setup a partir da raiz do projeto:

**Linux / macOS / Git Bash**
```bash
./setup.sh
```

**Windows (PowerShell)**
```powershell
.\setup.ps1
```

Ao final, os serviços ficam disponíveis em:

| Serviço | URL |
|---|---|
| API | http://localhost:8000/api |
| Documentação (Scribe) | http://localhost:8000/docs |
| Mailpit (caixa de e-mails) | http://localhost:8025 |
| MySQL | `localhost:3306` |

### Serviços do Docker Compose

- **app** — servidor HTTP (`php artisan serve`, porta 8000)
- **queue** — worker da fila (`php artisan queue:work`), consome os jobs de e-mail
- **db** — MySQL 9
- **mailpit** — captura os e-mails enviados em ambiente de desenvolvimento

### Usuários criados pelo seeder

Todos com a senha **`password`**:

| E-mail | Papel |
|---|---|
| `admin@example.com` | admin |
| `user1@example.com` | user |
| `user2@example.com` | user |

### Exemplo rápido de uso

```bash
# 1. Login → obtém o token JWT
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"email":"user1@example.com","password":"password"}'

# 2. Usar o token nas rotas protegidas
curl http://localhost:8000/api/travel-orders \
  -H "Authorization: Bearer <TOKEN>" -H "Accept: application/json"
```

---

## Executar os testes

```bash
docker compose exec app php artisan test
```

Rodar um arquivo/suíte específico:

```bash
docker compose exec app php artisan test tests/Feature/TravelOrders/ListTravelOrdersTest.php
```

### Desenvolvimento guiado por testes (TDD)

O projeto foi desenvolvido seguindo **TDD**: para cada endpoint, os testes de *feature* foram escritos primeiro (fase vermelha), descrevendo o contrato esperado, e só então a implementação foi feita até passarem (fase verde). Além dos testes de feature (autenticação, CRUD, filtros, regras de transição de status, autorização), há testes de unidade para partes com lógica isolável — por exemplo, o conteúdo do e-mail de notificação. Os testes usam SQLite em memória (`RefreshDatabase`), então rodam sem depender do MySQL.

---

## Documentação da API (Scribe)

A documentação interativa é gerada por [`knuckleswtf/scribe`](https://scribe.knuckles.wtf/) a partir de anotações nos controllers, e fica em **http://localhost:8000/docs**.

Para regenerá-la após alterar endpoints:

```bash
docker compose exec app php artisan scribe:generate
```

---

## Pacotes adicionais

### `php-open-source-saver/jwt-auth`
Pacote para autenticação JWT, fork mantido do `tymon/jwt-auth`, compatível com as versões atuais do Laravel.

### `knuckleswtf/scribe` (dev)
Gera a documentação da API automaticamente a partir dos Form Requests, Resources e anotações manuais — mantendo a doc próxima do código e reduzindo o risco de ela divergir da implementação. Publica uma página navegável em `/docs` além de uma coleção OpenAPI/Postman.

---

## Mailpit

Em desenvolvimento, os e-mails **não são enviados de verdade**: o `.env` aponta o mailer para o **Mailpit** (`MAIL_HOST=mailpit`, `MAIL_PORT=1025`), um servidor SMTP de testes que captura toda mensagem enviada. Isso permite inspecionar visualmente os e-mails de notificação de mudança de status — com HTML renderizado — na interface web em **http://localhost:8025**, sem precisar vasculhar logs nem configurar um provedor real.

---

## Notas adicionais

- **Padronização de código**: o projeto usa [Laravel Pint](https://laravel.com/docs/pint). Para checar/corrigir o estilo: `docker compose exec app ./vendor/bin/pint`.
- **Fila**: `QUEUE_CONNECTION=database`. O container `queue` processa os jobs automaticamente; não é preciso rodar `queue:work` manualmente.
- **Recriar o banco do zero** (dev): `docker compose exec app php artisan migrate:fresh --seed`.
