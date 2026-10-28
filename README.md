# Tonyapps_ShippingRules

Hide shipping methods, or offer a "contact store for a quote" method, based on conditions about the cart.

## Use

**Stores > Shipping Rules** in the admin menu (ACL `Tonyapps_ShippingRules::rules`). Each rule has:

| Field | Meaning |
|---|---|
| Action | **Hide selected shipping methods** (all of them when the list is empty), or **Show "Contact store"**, which shows the contact-store method and, with *Hide others*, removes every other method. |
| Methods | Rate codes the rule applies to, e.g. `flatrate_flatrate`. |
| Conditions | The cart price rule condition builder: subtotal, weight, destination, payment method, and product attributes ("an item in the cart with material = Canvas"). |
| Scope | Store views, customer groups, from/to dates, sort order, active. |

The contact-store method is a carrier configured under **Stores > Configuration > Sales > Delivery Methods >
Contact Store for a Quote** (title, method name, displayed price, countries). It only appears when a
`contact_store` rule matches.

## Import / export

The grid exports the selected rules as JSON (default, keeps the condition tree as-is) or CSV (conditions as JSON in
one cell). Import detects the format from the file extension. Every rule is validated first (name, action, dates
as `YYYY-MM-DD`, numeric group and store IDs, readable conditions) and all rules are saved in one transaction, so
a bad file imports nothing. Imports always create new rules.

## How it works

| Piece | What it does |
|---|---|
| `Model\RuleEvaluator` | Matches active rules against a rate request: scope, then the condition tree. Memoised per rate request, so the carrier and the plugin agree, and a recalculation after a cart change re-evaluates. A broken condition tree is logged and never breaks rate calculation. |
| `Plugin\Shipping\HideMethodsPlugin` | After `Shipping::collectRates`, removes the methods matching rules hide. |
| `Model\Carrier\ContactStore` | The contact-store pseudo-carrier. |
| `Plugin\Quote\ProductAttributesPlugin` | Quote items only load the product attributes core asks for; this adds the ones active rules use, read with core's own cart-price-rule parser. Without it, product-attribute conditions never match. |

## Tests

```
vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist app/code/Tonyapps/ShippingRules/Test/Unit
```

71 unit tests. `REVIEW.md` (Polish, 2026-09-21) is an earlier review; its test count predates later changes.
