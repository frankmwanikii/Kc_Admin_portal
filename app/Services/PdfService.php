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
                    <p class='doc-title'>Operating Statement</p>
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

    /**
     * @param array<string, mixed> $position
     */
    public function generateConsolidatedPosition(array $position, string $churchName, string $churchAddress = ''): string
    {
        $esc = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
        $fmt = static fn (float $n, bool $out = false): string => FinanceReconciliationService::formatPositionAmount($n, true, $out);

        $year = (int) ($position['year'] ?? date('Y'));
        $priorYear = (int) ($position['prior_year'] ?? ($year - 1));
        $columnYears = array_values(array_map('intval', $position['column_years'] ?? [$year, $priorYear]));
        if ($columnYears === []) {
            $columnYears = [$year, $priorYear];
        }
        $colCount = count($columnYears);
        $docTitle = (string) ($position['document_title'] ?? 'Consolidated Statement of Income and Expenditure');
        $addressLine = $churchAddress !== '' ? '<p class="addr">' . $esc($churchAddress) . '</p>' : '';
        $colColors = ['#e8f6fc', '#fff4eb', '#ecfdf5', '#f8fafc'];

        $body = '';
        foreach ($position['rows'] ?? [] as $row) {
            $type = (string) ($row['type'] ?? 'line');
            if ($type === 'section') {
                $body .= '<tr class="section"><td colspan="' . (1 + $colCount) . '">' . $esc((string) ($row['label'] ?? '')) . '</td></tr>';
                continue;
            }
            $out = !empty($row['outflow']);
            $byYear = $row['amounts']['by_year'] ?? null;
            $bucket = $row['amounts']['group'] ?? $row['amounts']['entity'] ?? ['current' => 0, 'prior' => 0];
            $body .= '<tr class="' . $esc($type) . '">'
                . '<td class="label">' . $esc((string) ($row['label'] ?? '')) . '</td>';
            foreach ($columnYears as $idx => $colYear) {
                $val = is_array($byYear)
                    ? (float) ($byYear[$colYear] ?? 0)
                    : (float) ($idx === 0 ? ($bucket['current'] ?? 0) : ($bucket['prior'] ?? 0));
                $tone = '';
                if ($type === 'final' && abs($val) >= 0.005) {
                    $tone = $val >= 0 ? ' amt-surplus' : ' amt-deficit';
                }
                $body .= '<td class="amt y' . (int) $idx . $tone . '">' . $fmt($val, $out) . '</td>';
            }
            $body .= '</tr>';
        }

        $logoDataUri = FinanceReconciliationService::statementLogoDataUri();
        $watermarkHtml = $logoDataUri !== ''
            ? "<div class='watermark'><img src='" . $logoDataUri . "' alt=''></div>"
            : '';
        $headerLogoHtml = $logoDataUri !== ''
            ? "<img src='" . $logoDataUri . "' alt='' class='header-logo'>"
            : '';
        $disclaimer = FinanceReconciliationService::STATEMENT_DISCLAIMER;

        $thHtml = "<th class='label'>Items</th>";
        foreach ($columnYears as $idx => $colYear) {
            $thHtml .= '<th class="y' . (int) $idx . '">' . (int) $colYear . '<br>KShs</th>';
        }

        $widthPct = $colCount > 0 ? max(12, (int) floor(70 / $colCount)) : 22;

        $html = "<!DOCTYPE html><html><head><style>
            body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; }
            .watermark { position: fixed; top: 34%; left: 0; right: 0; text-align: center; opacity: 0.05; z-index: -1; }
            .watermark img { width: 240px; }
            .header { text-align: center; border-bottom: 2px solid #0b486d; padding-bottom: 10px; margin-bottom: 12px; }
            .header-logo { width: 44px; display: block; margin: 0 auto 6px; }
            .org { font-size: 13px; font-weight: bold; text-transform: uppercase; color: #0b486d; margin: 0 0 4px; }
            .addr { font-size: 8px; color: #64748b; margin: 0 0 4px; }
            .doc-title { font-size: 11px; font-weight: bold; text-decoration: underline; margin: 0; }
            .doc-asat { font-size: 9px; margin: 3px 0 0; }
            .meta { font-size: 8px; color: #64748b; margin-top: 6px; }
            table.ie { width: 100%; border-collapse: collapse; }
            table.ie th { font-size: 8px; text-align: right; padding: 5px 4px; border-bottom: 1px solid #333; vertical-align: bottom; }
            table.ie th.label { text-align: left; }
            table.ie td { padding: 4px; border: none; vertical-align: bottom; }
            table.ie td.amt { text-align: right; white-space: nowrap; width: {$widthPct}%; }
            table.ie .y0 { background: {$colColors[0]}; }
            table.ie .y1 { background: {$colColors[1]}; }
            table.ie .y2 { background: {$colColors[2]}; }
            table.ie .y3 { background: {$colColors[3]}; }
            table.ie tr.section td { font-weight: bold; text-transform: uppercase; padding-top: 8px; background: #fff !important; }
            table.ie tr.subtotal td.label, table.ie tr.result td.label, table.ie tr.final td.label { font-weight: bold; }
            table.ie tr.result td.label, table.ie tr.final td.label { text-transform: uppercase; }
            table.ie tr.subtotal td.amt, table.ie tr.result td.amt { border-top: 1px solid #111; border-bottom: 1px solid #111; font-weight: bold; }
            table.ie tr.final td.amt { border-top: 1px solid #111; border-bottom: 3px double #111; font-weight: bold; }
            table.ie td.amt-surplus { color: #047857; border-top-color: #047857; border-bottom-color: #047857; }
            table.ie td.amt-deficit { color: #b91c1c; border-top-color: #b91c1c; border-bottom-color: #b91c1c; }
            .footer { margin-top: 14px; padding-top: 8px; border-top: 1px solid #e2e8f0; font-size: 8px; color: #64748b; text-align: center; }
            .disclaimer { font-style: italic; max-width: 420px; margin: 0 auto 6px; }
            .signoff { font-weight: bold; color: #334155; }
        </style></head><body>
            {$watermarkHtml}
            <div class='header'>
                {$headerLogoHtml}
                <p class='org'>" . $esc(strtoupper($churchName)) . "</p>
                {$addressLine}
                <p class='doc-title'>" . $esc($docTitle) . "</p>
                <p class='doc-asat'>for the year ended " . $esc((string) ($position['as_at_label'] ?? ('31st December ' . $year))) . "</p>
                <p class='meta'>Generated " . date('j F Y, g:i a') . "</p>
            </div>
            <table class='ie'>
                <thead>
                    <tr>{$thHtml}</tr>
                </thead>
                <tbody>{$body}</tbody>
            </table>
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

    /**
     * @param array<string, mixed> $member Decoded form_submissions row
     */
    public function generateMemberRegistration(array $member, string $churchName, string $churchAddress = ''): string
    {
        $esc = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
        $name = (string) ($member['submitter_name'] ?? 'Member');
        $formLabel = FormSubmissionService::formTypeLabel((string) ($member['form_type'] ?? 'join'));
        $status = ucfirst((string) ($member['status'] ?? ''));
        $campus = ucfirst((string) ($member['campus_id'] ?? ''));
        $submitted = !empty($member['created_at'])
            ? date('j F Y, g:i a', strtotime((string) $member['created_at']))
            : '—';

        $sectionsHtml = '';
        $currentSection = null;
        foreach (FormSubmissionService::exportRows($member) as $row) {
            $section = (string) ($row['section'] ?? '');
            if ($section !== $currentSection) {
                if ($currentSection !== null) {
                    $sectionsHtml .= '</tbody></table>';
                }
                $currentSection = $section;
                $sectionsHtml .= '<h3>' . $esc($section) . '</h3><table class="data"><tbody>';
            }
            $sectionsHtml .= '<tr>'
                . '<th>' . $esc((string) ($row['label'] ?? '')) . '</th>'
                . '<td>' . nl2br($esc((string) ($row['value'] ?? ''))) . '</td>'
                . '</tr>';
        }
        if ($currentSection !== null) {
            $sectionsHtml .= '</tbody></table>';
        }

        $addressLine = $churchAddress !== ''
            ? '<p class="sub">' . $esc($churchAddress) . '</p>'
            : '';

        $html = "<!DOCTYPE html><html><head><meta charset='utf-8'><style>
            body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #0f172a; margin: 28px; }
            .header { text-align: center; margin-bottom: 18px; padding-bottom: 14px; border-bottom: 2px solid #0b486d; }
            .header h1 { margin: 0; font-size: 18px; color: #0b486d; }
            .header .sub { margin: 4px 0 0; color: #64748b; font-size: 10px; }
            .header .doc-title { margin: 10px 0 0; font-size: 14px; font-weight: 700; color: #1a7aab; }
            .meta { margin: 0 0 16px; padding: 10px 12px; background: #f0f9ff; border: 1px solid #d6f0fa; border-radius: 6px; }
            .meta p { margin: 3px 0; }
            h3 { margin: 16px 0 6px; font-size: 11px; letter-spacing: 0.04em; text-transform: uppercase; color: #0b486d; }
            table.data { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
            table.data th, table.data td { padding: 6px 8px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
            table.data th { width: 34%; text-align: left; font-weight: 600; color: #64748b; background: #f8fafc; }
            table.data td { color: #0f172a; }
            .footer { margin-top: 28px; padding-top: 10px; border-top: 1px solid #e2e8f0; text-align: center; color: #94a3b8; font-size: 9px; }
        </style></head><body>
            <div class='header'>
                <h1>" . $esc($churchName) . "</h1>
                {$addressLine}
                <p class='doc-title'>Member registration</p>
            </div>
            <div class='meta'>
                <p><strong>Name:</strong> " . $esc($name) . "</p>
                <p><strong>Form:</strong> " . $esc($formLabel) . " · <strong>Status:</strong> " . $esc($status) . " · <strong>Campus:</strong> " . $esc($campus !== '' ? $campus : '—') . "</p>
                <p><strong>Submitted:</strong> " . $esc($submitted) . " · <strong>Generated:</strong> " . $esc(date('j F Y, g:i a')) . "</p>
            </div>
            {$sectionsHtml}
            <div class='footer'>
                <p>" . $esc($churchName) . " · Administration · Generated electronically</p>
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
