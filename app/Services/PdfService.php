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

    /**
     * @param array<string, mixed> $statement
     */
    public function generateFinanceStatement(array $statement, string $churchName, string $churchAddress = ''): string
    {
        $summary = $statement['summary'] ?? [
            'collections' => 0,
            'expenses' => 0,
            'balance' => 0,
            'status_label' => 'Balanced position',
        ];
        $fmt = static fn (float $n): string => number_format($n, 2);
        $esc = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');

        $collectionRows = '';
        foreach ($statement['collection_lines'] ?? [] as $line) {
            $collectionRows .= '<tr><td>' . $esc((string) ($line['label'] ?? '')) . '</td>'
                . '<td class="amount">' . $fmt((float) ($line['amount'] ?? 0)) . '</td></tr>';
        }

        $expenseRows = '';
        foreach ($statement['expense_lines'] ?? [] as $line) {
            $expenseRows .= '<tr><td>' . $esc((string) ($line['label'] ?? '')) . '</td>'
                . '<td class="amount">' . $fmt((float) ($line['amount'] ?? 0)) . '</td></tr>';
        }

        $activitySection = '';
        if (!empty($statement['activity_rows'])) {
            $activityRows = '';
            foreach ($statement['activity_rows'] as $row) {
                $bal = (float) ($row['balance'] ?? 0);
                $balClass = $bal >= 0 ? 'pos' : 'neg';
                $balText = ($bal < 0 ? '-' : '') . $fmt(abs($bal));
                $period = $esc((string) ($row['label'] ?? ''));
                if (!empty($row['sub_label'])) {
                    $period .= '<br><span class="sub">' . $esc((string) $row['sub_label']) . '</span>';
                }
                $activityRows .= '<tr>'
                    . '<td>' . $period . '</td>'
                    . '<td class="amount">' . $fmt((float) ($row['collections'] ?? 0)) . '</td>'
                    . '<td class="amount">' . $fmt((float) ($row['expenses'] ?? 0)) . '</td>'
                    . '<td class="amount ' . $balClass . '">' . $balText . '</td>'
                    . '</tr>';
            }
            $summaryBal = (float) $summary['balance'];
            $summaryBalClass = $summaryBal >= 0 ? 'pos' : 'neg';
            $summaryBalText = ($summaryBal < 0 ? '-' : '') . $fmt(abs($summaryBal));
            $activitySection = "
            <h3>" . $esc((string) ($statement['activity_heading'] ?? 'Activity')) . "</h3>
            <table class='data'>
                <thead><tr>
                    <th>Period</th>
                    <th class='amount'>Collections</th>
                    <th class='amount'>Expenses</th>
                    <th class='amount'>Balance</th>
                </tr></thead>
                <tbody>{$activityRows}</tbody>
                <tfoot><tr>
                    <td><strong>Period total</strong></td>
                    <td class='amount'><strong>" . $fmt((float) $summary['collections']) . "</strong></td>
                    <td class='amount'><strong>" . $fmt((float) $summary['expenses']) . "</strong></td>
                    <td class='amount {$summaryBalClass}'><strong>{$summaryBalText}</strong></td>
                </tr></tfoot>
            </table>";
        }

        $balance = (float) $summary['balance'];
        $balanceText = ($balance < 0 ? '-' : '') . 'KES ' . $fmt(abs($balance));
        $addressLine = $churchAddress !== '' ? '<p>' . $esc($churchAddress) . '</p>' : '';
        $narrative = !empty($statement['narrative'])
            ? '<p class="narrative">' . $esc((string) $statement['narrative']) . '</p>'
            : '';

        $truePicture = $statement['true_picture'] ?? null;
        $arrears = $statement['arrears'] ?? null;
        $truePictureSection = '';
        if (is_array($truePicture) && is_array($arrears)) {
            $opBal = (float) ($truePicture['operating_balance'] ?? 0);
            $arrearsOwing = (float) ($arrears['balance_owing'] ?? 0);
            $netPos = (float) ($truePicture['net_position'] ?? 0);
            $netClass = $netPos >= 0 ? ($netPos > 0 ? 'surplus' : '') : 'deficit';
            $trueNarrative = !empty($statement['true_picture_narrative'])
                ? '<p class="narrative">' . $esc((string) $statement['true_picture_narrative']) . '</p>'
                : '';
            $truePictureSection = "
            <h3>Net Financial Position</h3>
            <table class='summary'>
                <tr>
                    <td><p class='label'>" . $esc((string) ($summary['status_label'] ?? 'Operating balance')) . "</p><p class='value'>" . ($opBal < 0 ? '-' : '') . 'KES ' . $fmt(abs($opBal)) . "</p></td>
                    <td><p class='label'>Outstanding arrears</p><p class='value'>KES " . $fmt($arrearsOwing) . "</p></td>
                    <td><p class='label'>" . $esc((string) ($truePicture['status_label'] ?? 'Net position')) . "</p><p class='value {$netClass}'>" . ($netPos < 0 ? '-' : '') . 'KES ' . $fmt(abs($netPos)) . "</p></td>
                </tr>
            </table>
            {$trueNarrative}";
        }

        $arrearsSection = '';
        if (!empty($statement['arrears_lines'])) {
            $arrearsRows = '';
            foreach ($statement['arrears_lines'] as $line) {
                $arrearsRows .= '<tr>'
                    . '<td>' . $esc((string) ($line['expense_item'] ?? '')) . '</td>'
                    . '<td>' . $esc((string) ($line['month_incurred'] ?? '')) . '</td>'
                    . '<td class="amount">' . $fmt((float) ($line['amount_due'] ?? 0)) . '</td>'
                    . '<td class="amount">' . $fmt((float) ($line['amount_paid'] ?? 0)) . '</td>'
                    . '<td class="amount">' . $fmt((float) ($line['balance_owing'] ?? 0)) . '</td>'
                    . '<td>' . $esc((string) ($line['status_label'] ?? '')) . '</td>'
                    . '</tr>';
            }
            $arrearsSummary = $statement['arrears'] ?? [];
            $arrearsSection = "
            <h3>Outstanding arrears</h3>
            <table class='data'>
                <thead><tr>
                    <th>Expense item</th>
                    <th>Period incurred</th>
                    <th class='amount'>Amount due</th>
                    <th class='amount'>Amount paid</th>
                    <th class='amount'>Balance owing</th>
                    <th>Status</th>
                </tr></thead>
                <tbody>{$arrearsRows}</tbody>
                <tfoot><tr>
                    <td colspan='2'><strong>Year totals</strong></td>
                    <td class='amount'><strong>" . $fmt((float) ($arrearsSummary['total_due'] ?? 0)) . "</strong></td>
                    <td class='amount'><strong>" . $fmt((float) ($arrearsSummary['total_paid'] ?? 0)) . "</strong></td>
                    <td class='amount'><strong>" . $fmt((float) ($arrearsSummary['balance_owing'] ?? 0)) . "</strong></td>
                    <td></td>
                </tr></tfoot>
            </table>";
        }

        $logoDataUri = FinanceReconciliationService::statementLogoDataUri();
        $watermarkHtml = $logoDataUri !== ''
            ? "<div class='watermark'><img src='" . $logoDataUri . "' alt=''></div>"
            : '';
        $disclaimer = FinanceReconciliationService::STATEMENT_DISCLAIMER;
        $headerLogoHtml = $logoDataUri !== ''
            ? "<img src='" . $logoDataUri . "' alt='' class='header-logo'>"
            : '';

        $html = "<!DOCTYPE html><html><head><style>
            body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #0f172a; line-height: 1.45; position: relative; }
            .watermark { position: fixed; top: 32%; left: 0; right: 0; text-align: center; opacity: 0.06; z-index: -1; }
            .watermark img { width: 280px; height: auto; }
            .header { border-bottom: 2px solid #1e3a5f; padding-bottom: 14px; margin-bottom: 18px; }
            .header-brand { text-align: center; margin-bottom: 12px; }
            .header-logo { width: 52px; height: auto; margin: 0 auto 8px; display: block; }
            .header-meta { display: table; width: 100%; margin: 10px auto 0; border-collapse: separate; border-spacing: 24px 0; }
            .header-meta p { display: table-cell; text-align: center; font-size: 10px; color: #475569; margin: 0; vertical-align: top; }
            .org { font-size: 18px; font-weight: bold; color: #1e3a5f; margin: 0 0 4px; text-align: center; }
            .doc-title { font-size: 13px; font-weight: bold; color: #334155; margin: 0; text-transform: uppercase; letter-spacing: 0.04em; text-align: center; }
            .meta-label { font-size: 9px; text-transform: uppercase; letter-spacing: 0.06em; color: #64748b; }
            .subtitle { color: #475569; margin: 0 0 16px; }
            .summary { width: 100%; border-collapse: separate; border-spacing: 8px 0; margin: 0 -8px 16px; }
            .summary td { width: 33.33%; border: 1px solid #e2e8f0; border-radius: 4px; padding: 10px 12px; background: #f8fafc; vertical-align: top; }
            .summary .label { font-size: 9px; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; margin: 0 0 4px; }
            .summary .value { font-size: 15px; font-weight: bold; color: #0f172a; margin: 0; }
            .summary .value.surplus { color: #0369a1; }
            .summary .value.deficit { color: #dc2626; }
            .narrative { background: #f8fafc; border-left: 3px solid #1e3a5f; padding: 10px 12px; margin: 0 0 16px; color: #334155; }
            .columns { width: 100%; border-collapse: separate; border-spacing: 12px 0; margin: 0 -12px 16px; }
            .columns td { width: 50%; vertical-align: top; }
            h3 { font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: #1e3a5f; border-bottom: 1px solid #cbd5e1; padding-bottom: 4px; margin: 0 0 8px; }
            table.data { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
            table.data th { background: #1e3a5f; color: #fff; text-align: left; padding: 6px 8px; font-size: 10px; }
            table.data th.amount, table.data td.amount { text-align: right; }
            table.data td { padding: 6px 8px; border-bottom: 1px solid #e2e8f0; }
            table.data tfoot td { font-weight: bold; background: #f1f5f9; border-top: 2px solid #cbd5e1; }
            .sub { font-size: 9px; color: #64748b; }
            .pos { color: #0369a1; }
            .neg { color: #dc2626; }
            .footer { margin-top: 24px; padding-top: 12px; border-top: 1px solid #e2e8f0; font-size: 9px; color: #64748b; text-align: center; }
            .disclaimer { font-style: italic; max-width: 420px; margin: 0 auto 8px; line-height: 1.5; }
            .signoff { font-weight: bold; color: #334155; margin-top: 8px; }
        </style></head><body>
            {$watermarkHtml}
            <div class='header'>
                <div class='header-brand'>
                    {$headerLogoHtml}
                    <p class='org'>" . $esc($churchName) . "</p>
                    {$addressLine}
                    <p class='doc-title'>Financial Statement</p>
                    <div class='header-meta'>
                        <p><span class='meta-label'>Statement period</span><br>" . $esc((string) ($statement['period_label'] ?? '')) . "</p>
                        <p><span class='meta-label'>Generated</span><br>" . date('j F Y, g:i a') . "</p>
                    </div>
                </div>
            </div>
            <p class='subtitle'>" . $esc((string) ($statement['period_subtitle'] ?? '')) . "</p>
            <table class='summary'>
                <tr>
                    <td><p class='label'>Total collections</p><p class='value'>KES " . $fmt((float) $summary['collections']) . "</p></td>
                    <td><p class='label'>Weekly expenses</p><p class='value'>KES " . $fmt((float) $summary['expenses']) . "</p></td>
                    <td><p class='label'>" . $esc((string) ($summary['status_label'] ?? 'Balance')) . "</p><p class='value " . ($balance >= 0 ? ($balance > 0 ? 'surplus' : '') : 'deficit') . "'>{$balanceText}</p></td>
                </tr>
            </table>
            {$narrative}
            {$truePictureSection}
            <table class='columns'><tr>
                <td>
                    <h3>Collections</h3>
                    <table class='data'>
                        <thead><tr><th>Description</th><th class='amount'>Amount (KES)</th></tr></thead>
                        <tbody>{$collectionRows}</tbody>
                        <tfoot><tr><td>Total collections</td><td class='amount'>" . $fmt((float) $summary['collections']) . "</td></tr></tfoot>
                    </table>
                </td>
                <td>
                    <h3>Weekly expenses</h3>
                    <table class='data'>
                        <thead><tr><th>Description</th><th class='amount'>Amount (KES)</th></tr></thead>
                        <tbody>{$expenseRows}</tbody>
                        <tfoot><tr><td>Total weekly expenses</td><td class='amount'>" . $fmt((float) $summary['expenses']) . "</td></tr></tfoot>
                    </table>
                </td>
            </tr></table>
            {$activitySection}
            {$arrearsSection}
            <div class='footer'>
                <p class='disclaimer'>" . $esc($disclaimer) . "</p>
                <p class='signoff'>" . $esc($churchName) . " · Finance Office</p>
            </div>
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
