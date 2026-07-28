## Context

Checkout stores one selected shipping method per warehouse in the Inertia form. A watcher reloads shipping costs whenever the selected address changes. The current response handler replaces every selection with the first option, even when the previous courier remains valid. Shipping options and prices are address-dependent, so a prior selection cannot be reused without reconciling it against the latest response.

## Goals / Non-Goals

**Goals:**

- Preserve a matching courier code per warehouse from the current form state.
- Replace preserved method details with values from the latest shipping-cost response.
- Choose a deterministic fallback and notify the customer only when preservation is impossible.
- Prevent older asynchronous responses from changing the state for a newer address.

**Non-Goals:**

- Change the shipping-cost endpoint, courier availability rules, or checkout payload shape.
- Persist a preferred courier beyond the active checkout session.

## Decisions

### Reconcile each warehouse against the latest options

For each warehouse in the latest response, the frontend will find an option whose `courier_code` equals the currently selected method's code. It will use that latest option plus the latest warehouse weight when found; otherwise it will use the first current option.

This keeps the request response authoritative for cost and availability while retaining an intentional user selection. Keeping the old method object was rejected because it could submit an outdated price or a courier no longer offered.

### Rebuild selections from the response

The new `shipping_methods` object will be built only from warehouses present in the latest response. This removes stale warehouse entries instead of retaining them through a shallow clone.

### Ignore obsolete requests

Each reload will capture a monotonically increasing request token. Only the newest request may update shipping options, selections, loading state, or fallback notice. Cancellation is not required because a token check prevents stale UI state without coupling the component to Axios cancellation APIs.

### Use an existing frontend notification pattern

When a fallback is applied, the page will surface a concise warning through the project notification mechanism. It will not block checkout because the fallback is a valid current shipping method.

## Risks / Trade-offs

- [Multiple warehouses can fall back simultaneously] → The warning will describe that one or more shipping choices changed rather than emitting one alert per warehouse.
- [Courier names may differ while codes remain stable] → Reconciliation uses `courier_code`, the value used by the checkout payload and selection UI.
- [A request fails after an address change] → Existing selections remain intact and the existing error logging behavior remains; no invalid fallback is submitted from a failed response.

## Migration Plan

No data migration or API deployment coordination is required. The frontend change can be rolled back as one component update.

## Open Questions

None.
