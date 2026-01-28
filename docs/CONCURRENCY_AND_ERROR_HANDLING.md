# Concurrency and Error Handling

## Webhook Endpoints

When creating an automation with `trigger_type: webhook`, the system provides a webhook URL that you can configure in Shopify or Recharge.

### Getting Webhook URL

**API Endpoint:**
```
POST /api/automations/{automation_id}/webhook-url
```

**Response:**
```json
{
  "webhook_url": "https://your-domain.com/webhooks/shopify/1/orders/create",
  "event": "orders/create",
  "shop_id": 1,
  "instructions": "Configure this URL in your Shopify/Recharge webhook settings"
}
```

### Webhook Endpoints Available

- `POST /webhooks/shopify/{shop_id}/orders/create`
- `POST /webhooks/shopify/{shop_id}/orders/update`
- `POST /webhooks/recharge/{shop_id}/subscription/created`
- `POST /webhooks/recharge/{shop_id}/subscription/updated`

## Concurrent Request Handling

The system uses **Redis queue** to handle concurrent requests:

1. **Webhook receives request** → Creates Run record with status `queued`
2. **Run is dispatched to queue** → Multiple requests can be queued simultaneously
3. **Queue workers process jobs** → Each worker processes one job at a time
4. **Concurrent execution** → Multiple workers can process different runs in parallel

### Configuration

Set in `.env`:
```env
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

### Scaling Workers

To handle more concurrent requests, scale up queue workers:

```bash
# Run multiple workers
php artisan queue:work --tries=3 &
php artisan queue:work --tries=3 &
php artisan queue:work --tries=3 &
```

Or use Laravel Horizon (if available on your platform) for better monitoring and scaling.

## Error Handling

### Automatic Retry

When a step fails:

1. **Step-level retry**: Configured in automation step's `retry_policy`:
   ```json
   {
     "retry_policy": {
       "max_attempts": 3,
       "backoff_seconds": 5,
       "retry_on_status_codes": [500, 502, 503]
     }
   }
   ```

2. **Job-level retry**: `RunAutomationJob` automatically retries up to 3 times with exponential backoff (5s, 15s, 30s)

3. **On failure**: 
   - Run status set to `failed`
   - Error logged to `run_steps.error` JSON field
   - Domain log entry created with error status
   - **Other runs continue processing** - they are independent

### Error Behavior

- **Step fails without `continue_on_error`**: Automation stops, run marked as `failed`
- **Step fails with `continue_on_error: true`**: Automation continues to next step
- **Job fails after all retries**: Run marked as `failed`, other runs unaffected

### Manual Retry

**API Endpoint:**
```
POST /api/runs/{run_id}/retry
```

Creates a new run with the same configuration and queues it for execution.

## Running Automation for Multiple Orders

### Manual Trigger with Order List

**API Endpoint:**
```
POST /api/automations/{automation_id}/run
```

**Request Body:**
```json
{
  "mode": "execute",
  "order_ids": ["12345", "12346", "12347"]
}
```

**Response:**
```json
{
  "run_ids": [101, 102, 103],
  "count": 3,
  "message": "3 automations queued for execution"
}
```

Each order ID creates a separate Run that is queued independently. They will execute concurrently based on available queue workers.

### Single Order Execution

**Request Body:**
```json
{
  "mode": "dry_run",
  "payload": {
    "order_id": "12345"
  }
}
```

## Idempotency

The system prevents duplicate executions using idempotency keys:

- Generated from: `shop_id + automation_id + trigger_type + payload identifiers`
- If a run with the same key exists, the new request is ignored
- Ensures webhook duplicates don't create multiple runs

## Monitoring

### Check Run Status

```bash
# View runs
GET /api/runs/{run_id}

# View failed runs
GET /api/runs?status=failed

# View queue status
php artisan queue:monitor
```

### Logs

- **Application logs**: `storage/logs/laravel.log`
- **Run errors**: Stored in `run_steps.error` JSON field
- **Domain logs**: Fast lookup by order/subscription ID in `domain_logs` table

## Best Practices

1. **Configure retry policies** for steps that might fail due to network issues
2. **Use dry-run mode** to test automations before executing
3. **Monitor queue workers** to ensure they're processing jobs
4. **Set appropriate timeouts** for long-running automations
5. **Use idempotency** to prevent duplicate processing
