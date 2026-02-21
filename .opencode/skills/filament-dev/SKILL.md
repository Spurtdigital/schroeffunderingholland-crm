---
name: filament-dev
description: Expert in building Filament v4 admin panels with best practices and correct v4 component usage
license: MIT
compatibility: opencode
metadata:
  framework: filament
  version: v4.7.1
  language: php
  last_updated: 2026-02-15
---

## Wat ik doe

Ik help je met het ontwikkelen van Filament admin panels volgens best practices:

- **Resources maken**: Genereer complete Filament Resources met forms, tables en actions
- **Custom Pages**: Bouw custom pages met widgets en interactieve elementen
- **Relations**: Implementeer complex relationship management (HasMany, BelongsToMany, etc.)
- **Actions & Bulk Actions**: Creëer krachtige actions met modals en notifications
- **Widgets**: Ontwikkel stats widgets, chart widgets en custom widgets
- **Table Columns & Filters**: Implementeer geavanceerde columns, filters en searches
- **Form Components**: Gebruik alle Filament form components effectief (Select, Repeater, FileUpload, etc.)
- **Authorization**: Implementeer policies en gates voor Filament resources
- **Themes & Styling**: Pas Filament aan met custom themes en Tailwind

## Best Practices die ik volg

### Resource Structuur
- Gebruik Resource classes voor CRUD operations
- Scheid concerns met apest RelationManagers
- Gebruik Enums voor statuses en constanten
- Implementeer observers voor model events

### Forms
```php
// Gebruik ->columnSpan() voor responsive layouts
Forms\Components\TextInput::make('name')
    ->required()
    ->maxLength(255)
    ->columnSpan(['lg' => 2]),

// Gebruik ->reactive() voor dynamische fields
Forms\Components\Select::make('type')
    ->options(['type1' => 'Type 1', 'type2' => 'Type 2'])
    ->reactive()
    ->afterStateUpdated(fn ($state, callable $set) => 
        $set('description', null)
    ),
```

### Tables
```php
// Gebruik searchable() en sortable() waar mogelijk
Tables\Columns\TextColumn::make('name')
    ->searchable()
    ->sortable(),

// Gebruik custom queries voor performance
Tables\Columns\TextColumn::make('posts_count')
    ->counts('posts')
    ->sortable(),
```

### Performance
- Gebruik eager loading in Resource::getEloquentQuery()
- Implementeer caching voor complexe queries
- Gebruik defer() voor lazy loading van modals
- Optimaliseer N+1 queries met ->with()

### Notifications
```php
Notification::make()
    ->title('Saved successfully')
    ->success()
    ->send();
```

## Wanneer mij te gebruiken

Gebruik deze skill wanneer je:
- Een nieuw Filament resource wilt maken
- Complex relationship management nodig hebt
- Custom actions of bulk actions wilt implementeren
- Widgets en dashboards wilt bouwen
- Filament moet customizen of uitbreiden
- Performance optimalisaties nodig hebt in Filament
- Vragen hebt over Filament best practices

## Project-Specifieke Voorbeelden (Schroeffundering Holland CRM)

### Custom Action met Email Notification
```php
// app/Filament/Resources/Leads/Actions/RequestMoreInfoAction.php
use Filament\Forms\Components\Textarea;
use App\Notifications\InfoRequiredNotification;

Action::make('requestMoreInfo')
    ->label('Meer info nodig')
    ->icon('heroicon-o-envelope')
    ->form([
        Textarea::make('message')
            ->label('Bericht')
            ->required()
            ->rows(5)
            ->placeholder('Welke informatie heb je nodig?'),
    ])
    ->action(function (Lead $record, array $data) {
        $record->notify(new InfoRequiredNotification($record, $data['message']));
        
        Notification::make()
            ->success()
            ->title('Email verzonden')
            ->body('De lead ontvangt een email met je verzoek.')
            ->send();
    });
```

### Resource met Notifiable Model
```php
// app/Models/Lead.php
use Illuminate\Notifications\Notifiable;

class Lead extends Model
{
    use Notifiable;
    
    public function routeNotificationForMail(): string
    {
        return $this->email;
    }
}
```

### Action met Toggle voor Optional Email
```php
// ConvertToProjectAction.php
->form([
    Toggle::make('send_confirmation')
        ->label('Opdrachtbevestiging versturen naar klant')
        ->default(true)
        ->inline(false),
])
->action(function (Quote $record, array $data) {
    $project = $record->convertToProject();
    
    if ($data['send_confirmation']) {
        $project->company->notify(
            new OrderConfirmationNotification($project)
        );
    }
});
```

## Belangrijke Filament Concepten

### Resources
- Gebruik `php artisan make:filament-resource ModelName` voor genereren
- Implementeer `getPages()` voor custom page routes
- Gebruik `getRelations()` voor RelationManagers

### Actions
- Header Actions: voor page-level acties
- Table Actions: voor row-level acties
- Bulk Actions: voor multiple row selecties
- Gebruik `Action::make()` voor custom logic

### Widgets
- StatsOverview: voor key metrics
- ChartWidget: voor grafieken
- Custom widgets: voor complexe UI

## Filament v4 Critical Compatibility Guide

⚠️ **IMPORTANT:** Filament v4 heeft verschillende namespaces dan v3! Gebruik ALTIJD de juiste imports.

### Correct Namespace Usage per Context

#### 1. **Resources (met Schema parameter)**
```php
// ✅ CORRECT imports voor Resource forms (LET OP: Schema parameter maar Form Components!)
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;  // ⚠️ Section komt uit Schemas namespace!
use Filament\Schemas\Schema;

class EmailTemplateResource extends Resource
{
    public static function form(Schema $schema): Schema  // ⚠️ Schema parameter, NIET Form!
    {
        return $schema
            ->components([
                Section::make('Details')  // ⚠️ Section uit Schemas namespace
                    ->schema([
                        TextInput::make('name'),  // ✅ Maar TextInput uit Forms namespace
                        Toggle::make('active'),   // ✅ Toggle uit Forms namespace
                    ]),
            ]);
    }
}
```

**CRITICAL:** In Resources:
- Parameter type is `Schema` (niet `Form`)
- `Section` komt uit `Filament\Schemas\Components\Section`
- Alle andere form components komen uit `Filament\Forms\Components\*`
- Dit is anders dan RelationManagers!

#### 2. **Custom Pages/Actions (met Form parameter)**
```php
// ✅ CORRECT imports voor Forms (Form parameter)
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;

public function form(Form $form): Form
{
    return $form
        ->schema([
            Section::make('Details')
                ->schema([
                    TextInput::make('name')
                        ->live()
                        ->afterStateUpdated(fn ($state, Set $set, Get $get) => 
                            $set('slug', Str::slug($state))
                        ),
                ]),
        ]);
}
```

#### 3. **RelationManagers (met Schema parameter)**
```php
// ✅ CORRECT imports voor RelationManagers
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\TextInput;
use Filament\Schemas\Components\Select;

public function form(Schema $schema): Schema
{
    return $schema
        ->columns(3)  // ✅ Set columns on schema level
        ->components([
            Section::make('Details')
                ->schema([
                    TextInput::make('name')
                        ->columnSpan(1)  // ✅ Control individual widths
                        ->live()
                        ->afterStateUpdated(fn ($state, Set $set, Get $get) => 
                            $set('slug', Str::slug($state))
                        ),
                ]),
        ]);
}
```

### Components That DON'T Exist in Filament v4

❌ **NEVER use these components:**
```php
// ❌ WRONG - These components don't exist in Filament v4!
use Filament\Forms\Components\Grid;  // DOESN'T EXIST
use Filament\Forms\Components\Group; // DOESN'T EXIST
use Filament\Schemas\Components\Grid; // DOESN'T EXIST
use Filament\Schemas\Components\Group; // DOESN'T EXIST

// Example of WRONG code:
Grid::make(3)  // ❌ ERROR! Component doesn't exist
    ->schema([...]);
```

✅ **Instead use:**
```php
// ✅ CORRECT - Use columns() on schema/section level
$schema->columns(3)
    ->components([
        TextInput::make('field1')->columnSpan(1),
        TextInput::make('field2')->columnSpan(2),
    ]);

// OR use on Section level
Section::make('Details')
    ->columns(3)
    ->schema([
        TextInput::make('field1')->columnSpan(1),
    ]);
```

### Common Filament v4 Errors & Solutions

#### Error 1: Grid/Group Not Found
```
Class "Filament\Forms\Components\Grid" not found
Class "Filament\Schemas\Components\Grid" not found
```

**Solution:** Use `->columns()` instead:
```php
// ❌ WRONG
Grid::make(3)->schema([...])

// ✅ CORRECT
$schema->columns(3)->components([...])
// OR
Section::make()->columns(3)->schema([...])
```

#### Error 2: Wrong Get/Set Type in RelationManagers
```
TypeError: Argument #1 ($get) must be of type Filament\Forms\Get, 
Filament\Schemas\Components\Utilities\Get given
```

**Solution:** Use correct imports for Schemas context:
```php
// ❌ WRONG (in RelationManagers)
use Filament\Forms\Get;
use Filament\Forms\Set;

// ✅ CORRECT (in RelationManagers)
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
```

#### Error 3: Section Wrong Namespace in Resources
```
Class "Filament\Forms\Components\Section" not found in Schema context
```

**Solution:** In Resources, Section comes from Schemas but other components from Forms:
```php
// ❌ WRONG (in Resources with Schema parameter)
use Filament\Forms\Components\Section;

// ✅ CORRECT (in Resources with Schema parameter)
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;  // Other components still from Forms!
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;

class MyResource extends Resource
{
    public static function form(Schema $schema): Schema  // Schema parameter!
    {
        return $schema
            ->components([
                Section::make('Details')  // From Schemas
                    ->schema([
                        TextInput::make('name'),  // From Forms
                    ]),
            ]);
    }
}
```

### Quick Reference: Context-Based Imports

| Context | Parameter Type | Section | Form Components | Get/Set |
|---------|---------------|---------|-----------------|---------|
| **Resources** | `Schema $schema` | `Filament\Schemas\Components\Section` ⚠️ | `Filament\Forms\Components\*` ✅ | N/A |
| **Pages/Actions** | `Form $form` | `Filament\Forms\Components\Section` | `Filament\Forms\Components\*` | `Filament\Forms\Get/Set` |
| **RelationManagers** | `Schema $schema` | `Filament\Schemas\Components\Section` | `Filament\Schemas\Components\*` ⚠️ | `Filament\Schemas\Components\Utilities\Get/Set` |

**Key Rule:** 
- If method parameter is `Schema` → Check if it's a Resource or RelationManager
- **Resources:** Section from Schemas, but other components from Forms
- **RelationManagers:** ALL components from Schemas
- **Pages/Actions:** ALL components from Forms

### Testing Filament v4 Code

Always test your Filament code to catch compatibility errors early:

```php
// Feature test for RelationManager
test('quote items relation manager form loads', function () {
    $quote = Quote::factory()->create();
    
    livewire(ItemsRelationManager::class, [
        'owner' => $quote,
        'pageClass' => EditQuote::class,
    ])
        ->assertSuccessful()
        ->assertSee('Nieuwe regel');
});
```

### Pre-commit Hook for Filament v4

Use this pre-commit hook to catch common Filament v4 errors:

```bash
#!/bin/bash

# Check for Grid/Group usage (doesn't exist in Filament v4)
if git diff --cached --name-only | grep -E '\.php$' | xargs grep -n "Forms\\Components\\Grid\|Forms\\Components\\Group\|Schemas\\Components\\Grid\|Schemas\\Components\\Group" 2>/dev/null; then
    echo "❌ ERROR: Grid/Group components don't exist in Filament v4!"
    echo "   Use ->columns() on schema/section level instead"
    exit 1
fi

# Check for wrong Get/Set imports in RelationManagers
if git diff --cached --name-only | grep "RelationManagers" | xargs grep -l "use Filament\\\\Forms\\\\Get\|use Filament\\\\Forms\\\\Set" 2>/dev/null; then
    echo "❌ ERROR: Wrong Get/Set imports in RelationManager!"
    echo "   Use: use Filament\\Schemas\\Components\\Utilities\\Get;"
    echo "   Use: use Filament\\Schemas\\Components\\Utilities\\Set;"
    exit 1
fi

echo "✅ Filament v4 compatibility checks passed"
```

## Filament Versie Specifieke Features

### Filament v5 (Latest - v5.2.1)
- **Callout Component**: Nieuwe component voor belangrijke berichten en alerts
- **Stacked Mobile Tables**: Tables stacken automatisch op mobiele apparaten voor betere UX
- **Nested Resources**: Diep geneste resources met URL hierarchy en breadcrumbs
- **Tables met Custom Data**: Tables kunnen nu gevuld worden met arrays/APIs (niet alleen Eloquent)
- **TipTap Rich Editor**: Nieuwe rich editor met custom blocks, merge tags, en private image URLs
- **Tailwind CSS v4**: Vereenvoudigd styling systeem voor eenvoudigere theme customization
- **Verbeterde Performance**: 2-3× snellere rendering, partial component re-rendering
- **Deferred Filters**: Chart widgets met deferred filter support
- **Overlay Parent Actions**: Gestapelde action modals met `overlayParentActions()`
- **Tenant Switching**: Kan nu uitgeschakeld worden waar nodig

### Filament v4 (Stable)
- Multiple panels support
- Nested resources met breadcrumbs
- Verbeterde table performance
- Custom data sources voor tables
- TipTap based rich editor

### Filament v3
- Gebruik van `->live()` in plaats van `->reactive()`
- Nieuwe table builder met verbeterde performance
- Enhanced form builder met betere UX
- Improved actions met modals en slides

## Nieuwe v5 Features in Detail

### Callout Component
```php
use Filament\Infolists\Components\Callout;

Callout::make()
    ->content('Dit is een belangrijke melding!')
    ->color('warning')
    ->icon('heroicon-o-exclamation-triangle'),
```

### Stacked Mobile Tables
Tables stacken nu automatisch op mobiele apparaten zonder extra configuratie.

### Nested Resources
```php
// Define parent-child relationship in URL
public static function getUrl(): string
{
    return route('filament.resources.courses.lessons.index', [
        'parent' => $this->course,
    ]);
}
```

### Tables met Custom Data
```php
use Filament\Tables\Table;

public function table(Table $table): Table
{
    return $table
        ->query(
            // Niet langer alleen Eloquent! 
            // Kan nu custom arrays/API data zijn
        )
        ->columns([...]);
}
```
