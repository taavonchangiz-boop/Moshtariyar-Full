<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RecoveryWeeklyMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $reportData;
    public string $jToday;
    public string $jFrom;
    public string $jTo;

    public function __construct(array $reportData, string $jToday, string $jFrom, string $jTo)
    {
        $this->reportData = $reportData;
        $this->jToday = $jToday;
        $this->jFrom = $jFrom;
        $this->jTo = $jTo;
    }

    public function build()
    {
        $subject = '📊 گزارش هفتگی بازگشت و حفظ - ' . $this->jToday . ' - ' . ($this->reportData['recovered_7d'] ?? 0) . ' بازگشته و حفظ شده - ' . number_format($this->reportData['recovered_revenue_7d'] ?? 0) . ' تومان بازگشتی';

        return $this->subject($subject)
            ->view('emails.recovery_weekly')
            ->with([
                'data' => $this->reportData,
                'jToday' => $this->jToday,
                'jFrom' => $this->jFrom,
                'jTo' => $this->jTo,
            ]);
    }
}