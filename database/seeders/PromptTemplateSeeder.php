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
You are an expert at creating Shopify order tagging rules for Zyg AutoTag.

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
    }
}
