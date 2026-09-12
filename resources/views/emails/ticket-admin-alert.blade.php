<x-email-layout title="New ticket" eyebrow="New ticket">
    <p style="margin:0 0 12px;font-size:16px;">A new {{ $ticket->role }} ticket was generated.</p>
    <p style="margin:0 0 16px;padding:12px 16px;background:#ecfdf5;border:1px solid #bbf7d0;border-radius:12px;font-size:20px;font-weight:bold;color:#166534;">
        {{ $ticket->ticket_no }}
    </p>
    <p style="margin:0 0 8px;font-size:14px;"><strong>From:</strong> {{ $ticket->user?->name ?? '—' }} ({{ $ticket->user?->mobile }})</p>
    <p style="margin:0 0 8px;font-size:14px;"><strong>Email:</strong> {{ $ticket->user?->email ?: 'Not set' }}</p>
    <p style="margin:0 0 8px;font-size:14px;"><strong>Category:</strong> {{ $ticket->categoryLabel() }}</p>
    <p style="margin:0 0 8px;font-size:14px;"><strong>Subject:</strong> {{ $ticket->subject }}</p>
    <p style="margin:0 0 16px;font-size:14px;line-height:1.6;white-space:pre-line;">{{ $ticket->message }}</p>
    <p style="margin:0;">
        <a href="{{ $ticketUrl }}" style="display:inline-block;background:#166534;color:#ffffff;text-decoration:none;padding:10px 16px;border-radius:10px;font-size:14px;font-weight:bold;">Reply in admin</a>
    </p>
</x-email-layout>
