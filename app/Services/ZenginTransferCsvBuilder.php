<?php

namespace App\Services;

use Carbon\CarbonInterface;

class ZenginTransferCsvBuilder
{
    /**
     * BIMONIで実際に使われている総合振込CSV(GAS)と同じ項目構成でCSV文字列(UTF-8)を組み立てる。
     * Shift_JISへの変換は呼び出し側で行う。
     *
     * @param  array<int, array{bank_code: ?string, branch_code: ?string, account_type: string, account_no: ?string, name: ?string, amount: int}>  $recipients
     */
    public function build(array $recipients, CarbonInterface $transferDate): string
    {
        $consignorCode = (string) config('services.zengin_transfer.consignor_code');
        $consignorName = (string) config('services.zengin_transfer.consignor_name');
        $sourceBankCode = (string) config('services.zengin_transfer.source_bank_code');
        $sourceBranchCode = (string) config('services.zengin_transfer.source_branch_code');
        $sourceAccountType = (string) config('services.zengin_transfer.source_account_type');
        $sourceAccountNo = (string) config('services.zengin_transfer.source_account_no');

        $mmdd = $transferDate->format('md');

        $rows = [];

        $rows[] = [
            '1', '21', '0', $consignorCode, $consignorName, $mmdd,
            $sourceBankCode, '', $sourceBranchCode, '', $sourceAccountType, $sourceAccountNo,
        ];

        $total = 0;
        $count = 0;

        foreach ($recipients as $recipient) {
            $bankCode = $this->digitsOnly((string) ($recipient['bank_code'] ?? ''));
            $branchCode = $this->digitsOnly((string) ($recipient['branch_code'] ?? ''));
            $accountNo = $this->digitsOnly((string) ($recipient['account_no'] ?? ''));
            $name = ZenginNameNormalizer::normalize((string) ($recipient['name'] ?? ''));
            $amount = (int) ($recipient['amount'] ?? 0);

            if ($bankCode === '' || $branchCode === '' || $accountNo === '' || $name === '' || $amount <= 0) {
                continue;
            }

            $rows[] = [
                '2',
                str_pad($bankCode, 4, '0', STR_PAD_LEFT),
                '',
                str_pad($branchCode, 3, '0', STR_PAD_LEFT),
                '',
                '0000',
                (string) $recipient['account_type'],
                str_pad($accountNo, 7, '0', STR_PAD_LEFT),
                $name,
                (string) $amount,
                '',
                '',
                '7',
                '',
            ];

            $total += $amount;
            $count++;
        }

        $rows[] = ['8', (string) $count, (string) $total];
        $rows[] = ['9'];

        return implode("\r\n", array_map(fn (array $row) => implode(',', $row), $rows));
    }

    private function digitsOnly(string $value): string
    {
        return preg_replace('/\D/', '', $value) ?? '';
    }
}
