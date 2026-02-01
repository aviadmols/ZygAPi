# Shopify Tags Management System

A comprehensive Shopify order tagging management system with AI support, multi-store capabilities, webhooks, and queue processing.

## Features

- ✅ Multi-store Shopify management
- ✅ Token configuration for each store (Shopify + Recharge)
- ✅ Dynamic tagging rules creation with AI (OpenRouter)
- ✅ Support for complex tags with functions (get, split, switch)
- ✅ Queue processing for thousands of orders
- ✅ Full management interface
- ✅ Webhook handler with HMAC verification
- ✅ Real-time progress bar

## Requirements

- PHP 8.2+
- MySQL 5.7+ or MariaDB 10.3+
- Composer
- Node.js & NPM (for Breeze)
- Redis (recommended for queue) or Database queue

## Installation

1. Navigate to the project:
```bash
cd shopify-tags-system
```

2. Install dependencies:
```bash
composer install
npm install
```

3. Copy environment file:
```bash
cp .env.example .env
```

4. Generate application key:
```bash
php artisan key:generate
```

5. Configure `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=shopify_tags
DB_USERNAME=your_username
DB_PASSWORD=your_password

QUEUE_CONNECTION=database  # or redis

OPENROUTER_API_KEY=your_openrouter_api_key
SHOPIFY_WEBHOOK_SECRET=your_webhook_secret
```

6. Run migrations:
```bash
php artisan migrate
```

7. Create first user:
```bash
php artisan migrate
# or via interface: /register
```

8. Build assets:
```bash
npm run build
```

9. Start queue worker (in separate terminal):
```bash
php artisan queue:work --queue=order-processing
```

## Usage

### 1. Adding a Store

1. Go to "Stores" in the menu
2. Click "New Store"
3. Fill in the details:
   - Store Name
   - Shopify Store URL (e.g., your-store.myshopify.com)
   - Shopify Access Token
   - Recharge Access Token (Optional)

### 2. Creating Tagging Rules

#### Via AI (Recommended):

1. Go to "AI Conversations"
2. Click "New Conversation"
3. Select a store
4. Define rules in text (e.g., "If property X exists then tag Y")
5. Enter sample order (JSON)
6. Click "Generate Rule from Conversation"

#### Manually:

1. Go to "Tagging Rules"
2. Click "New Rule"
3. Fill in the details:
   - Rule Name
   - Tags Template (with expressions like `{{switch(...)}}`)

### 3. Processing Orders

1. Go to "Order Processing"
2. Select a store
3. Select a rule (or leave empty for all active rules)
4. Enter order IDs separated by commas
5. Choose whether to overwrite existing tags
6. Click "Start Processing"
7. Monitor progress in the progress bar

### 4. Webhook Setup

1. In Shopify Admin → Settings → Notifications → Webhooks
2. Create a new webhook:
   - Event: `Order creation`
   - Format: `JSON`
   - URL: `https://your-domain.com/webhooks/shopify/order-created`
   - API version: Latest
3. Copy the Webhook Secret to `.env`:
   ```
   SHOPIFY_WEBHOOK_SECRET=your_secret_here
   ```

## Tag Templates

The system supports complex expressions:

### Basic Example:
```
Tag1, Tag2, Tag3
```

### With Expressions:
```
{{switch(12.Days + "-" + 12.Gram; "14D-50"; "A"; "14D-75"; "A"; "Unknown")}}
```

### Available Functions:

- `{{get(array, index)}}` - Extract element from array
- `{{split(string, delimiter)}}` - Split string into array
- `{{switch(value; case1; result1; case2; result2; ...; default)}}` - Switch statement
- `{{12.Days}}` - Access order fields (line item index 12, field Days)

## API Endpoints

### Webhook
```
POST /webhooks/shopify/order-created
```

### Order Processing
```
POST /orders/process
GET /orders/progress/{job}
GET /orders/results/{job}
```

### Tagging Rules
```
POST /tagging-rules/{rule}/test
```

## Troubleshooting

### Queue not working:
```bash
php artisan queue:work --queue=order-processing
```

### Cache not updating:
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

### OpenRouter Error:
Make sure the API key is correctly set in `.env`:
```
OPENROUTER_API_KEY=sk-or-v1-...
```

## License

MIT License
