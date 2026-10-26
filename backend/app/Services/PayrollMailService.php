<?php

namespace App\Services;

use App\Models\PayrollRun;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

/**
 * PayrollMailService
 *
 * Mengirim slip gaji via email menggunakan PHPMailer (SMTP).
 * Konfigurasi SMTP dibaca dari environment variables:
 *   MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD,
 *   MAIL_ENCRYPTION, MAIL_FROM_ADDRESS, MAIL_FROM_NAME
 */
class PayrollMailService
{
    /**
     * Kirim slip gaji 1 karyawan sebagai attachment PDF.
     *
     * @return array{success: bool, message: string}
     */
    public function sendPayslip(PayrollRun $run): array
    {
        $run->loadMissing([
            'employee.department',
            'employee.position',
            'employee.company',
            'employee.branch',
            'period',
            'details',
            'assignment',
        ]);

        $employee = $run->employee;
        $period   = $run->period;

        if (!$employee || !filter_var($employee->email ?? '', FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Email karyawan tidak valid atau tidak ada.'];
        }

        // Generate PDF slip gaji
        try {
            $pdf = Pdf::loadView('pdf.payslip', ['run' => $run])->setPaper('A4', 'portrait');
            $pdfContent = $pdf->output();
        } catch (\Throwable $e) {
            Log::error('PayrollMailService: gagal generate PDF', [
                'run_id' => $run->id,
                'error'  => $e->getMessage(),
            ]);
            return ['success' => false, 'message' => 'Gagal membuat PDF slip gaji: ' . $e->getMessage()];
        }

        $periodName = $period?->name ?? 'Periode';
        $empName    = $employee->name ?? 'Karyawan';
        $filename   = 'Slip_Gaji_' . str_replace(' ', '_', $empName) . '_' . str_replace(' ', '_', $periodName) . '.pdf';

        // Buat subject & body
        $subject = "Slip Gaji {$periodName} – {$empName}";
        $bodyHtml = $this->buildEmailBody($run, $empName, $periodName);

        // Kirim via PHPMailer
        $mail = $this->createMailer();

        try {
            $mail->addAddress($employee->email, $empName);
            $mail->Subject = $subject;
            $mail->Body    = $bodyHtml;
            $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>'], "\n", $bodyHtml));

            // Attach PDF
            $mail->addStringAttachment($pdfContent, $filename, PHPMailer::ENCODING_BASE64, 'application/pdf');

            $mail->send();

            Log::info('PayrollMailService: slip gaji terkirim', [
                'run_id'   => $run->id,
                'to_email' => $employee->email,
            ]);

            return ['success' => true, 'message' => "Email slip gaji berhasil dikirim ke {$employee->email}"];
        } catch (Exception $e) {
            Log::error('PayrollMailService: gagal kirim email', [
                'run_id' => $run->id,
                'error'  => $mail->ErrorInfo,
            ]);
            return ['success' => false, 'message' => 'Gagal mengirim email: ' . $mail->ErrorInfo];
        }
    }

    /**
     * Kirim bulk slip gaji untuk 1 periode.
     * Returns: ['sent' => int, 'failed' => int, 'errors' => array]
     */
    public function sendBulkPayslip(array $runs): array
    {
        $sent   = 0;
        $failed = 0;
        $errors = [];

        foreach ($runs as $run) {
            $result = $this->sendPayslip($run);
            if ($result['success']) {
                $sent++;
            } else {
                $failed++;
                $errors[] = [
                    'run_id'  => $run->id,
                    'employee' => $run->employee?->name ?? '?',
                    'reason'  => $result['message'],
                ];
            }
        }

        return compact('sent', 'failed', 'errors');
    }

    // ─────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────

    private function createMailer(): PHPMailer
    {
        $mail = new PHPMailer(true);

        $mailer = config('mail.default', 'smtp');

        // Jika MAIL_MAILER=log (development), tetap kirim via SMTP jika SMTP credentials tersedia
        $host = config('mail.mailers.smtp.host', env('MAIL_HOST', ''));
        $port = (int) config('mail.mailers.smtp.port', env('MAIL_PORT', 587));
        $username = config('mail.mailers.smtp.username', env('MAIL_USERNAME', ''));
        $password = config('mail.mailers.smtp.password', env('MAIL_PASSWORD', ''));
        $encryption = config('mail.mailers.smtp.encryption', env('MAIL_ENCRYPTION', 'tls'));

        $mail->isSMTP();
        $mail->Host       = $host;
        $mail->Port       = $port;
        $mail->CharSet    = PHPMailer::CHARSET_UTF8;
        $mail->isHTML(true);

        if ($username && $password) {
            $mail->SMTPAuth   = true;
            $mail->Username   = $username;
            $mail->Password   = $password;
        } else {
            $mail->SMTPAuth = false;
        }

        if ($encryption === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($encryption === 'tls' || $encryption === 'starttls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } else {
            $mail->SMTPSecure = '';
            $mail->SMTPAutoTLS = false;
        }

        $fromAddress = config('mail.from.address', env('MAIL_FROM_ADDRESS', 'noreply@company.com'));
        $fromName    = config('mail.from.name', env('MAIL_FROM_NAME', config('app.name', 'HRMS')));
        $mail->setFrom($fromAddress, $fromName);

        // Timeout
        $mail->Timeout = 15;

        return $mail;
    }

    private function buildEmailBody(PayrollRun $run, string $empName, string $periodName): string
    {
        $net     = 'Rp ' . number_format($run->net_salary ?? 0, 0, ',', '.');
        $gross   = 'Rp ' . number_format($run->gross_salary ?? 0, 0, ',', '.');
        $company = $run->employee?->company?->name ?? $run->assignment?->company?->name ?? config('app.name', 'Perusahaan');

        $cycleLabel = match ($run->period?->cycle_type ?? '') {
            'daily'          => 'Harian',
            'weekly_payment' => 'Mingguan (Gaji + Bonus)',
            'weekly_payslip' => 'Mingguan (Tunjangan)',
            'monthly'        => 'Bulanan',
            default          => 'Reguler',
        };

        return <<<HTML
        <!DOCTYPE html>
        <html lang="id">
        <head><meta charset="UTF-8"><title>Slip Gaji</title></head>
        <body style="font-family: Arial, sans-serif; background:#f5f5f5; margin:0; padding:20px;">
          <div style="max-width:600px; margin:0 auto; background:#fff; border-radius:8px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,.1)">
            <div style="background:#1a73e8; color:#fff; padding:24px 32px;">
              <h2 style="margin:0 0 4px">Slip Gaji – {$periodName}</h2>
              <p style="margin:0; opacity:.85; font-size:14px">{$company}</p>
            </div>
            <div style="padding:24px 32px">
              <p>Yth. <strong>{$empName}</strong>,</p>
              <p>Berikut adalah slip gaji Anda untuk periode <strong>{$periodName}</strong> (Skema: {$cycleLabel}).</p>
              <table style="width:100%; border-collapse:collapse; margin:16px 0">
                <tr style="background:#f0f4ff">
                  <td style="padding:10px 14px; font-weight:bold; width:50%">Gaji Bruto</td>
                  <td style="padding:10px 14px">{$gross}</td>
                </tr>
                <tr>
                  <td style="padding:10px 14px; font-weight:bold; background:#e8f5e9">Gaji Bersih (Take Home Pay)</td>
                  <td style="padding:10px 14px; background:#e8f5e9; color:#2e7d32; font-weight:bold; font-size:16px">{$net}</td>
                </tr>
              </table>
              <p style="font-size:13px; color:#666">Slip gaji lengkap terlampir dalam file PDF pada email ini.</p>
              <hr style="border:none; border-top:1px solid #eee; margin:20px 0">
              <p style="font-size:12px; color:#999; margin:0">Email ini dikirim secara otomatis oleh sistem HRMS. Harap tidak membalas email ini.</p>
            </div>
          </div>
        </body>
        </html>
        HTML;
    }
}
