# Tonyapps_B2bRequisitionLists

Saved, named SKU lists for Tonyapps_B2b company accounts, with an action to add the list to the normal Magento cart. Magento Open Source has no reusable company requisition-list feature.

## Scope and decisions

| Feature | Decision |
|---|---|
| Ownership | Lists are shared with members of the same exact company. Parent and child companies have separate lists; a hierarchy path never grants implicit access. |
| Permissions | Add `requisition_lists_view` and `requisition_lists_manage` to the B2B role catalogue. Manage implies view. Company admins retain the existing all-permissions bypass. |
| List management | Authorized members can create, rename, and delete company lists and edit their SKU/quantity rows. Names are unique within a company. |
| Add to cart | Submit the saved SKU/quantity rows to `Tonyapps_B2bQuickOrder`'s service. Magento's ordinary salability checks apply; unsupported or unavailable items are reported while valid rows can still be added. Existing cart, checkout, payment limits, and order approval remain in force. |
| Supported items | Simple and virtual products, matching B2bQuickOrder phase 1. Configurable/bundle option selection, quote attachment, approval behavior, and purchase-order conversion are out of scope. |
| Data | Store SKU and quantity per row; SKU remains the lookup key so unavailable/deleted products produce a useful add-to-cart error rather than stale product data. Duplicate SKUs in a list are merged. |
| Security | Every read and mutation is scoped by the visitor's company ID and checked against the B2B permission service. Mutations are POST-only with form keys. |

## Phases

1. **Company lists:** schema, role permissions, customer-account page, list/item create-edit-delete, add-to-cart integration, and focused unit tests.
2. **Manual verification:** install/upgrade, verify role access across two companies, create/edit/delete a list, and add valid and unavailable SKUs to the cart.

## Install

Install `tonyapps/module-b2b-requisition-lists` with `tonyapps/module-b2b` and `tonyapps/module-b2b-quick-order`, then run:

```sh
bin/magento setup:upgrade
```

## Tests

```sh
vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist app/code/Tonyapps/B2bRequisitionLists/Test/Unit
```

The repository scope test uses Magento's integration test configuration:

```sh
vendor/bin/phpunit -c dev/tests/integration/phpunit.xml.dist app/code/Tonyapps/B2bRequisitionLists/Test/Integration
```
