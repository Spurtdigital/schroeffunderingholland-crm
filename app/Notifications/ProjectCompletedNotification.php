<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
use App\Models\Project;
use App\Services\PdfService;
use App\Settings\GeneralSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class ProjectCompletedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Project $project,
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $template = EmailTemplate::getByKey('project_completed');

        if (! $template) {
            return $this->fallbackEmail($notifiable);
        }

        $company = $this->project->company;
        $photos = $this->project->getMedia('project-photos');
        $reviewUrl = app(GeneralSettings::class)->review_url;

        $rendered = $template->renderToHtml([
            'klant_naam' => $company->contact_name ?? 'relatie',
            'bedrijf_naam' => $company->name,
            'project_nummer' => $this->project->project_number,
            'opleverdatum' => $this->project->completion_date?->format('d-m-Y') ?? now()->format('d-m-Y'),
            'aantal_fotos' => $photos->count(),
            'review_url' => $reviewUrl ?? '',
        ]);

        $mail = (new MailMessage)
            ->subject($rendered['subject'])
            ->greeting('Dag '.($company->contact_name ?? 'relatie').',')
            ->line(new HtmlString($rendered['content']));

        if ($reviewUrl) {
            $mail->action('Laat een review achter', $reviewUrl);
        }

        // Voeg foto's toe als bijlagen
        foreach ($photos as $media) {
            $mail->attachData(
                file_get_contents($media->getPath()),
                $media->file_name,
                ['mime' => $media->mime_type]
            );
        }

        // Voeg opleverrapport PDF toe als bijlage
        $this->attachCompletionReport($mail);

        return $mail;
    }

    /**
     * Fallback email when template is not found.
     */
    private function fallbackEmail(object $notifiable): MailMessage
    {
        $company = $this->project->company;
        $photos = $this->project->getMedia('project-photos');
        $opleverdatum = $this->project->completion_date?->format('d-m-Y') ?? now()->format('d-m-Y');
        $reviewUrl = app(GeneralSettings::class)->review_url;

        $mail = (new MailMessage)
            ->subject('Project afgerond - '.$this->project->project_number)
            ->greeting('Dag '.($company->contact_name ?? 'relatie').',')
            ->line('Wij zijn blij u te informeren dat uw project is afgerond.')
            ->line('')
            ->line('**Projectnummer:** '.$this->project->project_number)
            ->line('**Bedrijf:** '.$company->name)
            ->line('**Opleverdatum:** '.$opleverdatum);

        if ($photos->count() > 0) {
            $mail->line('')
                ->line('In de bijlage vindt u de foto\'s van het opgeleverde werk en het ondertekende opleverrapport.');
        }

        $mail->line('')
            ->line('Hartelijk dank voor uw vertrouwen in Schroeffundering Holland.')
            ->line('Heeft u vragen over het project? Neem gerust contact met ons op.')
            ->salutation('Met vriendelijke groet,')
            ->salutation(config('mail.from.name', 'Schroeffundering Holland'));

        if ($reviewUrl) {
            $mail->action('Laat een review achter', $reviewUrl);
        }

        // Voeg foto's toe als bijlagen
        foreach ($photos as $media) {
            $mail->attachData(
                file_get_contents($media->getPath()),
                $media->file_name,
                ['mime' => $media->mime_type]
            );
        }

        // Voeg opleverrapport PDF toe als bijlage
        $this->attachCompletionReport($mail);

        return $mail;
    }

    /**
     * Voeg het opgeslagen opleverrapport PDF toe als bijlage.
     * Gebruikt de opgeslagen media als die beschikbaar is, anders on-the-fly genereren.
     */
    private function attachCompletionReport(MailMessage $mail): void
    {
        $reportMedia = $this->project->getFirstMedia('project-completion-report');

        if ($reportMedia && file_exists($reportMedia->getPath())) {
            $mail->attachData(
                file_get_contents($reportMedia->getPath()),
                $reportMedia->file_name,
                ['mime' => 'application/pdf']
            );

            return;
        }

        // Fallback: genereer de PDF on-the-fly als media nog niet bestaat
        try {
            $pdfService = app(PdfService::class);
            $mail->attachData(
                $pdfService->getCompletionReportPdfOutput($this->project),
                $pdfService->getCompletionReportPdfFilename($this->project),
                ['mime' => 'application/pdf']
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning("Kon opleverrapport niet toevoegen aan mail: {$e->getMessage()}");
        }
    }
}
