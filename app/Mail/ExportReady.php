<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;

class ExportReady extends Mailable
{
    use Queueable, SerializesModels;

    public $fileName;
    public $columns;
    public $model;
    /**
     * Create a new message instance.
     */
    public function __construct($fileName, $columns, $model)
    {
        $this->fileName = $fileName;
        $this->columns = $columns;
        $this->model = $model;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->model . ' Export Ready',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.export-ready',
            with: [
                'fileName' => $this->fileName,
                'columns' => $this->columns,
                'model' => $this->model,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromStorage($this->fileName)->withMime('application/octet-stream'),
        ];
    }
}
