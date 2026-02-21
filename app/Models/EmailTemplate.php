<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'name',
        'subject',
        'content',
        'available_variables',
        'description',
        'active',
    ];

    protected $casts = [
        'available_variables' => 'array',
        'active' => 'boolean',
    ];

    /**
     * Replace placeholders in subject and content with actual values.
     *
     * Supports:
     * - {{variable}}           → simple value replacement
     * - {{#variable}}...{{/variable}} → conditional block: shown when value is truthy (non-empty, non-zero)
     */
    public function render(array $variables = []): array
    {
        $subject = $this->subject;
        $content = $this->content;

        // First pass: process conditional blocks {{#key}}...{{/key}}
        // A block is shown when the variable is truthy (non-empty string, non-zero number)
        foreach ($variables as $key => $value) {
            $pattern = '/\{\{#'.preg_quote($key, '/').'\}\}(.*?)\{\{\/'.preg_quote($key, '/').'\}\}/s';
            $content = preg_replace_callback(
                $pattern,
                fn ($matches) => $value ? trim($matches[1]) : '',
                $content
            );
            $subject = preg_replace_callback(
                $pattern,
                fn ($matches) => $value ? trim($matches[1]) : '',
                $subject
            );
        }

        // Second pass: replace simple {{variable}} placeholders
        foreach ($variables as $key => $value) {
            $placeholder = '{{'.$key.'}}';
            $subject = str_replace($placeholder, $value, $subject);
            $content = str_replace($placeholder, $value, $content);
        }

        return [
            'subject' => $subject,
            'content' => $content,
        ];
    }

    /**
     * Render template with HTML formatting
     * Converts plain text with bullets, numbered lists, and paragraphs to HTML
     */
    public function renderToHtml(array $variables = []): array
    {
        $rendered = $this->render($variables);
        $content = $rendered['content'];

        // Split content into lines for processing
        $lines = explode("\n", $content);
        $html = [];
        $inList = false;
        $listType = null; // 'ul' or 'ol'
        $currentParagraph = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);

            // Check if line is a bullet point (starts with • or - or *)
            if (preg_match('/^[•\-\*]\s+(.+)/', $trimmed, $matches)) {
                // Close any open paragraph
                if (! empty($currentParagraph)) {
                    $html[] = '<p>'.implode('<br>', $currentParagraph).'</p>';
                    $currentParagraph = [];
                }

                // Start a new unordered list if needed
                if (! $inList || $listType !== 'ul') {
                    if ($inList) {
                        $html[] = '</'.$listType.'>';
                    }
                    $html[] = '<ul>';
                    $inList = true;
                    $listType = 'ul';
                }

                $html[] = '<li>'.trim($matches[1]).'</li>';
            }
            // Check if line is a numbered list item (starts with 1. 2. 3. etc.)
            elseif (preg_match('/^(\d+)\.\s+(.+)/', $trimmed, $matches)) {
                // Close any open paragraph
                if (! empty($currentParagraph)) {
                    $html[] = '<p>'.implode('<br>', $currentParagraph).'</p>';
                    $currentParagraph = [];
                }

                // Start a new ordered list if needed
                if (! $inList || $listType !== 'ol') {
                    if ($inList) {
                        $html[] = '</'.$listType.'>';
                    }
                    $html[] = '<ol>';
                    $inList = true;
                    $listType = 'ol';
                }

                $html[] = '<li>'.trim($matches[2]).'</li>';
            }
            // Empty line - paragraph break
            elseif ($trimmed === '') {
                // Close any open list
                if ($inList) {
                    $html[] = '</'.$listType.'>';
                    $inList = false;
                    $listType = null;
                }

                // Close and add current paragraph if any
                if (! empty($currentParagraph)) {
                    $html[] = '<p>'.implode('<br>', $currentParagraph).'</p>';
                    $currentParagraph = [];
                }
            }
            // Regular text line
            else {
                // Close any open list
                if ($inList) {
                    $html[] = '</'.$listType.'>';
                    $inList = false;
                    $listType = null;
                }

                // Add to current paragraph
                $currentParagraph[] = $line;
            }
        }

        // Close any remaining list
        if ($inList) {
            $html[] = '</'.$listType.'>';
        }

        // Add any remaining paragraph
        if (! empty($currentParagraph)) {
            $html[] = '<p>'.implode('<br>', $currentParagraph).'</p>';
        }

        return [
            'subject' => $rendered['subject'],
            'content' => implode("\n", $html),
        ];
    }

    /**
     * Get template by key
     */
    public static function getByKey(string $key): ?self
    {
        return static::where('key', $key)->where('active', true)->first();
    }

    /**
     * Get available variables as a formatted string for display
     */
    public function getAvailableVariablesTextAttribute(): string
    {
        if (! $this->available_variables) {
            return 'Geen variabelen beschikbaar';
        }

        $text = "Beschikbare variabelen:\n\n";
        foreach ($this->available_variables as $var => $description) {
            $text .= '{{'.$var.'}} - '.$description."\n";
        }

        return $text;
    }
}
