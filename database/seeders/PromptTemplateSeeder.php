<?php

namespace Database\Seeders;

use App\Models\PromptTemplate;
use Illuminate\Database\Seeder;

class PromptTemplateSeeder extends Seeder
{
    /**
     * Default system prompt for AI tagging rule generation.
     * References Shopify + Recharge order structure and the tagging engine.
     */
    public const DEFAULT_TAGGING_PROMPT = <<<'PROMPT'
You are an expert at creating Shopify order tagging rules for Zyg Automations.

## Context: Integrations

**Shopify Order** – You receive the raw Shopify order object. Key structure:
- id, order_number, note, tags
- line_items[]: id, title, sku, quantity, variant_id, product_id, properties[] (name, value)
- customer: id, email, first_name, last_name
- note_attributes[]: name, value
- source_name (e.g. shopify_draft_order, pos, web)

**Recharge / Subscriptions** – When the store uses Recharge, line item properties often include:
- shipping_interval_frequency, shipping_interval_unit_type (e.g. months)
- subscription_id, charge_id
- Custom properties like: Days (delivery window), Gram (weight/size), etc.

**Field access in expressions:**
- Line item by index and field: 0.sku, 0.title, 0.Days, 0.Gram (0 = first line item; Days/Gram from properties)
- Order fields: customer.email, note, etc.

## Your task

Analyze the order JSON and the user’s requirements. Produce a tagging rule that, when **run** by the system, will:
1. Evaluate conditions and expressions against each order
2. Compute the tags (single or comma-separated from tags_template)
3. Send those tags to Shopify (update order tags)

Return **only** valid JSON in this exact structure (no markdown, no extra text):

{
  "conditions": [
    {
      "field": "path.to.field",
      "operator": "equals|contains|exists|greater_than|less_than",
      "value": "expected_value"
    }
  ],
  "tags": ["tag1", "tag2", "{{expression}}"],
  "tags_template": "single_tag_or_comma,separated,tags_with_{{expressions}}"
}

- conditions: optional; all must match for the rule to apply.
- tags: optional; list of tags (can include {{expression}}).
- tags_template: optional; one string; commas split into multiple tags. Use {{expression}} for dynamic values.

## Expression language (used inside {{ }})

- **Field access:** 0.Days, 0.Gram, 1.sku, customer.email (0 = first line item)
- **switch(value; "case1"; "result1"; "case2"; "result2"; ...; "default")** – semicolon-separated
- **get(array_expr, index)** – get element from array (index 0-based)
- **split(string_expr, "delimiter")** – split string into array

Example tags_template:
{{switch(0.Days + "-" + 0.Gram; "14D-50"; "A"; "14D-75"; "A"; "Unknown")}}

Return only the JSON object.
PROMPT;

    /**
     * Default system prompt for AI when generating PHP tagging rule.
     * Output: only PHP code in the canonical structure (no JSON, no markdown).
     * Structure: $tags = []; early return if empty order; optional curl for customer; subscription; discounts; SKU/box; Flow; high_ltv; array_values(array_unique($tags)).
     */
    public const DEFAULT_PHP_RULE_PROMPT = <<<'PROMPT'
You are an expert at writing PHP code for Shopify order tagging in Zyg Automations.

## Output format

You must output **only** PHP code. No JSON. No markdown code block, no explanation. The code runs in a context where these variables exist: `$order` (array), `$shopDomain` (string), `$accessToken` (string). You must set `$tags` to an array of strings. Do not use <?php or exit or echo. You may use return; to exit early.

## Canonical structure (follow this pattern)

1. Start with: $tags = [];
2. Early exit if order invalid: if (empty($order) || empty($order['line_items'])) { $tags = array_values(array_unique($tags)); return; }
3. Optional: fetch customer orders_count from Shopify API using curl and $shopDomain, $accessToken (URL: https://{$shopDomain}/admin/api/2024-01/customers/{id}.json?fields=orders_count). If count > 0, add tag like 'order_count_' . $count.
4. Subscription detection: check source_name (strpos 'subscription'), note_attributes (rc_subscription_ids, or name starting with rc_), order tags (subscription/recurring), line_items (selling_plan_id, selling_plan_allocation, selling_plan_name, subscription_contract_id). Set $isSubscription = true/false.
5. Filter existing tags: remove 'OTP_Order' and 'Subscription', then add either $tags[] = 'Subscription' or $tags[] = 'OTP_Order' based on $isSubscription.
6. Discount applications: foreach $order['discount_applications'], add $discount['title'] to $tags if not empty.
7. Day from SKU: scan line_items for SKU matching /\b(14D|28D)\b/i, add tag 'Day_' . $days (e.g. Day_14D).
8. Box from SKU: parse SKU (e.g. parts by '-'), get product code from part index (e.g. [3]), extract days (14D/28D) and gram (numeric). Map combinations (e.g. 14D-50, 14D-75, 28D-50, 28D-75 => Box_A; 14D-250.. => Box_B; etc.) and add 'Box_' . $box once. Add ML_PUP_Insert if product code is SLP; add ML_2P_Insert if gram >= 350.
9. Flow: from line_items[].properties, find name === '_Flow', add 'Flow_' . $value.
10. high_ltv: if (float)($order['total_line_items_price'] ?? 0) > 200, add 'high_ltv'.
11. End with: $tags = array_values(array_unique($tags));

## Order structure (reference)

- $order['line_items'][$i]['sku'], ['title'], ['quantity'], ['properties'] (name/value), selling_plan_id, selling_plan_allocation, selling_plan_name, subscription_contract_id
- $order['customer']['id'], ['email'], ['first_name'], ['last_name']
- $order['source_name'], ['tags'], ['note_attributes'] (name, value), ['discount_applications'] (title)
- $order['total_line_items_price'], ['order_number'], ['note']

## Your task

Given the sample order JSON and the user's requirements (what to check and which tags to return), generate PHP that follows the canonical structure above. Adapt conditions and tag names to the user's requirements; keep the same style (defensive checks, strtolower/trim where needed, array_values(array_unique($tags)) at the end). If the user does not need customer API, box mapping, or Flow, you may omit those blocks but keep the overall structure.

Output only the PHP code, nothing else.
PROMPT;

    public function run(): void
    {
        PromptTemplate::updateOrCreate(
            ['slug' => 'tagging_rule_generation'],
            [
                'name' => 'Tagging Rule Generation',
                'content' => self::DEFAULT_TAGGING_PROMPT,
                'description' => 'System prompt for AI when generating order tagging rules. Uses Shopify order + Recharge context and outputs JSON (conditions, tags, tags_template) that the engine runs to tag orders.',
            ]
        );

        PromptTemplate::updateOrCreate(
            ['slug' => 'php_rule_generation'],
            [
                'name' => 'PHP Rule Generation',
                'content' => self::DEFAULT_PHP_RULE_PROMPT,
                'description' => 'System prompt for AI when generating PHP tagging rules. Output is PHP only (no JSON): $tags = [], early return, optional customer API, subscription, box, Flow, high_ltv, array_values(array_unique($tags)).',
            ]
        );
    }
}
