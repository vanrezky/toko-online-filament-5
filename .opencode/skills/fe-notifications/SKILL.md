---
name: fe-notifications
description: Implement notifications, toasts, flash messages, and confirmation dialogs in the frontend. Use vue-sonner and vue3-confirm-dialog following project patterns.
license: MIT
compatibility: opencode
metadata:
  category: frontend
  type: notifications
---

## What I do

I help implement notifications, toasts, and confirmation dialogs following the project's established patterns.

## Notification Systems Available

### 1. Vue Sonner (Primary Toast System)

**Already configured** in `BaseLayout.vue` and `Layout.vue`:
```vue
<Toaster position="top-right" richColors closeButton />
```

**Usage in components:**
```vue
<script setup>
import { toast } from "vue-sonner";

// Success toast
toast.success("Product added to cart successfully!");

// Error toast
toast.error("Failed to add product. Please try again.");

// Warning toast
toast.warning("Stock is running low.");

// Info toast
toast.info("Your order has been shipped.");

// Custom toast with action
toast("New message received", {
    description: "You have a new notification",
    action: {
        label: "View",
        onClick: () => router.visit(route("frontend.notifications")),
    },
    duration: 5000,
});

// Promise toast
const promise = () => new Promise((resolve) => setTimeout(resolve, 2000));
toast.promise(promise, {
    loading: "Processing...",
    success: "Done!",
    error: "Failed!",
});

// Dismiss all
toast.dismiss();
</script>
```

**Toast options:**
```js
toast.success("Message", {
    description: "Optional description text",
    duration: 4000,        // Auto dismiss time (ms)
    position: "top-right", // top-left, top-center, bottom-left, etc
    closeButton: true,     // Show close button
    richColors: true,      // Use rich color variants
    action: {              // Add action button
        label: "Undo",
        onClick: () => { /* action */ }
    },
});
```

### 2. Flash Messages (Backend → Frontend)

**Automatic bridge** via `FlashMessages.vue`:

```php
// In Laravel controller
return redirect()->back()->with('success', 'Profile updated successfully!');
return redirect()->back()->with('error', 'Failed to update profile.');
return redirect()->back()->with('warning', 'Please verify your email.');
return redirect()->back()->with('info', 'New features available!');
```

These auto-appear as toasts on the frontend. **No frontend code needed!**

### 3. vue3-confirm-dialog (Confirmation Dialogs)

**Already configured** globally in `main.js`:
```js
import ConfirmDialog from "vue3-confirm-dialog";
app.use(ConfirmDialog);
```

**Already mounted** in `BaseLayout.vue`:
```vue
<vue3-confirm-dialog />
```

**Usage in components:**

```vue
<script setup>
import { createConfirmDialog } from "vue3-confirm-dialog";
import ConfirmModal from "../UI/ConfirmModal.vue"; // Or use built-in

// Basic usage
const { reveal, onConfirm, onCancel } = createConfirmDialog(ConfirmModal, {
    title: "Delete Product?",
    description: "Are you sure you want to delete this product? This action cannot be undone.",
});

onConfirm(() => {
    router.delete(route("frontend.product.destroy", productId), {
        onSuccess: () => toast.success("Product deleted successfully!"),
    });
});

onCancel(() => {
    toast.info("Deletion cancelled");
});

const handleDelete = () => reveal();
</script>

<template>
    <button @click="handleDelete" class="text-destructive">
        Delete Product
    </button>
</template>
```

**ConfirmModal.vue component:**
```vue
<script setup>
const props = defineProps({
    title: String,
    description: String,
});
</script>

<template>
    <div class="rounded-2xl bg-white p-6 shadow-xl">
        <h3 class="text-lg font-bold text-foreground">{{ title }}</h3>
        <p class="mt-2 text-sm text-muted-foreground">{{ description }}</p>
        <div class="mt-6 flex justify-end gap-3">
            <button @click="$emit('cancel')" class="rounded-xl px-4 py-2 text-sm font-medium text-muted-foreground hover:bg-muted">
                Cancel
            </button>
            <button @click="$emit('confirm')" class="rounded-xl bg-destructive px-4 py-2 text-sm font-medium text-destructive-foreground hover:bg-destructive/90">
                Confirm
            </button>
        </div>
    </div>
</template>
```

## Notification Patterns by Scenario

### Form Submission
```js
const submit = () => {
    form.post(route("frontend.contact.store"), {
        onSuccess: () => {
            toast.success("Message sent successfully!");
            form.reset();
        },
        onError: () => {
            toast.error("Please check your input and try again.");
        },
    });
};
```

### Cart Operations
```js
const addToCart = () => {
    router.post(
        route("frontend.cart.store"),
        { product_id: id, quantity: 1 },
        {
            preserveScroll: true,
            onSuccess: () => toast.success("Added to cart!"),
            onError: () => toast.error("Failed to add to cart"),
        }
    );
};
```

### Async API Call
```js
const applyVoucher = async () => {
    toast.loading("Applying voucher...");
    try {
        const response = await axios.post("/api/vouchers/apply", { code });
        toast.dismiss();
        toast.success("Voucher applied successfully!");
    } catch (error) {
        toast.dismiss();
        toast.error(error.response?.data?.message || "Invalid voucher code");
    }
};
```

### Destructive Action with Confirmation
```js
const deleteAccount = () => {
    const { reveal, onConfirm } = createConfirmDialog(ConfirmModal, {
        title: "Delete Account?",
        description: "This will permanently delete your account and all associated data. This action cannot be undone.",
    });
    
    onConfirm(() => {
        router.delete(route("frontend.account.destroy"), {
            onSuccess: () => toast.success("Account deleted successfully"),
        });
    });
    
    reveal();
};
```

## Toast Style Guidelines

| Scenario | Toast Type | Message Style |
|----------|-----------|---------------|
| Success action | `toast.success()` | Short, positive: "Added to cart!" |
| Error/Failure | `toast.error()` | Clear, actionable: "Failed to save. Try again." |
| Warning | `toast.warning()` | Informative: "Stock running low" |
| Info | `toast.info()` | Neutral: "New update available" |
| Loading | `toast.loading()` | With promise resolution |

## When to use me

- Adding toast notifications to actions
- Implementing confirmation dialogs
- Handling backend flash messages
- Showing loading/progress states
- Implementing undo actions

## Important Notes

- **Flash messages from backend are automatic** — no frontend code needed
- Use `toast.dismiss()` before showing a new toast if replacing
- Keep toast messages **short and clear** (under 50 characters)
- Always provide **error handling** with user-friendly messages
- Use `preserveScroll: true` with Inertia actions that show toasts
- `vue3-confirm-dialog` is globally available — just call `createConfirmDialog()`
