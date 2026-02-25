<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'key' => 'quote_sent',
                'name' => 'Offerte verzonden',
                'subject' => 'Offerte verzonden - {{offerte_nummer}}',
                'description' => 'Interne notificatie wanneer een offerte naar een klant is verstuurd',
                'available_variables' => [
                    'offerte_nummer' => 'Het offerte nummer',
                    'klant_naam' => 'Naam van het bedrijf',
                    'contactpersoon' => 'Naam van de contactpersoon',
                    'email' => 'Email adres',
                    'telefoon' => 'Telefoonnummer',
                    'subtotaal' => 'Bedrag excl. BTW',
                    'btw' => 'BTW bedrag',
                    'totaal' => 'Totaalbedrag incl. BTW',
                    'geldig_tot' => 'Geldig tot datum',
                    'status' => 'Offerte status',
                ],
                'content' => 'Een nieuwe offerte is verzonden.

📋 Offerte: {{offerte_nummer}}
Klant: {{klant_naam}}
Totaal: {{totaal}}
Geldig tot: {{geldig_tot}}',
                'active' => true,
            ],
            [
                'key' => 'quote_approved',
                'name' => 'Offerte goedgekeurd',
                'subject' => 'Offerte goedgekeurd - {{offerte_nummer}}',
                'description' => 'Interne notificatie wanneer een klant een offerte goedkeurt',
                'available_variables' => [
                    'team_naam' => 'Naam van team lid dat de notificatie ontvangt',
                    'offerte_nummer' => 'Het offerte nummer',
                    'klant_naam' => 'Naam van het bedrijf',
                    'contactpersoon' => 'Naam van de contactpersoon',
                    'email' => 'Email adres',
                    'telefoon' => 'Telefoonnummer',
                    'totaal' => 'Totaalbedrag incl. BTW',
                    'goedgekeurd_op' => 'Datum/tijd goedkeuring',
                ],
                'content' => 'Fantastisch nieuws, {{team_naam}}!

Een offerte is goedgekeurd door de klant.

📋 Offerte details
Nummer: {{offerte_nummer}}
Klant: {{klant_naam}}
Contactpersoon: {{contactpersoon}}

💰 Orderwaarde
Totaal: {{totaal}}
Goedgekeurd op: {{goedgekeurd_op}}

⚡ Volgende stappen:
1. Converteer naar project
2. Plan werkzaamheden
3. Verstuur opdrachtbevestiging
4. Genereer eerste factuur',
                'active' => true,
            ],
            [
                'key' => 'info_required',
                'name' => 'Extra informatie nodig',
                'subject' => 'Extra informatie nodig - {{bedrijf_naam}}',
                'description' => 'Email naar lead/klant wanneer meer informatie nodig is voor een offerte',
                'available_variables' => [
                    'klant_naam' => 'Naam contactpersoon',
                    'bedrijf_naam' => 'Naam bedrijf',
                    'bericht' => 'Custom message met benodigde informatie',
                    'bedrijf_email' => 'Jullie contactemail',
                    'bedrijf_telefoon' => 'Jullie telefoonnummer',
                ],
                'content' => 'Bedankt voor je interesse in Schroeffundering Holland.

Voor een nauwkeurige offerte hebben we graag nog de volgende informatie:

{{bericht}}

Je kunt deze informatie sturen naar {{bedrijf_email}} of bel ons op {{bedrijf_telefoon}}.',
                'active' => true,
            ],
            [
                'key' => 'order_confirmation',
                'name' => 'Opdrachtbevestiging',
                'subject' => 'Opdrachtbevestiging - Project {{project_nummer}}',
                'description' => 'Bevestiging naar klant wanneer een project start (quote wordt geconverteerd)',
                'available_variables' => [
                    'klant_naam' => 'Naam contactpersoon',
                    'bedrijf_naam' => 'Naam bedrijf',
                    'project_nummer' => 'Project nummer',
                    'offerte_nummer' => 'Offerte nummer',
                    'totaal' => 'Totaalbedrag incl. BTW',
                    'verwachte_oplevering' => 'Verwachte opleverdatum',
                    'facturatiemethode' => 'Uitleg facturatiemethode (50/50, 30/70, 100%)',
                    'eerste_factuur_info' => 'Info over eerste factuur indien van toepassing',
                ],
                'content' => 'Hartelijk dank voor je opdracht!

Hierbij bevestigen we de opdracht voor project {{project_nummer}} op basis van offerte {{offerte_nummer}}.

📋 Project Details
Project nummer: {{project_nummer}}
Totaalbedrag: {{totaal}}
Verwachte oplevering: {{verwachte_oplevering}}

💰 Facturering
{{facturatiemethode}}

{{eerste_factuur_info}}

⚡ Wat gebeurt er nu?
1. We plannen de werkzaamheden
2. Je ontvangt een planning
3. We starten met de uitvoering
4. Je ontvangt regelmatige updates

Heb je vragen? Neem gerust contact met ons op.',
                'active' => true,
            ],
            [
                'key' => 'project_completed',
                'name' => 'Project afgerond',
                'subject' => 'Project afgerond - {{project_nummer}}',
                'description' => 'Bevestigingsmail naar klant wanneer een project wordt afgerond, inclusief foto\'s als bijlage',
                'available_variables' => [
                    'klant_naam' => 'Naam contactpersoon (of bedrijfsnaam als fallback)',
                    'bedrijf_naam' => 'Naam bedrijf',
                    'project_nummer' => 'Project nummer',
                    'opleverdatum' => 'Datum van oplevering (dd-mm-yyyy)',
                    'aantal_fotos' => 'Aantal bijgevoegde foto\'s (0 = sectie verborgen)',
                    'review_url' => 'Link naar reviewpagina (leeg als niet ingesteld)',
                ],
                'content' => "Wij zijn blij u te informeren dat het project voor {{bedrijf_naam}} is afgerond!

📋 Project Details
Project nummer: {{project_nummer}}
Opleverdatum: {{opleverdatum}}

{{#aantal_fotos}}
In de bijlage vindt u {{aantal_fotos}} foto('s) van het opgeleverde werk, samen met het ondertekende opleverrapport.
{{/aantal_fotos}}

Hartelijk dank voor uw vertrouwen in Schroeffundering Holland.

Heeft u vragen of opmerkingen over het opgeleverde werk? Neem dan gerust contact met ons op.

Met vriendelijke groet,
Schroeffundering Holland",
                'active' => true,
            ],
        ];

        foreach ($templates as $template) {
            EmailTemplate::updateOrCreate(
                ['key' => $template['key']],
                $template
            );
        }
    }
}
