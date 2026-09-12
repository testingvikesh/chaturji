<x-email-layout title="Ticket reply" eyebrow="Ticket reply">
    <p style="margin:0 0 12px;font-size:16px;">
        @if ($toAdmin)
            {{ $ticket->user?->name ?? 'User' }} replied on {{ $ticket->ticket_no }}.
        @else
            Admin replied to your ticket {{ $ticket->ticket_no }}.
        @endif
    </p>
    <p style="margin:0 0 8px;font-size:14px;"><strong>Subject:</strong> {{ $ticket->subject }}</p>
    <p style="margin:0 0 16px;padding:12px 16px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;font-size:14px;line-height:1.6;white-space:pre-line;">{{ $reply->message }}</p>
    <p style="margin:0;">
        <a href="{{ $ticketUrl }}" style="display:inline-block;background:#166534;color:#ffffff;text-decoration:none;padding:10px 16px;border-radius:10px;font-size:14px;font-weight:bold;">Open ticket</a>
    </p>
</x-email-layout>
