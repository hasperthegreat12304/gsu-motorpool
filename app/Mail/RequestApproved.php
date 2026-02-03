<?php

namespace App\Mail;

use App\Models\Request as VehicleRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;

class RequestApproved extends Mailable
{
    use Queueable, SerializesModels;

    public $request;
    public $pdfContent;

    /**
     * Create a new message instance.
     * 
     * @param VehicleRequest $request The approved vehicle request
     * @param string|null $pdfContent The PDF content as a string (from FPDI Output('S'))
     */
    public function __construct(VehicleRequest $request, ?string $pdfContent = null)
    {
        $this->request = $request;
        $this->pdfContent = $pdfContent;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                config('mail.from.address'),
                config('mail.from.name')
            ),
            subject: '✅ Vehicle Request Approved - GSU Motorpool Services',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.request-approved',
            with: [
                'requesterName' => $this->request->user->name,
                'request' => $this->request,
                'destination' => $this->request->destination,
                'dateOfTravel' => $this->request->date_of_travel->format('F d, Y'),
                'timeOfTravel' => $this->request->time_of_travel,
                'vehicle' => $this->request->vehicle->description ?? 'N/A',
                'vehiclePlate' => $this->request->vehicle->plate_number ?? 'N/A',
                'driverName' => $this->request->driver->name ?? 'N/A',
                'approvedBy' => $this->request->approver->name ?? 'Admin',
                'approvedAt' => $this->request->approved_at->format('F d, Y h:i A'),
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
        $attachments = [];

        // If PDF content is provided, attach it directly from string
        if ($this->pdfContent) {
            $attachments[] = Attachment::fromData(
                fn () => $this->pdfContent,
                'Vehicle_Request_Approved_' . $this->request->id . '.pdf'
            )->withMime('application/pdf');
        }

        return $attachments;
    }
}