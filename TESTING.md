# 🧪 Test & Quality Assurance Strategy

## Probleem Statement

Het project had meerdere runtime errors die pas tijdens browser testing ontdekt werden:
- `Class "Filament\Forms\Components\Grid" not found`
- `Class "Filament\Forms\Components\Group" not found`  
- `Undefined constant LeadStatus::ConvertedToQuote`
- Type mismatch errors met `Get`/`Set` utilities

**Dit moet voorkomen worden door geautomatiseerde tests.**

---

## 📊 Test Pyramid

```
           /\
          /UI\ ← Browser tests (Dusk)
         /────\
        /  API \ ← Feature tests  
       /────────\
      / Unit Tests \ ← Model/Service tests
     /──────────────\
    /  Static Analysis \ ← PHPStan/Psalm
   /────────────────────\
```

---

## ✅ Implemented Solutions

### 1. **Pre-Commit Hook** (.git/hooks/pre-commit)

Voert automatisch checks uit VOOR elke commit:
- ✅ PHP Syntax check
- ✅ Filament v4 compatibility check  
- ✅ Runs tests
- ❌ Blokkeert commit bij errors

**Gebruik:**
```bash
# De hook werkt automatisch bij:
git commit -m "message"

# Skip de hook (NIET AANBEVOLEN):
git commit -m "message" --no-verify
```

---

### 2. **GitHub Actions CI/CD** (.github/workflows/laravel-tests.yml)

Draait automatisch bij elke push/PR:
- ✅ PHP 8.4 + MySQL 8.0
- ✅ Composer install
- ✅ Migrations
- ✅ PHPUnit tests
- ✅ Filament compatibility checks

**Status badge toevoegen aan README:**
```markdown
![Tests](https://github.com/jouw-repo/sfh/workflows/Laravel%20Tests/badge.svg)
```

---

### 3. **Composer Scripts** (composer.json)

Snelle lokale checks:

```bash
# Run alle checks
composer check

# Alleen syntax check
composer check:syntax

# Alleen Filament compatibility
composer check:filament

# Alleen tests
composer test

# Alleen Filament tests
composer test:filament
```

---

### 4. **Feature Tests**

#### A. FilamentResourcesLoadTest
Test dat alle Filament pagina's laden zonder errors:

```bash
php artisan test --filter=FilamentResourcesLoadTest
```

**Wat wordt getest:**
- `/admin/leads` list pagina
- `/admin/leads/{id}/edit` edit pagina  
- `/admin/quotes` list pagina
- `/admin/quotes/{id}/edit` edit pagina
- Alle andere resources

#### B. LeadActionsTest
Test de custom Lead acties:

```bash
php artisan test --filter=LeadActionsTest
```

**Wat wordt getest:**
- "Maak Offerte" knop bestaat
- "Meer info nodig" knop bestaat
- Lead kan worden omgezet naar Quote
- Status update werkt correct

#### C. QuoteItemsRelationManagerTest
Test de Quote items relation manager:

```bash
php artisan test --filter=QuoteItemsRelationManagerTest
```

**Wat wordt getest:**
- Items formulier laadt zonder Grid/Group errors
- Items kunnen worden aangemaakt
- Total price berekening werkt

---

## 🚀 Development Workflow

### Voor elke code change:

```bash
# 1. Maak je changes
vim app/Filament/Resources/Leads/LeadResource.php

# 2. Run syntax check
composer check:syntax

# 3. Run Filament compatibility check
composer check:filament

# 4. Run relevante tests
php artisan test --filter=Lead

# 5. Als alles slaagt, commit
git add .
git commit -m "Add new lead feature"
# → Pre-commit hook draait automatisch

# 6. Push naar GitHub
git push
# → GitHub Actions draait automatisch
```

---

## 🔍 Common Filament v4 Errors & Solutions

### Error 1: Grid Component
```php
// ❌ FOUT:
Forms\Components\Grid::make(3)
    ->schema([...])

// ✅ FIX:
// Gebruik ->columns() op schema level:
$schema->columns(3)->components([...])

// Of gebruik ->columnSpan() op velden:
TextInput::make('field')->columnSpan(1)
```

### Error 2: Section Component
```php
// ❌ FOUT:
use Filament\Forms\Components\Section;

// ✅ FIX:
use Filament\Schemas\Components\Section;
```

### Error 3: Get/Set in Schemas
```php
// ❌ FOUT:
use Filament\Forms\Get;
use Filament\Forms\Set;

// ✅ FIX (in RelationManagers/Schemas):
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
```

---

## 📝 Test Checklist

Voordat je nieuwe Filament code pusht:

- [ ] PHP syntax check passed
- [ ] No Grid/Group components gebruikt
- [ ] Correcte Get/Set imports  
- [ ] Feature test geschreven
- [ ] Lokale tests slagen
- [ ] Pre-commit hook passed
- [ ] Browser test gedaan

---

## 🐛 Debug Tips

### Test faalt maar browser werkt?
```bash
# Clear alle caches:
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Regenerate autoload:
composer dump-autoload
```

### Browser error maar tests slagen?
```bash
# Check Livewire cache:
php artisan livewire:discover

# Check Filament cache:
php artisan filament:cache-components
```

### Pre-commit hook te langzaam?
```bash
# Edit .git/hooks/pre-commit
# Comment out test section (regel ~40-50)
# Maar run tests dan HANDMATIG!
```

---

## 📚 Toevoegingen TODO

### Kort termijn:
- [ ] Fix DatabaseSeeder (quote_number nullable issue)
- [ ] Add PHPStan level 5
- [ ] Add Laravel Pint (code style)
- [ ] Add Larastan (Laravel + PHPStan)

### Middellang termijn:
- [ ] Browser tests met Laravel Dusk
- [ ] Visual regression testing (Percy/BackstopJS)
- [ ] Performance tests (load testing)
- [ ] API tests (Postman/Insomnia collections)

### Lang termijn:
- [ ] E2E tests (Cypress/Playwright)
- [ ] Mutation testing (Infection)
- [ ] Security scanning (OWASP ZAP)
- [ ] Dependency scanning (Snyk/Dependabot)

---

## 🎯 Metrics

### Current Coverage (geschat):
- Unit Tests: ~40%
- Feature Tests: ~20%
- Browser Tests: 0%

### Target Coverage:
- Unit Tests: 70%+
- Feature Tests: 60%+
- Browser Tests: Critical paths only

---

## 📖 Resources

- [Filament v4 Upgrade Guide](https://filamentphp.com/docs/4.x/upgrade-guide)
- [Laravel Testing Docs](https://laravel.com/docs/testing)
- [PHPUnit Best Practices](https://phpunit.de/best-practices.html)
- [GitHub Actions for Laravel](https://github.com/marketplace/actions/setup-php-action)

---

## ❓ FAQ

**Q: Waarom falen de tests nu?**  
A: DatabaseSeeder heeft een bug met quote_number generatie. Dit is een bekend issue en wordt gefixed.

**Q: Moet ik altijd tests schrijven?**  
A: Voor nieuwe features: JA. Voor kleine fixes: gebruik je oordeel.

**Q: Hoe test ik Livewire components?**  
A: Gebruik `Livewire::test()` helper. Zie [docs](https://livewire.laravel.com/docs/testing).

**Q: Pre-commit hook is irritant, kan ik die uitzetten?**  
A: Ja, maar DAN BEN JE VERANTWOORDELIJK voor het draaien van tests!

---

**Laatste update:** 15-02-2026  
**Auteur:** Development Team
