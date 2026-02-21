# Schroeffundering Holland CRM - Project Agent

Dit is de project-specifieke agent voor het Schroeffundering Holland CRM systeem.

## Wanneer deze agent te gebruiken

Gebruik deze agent wanneer je:
- Vragen hebt over hoe het systeem werkt
- Een feature wilt toevoegen aan de CRM
- Bugs moet fixen
- De workflow wilt begrijpen
- Email templates wilt aanpassen
- Filament resources wilt uitbreiden

## Project Kennis

### Database Schema
- 9 hoofdtabellen: companies, leads, quotes, quote_items, projects, invoices, products, stock_reservations, email_templates
- Circular foreign keys: Lead ← Quote ← Project (opgelost met nullable FKs)
- Polymorphic: QuoteItem kan Product of Custom zijn

### Core Services
1. **QuoteCalculationService** - Auto-calculate quote totalen
2. **MoneybirdService** - Sync met Moneybird accounting
3. **PdfService** - Generate PDFs voor quotes en invoices

### Email System
- Database-driven templates in `email_templates` tabel
- `EmailTemplate` model met `render()` en `renderToHtml()` methods
- 4 notificaties: QuoteSent, QuoteApproved, InfoRequired, OrderConfirmation
- Alle notificaties zijn queued (ShouldQueue)
- Mailpit voor testing: https://sfh.ddev.site:8026

### Filament v4 Specifics
**CRITICAL:** Dit project gebruikt Filament v4.7.1!

**Resources (Schema parameter):**
- Section komt uit `Filament\Schemas\Components\Section`
- Andere components komen uit `Filament\Forms\Components\*`

**RelationManagers:**
- ALLES komt uit `Filament\Schemas\Components\*`
- Get/Set uit `Filament\Schemas\Components\Utilities\*`

**Geen Grid/Group:**
- Gebruik `->columns()` op schema/section level

### Custom Actions
1. **Lead → ConvertToQuoteAction** - Maak quote van lead
2. **Lead → RequestMoreInfoAction** - Email naar klant voor info
3. **Quote → SendQuoteAction** - Verstuur quote + reserve stock
4. **Quote → ApproveQuoteAction** - Keur quote goed
5. **Quote → GeneratePdfAction** - Download quote PDF
6. **Quote → ConvertToProjectAction** - Maak project + optional email
7. **Company → SyncToMoneybirdAction** - Sync naar Moneybird
8. **Company → ImportAllFromMoneybirdAction** - Import contacts
9. **Invoice → GeneratePdfAction** - Download invoice PDF
10. **Invoice → SendToMoneybirdAction** - Verstuur naar Moneybird

### Observers
- **QuoteObserver** - Auto-calculate bij nieuwe quote, auto-release stock bij reject
- **QuoteItemObserver** - Recalculate quote bij item changes

### Testing Commands
```bash
# Run tests
ddev exec php artisan test

# Test emails
ddev exec "php artisan test:emails all <<< 'test@example.com'"
ddev exec "php artisan queue:work --stop-when-empty"

# Fresh install
ddev exec php artisan migrate:fresh --seed
```

### URLs
- Admin: https://sfh.ddev.site/admin (admin@sfh.nl / password)
- Mailpit: https://sfh.ddev.site:8026

## Skills om te gebruiken

- **filament-dev** - Voor Filament resources, actions, widgets
- **laravel-boost** - Voor services, jobs, API endpoints
- **php-test-writer** - Voor Pest tests

## Belangrijke Bestanden

### Models
- `app/Models/Company.php` - Klanten (Notifiable)
- `app/Models/Lead.php` - Leads (Notifiable)
- `app/Models/Quote.php` - Offertes
- `app/Models/Project.php` - Projecten
- `app/Models/Invoice.php` - Facturen
- `app/Models/Product.php` - Producten
- `app/Models/EmailTemplate.php` - Email templates

### Services
- `app/Services/QuoteCalculationService.php`
- `app/Services/MoneybirdService.php`
- `app/Services/PdfService.php`

### Notifications
- `app/Notifications/InfoRequiredNotification.php`
- `app/Notifications/OrderConfirmationNotification.php`
- `app/Notifications/QuoteApprovedNotification.php`
- `app/Notifications/QuoteSentNotification.php`

### Resources
- `app/Filament/Resources/Companies/CompanyResource.php`
- `app/Filament/Resources/Leads/LeadResource.php`
- `app/Filament/Resources/Quotes/QuoteResource.php`
- `app/Filament/Resources/Projects/ProjectResource.php`
- `app/Filament/Resources/Invoices/InvoiceResource.php`
- `app/Filament/Resources/Products/ProductResource.php`
- `app/Filament/Resources/EmailTemplates/EmailTemplateResource.php`

## Project Context

Lees altijd `.opencode/PROJECT_CONTEXT.md` voor volledige project documentatie.
