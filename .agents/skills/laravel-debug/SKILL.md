---
name: laravel-debug
description: Debug Laravel application issues using logs, errors, and application info. Use this when encountering errors, exceptions, or unexpected behavior in the Laravel app.
license: MIT
compatibility: opencode
metadata:
  category: debugging
  framework: laravel
---

## What I do

I help debug Laravel application issues by:

1. **Check last error** - Using `last-error` tool to see the most recent exception
2. **Read application logs** - Using `read-log-entries` to find relevant error messages
3. **Check application info** - Using `application-info` to verify PHP version, Laravel version, and installed packages
4. **Inspect configuration** - Using `get-config` to check relevant config values
5. **Browser logs** - Using `browser-logs` to catch frontend errors

## When to use me

Use this skill when:
- An exception or error occurs
- Something is not working as expected
- You need to investigate a bug
- A feature is broken after a deployment
- You see a 500 error or similar

## Workflow

1. Always start with `last-error` to get the most recent exception
2. If needed, read recent log entries with `read-log-entries`
3. Check `application-info` to understand the environment
4. Use `get-config` for relevant config keys if the issue is config-related
5. Check `browser-logs` if the issue might be frontend-related

## Important Notes

- Prefer reading logs first before running arbitrary code
- For database issues, use the `database-inspector` skill instead
- For routing issues, use the `route-analyzer` skill instead
- Do NOT create or modify data without explicit user approval
