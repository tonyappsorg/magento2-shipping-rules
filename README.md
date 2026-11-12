# Shipping Rules for Magento 2

Hide shipping methods, or offer "contact us for a shipping quote", based on what is in the cart.

Magento Open Source shows every enabled shipping method that returns a rate, with no way to say "no express
shipping for heavy items" or "bulky products need a quote". This module adds shipping rules built with the same
condition builder as cart price rules. A rule can hide methods, or show a "contact store for a quote" method
instead.

## Features

- **Hide shipping methods by condition**: hide chosen methods, or all of them, when the cart matches a rule.
- **"Contact store for a quote" method**: offer a placeholder shipping method (your own title, name and displayed
  price, usually 0) when a rule matches, optionally as the only choice.
- **Familiar conditions**: use the cart price rule condition builder: subtotal, weight, destination, payment
  method, and product attributes ("an item in the cart with material = Canvas").
- **Scheduling and targeting**: limit rules to store views and customer groups, set from and to dates, sort order
  and an active switch.
- **Bulk management**: enable, disable or delete rules from the grid in one go.
- **Import and export**: export rules as JSON or CSV (Excel-safe) and import them on another store. Every rule in
  a file is checked first and the file is imported in one go, so a bad file changes nothing.
- **Fail-safe**: a broken rule is logged and skipped, it never stops shipping rates from being calculated. If the
  quote method can't be offered (for example the country isn't allowed), customers keep the normal methods
  instead of seeing none.

## How it works

Create rules in **Stores > Shipping Rules**. Each rule has:

| Field | Meaning |
|---|---|
| Action | **Hide selected shipping methods** (all of them when the list is empty), or **Show "Contact store"**, which shows the contact-store method and, with *Hide others*, removes every other method. |
| Methods | The shipping methods a *Hide* rule applies to, picked from a list of the store's methods. |
| Conditions | The cart price rule condition builder. |
| Scope | Store views, customer groups, from/to dates, sort order, active. |

At checkout, the matching rules are applied to the shipping methods the customer sees, and the rules are checked
again when the cart changes. The "contact store" method appears only when a matching rule asks for it.

## Good to know

- Export downloads all rules; there is no export of only selected rules.
- Imports always create new rules; they don't update existing ones.
- The "contact store" method doesn't collect the request for you: the customer places the order with it, and you
  follow up with the real shipping cost.

## Requirements

- Magento Open Source 2.4 (developed on 2.4.8)
- PHP 8.3 or 8.4
- Magento modules: Backend, CatalogRule, Config, Customer, Directory, OfflineShipping, Quote, Rule, SalesRule,
  Shipping, Store, Ui

## Installation

```
composer require tonyapps/module-shippingrules
bin/magento module:enable Tonyapps_ShippingRules
bin/magento setup:upgrade
```

## Configuration

- **Stores > Shipping Rules**: rules grid, import and export.
- **Stores > Configuration > Sales > Delivery Methods > Contact Store for a Quote (Shipping Rules)**: enabled,
  title, method name, displayed price, sort order, countries.
- **System > Permissions > User Roles**: *Stores > Shipping Rules* grants access.

## For developers

Architecture, decisions, data model and tests are in [AGENTS.md](AGENTS.md).
