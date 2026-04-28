---
name: database-inspector
description: Inspect database schema, run read-only queries, and analyze table structures. Use this for understanding data model, checking records, or verifying database state.
license: MIT
compatibility: opencode
metadata:
  category: database
  framework: laravel
---

## What I do

I inspect the database by:

1. **Read database schema** - Using `database-schema` to get full table structures (columns, indexes, foreign keys)
2. **Run read-only queries** - Using `database-query` to execute SELECT, SHOW, EXPLAIN, DESCRIBE queries
3. **Check database connections** - Using `database-connections` to see available connections

## When to use me

Use this skill when:
- You need to understand the database structure
- You want to check data in tables
- You need to verify relationships between tables
- You want to analyze query performance with EXPLAIN
- You need to check if a table or column exists

## Workflow

1. Start with `database-schema` to understand the overall structure
2. Use `database-query` with `SELECT` to inspect actual data
3. Use `EXPLAIN` before `SELECT` for performance analysis
4. Filter tables by name if needed using the `filter` parameter

## Safety Rules

- ONLY read-only queries are allowed (SELECT, SHOW, EXPLAIN, DESCRIBE)
- NEVER run INSERT, UPDATE, DELETE, DROP, ALTER, or TRUNCATE
- If data modification is needed, ask the user for explicit approval first
- Prefer `database-schema` over raw queries for structure inspection
