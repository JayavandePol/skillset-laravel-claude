# Mail & notifications (ECO-08)

## Mailables

```php
final class InvoiceMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Invoice $invoice) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('Invoice :number', ['number' => $this->invoice->number]));
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.invoice', with: ['url' => route('invoices.show', $this->invoice)]);
    }

    /** @return list<Attachment> */
    public function attachments(): array
    {
        return [Attachment::fromStorageDisk('s3', $this->invoice->pdf_path)->as("{$this->invoice->number}.pdf")];
    }
}
```

- Send in the recipient's locale: `Mail::to($customer)->locale($customer->locale)->queue(new InvoiceMail($invoice));` or `HasLocalePreference` on the model.
- Preview mailables in the browser during development (`Route::get('/mail-preview', fn () => new InvoiceMail(...))` in local only) or use Mailpit (Sail/Herd include it).
- Production transport: Postmark, Mailgun, SES, or SMTP; set SPF, DKIM, DMARC; use a subdomain (`mail.example.com`) for sending reputation.
- Failover: `'mailers' => ['failover' => ['transport' => 'failover', 'mailers' => ['postmark', 'smtp']]]`.

## Notifications

```php
final class InvoiceOverdue extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Invoice $invoice) {}

    /** @return list<string> */
    public function via(User $notifiable): array
    {
        return $notifiable->prefersChannels(self::class, default: ['mail', 'database']);
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Invoice :number is overdue', ['number' => $this->invoice->number]))
            ->line(__('The invoice is :days days overdue.', ['days' => $this->invoice->due_on->diffInDays(today())]))
            ->action(__('View invoice'), route('invoices.show', $this->invoice));
    }

    /** @return array<string, mixed> */
    public function toArray(User $notifiable): array
    {
        return ['invoice' => $this->invoice->ulid, 'number' => $this->invoice->number];
    }
}
```

- Database notifications for an in-app bell; broadcast channel for live updates.
- User preferences table (per notification type × channel); one-click unsubscribe links (signed URLs) for marketing-type mails.
- Digest instead of spam: aggregate frequent events into a scheduled daily mail.
- Tests: `Notification::fake()` / `Mail::fake()` + `assertQueued`, and render tests (`$mail->assertSeeInHtml(...)`).
