<?php

use Illuminate\Mail\Events\MessageSending;
use Symfony\Component\Mime\Email;

it('bcc\'t geen wachtwoord-resetmails naar de eigenaar', function () {
    $email = (new Email)
        ->from('crm@example.com')
        ->to('gebruiker@example.com')
        ->subject('Reset your password')
        ->html('<a href="https://app.example.com/admin/password-reset/reset/abc123">Reset</a>');

    event(new MessageSending($email));

    expect($email->getHeaders()->get('Bcc'))->toBeNull();
});

it('bcc\'t normale mails wel naar de eigenaar', function () {
    $email = (new Email)
        ->from('crm@example.com')
        ->to('gebruiker@example.com')
        ->subject('Offerte 2026-001')
        ->text('Bijgevoegd de offerte.');

    event(new MessageSending($email));

    expect($email->getHeaders()->get('Bcc'))->not->toBeNull();
});

it('toont de wachtwoord-vergeten pagina', function () {
    $this->get('/admin/password-reset/request')->assertOk();
});
