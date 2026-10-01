# Tonyapps_ShippingRules — Review (2026-09-21)

Magento 2 module: rule-driven hiding of shipping methods + "contact store" pseudo-carrier,
with admin CRUD, grid, and JSON/CSV import/export. No git repo, no TODOs/FIXMEs in source.

## Gotowe (działające, kompletne)

- **Model danych**: `Rule` extends `Magento\Rule\Model\AbstractModel` — reużywa silnik warunków
  z Cart Price Rule (AND/OR drzewo warunków na atrybutach koszyka/adresu). Schemat DB
  (`db_schema.xml` + whitelist) kompletny, z indeksem `is_active + sort_order`.
- **Ewaluacja reguł**: `RuleEvaluator` — wspólny punkt oceny dla pluginu i carriera, filtruje po
  store/customer group/dacie, memoizuje wynik per-adres, chroniony `try/catch` na wypadek
  uszkodzonego drzewa warunków (nie wywala kalkulacji stawek).
- **Akcje reguł**: `hide` (ukrywa wskazane metody albo wszystkie, gdy lista pusta) i
  `contact_store` (pokazuje pseudo-carrier `ContactStore`, opcjonalnie z `hide_others`).
  Własna metoda `contact_store` nigdy nie jest chowana przez samą siebie.
- **Carrier `ContactStore`**: standardowy offline carrier, konfigurowalny w
  Stores > Configuration > Shipping Methods, pojawia się tylko gdy pasuje reguła.
- **Admin CRUD**: grid (UI component, mass delete/enable/disable), edit/new form (fieldsets
  General/Action/Scope + natywny condition builder + AJAX `NewConditionHtml`), delete, save
  z poprawną normalizacją multiselectów i pustych dat.
- **Import/Export**: `ExporterPool`/`ImporterPool` (DI-konfigurowane), JSON (domyślny,
  zachowuje strukturę warunków 1:1) i CSV (warunki jako zagnieżdżony JSON w komórce). Auto-detekcja
  formatu po rozszerzeniu pliku.
- **ACL/menu/routing**: kompletne — jeden resource na grid (`::rules`).
- **i18n**: `en_US.csv` pokrywa wszystkie stringi z kontrolerów/bloków oraz etykiety z
  `system.xml`.
- **Testy jednostkowe**: `RuleEvaluator`, `HideMethodsPlugin`, `ExporterPool`, `ImporterPool`,
  `Action` source, `Rule`, `RuleRepository`, `RuleArrayConverter`, `CsvExporter`, `CsvImporter`,
  `JsonExporter`, `JsonImporter` — 82 testy, wszystkie zielone (potwierdzone przez uruchomienie
  na prawdziwym `dev/tests/unit` z pełnym Magento, PHP 8.4.24).

## Problemy / ryzyka

1. ~~**Niespójność wersji PHP (blokujące)**~~ — **naprawione.** `CsvImporter::isBlankRow()` używał
   `array_all()` (PHP 8.4+); zamieniony na `array_filter()` (działa od PHP 8.1). `composer.json`
   podniesiony do `"php": ">=8.3"` — realny floor wymuszony przez typed class constants
   (`public const string X = ...`), których kod używa wszędzie.
2. ~~**ACL config resource nieużyty**~~ — **naprawione.** `Tonyapps_ShippingRules::config` usunięty
   z `acl.xml`: nie dało się go poprawnie podpiąć bez ograniczenia configu wszystkim innym
   carrierom (sekcja `carriers` jest współdzielona, ACL na configu działa na poziomie sekcji, nie
   grupy).
3. ~~**`fputcsv`/`fgetcsv` bez `$escape` (PHP 8.4 deprecation)**~~ — **naprawione, znalezione przy
   pisaniu testów.** Od PHP 8.4 wywołanie bez trzeciego/piątego parametru `$escape` emituje
   deprecation ("the default will change"); w dev-mode Magento (`display_errors` + pełny
   `error_reporting`) taki deprecation trafia do logu/ekranu przy każdym imporcie/eksporcie CSV.
   Dodano `escape: '\\'` do wszystkich 4 wywołań w `CsvExporter`/`CsvImporter`, zachowując
   dotychczasowe zachowanie na wprost.
4. ~~**Braki w testach jednostkowych**~~ — **naprawione.** Dopisano `RuleTest`, `RuleRepositoryTest`,
   `RuleArrayConverterTest`, `CsvExporterTest`, `CsvImporterTest`, `JsonExporterTest`,
   `JsonImporterTest`.
5. ~~**i18n niepełne**~~ — **naprawione.** Etykiety z `system.xml` dopisane do `i18n/en_US.csv`.
6. **Brak repo/CI** — katalog nie jest repozytorium git; brak `.gitignore`, brak pliku
   `phpunit.xml`/CI configu do samodzielnego odpalenia testów poza pełnym drzewem Magento.
   *(Użytkownik dodaje repo samodzielnie.)*

## Pomysły na kolejne kroki

- Rozważ integration test (Magento `dev/tests/integration`) na `RuleEvaluator` +
  `HideMethodsPlugin` z prawdziwym quote/address — unit testy mockują cały łańcuch Magento.
  Dobre uzupełnienie w projekcie z dużą wagą na poprawność, niekonieczne dla MVP.
  ponytail: dodaj gdy pojawi się drugi realny bug w integracji z prawdziwym Magento.
- `actions_serialized` w schemacie DB jest martwą kolumną (moduł działa tylko na warunkach, nie
  na akcjach silnika `Magento\Rule`) — udokumentowane w kodzie, ale warto rozważyć usunięcie
  kolumny, jeśli na pewno nigdy nie będzie używana.
