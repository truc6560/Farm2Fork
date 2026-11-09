<?php

namespace App\Mail;

use App\Models\LoiMoi;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MemberInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public LoiMoi $invitation,
        public string $acceptUrl,
        public int $expiresDays = 7
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Lời mời tham gia hệ thống Quản lý Chuỗi cung ứng Farm2Fork',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.member-invitation',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

