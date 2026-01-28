# Automation DSL Documentation

## Overview

The Automation DSL (Domain-Specific Language) defines how automations are structured and executed in Zyg.

## Automation Structure

```json
{
  "name": "Order Tag Automation",
  "status": "active",
  "trigger_type": "webhook",
  "trigger_config": {
    "event": "orders/create"
  },
  "steps": [
    {
      "id": "step-1",
      "name": "Get Order",
      "action_type": "shopify.order.get",
      "enabled": true,
      "config": {},
      "input_map": {
        "order_id": "trigger_payload.id"
      },
      "conditions": [],
      "retry_policy": {
        "max_attempts": 3,
        "backoff_seconds": 5
      }
    }
  ],
  "version": 1
}
```

## Trigger Types

- `webhook`: Triggered by incoming webhook events
- `schedule`: Triggered on a schedule (cron-like)
- `manual`: Triggered manually via UI
- `playground`: Triggered from playground/testing

## Action Types

### Shopify Actions

- `shopify.order.get`: Retrieve order details
- `shopify.order.add_tags`: Add tags to an order
- `shopify.order.remove_tags`: Remove tags from an order
- `shopify.order.add_variant`: Add variant to order (order edit)
- `shopify.order.add_note`: Add note to order

### Recharge Actions

- `recharge.subscription.get`: Get subscription details
- `recharge.subscription.update`: Update subscription
- `recharge.subscription.set_next_charge_date`: Set next charge date

### Utility Actions

- `transform.set`: Set a value in context
- `transform.template`: Apply template rendering
- `condition.if`: Conditional step execution

## Input Mapping

Use dot notation to map values from context:

```json
{
  "input_map": {
    "order_id": "trigger_payload.id",
    "customer_email": "trigger_payload.customer.email"
  }
}
```

## Conditions

Conditions determine if a step should execute:

```json
{
  "conditions": [
    {
      "field": "trigger_payload.total_price",
      "operator": "greater_than",
      "value": 100
    }
  ]
}
```

Operators: `equals`, `not_equals`, `contains`, `greater_than`, `less_than`, `exists`, `not_exists`

## Dry-Run Plan Format

When running in dry-run mode, each step returns a simulation diff:

```json
{
  "action": "PUT",
  "endpoint": "/admin/api/2024-01/orders/123.json",
  "expected_effect": "Add tags: VIP, Premium",
  "dry_run": true
}
```

## Patch Format

Patches use JSON Patch (RFC 6902) format:

```json
[
  {
    "op": "add",
    "path": "/steps/0/input_map/order_id",
    "value": "trigger_payload.id"
  }
]
```
