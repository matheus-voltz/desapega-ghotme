# Desapego Canadá — Starter Laravel

Catálogo simples para vender itens antes da mudança para o Canadá, com Pix via QR Code do Asaas, Shopee e sincronização automática de disponibilidade.

## O que já vem pronto

- Catálogo público de produtos
- Status: disponível, reservado e vendido
- Preço Pix e preço Shopee
- Botão Pix que abre uma tela com o QR Code do Asaas
- Botão Shopee direto para o anúncio
- Cálculo de economia no Pix
- Combos de produtos
- Painel administrativo usando `auth`
- Upload de imagem para `storage/app/public/products`
- Integração com Shopee Open Platform
- OAuth/autorização da loja Shopee pelo painel
- Webhook de mudança de status de pedidos
- Renovação automática de `access_token`
- Sincronização de pedidos por polling como fallback do webhook
- Proteção para cancelamento da Shopee não reabrir item que já foi vendido manualmente/Pix
- Notificação no Telegram somente quando uma venda é confirmada, com produto, valor e canal

## Requisitos

- Laravel 11 ou 12
- PHP 8.2+
- Banco configurado no `.env`
- Domínio HTTPS público para receber o webhook da Shopee
- Aplicação criada na Shopee Open Platform, com `partner_id` e `partner_key`
- Bot do Telegram (opcional) para receber alertas de venda confirmada

## Instalação

1. Copie os arquivos deste pacote para um projeto Laravel existente.

> Se o seu projeto já possui `routes/web.php` ou `routes/console.php`, faça **merge** das rotas em vez de sobrescrever esses arquivos.

2. Configure no `.env`:

```env
DESAPEGO_SITE_NAME="Desapego do Matheus"
DESAPEGO_PIX_LABEL="Pagamento via Pix"
DESAPEGO_PIX_QR_CODE_PATH="images/pix-asaas.png"
DESAPEGO_PIX_QR_CODE_URL=
DESAPEGO_PIX_COPY_PASTE=

SHOPEE_ENABLED=true
SHOPEE_ENV=production
SHOPEE_PARTNER_ID=
SHOPEE_PARTNER_KEY=
SHOPEE_REDIRECT_URL=https://seu-dominio.com/admin/shopee/callback
SHOPEE_WEBHOOK_URL=https://seu-dominio.com/webhooks/shopee
SHOPEE_VERIFY_WEBHOOK_SIGNATURE=true
SHOPEE_TIMEOUT=15

TELEGRAM_ENABLED=true
TELEGRAM_BOT_TOKEN=
TELEGRAM_CHAT_ID=
TELEGRAM_TIMEOUT=5
```

3. Rode:

```bash
php artisan migrate
php artisan storage:link
```

4. O painel usa o middleware `auth`. Se o projeto ainda não tiver login, instale Breeze, Fortify ou outro sistema de autenticação.

5. Acesse:

- Catálogo: `/`
- Admin produtos: `/admin/products`
- Admin combos: `/admin/bundles`
- Integração Shopee: `/admin/shopee`

---

# Telegram — aviso de venda confirmada

O Telegram não envia a mensagem para um **número de telefone** diretamente. A Bot API usa um `chat_id`.

## 1. Crie o bot

No Telegram, abra o **@BotFather**, execute `/newbot` e copie o token fornecido. Coloque no `.env`:

```env
TELEGRAM_ENABLED=true
TELEGRAM_BOT_TOKEN=123456789:SEU_TOKEN_AQUI
```

Não publique esse token no Git.

## 2. Descubra seu Chat ID

Abra o bot que você acabou de criar e envie:

```text
/start
```

Depois rode no Laravel:

```bash
php artisan telegram:chat-id
```

Copie o ID retornado para:

```env
TELEGRAM_CHAT_ID=123456789
```

Se a configuração estiver em cache:

```bash
php artisan config:clear
```

Teste:

```bash
php artisan telegram:test
```

## 3. Quando a notificação é enviada

O Telegram **não é disparado quando alguém apenas clica em comprar**.

A mensagem é enviada somente quando o produto muda para **Vendido**:

- pela Shopee, quando o pedido passa para um status de venda/pagamento confirmado;
- pelo painel, quando você altera manualmente o produto para **Vendido** após confirmar um Pix.

A mensagem informa produto, valor, canal e horário.

---

# Configurando a Shopee

## 1. Crie/configure o app na Shopee Open Platform

Você precisará do `partner_id` e `partner_key` do aplicativo.

Cadastre como callback/redirect do app exatamente o mesmo valor usado em:

```env
SHOPEE_REDIRECT_URL=https://seu-dominio.com/admin/shopee/callback
```

O webhook precisa ser uma URL HTTPS pública:

```env
SHOPEE_WEBHOOK_URL=https://seu-dominio.com/webhooks/shopee
```

A URL precisa ser estável. Ela faz parte da validação da assinatura do push.

## 2. Conecte sua loja

Entre no painel:

```text
/admin/shopee
```

Clique em **Conectar minha Shopee** e autorize a loja.

No retorno, o sistema:

1. troca o `code` por `access_token` e `refresh_token`;
2. salva os tokens criptografados no banco usando o cast `encrypted` do Laravel;
3. configura o push de mudança de status de pedidos.

O `access_token` é renovado automaticamente quando estiver perto de expirar.

## 3. Relacione os produtos

Em `/admin/products`, edite cada produto e informe:

- `Shopee Item ID` — obrigatório para sincronização;
- `Shopee Model ID` — apenas se o anúncio possuir variações.

O link da Shopee sozinho continua servindo para o botão público, mas a sincronização usa o `item_id` retornado pela API.

---

# Regras de status

Quando a Shopee enviar uma alteração de pedido, o site aplica:

| Status Shopee | Status no site |
|---|---|
| `UNPAID` / `PENDING` | Reservado |
| `READY_TO_SHIP` | Vendido |
| `PROCESSED` | Vendido |
| `SHIPPED` | Vendido |
| `TO_CONFIRM_RECEIVE` | Vendido |
| `COMPLETED` | Vendido |
| `INVOICE_PENDING` / `RETRY_SHIP` | Vendido |
| `CANCELLED` | Disponível novamente* |

\* O cancelamento só reabre o item se **aquele mesmo pedido da Shopee** foi quem o reservou/vendeu. Se você marcou a venda manualmente/Pix, um evento antigo da Shopee não sobrescreve essa decisão.

Se um pedido da Shopee chegar como pago depois de o mesmo item já ter sido marcado como vendido manualmente/Pix, o sistema **não troca a origem da venda**: registra o pedido, grava um log crítico e mostra um aviso de conflito no painel para você resolver a possível venda duplicada.

O webhook recebe apenas a mudança do pedido e consulta `get_order_detail` para descobrir quais `item_id` estavam no pedido.

---

# Webhook + fallback

O endpoint é:

```text
POST /webhooks/shopee
```

A assinatura é validada antes do processamento. O corpo bruto (`raw body`) é preservado para a validação.

O processamento do pedido é disparado **depois da resposta HTTP**, para responder rapidamente à Shopee. Não é necessário manter um queue worker só para esse webhook; a sincronização roda no encerramento da própria requisição e o scheduler funciona como redundância.

Além do webhook, foi incluído um fallback:

```bash
php artisan shopee:sync-orders --minutes=15
```

Ele consulta pedidos alterados recentemente e corrige o catálogo caso algum push não tenha chegado.

`routes/console.php` já contém:

```php
Schedule::command('shopee:sync-orders --minutes=15')
    ->everyFiveMinutes()
    ->withoutOverlapping();
```

No servidor, mantenha o scheduler do Laravel funcionando, por exemplo com cron:

```cron
* * * * * cd /caminho/do/projeto && php artisan schedule:run >> /dev/null 2>&1
```

## Comandos úteis

```bash
# Sincronizar manualmente os pedidos recentes
php artisan shopee:sync-orders --minutes=60

# Reconfigurar o push/webhook da Shopee
php artisan shopee:configure-push

# Encontrar seu Chat ID no Telegram
php artisan telegram:chat-id

# Testar a notificação do Telegram
php artisan telegram:test
```

Também existem botões equivalentes na página `/admin/shopee`.

---

# Segurança

- Não coloque `SHOPEE_PARTNER_KEY` no Git.
- Use HTTPS em produção.
- Os tokens da loja são armazenados criptografados pelo Laravel.
- Mantenha `APP_KEY` segura e estável; trocar a `APP_KEY` impede descriptografar tokens já salvos.
- Deixe `SHOPEE_VERIFY_WEBHOOK_SIGNATURE=true` em produção.
- Se estiver atrás de Cloudflare/proxy, mantenha `SHOPEE_WEBHOOK_URL` exatamente igual à URL pública configurada na Shopee.

---

# Pix / Asaas

O botão **Comprar no Pix** abre uma página do próprio site com o QR Code do Asaas. Ele não abre WhatsApp e não envia Telegram apenas pelo clique.

Para usar uma imagem local, salve seu QR Code em:

```text
public/images/pix-asaas.png
```

e mantenha:

```env
DESAPEGO_PIX_QR_CODE_PATH="images/pix-asaas.png"
```

Se preferir uma URL pública para a imagem do QR Code, configure:

```env
DESAPEGO_PIX_QR_CODE_URL=https://exemplo.com/seu-qr-code.png
```

`DESAPEGO_PIX_QR_CODE_URL` tem prioridade sobre o arquivo local. Você também pode exibir opcionalmente um Pix copia e cola:

```env
DESAPEGO_PIX_COPY_PASTE="SEU_CODIGO_PIX"
```

## Importante sobre confirmação automática

Um **QR Code Pix estático** não informa ao site qual produto foi pago. Portanto, só abrir ou pagar esse QR não permite ao Laravel identificar automaticamente a venda de um produto específico.

Nesta versão, para vendas Pix, depois de confirmar o recebimento no Asaas, marque o produto como **Vendido** no painel. Nesse momento o Telegram envia a notificação com produto e valor.

Para automatizar também o Pix, o próximo passo seria criar uma cobrança identificada por produto no Asaas e receber o webhook de pagamento confirmado. Aí o sistema poderia marcar o produto como vendido automaticamente.

> A sincronização automática atual é **Shopee → site**. Vendas Pix continuam com confirmação manual no painel para evitar associação errada entre pagamento e produto.

---

# Imagem externa por URL

Cada produto agora pode ter uma imagem principal de duas formas:

1. **Foto enviada** pelo painel (`cover_image_path`);
2. **URL externa da imagem** (`cover_image_url`), por exemplo um `src` de CDN, Asaas, Cloudinary, Imgur etc.

No painel de produtos existe o campo **Imagem por URL (src)**.

A prioridade é:

```text
foto enviada > URL externa > placeholder "Sem foto"
```

Então, se você deixar o upload de foto vazio e informar apenas:

```text
https://cdn.exemplo.com/meu-livro.jpg
```

essa URL será usada automaticamente como a primeira/imagem principal do produto no catálogo e na página do item.

Depois de atualizar o projeto, rode:

```bash
php artisan migrate
```

A migration `2026_10_06_000005_add_cover_image_url_to_products_table.php` adiciona o campo `cover_image_url` à tabela `products`.
