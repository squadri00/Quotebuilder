# Database Conventions

These rules apply to every migration written for this project, starting now.

## 1. Index every foreign key

Any column that references another table's id (`business_id`, `product_id`,
`question_id`, `option_id`, `quote_id`, etc.) must be indexed in the migration
that creates it. In Laravel migrations this usually means using
`foreignId('business_id')->constrained()` (which indexes automatically) rather
than a plain unindexed integer column.

Why: without an index, every query that filters or joins on that column forces
the database to scan the whole table. That's invisible with a handful of test
rows and slow once a table has real data.

## 2. Index `business_id` + `created_at` on large tables

Tables expected to grow large over time — `quotes`, `quote_answers`, and any
future table with similar volume — must have a composite index on
(`business_id`, `created_at`).

Why: the most common query pattern in a multi-tenant app is "this business's
records, most recent first." A composite index on those two columns together
serves that query directly instead of falling back to a table scan or a
slower single-column index.

```php
$table->index(['business_id', 'created_at']);
```

## Applying this

When writing a new migration, check it against this file before running it.
Existing migrations should be revisited if they're found to be missing an
index required by these rules.
