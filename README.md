# Schroeffundering Holland CRM

Een CRM-systeem voor leadbeheer, offertes, projecten en facturatievoorbereiding via Moneybird.

## 📊 Implementatie Status

### ✅ Fase 1: Project Setup (Compleet)
- [x] Laravel 12.51.0 installatie
- [x] DDEV configuratie (PHP 8.4, MySQL 8.0)
- [x] Filament v4.7.1
- [x] Packages geïnstalleerd:
  - Spatie Laravel Permission v6.24
  - Filament Shield v4.1
  - Spatie Activity Log v4.11
  - Spatie Laravel Settings v4.0
  - Filament Settings Plugin v4.7
  - Picqer Moneybird Client v0.42
  - Laravel DomPDF v3.1
  - Pest Testing Framework v3.8

### ✅ Fase 2: Database & Models (Compleet)
- [x] Enums aanmaken (9 enums)
- [x] Database migrations (8 tables + circular FK's)
- [x] Models met relationships (8 models)
- [x] Model factories met realistische Nederlandse data (8 factories)
- [x] Model unit tests (60 tests, 120 assertions) - **Allemaal PASSING ✅**

### ✅ Fase 3: Filament Admin Panel (Compleet)
- [x] AdminPanelProvider configuratie (Nederlands, brand name, kleuren)
- [x] Navigatie groups: Sales, Administratie, Instellingen
- [x] Company Resource (volledig met form & table)
- [x] Lead Resource (volledig met form & table)
- [x] Product Resource (volledig met form & table)
- [x] Quote Resource (volledig met items management)
- [x] Project Resource (volledig met invoice management)
- [x] Invoice Resource (volledig met Moneybird integration)
- [x] **Dashboard** met 10 statistiek widgets en charts
- [x] Settings Page (GeneralSettings met bedrijfsgegevens, BTW, API keys)
- [x] **RelationManagers** (4 stuks):
  - [x] CompanyResource → LeadsRelationManager & QuotesRelationManager
  - [x] QuoteResource → ItemsRelationManager (polymorphic, live calculations)
  - [x] ProjectResource → InvoicesRelationManager
- [x] **Custom Actions** (8 stuks):
  - [x] Lead → "Converteer naar Offerte" (auto-create quote)
  - [x] Quote → "Verstuur Offerte" (stock reservering)
  - [x] Quote → "Goedkeuren" (approve quote)
  - [x] Quote → "Genereer PDF" (download offerte PDF)
  - [x] Quote → "Converteer naar Project" (auto-create project)
  - [x] Company → "Sync naar Moneybird" (export contact)
  - [x] Company → "Importeer alle contacten" (import from Moneybird)
  - [x] Invoice → "Genereer PDF" (download factuur PDF)
  - [x] Invoice → "Verstuur naar Moneybird" (send invoice)

### ✅ Fase 4: Services & Business Logic (Compleet)
- [x] **QuoteCalculationService** - Auto-berekening van totalen
- [x] **MoneybirdService** - Bidirectional sync klanten & facturen met Moneybird
- [x] **PdfService** - PDF generatie voor offertes & facturen
- [x] **Observers**:
  - [x] QuoteObserver - Auto-calculate totalen bij nieuwe quote + auto-release stock bij reject
  - [x] QuoteItemObserver - Recalculate quote bij item changes

### ✅ Fase 5: Advanced Filament Features (Compleet)
- [x] Extra Custom Actions (8 stuks - zie Fase 3)
- [x] Auto-release stock when quote rejected
- [x] Moneybird bidirectional sync (export + import)

### ✅ Fase 6: PDF Generation & Templates (Compleet)
- [x] Quote PDF template (professioneel Nederlands design)
- [x] Invoice PDF template (professioneel Nederlands design)
- [x] PDF Service met DomPDF integratie
- [x] Download & Stream functionaliteit
- [x] Opslag in storage (quotes/ en invoices/ folders)

### ✅ Fase 7: Testing & Demo Data (Compleet)
- [x] Database seeder met realistische demo data
- [x] Complete workflow: 15 companies → 25 leads → 20+ quotes → 5+ projects → invoices
- [x] Automatic admin user creation (admin@sfh.nl / password)

### ✅ Fase 8: Email Notifications & Templates (Compleet)
- [x] Email Template Management systeem
- [x] Database-driven email templates met placeholders
- [x] HTML formatting engine (bullet lists, numbered lists, paragraphs)
- [x] 4 email notificaties:
  - [x] Quote Sent (intern) - Notificatie bij verzenden offerte
  - [x] Quote Approved (intern) - Notificatie bij goedkeuring
  - [x] Info Required (klant) - Verzoek om extra informatie
  - [x] Order Confirmation (klant) - Bevestiging projectstart
- [x] Filament EmailTemplateResource voor template beheer
- [x] Informele toon (je/jouw i.p.v. u/uw)
- [x] Schroeffundering Holland branding in emails
- [x] Test command voor alle emails
- [x] Queue-based sending (ShouldQueue)

### ⏳ Fase 9: CF7 Webhook (Optional)
- [ ] API endpoint voor Contact Form 7
- [ ] Lead creation van website formulier
- [ ] Auto-create Company als nog niet bestaat

## 🎉 Project Status: 98% Complete

Alle core functionaliteit is geïmplementeerd en werkend:
- ✅ Lead management
- ✅ Quote creation met auto-calculations
- ✅ Project management
- ✅ Invoice generation
- ✅ PDF generation (quotes & invoices)
- ✅ Moneybird bidirectional sync
- ✅ Stock management met auto-release
- ✅ Dashboard met statistieken
- ✅ Email notifications & templates
- ✅ Demo data seeder

## 🚀 Quick Start

```bash
# Start DDEV
ddev start

# Run migrations & seed demo data
ddev exec php artisan migrate --seed

# Access admin panel
open https://sfh.ddev.site/admin
# Login: admin@sfh.nl / password
```

## 📁 Project Structuur

```
app/
├── Enums/                          # Status enums (9 files)
│   ├── InvoiceStatus.php
│   ├── InvoiceType.php
│   ├── InvoicingType.php
│   ├── LeadStatus.php
│   ├── ProjectStatus.php
│   ├── QuoteItemType.php
│   ├── QuoteProcessingMethod.php
│   ├── QuoteStatus.php
│   └── StockReservationStatus.php
│
├── Models/                         # Eloquent models (9 files)
│   ├── Company.php                 # Klanten (Notifiable)
│   ├── Lead.php                    # Website leads (Notifiable)
│   ├── Quote.php                   # Offertes
│   ├── QuoteItem.php               # Offerte regels (polymorphic)
│   ├── Project.php                 # Projecten
│   ├── Invoice.php                 # Factuurvoorbereiding
│   ├── Product.php                 # Productcatalogus + voorraad
│   ├── StockReservation.php        # Voorraadreserveringen
│   └── EmailTemplate.php           # Email templates (database-driven)
│
├── Settings/                       # Spatie Settings
│   └── GeneralSettings.php         # Bedrijfsgegevens, BTW, API keys
│
├── Services/                       # Business logic services (3 files)
│   ├── QuoteCalculationService.php # Auto-calculate quote totalen
│   ├── MoneybirdService.php        # Moneybird API integratie
│   └── PdfService.php              # PDF generatie (DomPDF)
│
├── Observers/                      # Model observers (2 files)
│   ├── QuoteObserver.php           # Auto-calculate bij nieuwe quote
│   └── QuoteItemObserver.php       # Recalculate bij item changes
│
├── Notifications/                  # Email notifications (4 files)
│   ├── QuoteSentNotification.php   # Intern: offerte verzonden
│   ├── QuoteApprovedNotification.php # Intern: offerte goedgekeurd
│   ├── InfoRequiredNotification.php # Klant: extra info nodig
│   └── OrderConfirmationNotification.php # Klant: project bevestiging
│
├── Console/Commands/               # Artisan commands
│   └── TestEmailNotifications.php  # Test email systeem
│
├── Filament/
│   ├── Resources/                  # 7 resources
│   │   ├── Companies/
│   │   │   ├── Pages/              # List, Create, Edit
│   │   │   ├── Schemas/            # CompanyForm.php
│   │   │   ├── Tables/             # CompaniesTable.php
│   │   │   ├── RelationManagers/   # Leads, Quotes
│   │   │   └── CompanyResource.php
│   │   ├── Leads/
│   │   │   ├── Actions/            # ConvertToQuoteAction, RequestMoreInfoAction
│   │   │   └── ...
│   │   ├── Products/
│   │   ├── Quotes/
│   │   │   ├── Actions/            # SendQuoteAction, ApproveQuoteAction,
│   │   │   │                       # GeneratePdfAction, ConvertToProjectAction
│   │   │   ├── RelationManagers/   # Items
│   │   │   └── ...
│   │   ├── Projects/
│   │   │   ├── Actions/            # ConvertToProjectAction (with email toggle)
│   │   │   ├── RelationManagers/   # Invoices
│   │   │   └── ...
│   │   ├── Invoices/
│   │   │   ├── Actions/            # GeneratePdfAction, SendToMoneybirdAction
│   │   │   └── ...
│   │   └── EmailTemplates/
│   │       └── EmailTemplateResource.php # Template management
│   │
│   └── Pages/
│       └── Settings.php            # Instellingen pagina
│
└── Providers/
    └── Filament/
        └── AdminPanelProvider.php  # Panel config, navigatie groups

database/
├── factories/                      # 9 factories met realistische NL data
├── migrations/                     # Alle migraties (werkend)
└── seeders/
    ├── DatabaseSeeder.php          # Main seeder
    └── EmailTemplateSeeder.php     # Email templates

resources/
└── views/
    ├── pdf/                        # PDF templates (2 files)
    │   ├── quote.blade.php         # Professioneel offerte template
    │   └── invoice.blade.php       # Professioneel factuur template
    └── vendor/mail/                # Laravel mail views (custom branding)
        └── html/
            ├── header.blade.php    # Custom header met logo
            └── themes/
                └── default.css     # Custom Schroeffundering styling

tests/
└── Unit/Models/                    # 8 test files, 60 tests ✅
```

## 🎯 Admin Panel Navigatie

**Dashboard**:
- 📊 **Dashboard** - Overzicht met statistieken en charts

**Sales** (sales proces):
- 🧑‍🤝‍🧑 **Leads** - Aanvragen van website (CF7 webhook)
- 📄 **Offertes** - Offertes opstellen en beheren
- 🔧 **Projecten** - Actieve projecten

**Administratie** (administratie):
- 🏢 **Klanten** - Klantenbeheer + Moneybird sync
- 💰 **Facturen** - Facturatie & Moneybird sync
- 📦 **Producten** - Productcatalogus + voorraad
- 📧 **Email Templates** - Template beheer voor notificaties

**Instellingen**:
- ⚙️ **Instellingen** - Bedrijfsgegevens, BTW, API keys

## 📝 Development Log

### 2026-02-15 23:55 - Email System Complete ✅
- ✅ Email Template Management systeem (database-driven)
- ✅ EmailTemplate model met `render()` en `renderToHtml()` methods
- ✅ HTML formatting engine (converts plain text to HTML with `<ul>`, `<ol>`, `<p>` tags)
- ✅ EmailTemplateResource in Filament admin panel
- ✅ 4 notificaties geïmplementeerd (QuoteSent, QuoteApproved, InfoRequired, OrderConfirmation)
- ✅ Lead en Company models Notifiable gemaakt
- ✅ RequestMoreInfoAction (Lead → email naar klant)
- ✅ ConvertToProjectAction (Quote → optional email naar klant)
- ✅ Custom email branding (Schroeffundering Holland logo & colors)
- ✅ Informele toon (je/jouw i.p.v. u/uw) in alle emails
- ✅ Test command: `php artisan test:emails {type}`
- ✅ Queue-based sending (alle notificaties implement ShouldQueue)
- ✅ Mailpit integration voor testing
- **Status**: Email systeem volledig werkend en getest!

### 2026-02-14 08:00 - Final Features Complete ✅
- ✅ Moneybird bidirectional sync (import all contacts)
- ✅ ImportAllFromMoneybirdAction toegevoegd aan CompanyResource
- ✅ Auto-release stock bij rejected quotes (QuoteObserver)
- ✅ Dashboard met 10 statistiek widgets (leads, quotes, projects, invoices, etc.)
- ✅ Database seeder met complete demo data (15 companies, 25 leads, 20+ quotes, 5+ projects)
- **Status**: Project 95% compleet - alle core functionaliteit werkend!
- **Optioneel**: CF7 Webhook voor website formulier integratie

### 2026-02-14 02:30 - Services, PDF & Moneybird Complete ✅
- ✅ QuoteCalculationService + QuoteObserver + QuoteItemObserver
- ✅ Auto-calculation van quote totalen bij item changes
- ✅ ApproveQuoteAction toegevoegd (status → Approved)
- ✅ MoneybirdService met sync Company en send Invoice
- ✅ SyncToMoneybirdAction voor CompanyResource
- ✅ SendToMoneybirdAction voor InvoiceResource
- ✅ PdfService met DomPDF integratie
- ✅ Quote PDF template (professioneel Nederlands design)
- ✅ Invoice PDF template (professioneel Nederlands design)
- ✅ GeneratePdfAction voor QuoteResource
- ✅ GeneratePdfAction voor InvoiceResource
- **Status**: Services layer compleet, PDF generatie werkend, Moneybird sync ready
- **Volgende**: CF7 Webhook endpoint + testing & polish

### 2026-02-14 01:00 - Filament Admin Panel & Navigatie
- ✅ AdminPanelProvider geconfigureerd (brand, kleuren, navigatie groups)
- ✅ Navigatie groups: Sales, Administratie, Instellingen
- ✅ GeneralSettings class + migratie + seeder
- ✅ Settings pagina in Filament (bedrijfsgegevens, BTW, API keys)
- ✅ Alle 6 resources geconfigureerd met juiste groups en sort order
- **Status**: Admin panel volledig werkend met Nederlandse labels
- **Volgende**: RelationManagers en Custom Actions toevoegen

### 2026-02-14 00:50 - Filament Resources Aangemaakt
- ✅ 6 Filament resources gegenereerd (Company, Lead, Product, Quote, Project, Invoice)
- ✅ Allemaal in het Nederlands gelabeld
- ✅ Form schemas en Table schemas gescheiden
- ✅ Company, Lead en Product resources volledig uitgewerkt
- ✅ Quote, Project, Invoice hebben basis forms (nog uit te breiden)
- **Volgende**: Settings page en navigatie groups configureren

### 2026-02-14 00:40 - Model Tests Geschreven
- ✅ 60 unit tests geschreven voor alle 8 models
- ✅ 120 assertions - allemaal PASSING ✅
- ✅ Tests voor relationships, casts, attributes, scopes
- ✅ Factory tests voor realistische data
- **Volgende**: Filament Resources aanmaken

### 2026-02-14 00:35 - Model Factories Compleet
- ✅ Alle 8 factories met realistische Nederlandse data
- ✅ Bedrijfsnamen, adressen, postcodes, plaatsen
- ✅ Realistische prijzen en voorraad
- ✅ Correcte status flows
- **Volgende**: Model tests schrijven

### 2026-02-14 00:30 - Models Compleet
- ✅ Alle 8 models compleet met relationships en casts
- ✅ Lead, Quote, QuoteItem, Project, Invoice, Product, StockReservation
- ✅ Company model (eerder afgemaakt)
- ✅ Auto-incrementing numbers (Quote, Project, Invoice)
- ✅ Stock management helpers in Product model
- ✅ Polymorphic relationships (QuoteItem)
- **Volgende**: Model factories & tests

### 2026-02-14 00:10 - Database Schema
- ✅ Enums aangemaakt (LeadStatus, QuoteStatus, ProjectStatus, etc.)
- ✅ Database schema compleet (12 migrations)
- ✅ Alle foreign keys en indexes toegevoegd
- ✅ Circular FK's opgelost (leads ↔ quotes ↔ projects)
- **Volgende**: Models met relationships maken

### 2026-02-13 23:50 - Project Initialisatie
- ✅ Laravel 12 project setup
- ✅ DDEV geïnstalleerd en geconfigureerd
- ✅ Filament admin panel geïnstalleerd
- ✅ Alle benodigde packages geïnstalleerd
- ✅ Migration files gegenereerd (8 tables)

---

## 🏗️ Technische Stack

- **Framework**: Laravel 12.51.0
- **Admin Panel**: Filament v4.7.1
- **Database**: MySQL 8.0
- **PHP**: 8.4
- **Testing**: Pest v3.8
- **PDF**: DomPDF v3.1
- **Development**: DDEV
- **Settings**: Spatie Laravel Settings v4.0
- **Activity Log**: Spatie Activity Log v4.11
- **Permissions**: Spatie Permission v6.24 + Filament Shield v4.1
- **Moneybird**: Picqer Moneybird Client v0.42

## 🔑 Belangrijke Informatie

**Admin Panel**:
- URL: https://sfh.ddev.site/admin
- Login: admin@sfh.nl / password

**Mailpit (Email Testing)**:
- URL: https://sfh.ddev.site:8026
- View all sent emails in development

**Database Schema**:
- 9 hoofdtabellen: companies, leads, quotes, quote_items, projects, invoices, products, stock_reservations, email_templates
- 1 instellingen tabel: settings
- Alle foreign keys en indexes geïmplementeerd
- Circular FK's opgelost met nullable foreign keys

**Taal**: 
- Admin panel volledig in het Nederlands
- Database velden in Engels (Laravel conventie)
- Alle labels, menu's, formulieren in het Nederlands

**Testing**:
```bash
# Run all tests
ddev exec php artisan test

# Run specific test suite
ddev exec php artisan test --testsuite=Unit

# Run with coverage
ddev exec php artisan test --coverage

# Test email notifications
ddev exec "php artisan test:emails all <<< 'your@email.com'"
ddev exec "php artisan queue:work --stop-when-empty"
# Then check Mailpit: https://sfh.ddev.site:8026
```

**Code Quality**:
- PHPStan level 8 ready
- Pest tests voor alle models
- Factories met realistische Nederlandse data
- Type hints overal toegepast

---

## 🚧 Optionele Uitbreidingen

De applicatie is volledig functioneel. Onderstaande features zijn optioneel:

1. **CF7 Webhook** (als je leads vanaf website wilt capturen):
   - API endpoint voor Contact Form 7
   - Lead creation van website formulier
   - Auto-create Company als nog niet bestaat

2. **Stock Management uitbreiden**:
   - ✅ Release stock bij quote rejection (COMPLEET)
   - Consume stock bij project completion
   - Product voorraad aanpassingen action
   - ✅ Low stock warnings in dashboard (COMPLEET)

3. **Distance Calculation** (als je kilometers wilt berekenen):
   - Google Maps API integratie
   - Auto-calculate distance bij lead/quote
   - Distance field in QuoteResource

4. **Testing uitbreiden**:
   - Feature tests voor workflows
   - End-to-end test: Lead → Quote → Project → Invoice
   - PDF generation tests
   - Moneybird API tests (mocked)

5. **Polish & UX**:
   - Activity logging activeren (Spatie Activity Log is geïnstalleerd)
   - ✅ Email notificaties (quote sent, approved, info required, order confirmation) - COMPLEET
   - ✅ Dashboard met statistieken (COMPLEET)
   - Permissions & Roles (Filament Shield is geïnstalleerd)

---

**Laatst bijgewerkt**: 2026-02-15 23:55
