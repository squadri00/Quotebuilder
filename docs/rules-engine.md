# Rules Engine

`App\Services\RulesEngine` calculates a product's final price for a given
set of customer answers. It's a standalone PHP class — no controller, no
routes, no UI yet. Call it like this:

```php
$result = (new App\Services\RulesEngine)->calculate($productId, $answers);

// $result = [
//     'base_price'    => 49.99,
//     'final_price'   => 77.49,
//     'applied_rules' => [
//         ['rule_id' => 1, 'name' => 'Glossy surcharge', 'action' => [...], 'amount_changed' => 20.0],
//     ],
// ]
```

`$answers` is `[question_id => value]` — for a `single_choice` question, the
value is the chosen `option_id`; for `number`/`text` questions, it's whatever
the customer typed.

## How it calculates

1. Start at the product's `base_price`.
2. For every answered `single_choice` question, add that option's own
   `price_modifier`.
3. Go through the product's rules **in the order they were created**. For
   each rule whose `condition_logic` matches the given answers, apply its
   `action_logic` on top of the running total, then move to the next rule.

Rules stack — if two rules both match, both apply, in order.

## condition_logic format

The simple case — one condition:

```json
{
  "question_id": 1,
  "operator": "equals",
  "value": 2
}
```

This reads as: "the answer to question 1 equals 2." Supported operators:
`equals`, `not_equals`, `greater_than`, `less_than`, `greater_than_or_equal`,
`less_than_or_equal`. The numeric comparisons are for `number`-type
questions (e.g. "if quantity is greater than 500").

For a rule that needs more than one condition to all be true at once, use a
list instead of a single object:

```json
[
  { "question_id": 1, "operator": "equals", "value": 2 },
  { "question_id": 4, "operator": "greater_than", "value": 100 }
]
```

## action_logic format

Three kinds of action:

**Add a fixed dollar amount:**
```json
{ "type": "add_fixed", "amount": 20 }
```

**Add a percentage of the running total so far:**
```json
{ "type": "add_percentage", "percent": 10 }
```

**Override what a specific option contributes to the price** (only has an
effect if the customer actually selected that option):
```json
{ "type": "set_option_price", "option_id": 2, "price_modifier": 2 }
```

## Worked example: "If Paper Type = Glossy, add $20"

Using the real seeded Printing Shop data (Flyers is product id 1, Paper Type
is question id 1, Glossy is option id 2):

```php
Rule::create([
    'product_id' => 1,
    'name' => 'Glossy surcharge',
    'condition_logic' => [
        'question_id' => 1,
        'operator' => 'equals',
        'value' => 2, // the Glossy option's id
    ],
    'action_logic' => [
        'type' => 'add_fixed',
        'amount' => 20,
    ],
]);
```

Calling `(new RulesEngine)->calculate(1, [1 => 2])` (question 1 answered
with option 2 = Glossy) now returns a final price of base price + Glossy's
own price modifier + this rule's $20.

## Known limitation

The engine currently only looks at rules tied directly to the product
(`rules.product_id = this product`). Business-wide rules (`product_id` left
null) aren't picked up yet — that's a natural follow-up once there's a UI
for creating them.
