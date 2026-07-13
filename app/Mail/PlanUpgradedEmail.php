<?php
namespace App\Mail;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PlanUpgradedEmail extends Mailable
{
    use Queueable, SerializesModels;
    public function __construct(
        public User $user,
        public Plan $plan,
        public string $billingCycle,
        public float $amount
    ) {}
    public function envelope(): Envelope
    {
        return new Envelope(subject: "You're now on the {$this->plan->name} plan! 🎉");
    }
    public function content(): Content
    {
        return new Content(view: 'emails.plan-upgraded');
    }
}
