---
name: tailwindcss-3
description: Use Tailwind CSS 3 with the project's custom design system. Follow existing color tokens, utilities, and component patterns to avoid duplicating styles.
license: MIT
compatibility: opencode
metadata:
  category: styling
  framework: tailwindcss
  version: "3"
---

## What I do

I help write Tailwind CSS classes following this project's established design system and patterns.

## Color Tokens (Design System)

This project uses **HSL CSS variables** with shadcn/ui-style tokens:

| Token | Usage | Example |
|-------|-------|---------|
| `text-foreground` | Primary text | `text-foreground` |
| `text-muted-foreground` | Secondary text | `text-muted-foreground` |
| `bg-background` | Page background | `bg-background` |
| `bg-primary` | Primary brand color | `bg-primary` |
| `text-primary` | Primary text color | `text-primary` |
| `text-primary-foreground` | Text on primary bg | `text-primary-foreground` |
| `bg-secondary` | Secondary background | `bg-secondary` |
| `bg-destructive` | Error/danger | `bg-destructive` |
| `text-destructive` | Error text | `text-destructive` |
| `border-border` | Default borders | `border-border` |

**Dynamic colors:** These map to admin-configurable colors via `useColorScheme()`. Default is orange (`#F97316`).

## Custom Utility Classes (Already Defined)

Do NOT recreate these — use them directly:

| Class | Description |
|-------|-------------|
| `.container` | `max-w-[1440px] mx-auto px-4 md:px-8` |
| `.btn-primary` | Black button, uppercase, tracking-widest |
| `.input-elegant` | Rounded-none input with gray border |
| `.without-ring` | Removes focus ring (`!important`) |
| `.text-link` | Blue link with hover underline |
| `.scrollbar-hidden` | Custom hidden scrollbar with hover reveal |

## Common Component Patterns

### Card Pattern
```html
<div class="group relative flex flex-col overflow-hidden rounded-2xl bg-white shadow-md transition-all duration-300 hover:-translate-y-2 hover:shadow-xl hover:shadow-primary/10">
```

### Button Patterns
```html
<!-- Primary gradient button -->
<button class="rounded-xl bg-gradient-to-r from-primary to-primary/90 px-8 py-3.5 text-sm font-semibold text-primary-foreground shadow-lg shadow-primary/30 transition-all duration-300 hover:shadow-xl hover:shadow-primary/40 active:scale-95">

<!-- Secondary button -->
<button class="inline-flex items-center gap-2 rounded-full bg-secondary px-8 py-3 text-sm font-semibold text-foreground transition-colors hover:bg-primary hover:text-primary-foreground">

<!-- Icon button -->
<button class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-primary to-primary/90 text-primary-foreground shadow-lg shadow-primary/30 transition-all duration-300 hover:shadow-xl active:scale-90">
```

### Section Patterns
```html
<!-- Standard section -->
<section class="py-8 md:py-12">
    <div class="container mx-auto px-4">
        <!-- Content -->
    </div>
</section>

<!-- Gradient background section -->
<section class="relative overflow-hidden bg-gradient-to-r from-primary/5 via-primary/10 to-primary/5 py-12 md:py-16">
    <div class="absolute -left-20 -top-20 h-64 w-64 rounded-full bg-primary/10 blur-3xl"></div>
    <div class="absolute -bottom-20 -right-20 h-64 w-64 rounded-full bg-primary/10 blur-3xl"></div>
    <div class="container relative z-10 mx-auto px-4">
        <!-- Content -->
    </div>
</section>
```

### Badge Patterns
```html
<!-- Sale badge -->
<div class="bg-gradient-to-r from-destructive to-rose-500 px-2.5 py-1 text-xs font-bold text-white shadow-lg">

<!-- New badge -->
<div class="bg-gradient-to-r from-primary to-primary/80 px-2.5 py-1 text-xs font-bold text-primary-foreground">

<!-- Best seller badge -->
<div class="bg-gradient-to-r from-amber-500 to-orange-500 px-2.5 py-1 text-xs font-bold text-white">

<!-- Category badge -->
<div class="rounded-full bg-primary/10 px-2 py-0.5 text-xs font-semibold text-primary">
```

### Form Input Patterns
```html
<!-- Standard input -->
<input class="flex-grow rounded-xl border border-white/50 bg-white px-5 py-3.5 text-sm shadow-lg focus:outline-none focus:ring-2 focus:ring-primary/30">

<!-- Elegant input (uses custom class) -->
<input class="input-elegant" placeholder="...">
```

### Empty State Pattern
```html
<div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-50 to-slate-100 py-20 text-center">
    <div class="absolute -right-10 -top-10 h-40 w-40 rounded-full bg-primary/5"></div>
    <div class="absolute -bottom-10 -left-10 h-32 w-32 rounded-full bg-primary/5"></div>
    <div class="relative z-10">
        <!-- Icon + message -->
    </div>
</div>
```

## Spacing Scale Used

- `gap-4` for grids
- `p-4` for card padding
- `py-8 md:py-12` for section vertical spacing
- `px-4` for horizontal padding
- `mb-6` for heading margins
- `space-y-4` for vertical stacks

## Grid Patterns

```html
<!-- Product grid -->
<div class="grid grid-cols-2 gap-4 md:grid-cols-4 lg:grid-cols-5">

<!-- Two column layout -->
<div class="grid gap-8 md:grid-cols-2">

<!-- Three column layout -->
<div class="grid gap-6 md:grid-cols-3">
```

## Typography Patterns

```html
<!-- Page title -->
<h1 class="text-2xl font-bold text-foreground md:text-3xl">

<!-- Section title -->
<h2 class="text-xl font-bold text-foreground md:text-2xl">

<!-- Card title -->
<h3 class="text-lg font-semibold text-foreground">

<!-- Body text -->
<p class="text-sm text-muted-foreground">

<!-- Price -->
<span class="text-sm font-bold text-primary sm:text-base">
```

## Animation Patterns

```html
<!-- Hover lift card -->
class="transition-all duration-300 hover:-translate-y-2 hover:shadow-xl"

<!-- Image zoom on hover -->
class="transition-transform duration-500 group-hover:scale-110"

<!-- Fade overlay -->
class="opacity-0 transition-opacity duration-300 group-hover:opacity-100"

<!-- Button press -->
class="transition-all duration-300 active:scale-95"
```

## When to use me

- Adding or modifying Tailwind classes
- Creating new UI components
- Styling forms, buttons, or cards
- Implementing responsive layouts
- Adding hover/focus states

## Important Notes

- **ALWAYS use `cn()` utility** from `lib/utils.js` for conditional classes:
  ```js
  import { cn } from "../../lib/utils";
  cn("base-class", condition && "conditional-class", active ? "active" : "inactive");
  ```
- **ALWAYS use `container` class** for max-width wrapper (not `max-w-7xl` etc)
- Use `rounded-2xl` for cards, `rounded-xl` for buttons, `rounded-full` for pills
- Use `shadow-lg` as base shadow, `shadow-xl` for hover states
- Use `bg-gradient-to-*` extensively for brand consistency
- Do NOT hardcode hex colors — use design tokens (`primary`, `foreground`, etc)
- Use `lucide-vue-next` for icons (import as Vue components)
- Currency formatting uses `formatCurrency()` from `lib/utils.js`
