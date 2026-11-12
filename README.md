# B2B Requisition Lists for Magento 2

Saved, shared shopping lists for company buyers — reorder the usual products in one click.

B2B customers buy the same products again and again. Requisition lists are an Adobe Commerce B2B feature; Magento
Open Source has nothing like them. This add-on for B2B Company Accounts (`tonyapps/module-b2b`) lets a company keep
named lists of SKUs and quantities that every authorised colleague can use, and add a whole list to the cart at once.

## Features

- **Named company lists** — "Monthly office supplies", "Site A consumables": create as many lists as you need, each
  with its own SKUs and quantities. List names are unique within a company.
- **Shared with the team** — a list belongs to the company, not to the person who created it, so colleagues can use
  it too.
- **One-click reorder** — **Add List to Cart** puts every row into the normal cart. Rows that can't be added (an
  unknown SKU, an out-of-stock product) are reported by line while the rest still go in.
- **Role-based access** — two new company role permissions: *view and add lists to the cart*, and *create, edit and
  delete lists*. Company administrators can always do both.
- **Kept apart per company** — parent and child companies each have their own lists; nobody sees another company's
  lists.
- **Safe deletes** — deleting a shared list asks for confirmation first.
- **Your normal checkout** — products go into the regular cart, so prices, stock checks, company payment rules and B2B
  Order Approval still apply.

## How it works

Members with access see **Requisition Lists** in their customer account menu. There they create a list by giving it a
name and entering one `SKU, quantity` per row, and later edit, rename or delete it. Each list has **Add List to Cart**,
**Save Changes** and **Delete List** buttons. Lists are shown 20 per page. Members with view-only access can browse
lists and add them to the cart but not change them.

## Good to know

- Only **simple and virtual products** can be added from a list; configurable and bundle products (which need options
  chosen) are not supported.
- A list stores SKUs, not prices: prices are worked out when the list is added to the cart.
- Up to 500 rows per list and up to 10,000 units per SKU; repeated SKUs in a list are merged.
- Lists are not shared between parent and child companies.
- Not included: attaching a list to a quote, approval of lists, or turning a list into a purchase order.

## Requirements

- Magento Open Source 2.4.8
- PHP 8.3 or 8.4
- `tonyapps/module-b2b` (B2B Company Accounts) and `tonyapps/module-b2b-quick-order` (B2B Quick Order)

## Installation

```sh
composer require tonyapps/module-b2b-requisition-lists
bin/magento setup:upgrade
```

## Configuration

No settings of its own. Give company roles the **Requisition Lists** permissions on the storefront (*My Company ›
Roles and Permissions*) or in the admin (*Customers › Companies › Roles*). The module follows **Stores ›
Configuration › Tonyapps › B2B Company Account › Enable B2B Company Accounts**.

## For developers

Architecture, decisions, data model and tests are in [AGENTS.md](AGENTS.md).
