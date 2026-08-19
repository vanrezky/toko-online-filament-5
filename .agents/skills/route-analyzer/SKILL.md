---
name: route-analyzer
description: Analyze Laravel application routes including web, API, and Folio routes. Use this to understand URL structure, controller mappings, and middleware assignments.
license: MIT
compatibility: opencode
metadata:
  category: routing
  framework: laravel
---

## What I do

I analyze the application's routing by:

1. **List all routes** - Using `list-routes` to see all defined routes (web, API, Folio)
2. **Filter by method** - Show only GET, POST, PUT, DELETE routes
3. **Filter by path** - Search for specific URL patterns
4. **Check application info** - Verify routing-related packages

## When to use me

Use this skill when:
- You need to understand the URL structure
- You're adding new pages or endpoints
- You want to find which controller handles a specific URL
- You need to check middleware assignments
- You're debugging 404 errors

## Workflow

1. List all routes with `list-routes`
2. Filter by specific path pattern if looking for something specific
3. Check route names and actions for controller mappings
4. Verify Inertia/Folio routes if applicable

## Tips

- Routes with `inertia` actions are frontend pages
- Routes with `Filament` actions are admin panel pages
- API routes typically start with `/api/`
- Named routes can be referenced in code via `route('name')`
