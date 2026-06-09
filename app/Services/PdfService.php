<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Contribution;
use App\Models\Member;
use Dompdf\Dompdf;
use Dompdf\Options;

class PdfService
{
    public function generateGivingStatement(Member $member, string $from, string $to): string
    {
        $contributions = Contribution::byMember($member->id, $from, $to);
        $total = Contribution::totalByMember($member->id, $from, $to);
        $church = SettingsService::churchName();
        $address = SettingsService::churchAddress();

        $rows = '';
        foreach ($contributions as $c) {
            $rows .= '<tr>
                <td>' . htmlspecialchars($c['contribution_date']) . '</td>
                <td>' . htmlspecialchars($c['fund_name']) . '</td>
                <td style="text-align:right">' . number_format((float) $c['amount'], 2) . '</td>
                <td>' . htmlspecialchars($c['payment_method']) . '</td>
                <td>' . htmlspecialchars($c['transaction_ref'] ?? '—') . '</td>
            </tr>';
        }

        $html = "<!DOCTYPE html><html><head><style>
            body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1e293b; }
            .header { text-align: center; margin-bottom: 24px; border-bottom: 2px solid #2563eb; padding-bottom: 16px; }
            .header h1 { color: #1e3a5f; margin: 0; font-size: 22px; }
            .meta { margin: 20px 0; }
            table { width: 100%; border-collapse: collapse; margin-top: 16px; }
            th { background: #f1f5f9; text-align: left; padding: 8px; border-bottom: 1px solid #e2e8f0; }
            td { padding: 8px; border-bottom: 1px solid #f1f5f9; }
            .total { font-size: 16px; font-weight: bold; text-align: right; margin-top: 16px; color: #2563eb; }
            .footer { margin-top: 40px; text-align: center; color: #94a3b8; font-size: 10px; }
        </style></head><body>
            <div class='header'>
                <h1>{$church}</h1>
                <p>{$address}</p>
                <p><strong>Giving Statement</strong></p>
            </div>
            <div class='meta'>
                <p><strong>Member:</strong> " . htmlspecialchars($member->fullName()) . "</p>
                <p><strong>Period:</strong> {$from} to {$to}</p>
                <p><strong>Generated:</strong> " . date('Y-m-d H:i') . "</p>
            </div>
            <table>
                <thead><tr><th>Date</th><th>Fund</th><th>Amount (KES)</th><th>Method</th><th>Reference</th></tr></thead>
                <tbody>{$rows}</tbody>
            </table>
            <p class='total'>Total: KES " . number_format($total, 2) . "</p>
            <div class='footer'>This statement is generated electronically and is valid without signature.</div>
        </body></html>";

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }
}
