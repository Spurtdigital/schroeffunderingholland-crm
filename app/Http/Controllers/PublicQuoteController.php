<?php

namespace App\Http\Controllers;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Models\QuoteViewLog;
use App\Notifications\QuoteApprovedNotification;
use App\Services\PdfService;
use App\Settings\GeneralSettings;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PublicQuoteController extends Controller
{
    /**
     * Display the quote for the customer to view and sign.
     */
    public function show(Request $request, Quote $quote): View
    {
        // Abort if signature is invalid
        if (! $request->hasValidSignature()) {
            abort(403, 'Deze link is verlopen of ongeldig.');
        }

        // Log the view
        QuoteViewLog::create([
            'quote_id' => $quote->id,
            'event' => 'viewed',
            'ip_address' => $request->ip(),
            'user_agent' => substr($request->userAgent() ?? '', 0, 255),
        ]);

        $quote->load(['company', 'items.itemable', 'items.machine', 'assumptions', 'paymentTerm', 'machine', 'lead']);

        $settings = app(GeneralSettings::class);

        return view('public.quote', [
            'quote' => $quote,
            'settings' => $settings,
        ]);
    }

    /**
     * Stream the quote PDF for embedding in the public view.
     */
    public function pdf(Request $request, Quote $quote, PdfService $pdfService): Response
    {
        // Abort if signature is invalid
        if (! $request->hasValidSignature()) {
            abort(403, 'Deze link is verlopen of ongeldig.');
        }

        return $pdfService->generateQuotePdf($quote);
    }

    /**
     * Download the terms and conditions PDF.
     */
    public function terms()
    {
        $settings = app(GeneralSettings::class);

        if (empty($settings->terms_pdf)) {
            abort(404, 'Algemene voorwaarden niet gevonden.');
        }

        $path = Storage::disk('public')->path($settings->terms_pdf);

        if (! file_exists($path)) {
            abort(404, 'Algemene voorwaarden niet gevonden.');
        }

        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="algemene-voorwaarden.pdf"',
        ]);
    }

    /**
     * Handle the quote signature submission.
     */
    public function sign(Request $request, Quote $quote)
    {
        // Abort if signature is invalid
        if (! $request->hasValidSignature()) {
            abort(403, 'Deze link is verlopen of ongeldig.');
        }

        // Prevent double signing
        if ($quote->isSigned()) {
            return back()->with('error', 'Deze offerte is al ondertekend.');
        }

        // Prevent signing expired quotes
        if ($quote->isExpired()) {
            return back()->with('error', 'Deze offerte is verlopen en kan niet meer worden ondertekend.');
        }

        // Validate input
        $validated = $request->validate([
            'signature' => ['required', 'string', 'starts_with:data:image/png;base64,'],
            'signed_by_name' => ['required', 'string', 'max:255'],
            'accept_terms' => ['accepted'],
        ], [
            'signature.required' => 'Een handtekening is verplicht.',
            'signature.starts_with' => 'Ongeldige handtekening formaat.',
            'signed_by_name.required' => 'Vul uw naam in.',
            'accept_terms.accepted' => 'U moet akkoord gaan met de algemene voorwaarden.',
        ]);

        // Decode and store signature image
        $signatureData = $validated['signature'];
        $signatureData = str_replace('data:image/png;base64,', '', $signatureData);
        $signatureData = base64_decode($signatureData);

        // Store signature using Spatie Media Library
        $quote
            ->addMediaFromString($signatureData)
            ->usingFileName('signature_'.now()->format('YmdHis').'.png')
            ->toMediaCollection('quote-signature');

        // Update quote
        $quote->update([
            'signed_at' => now(),
            'signed_ip' => $request->ip(),
            'signed_by_name' => $validated['signed_by_name'],
            'status' => QuoteStatus::Approved,
            'approved_at' => now(),
        ]);

        // Log the signing event
        QuoteViewLog::create([
            'quote_id' => $quote->id,
            'event' => 'signed',
            'ip_address' => $request->ip(),
            'user_agent' => substr($request->userAgent() ?? '', 0, 255),
        ]);

        // Notify team about approval
        $quote->load('company');
        $users = \App\Models\User::all();
        Notification::send($users, new QuoteApprovedNotification($quote));

        return back()->with('success', 'Bedankt! Uw offerte is succesvol ondertekend. U ontvangt een bevestiging per e-mail.');
    }
}
