# Tonyapps_B2bRequisitionLists — notes for AI agents and developers

Read this before changing the module. Workspace-wide rules are in ../AGENTS.md. B2B core context (permissions,
`AbstractCompanyAction`, `CompanyContext`) is in ../B2b/AGENTS.md; add-to-cart reuses ../B2bQuickOrder.

## Status

- WORKPLAN shortlist #3 (quick win): **built, manual QA pending** on staging (`tonyapps/module-b2b-requisition-lists`
  v0.1.0, proprietary). Saved, named SKU lists for Tonyapps_B2b company accounts, with an action to add the list to
  the normal cart. Magento Open Source has no reusable company requisition-list feature (Commerce-only).
- Phases:
  1. **Company lists** — ✅ schema, role permissions, customer-account page, list/item create-edit-delete,
     add-to-cart integration, focused unit tests.
  2. **Manual verification** — install/upgrade, verify role access across two companies, create/edit/delete a list,
     add valid and unavailable SKUs to the cart. Scripted browser flow done 2026-11-09 in DDEV (see Change history);
     staging install + README checklist still pending per WORKPLAN.
- Integration tests green in DDEV 2026-11-11 (9 tests, see Tests).

## Architecture

| Piece | What |
|---|---|
| Route | `b2brequisitionlists` (frontend) |
| `Controller\List\Index` (GET) | page "Requisition Lists"; `requisition_lists_view` |
| `Controller\List\Save` (POST) | create / rename / edit rows; `requisition_lists_manage`; name required, ≤ 100 chars; duplicate name → "Check that the name is unique within your company." |
| `Controller\List\Delete` (POST) | `requisition_lists_manage` |
| `Controller\List\AddToCart` (POST) | `requisition_lists_view`; passes saved rows (with their `Line N`) to `Tonyapps\B2bQuickOrder\Model\QuickOrderService::addRows()` |
| `Model\RequisitionListRepository` | direct `ResourceConnection` SQL; every read/mutation scoped by company ID ("does not exist for this company") |
| `Model\ItemParser` | textarea `SKU, qty` per row; `MAX_LINES` 500, `MAX_QTY` 10000, `MAX_BYTES` 65536; duplicate SKUs merged (combined qty must stay ≤ 10000) |
| `Block\List\Index` | `PAGE_SIZE` 20; loads items only for lists on the current page |
| `Block\Account\RequisitionListsLink` | nav link "Requisition Lists", `customer_account_navigation` sortOrder 235, only for members with view permission |
| Layout / view | `b2brequisitionlists_list_index.xml` (block `cacheable="false"`, `customer_account` handle), `customer_account.xml`, `templates/list/index.phtml`, `web/css/source/_module.less` |

All controllers extend `Tonyapps\B2b\Controller\AbstractCompanyAction` (`$requiredPermission`), so the B2b master
switch, active-company check and company-admin bypass apply. Mutations are POST-only (`HttpPostActionInterface`) with
form keys.

`module.xml` sequence: `Magento_Catalog`, `Magento_Checkout`, `Tonyapps_B2b`, `Tonyapps_B2bQuickOrder`.
`Api/` directory exists but is empty.

## Data model (`etc/db_schema.xml` + whitelist)

| Table | Columns / constraints |
|---|---|
| `tonyapps_b2b_requisition_list` | `entity_id`, `company_id` (FK → `tonyapps_b2b_company.entity_id` CASCADE), `created_by_customer_id` (nullable, FK → `customer_entity` SET NULL), `name` varchar(100), `created_at`, `updated_at`; unique (`company_id`, `name`); index `company_id` |
| `tonyapps_b2b_requisition_list_item` | `entity_id`, `list_id` (FK → list CASCADE), `sku` varchar(64), `qty` decimal(12,4) unsigned; unique (`list_id`, `sku`); index `list_id` |

## Configuration and ACL

- No `system.xml`/`config.xml`/admin ACL. Gated by `tonyapps_b2b/general/enabled`.
- Storefront permissions live in B2b's catalogue (`Tonyapps\B2b\Model\Permissions`): `requisition_lists_view`
  ("View and add company requisition lists to the cart") and `requisition_lists_manage` ("Create, edit, and delete
  company requisition lists"). Manage implies view. Company admins keep the all-permissions bypass.

## Decisions and constraints

| Topic | Decision |
|---|---|
| Ownership | Lists are shared with members of the same exact company. Parent and child companies have separate lists; a hierarchy path never grants implicit access. |
| Permissions | `requisition_lists_view` / `requisition_lists_manage` added to the B2B role catalogue; manage implies view; company admins bypass. |
| List management | Authorized members create, rename, delete company lists and edit SKU/quantity rows. Names unique within a company. |
| Add to cart | Saved rows go through B2bQuickOrder's service. Magento's salability checks apply; unsupported/unavailable items reported while valid rows can still be added. Cart, checkout, payment limits and order approval remain in force. |
| Supported items | Simple and virtual, matching B2bQuickOrder phase 1. Configurable/bundle option selection, quote attachment, approval behavior and purchase-order conversion are out of scope. |
| Data | Store SKU and quantity per row; SKU stays the lookup key so unavailable/deleted products give a useful add-to-cart error instead of stale product data. Duplicate SKUs merged. |
| Security | Every read and mutation scoped by the visitor's company ID and checked against the B2B permission service. Mutations POST-only with form keys. |

## Known limitations and open points

- Delete confirmation was the open UX point from QA — fixed 2026-11-09.
- Staging install and manual checklist (phase 2) still pending per WORKPLAN.

## Tests

```sh
vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist app/code/Tonyapps/B2bRequisitionLists/Test/Unit
```

The repository scope test uses Magento's integration test configuration:

```sh
vendor/bin/phpunit -c dev/tests/integration/phpunit.xml.dist app/code/Tonyapps/B2bRequisitionLists/Test/Integration
```

- Unit: `Block/Account/RequisitionListsLinkTest`, `Block/List/IndexTest`, `Controller/List/AddToCartTest`,
  `Model/ItemParserTest`, `View/DeleteConfirmationTest`, `View/LayoutLocationTest`.
- Integration: `Test/Integration/Model/RequisitionListRepositoryTest` — 9 tests green in DDEV 2026-11-11 (company
  scoping, edit/overwrite, unique names per company, rollback of a failed save, scoped delete, cascades from company
  and creator, sorting/paging). DDEV setup and run command: see WORKPLAN.md item 3 and the integration-tests notes
  (`@magentoDbIsolation` makes the repository's own rollback a no-op — disable it for rollback tests; class-level
  `@magentoConfigFixture` is ignored).
- E2E: `../e2e/tests/b2b-requisition-lists.spec.js`.

## Change history

### 2026-11-09 QA fix
- **Delete List** asks for confirmation (core `data-post` confirm modal, which then posts with the form key), since a
  list is shared with the whole company. Unit test + e2e.
- List actions in one row: **Add List to Cart**, **Save Changes** (submits the list's edit form through the HTML
  `form` attribute) and **Delete List** on the right (`view/frontend/web/css/source/_module.less`).
- Browser flow scripted 2026-11-09 (WORKPLAN): create (duplicate SKUs merged), duplicate name rejected, add to cart
  (valid rows added, `Line 3: SKU "NOPE-123" was not found`), rename/edit, delete; manager without permission and
  direct POSTs redirected; view-only member sees lists without create/edit/delete and can add to cart; child company
  sees none of the parent's lists and a direct POST with the parent's list id is refused.

### 2026-11-08 smoke test (WORKPLAN)
- Found every B2b storefront page returning 500 (`<title translate>` failed the layout schema; fixed in B2b with a
  layout schema test). List page renders for the company admin; buyer without the permission gets no link.

### 2026-10-30 review fixes
- List page excluded from the full page cache (cached copies leaked lists between companies and hid the manage forms)
  and shows the customer account menu.
- Lists paginated, 20 per page; items loaded only for lists on the current page.
- Add-to-cart errors name the list row (`Line N`) instead of `Line ?`.
