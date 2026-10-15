# Desapego Canadá — Starter Laravel

Catálogo simples para vender itens antes da mudança para o Canadá, com Pix via QR Code do Asaas e cartão.

## O que já vem pronto

- Catálogo público de produtos
- Status: disponível, reservado e vendido
- Preço Pix e preço no cartão
- Botão Pix que abre uma tela com o QR Code do Asaas
- Cálculo de economia no Pix
- Combos de produtos
- Painel administrativo usando `auth`
- Upload de imagem para `storage/app/public/products`
- Notificação no Telegram somente quando uma venda é confirmada, com produto, valor e canal

## Requisitos

- Laravel 11 ou 12
- PHP 8.2+
- Banco configurado no `.env`
- Domínio HTTPS público para receber o webhook do Asaas
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

- pelo Asaas, quando o pagamento é confirmado;
- pelo painel, quando você altera manualmente o produto para **Vendido** após confirmar um Pix.

A mensagem informa produto, valor, canal e horário.

---

## Comandos úteis

```bash
# Encontrar seu Chat ID no Telegram
php artisan telegram:chat-id

# Testar a notificação do Telegram
php artisan telegram:test
```

---

# Segurança

- Não coloque chaves de API (Asaas, Telegram) no Git.
- Use HTTPS em produção.
- Os tokens da loja são armazenados criptografados pelo Laravel.
- Mantenha `APP_KEY` segura e estável; trocar a `APP_KEY` impede descriptografar tokens já salvos.

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
