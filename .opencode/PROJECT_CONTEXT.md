# Schroeffundering Holland CRM - Project Context

**Laatste update:** 2026-02-15 23:55

## Project Overzicht

Dit is een volledig functioneel CRM-systeem voor Schroeffundering Holland, gebouwd met Laravel 12 en Filament v4. Het systeem beheert leads, offertes, projecten, en facturatie met volledige Moneybird integratie.

**Project Status:** 98% Complete - Productie-ready

## Tech Stack

- **Framework:** Laravel 12.51.0
- **Admin Panel:** Filament v4.7.1 (LET OP: v4 heeft andere namespaces dan v3!)
- **Database:** MySQL 8.0
- **PHP:** 8.4.14
- **Testing:** Pest v3.8
- **PDF:** DomPDF v3.1
- **Development:** DDEV
- **Email:** Mailpit (localhost:1025)
- **Queue:** Database driver

## Belangrijke URLs

- **Admin Panel:** https://sfh.ddev.site/admin
- **Login:** admin@sfh.nl / password
- **Mailpit:** https://sfh.ddev.site:8026

## Database Schema

### Hoofdtabellen (9)
1. **companies** - Klantenbeheer
2. **leads** - Website aanvragen
3. **quotes** - Offertes
4. **quote_items** - Offerte regels (polymorphic: Product of Custom)
5. **projects** - Actieve projecten
6. **invoices** - Facturen (voor Moneybird)
7. **products** - Productcatalogus
8. **stock_reservations** - Voorraadreserveringen
9. **email_templates** - Email template beheer

### Relaties
- Lead → Quote (optional) - Lead kan geconverteerd worden naar Quote
- Quote → Project (optional) - Quote kan geconverteerd worden naar Project
- Project → Multiple Invoices - Project kan meerdere facturen hebben (split invoicing)
- Quote → Stock Reservations - Bij quote versturen worden producten gereserveerd
- Company ← Leads, Quotes, Projects - Alle entiteiten zijn gekoppeld aan Company

## Core Features

### 1. Lead Management
- Leads worden vastgelegd (voorbereid voor CF7 webhook)
- **Converteer naar Offerte** action
- **Meer info nodig** action → stuurt email naar lead

### 2. Quote Management
- Quote builder met items (Product of Custom)
- Auto-berekening van totalen (QuoteCalculationService)
- Stock reservering bij verzenden
- **Verstuur Offerte** action
- **Goedkeuren** action
- **Genereer PDF** action
- **Converteer naar Project** action (met optionele email)
- Auto-release stock bij afwijzing

### 3. Project Management
- Project creation vanaf Quote
- Invoicing types: 50/50, 30/70, of 100% bij oplevering
- Multiple invoice management
- Expected completion date tracking

### 4. Invoice Management
- Linked aan Project
- **Genereer PDF** action
- **Verstuur naar Moneybird** action
- Support voor split invoicing (50/50, 30/70)

### 5. Email Notifications System
**Database-Driven Templates:**
- Templates opgeslagen in `email_templates` tabel
- Placeholder systeem: `{{variable_name}}`
- HTML rendering engine (converteert `\n` naar `<p>`, bullets naar `<ul><li>`, etc.)
- Beheerbaar via `/admin/email-templates`

**4 Notificaties:**
1. **QuoteSentNotification** (intern) - Wanneer offerte verzonden wordt
2. **QuoteApprovedNotification** (intern) - Wanneer klant offerte goedkeurt
3. **InfoRequiredNotification** (klant) - Request meer informatie van lead
4. **OrderConfirmationNotification** (klant) - Bevestiging bij project start

**Email Branding:**
- Custom Schroeffundering Holland logo
- Custom kleuren (blue #1e40af, orange #f97316)
- Informele toon (je/jouw)

**Testing:**
```bash
php artisan test:emails all
php artisan queue:work --stop-when-empty
# Check Mailpit: https://sfh.ddev.site:8026
```

### 6. Moneybird Integration
- **Bidirectional sync** via MoneybirdService
- **Company → Moneybird Contact** sync
- **Invoice → Moneybird** verzenden
- **Import all contacts** from Moneybird

### 7. PDF Generation
- Professional Quote PDFs (Nederlandse template)
- Professional Invoice PDFs (Nederlandse template)
- PdfService voor DomPDF integratie
- Storage in `storage/app/quotes/` en `storage/app/invoices/`

### 8. Stock Management
- Product voorraad tracking
- Auto-reserve bij quote versturen
- Auto-release bij quote afwijzen
- Low stock warnings in dashboard

## Filament v4 Specifieke Implementatie

### CRITICAL - Namespace Verschillen

**Resources (met Schema parameter):**
```php
use Filament\Schemas\Components\Section;  // ⚠️ Section uit Schemas!
use Filament\Forms\Components\TextInput;  // ✅ Andere components uit Forms!
use Filament\Schemas\Schema;

public static function form(Schema $schema): Schema  // Schema parameter!
{
    return $schema->components([
        Section::make()->schema([
            TextInput::make('name'),
        ]),
    ]);
}
```

**RelationManagers (met Schema parameter):**
```php
use Filament\Schemas\Components\Section;      // Alles uit Schemas!
use Filament\Schemas\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
```

**Custom Pages/Actions (met Form parameter):**
```php
use Filament\Forms\Components\Section;        // Alles uit Forms!
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
```

### Components die NIET bestaan in Filament v4
- ❌ `Grid::make()` - gebruik `->columns()` op schema/section level
- ❌ `Group::make()` - gebruik `->columns()` of `Section::make()`

## Project Structuur

```
app/
├── Actions/              # (reserved voor toekomstige Actions)
├── Console/Commands/     # Artisan commands
│   └── TestEmailNotifications.php
├── Enums/               # 9 enums voor statuses
├── Filament/
│   ├── Resources/       # 7 resources
│   │   ├── Companies/
│   │   ├── EmailTemplates/  # Email template management
│   │   ├── Invoices/
│   │   ├── Leads/
│   │   │   └── Actions/     # ConvertToQuoteAction, RequestMoreInfoAction
│   │   ├── Products/
│   │   ├── Projects/
│   │   │   └── Actions/     # ConvertToProjectAction (with email toggle)
│   │   └── Quotes/
│   │       └── Actions/     # SendQuoteAction, ApproveQuoteAction, etc.
│   └── Pages/           # Dashboard, Settings
├── Models/              # 9 Eloquent models
│   ├── Company.php      # Notifiable
│   ├── Lead.php         # Notifiable
│   ├── EmailTemplate.php # render(), renderToHtml()
│   └── ...
├── Notifications/       # 4 ShouldQueue notifications
│   ├── InfoRequiredNotification.php
│   ├── OrderConfirmationNotification.php
│   ├── QuoteApprovedNotification.php
│   └── QuoteSentNotification.php
├── Observers/           # 2 observers
│   ├── QuoteObserver.php
│   └── QuoteItemObserver.php
├── Services/            # 3 services
│   ├── MoneybirdService.php
│   ├── PdfService.php
│   └── QuoteCalculationService.php
└── Settings/
    └── GeneralSettings.php
```

## Ontwikkeling Workflow

### Complete Customer Journey
1. **Lead binnenkomt** (handmatig of straks via CF7 webhook)
2. **Lead → Quote** via "Converteer naar Offerte" action
3. Quote opstellen met items (Product of Custom)
4. **Quote versturen** → Stock wordt gereserveerd, QuoteSentNotification
5. Klant keurt goed → **Goedkeuren** action → QuoteApprovedNotification
6. **Quote → Project** via action → Optional OrderConfirmationNotification
7. Project invoices aanmaken (automatisch bij conversie)
8. **Invoice → Moneybird** versturen via action

### Email Flow
1. Lead heeft meer info nodig → **RequestMoreInfoAction** → InfoRequiredNotification
2. Quote verzonden → QuoteSentNotification (intern team)
3. Quote goedgekeurd → QuoteApprovedNotification (intern team)
4. Project gestart → OrderConfirmationNotification (klant, optional)

## Testing

### Unit Tests
```bash
ddev exec php artisan test
# 60 tests, 120 assertions - All PASSING ✅
```

### Email Testing
```bash
# Test specifieke email
ddev exec "php artisan test:emails info_required <<< 'test@example.com'"

# Test alle emails
ddev exec "php artisan test:emails all <<< 'test@example.com'"

# Process queue
ddev exec "php artisan queue:work --stop-when-empty"

# Check Mailpit
open https://sfh.ddev.site:8026
```

## Common Commands

```bash
# Start development
ddev start

# Fresh install
ddev exec php artisan migrate:fresh --seed

# Run tests
ddev exec php artisan test

# Queue worker (for emails)
ddev exec php artisan queue:work

# Clear caches
ddev exec php artisan optimize:clear
```

## Belangrijke Patterns

### EmailTemplate Model
```php
// Render template with variables
$rendered = $template->render([
    'klant_naam' => 'John Doe',
    'totaal' => '€1.000,00',
]);
// Returns: ['subject' => '...', 'content' => '...']

// Render to HTML (with <ul>, <ol>, <p> tags)
$rendered = $template->renderToHtml([...]);
```

### Notifications
```php
// Send notification
$lead->notify(new InfoRequiredNotification($lead, $message));

// All notifications implement ShouldQueue
// So they're sent asynchronously
```

### Quote Calculation
```php
// Automatic via QuoteObserver + QuoteItemObserver
// Triggered on:
// - New quote creation
// - Quote item add/update/delete
// - Manual recalculate

// Service method:
app(QuoteCalculationService::class)->calculate($quote);
```

## Wat nog NIET geïmplementeerd is

1. **CF7 Webhook** - API endpoint voor website formulier
2. **Activity Logging** - Spatie Activity Log is geïnstalleerd maar niet actief
3. **Permissions & Roles** - Filament Shield is geïnstalleerd maar niet geconfigureerd
4. **Stock Consumption** - Bij project completion (nu alleen reservering)
5. **Distance Calculation** - Google Maps API voor afstandsberekening

## Skills & Agents

Voor dit project zijn 3 skills beschikbaar:

### 1. filament-dev
Gebruik voor:
- Nieuwe Filament resources maken
- Actions en bulk actions toevoegen
- Widgets bouwen
- RelationManagers implementeren
- Filament v4 compatibility issues oplossen

### 2. laravel-boost
Gebruik voor:
- Service classes bouwen
- API endpoints maken
- Jobs & Queues implementeren
- Performance optimalisatie
- Laravel best practices

### 3. php-test-writer
Gebruik voor:
- Pest tests schrijven
- Feature tests voor workflows
- Unit tests voor models/services
- Test coverage verbeteren

## Belangrijke Aandachtspunten

### Filament v4 Compatibility
- **Resources:** Section uit `Filament\Schemas\Components\Section`
- **RelationManagers:** ALLES uit `Filament\Schemas\Components\*`
- **Geen Grid/Group** - gebruik `->columns()` instead

### Email Templates
- Gebruik **informele toon** (je/jouw)
- **Geen** handmatige greetings/salutations in template content (Laravel voegt die toe)
- Test altijd via Mailpit

### Queue Processing
- Alle emails zijn **queued** (ShouldQueue)
- Run `queue:work` om emails daadwerkelijk te versturen
- Check Mailpit voor resultaat

### Moneybird Sync
- Altijd checken of Moneybird API credentials ingesteld zijn
- Bidirectional: export Companies, import from Moneybird
- Send Invoices to Moneybird

## Volgende Features (Optioneel)

1. **CF7 Webhook Endpoint** - Voor automatische lead creation vanaf website
2. **Activity Logging** - Track alle user actions
3. **Permissions System** - Role-based access control
4. **Stock Consumption** - Auto-consume bij project completion
5. **Email Attachments** - Quote PDF als bijlage bij OrderConfirmationNotification
6. **SMS Notifications** - Via Twilio of MessageBird
7. **Calendar Integration** - Voor project planning
8. **Client Portal** - Voor klanten om quotes/invoices te bekijken

---

**OpenCode Gebruik:**
- Gebruik `filament-dev` skill voor Filament-gerelateerde taken
- Gebruik `laravel-boost` skill voor Laravel backend werk
- Gebruik `php-test-writer` skill voor test coverage
- Raadpleeg altijd dit document voor project context
