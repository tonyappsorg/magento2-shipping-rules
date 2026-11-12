# Tonyapps_ShippingRules — notes for AI agents and developers

Read this before changing the module. Workspace-wide rules are in ../AGENTS.md.

Hide shipping methods, or offer a "contact store for a quote" method, based on conditions about the cart.
Package `tonyapps/module-shippingrules` 0.1.0.

## Status

- Built; review fixes applied 2026-10-30. 72 unit tests per the last README (see Tests).
- `REVIEW.md` (Polish, 2026-09-21) is an earlier review; its test count predates later changes.

## Architecture

| Piece | What it does |
|---|---|
| `Model\RuleEvaluator` | Matches active rules against a rate request: scope, then the condition tree. Memoised per rate request, so the carrier and the plugin agree, and a recalculation after a cart change re-evaluates. A broken condition tree is logged and never breaks rate calculation. |
| `Plugin\Shipping\HideMethodsPlugin` | After `Shipping::collectRates`, removes the methods matching rules hide. "Hide others" only hides when the contact-store rate is really in the result. |
| `Model\Carrier\ContactStore` | The contact-store pseudo-carrier (`carriers/tonyapps_contactstore`). Returns a rate only when active and a `contact_store` rule matches. |
| `Plugin\Quote\ProductAttributesPlugin` | Quote items only load the product attributes core asks for; this adds the ones active rules use, read with core's own cart-price-rule parser. Without it, product-attribute conditions never match. |
| `Model\Rule` | Extends `Magento\Rule\Model\AbstractModel`, reusing the cart price rule condition engine. |
| `Model\RuleRepository`, `Api\RuleRepositoryInterface`, `Api\Data\RuleInterface` | Rule persistence. |
| `Model\Rule\Io\{Json,Csv}{Exporter,Importer}`, `RuleArrayConverter` | Import / export. |
| `Controller\Adminhtml\Rule\*` | Index, New, Edit, Save, Delete, MassDelete, MassStatus, NewConditionHtml (AJAX conditions), Export, Import, ImportPost. |
| `view/adminhtml/ui_component/tonyapps_shipping_rule_listing.xml` | Grid with Add New Rule, Import, Export JSON, Export CSV buttons and mass delete / enable / disable. |
| `Model\Source\Action` | Actions `hide` and `contact_store`. |

### Rule fields

| Field | Meaning |
|---|---|
| Action | **Hide selected shipping methods** (all of them when the list is empty), or **Show "Contact store"**, which shows the contact-store method and, with *Hide others*, removes every other method. |
| Methods | Rate codes the rule applies to, e.g. `flatrate_flatrate` (multiselect from `Magento\Shipping\Model\Config\Source\Allmethods`; used by the Hide action). |
| Conditions | The cart price rule condition builder: subtotal, weight, destination, payment method, and product attributes ("an item in the cart with material = Canvas"). |
| Scope | Store views, customer groups, from/to dates, sort order, active. |

### Import / export

- Export (`Controller\Adminhtml\Rule\Export`, `format=json|csv`) writes **all** rules ordered by sort order, then
  ID. JSON is the default and keeps the condition tree as-is; CSV puts conditions as JSON in one cell.
- Import detects the format from the file extension. Every rule is validated first (name, action, dates as
  `YYYY-MM-DD`, numeric group and store IDs, readable conditions) and all rules are saved in one transaction, so a
  bad file imports nothing. Imports always create new rules.
- Import rejects unreadable conditions instead of importing an empty tree that matches every cart: invalid
  conditions JSON in a CSV cell, and any condition `type` that isn't a class implementing core's
  `ConditionInterface` (core `Combine::loadArray()` would only log it), fail the whole file.
- CSV is RFC 4180 (`escape: ''`, quotes doubled), so a file saved by Excel keeps its conditions JSON intact. Older
  exports written with `escape: '\\'` still import: when the RFC read fails, the importer retries the old way.

## Data model

- `tonyapps_shipping_rule`: `rule_id`, `name`, `description`, `is_active`, `sort_order`, `action`
  (`hide` | `contact_store`), `applies_to_methods` (comma-separated `carrier_method` codes; empty = all),
  `hide_others`, `conditions_serialized`, `actions_serialized`, `customer_group_ids` (comma-separated; empty =
  all), `store_ids` (comma-separated; empty = all), `from_date`, `to_date`, `created_at`, `updated_at`. Index on
  (`is_active`, `sort_order`).

## Configuration and ACL

- Menu **Stores > Shipping Rules** (`Tonyapps_ShippingRules::rules`, parent `Magento_Backend::stores`, route
  `tonyapps_shippingrules/rule/index`). ACL `Tonyapps_ShippingRules::rules` (one resource for everything).
- Carrier **Stores > Configuration > Sales > Delivery Methods > Contact Store for a Quote (Shipping Rules)**
  (`carriers/tonyapps_contactstore/*`): `active` (default 1), `title` (default "Contact store"), `name` (default
  "Contact us for a shipping quote"), `price` (default 0.00), `sort_order`, `sallowspecific`, `specificcountry`.
  It only appears when a `contact_store` rule matches.

## Decisions and constraints

- Conditions reuse the cart price rule engine, so the condition UI and attribute parsing are core's.
- Rule evaluation must never break rate calculation: errors are logged and the rule is skipped.
- "Hide others" must never leave the customer with no method: it hides only when the contact-store rate is
  actually present (carrier off or country-restricted → normal methods stay).
- `Magento_CatalogRule` is declared (composer and sequence): the conditions fieldset uses its
  `promo/fieldset.phtml`.

## Known limitations and open points

- Export always exports all rules (the old README said "selected rules"; the controller ignores grid selection).
- Imports never update existing rules.

## Tests

```
vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist app/code/Tonyapps/ShippingRules/Test/Unit
```

The last README said 72 unit tests. At the README split (2026-11-12) the suite had 64 `test*` methods; data
providers may account for the difference (not run during the split).

## Change history

- **2026-10-30 review fixes**
  - Import rejects unreadable conditions (see Import / export).
  - CSV is RFC 4180, with fallback for old `escape: '\\'` exports.
  - "Hide others" only hides when the contact-store rate is really in the result.
  - A malformed date on the edit form returns to the form with an error instead of an error page.
  - Mass delete / mass status carry on past a failing row and report it.
  - `Magento_CatalogRule` declared (composer and sequence).
- **2026-09-21** — earlier review, `REVIEW.md` (Polish).
