<?php

namespace App\Utils;

use App\Models\EmailTemplates;
use Exception;
use Illuminate\Support\Facades\Mail;

class MailService
{
    /**
     * Send an email with specified parameters.
     *
     * @param string $from The sender's email address.
     * @param string $to The recipient's email address.
     * @param string $subject The subject of the email.
     * @param string $content The HTML content of the email.
     * @return bool|string True if the email was sent successfully, error message otherwise.
     */
    public function sendMail(string $from, string $to, string $subject, string $content)
    {
        try {
            Mail::send([], [], function ($message) use ($from, $to, $subject, $content) {
                $message->from($from)
                    ->to($to)
                    ->subject($subject)
                    ->html($content);
            });

            return true;
        } catch (\Exception $e) {
            /* Return error message if something goes wrong */
            return $e->getMessage();
        }
    }

    public function sendVerificationMail($email, $templateName, $replacement = [])
    {
        try {
            $emailTemplate = app(EmailTemplates::class)->where('name', $templateName)->first();

            if (!$emailTemplate) {
                app(ActivityLogger::class)->logSystemActivity('Email template not found', ['name' => 'register_author'], 404);
                app(ActivityLogger::class)->logUserActivity('Email template not found', ['name' => 'register_author'], 404);
                throw new Exception('Email template not found');
            }

            $content = strtr($emailTemplate['content'], $replacement);
            /* Replace this with company name */
            self::sendMail('no_reply@newzy.com', $email, $emailTemplate['subject'], $content);
        } catch (\Exception $e) {
            /* Return error message if something goes wrong */
            return $e->getMessage();
        }
    }
}
