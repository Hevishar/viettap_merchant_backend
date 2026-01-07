<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Transaction;

class ViettapController extends Controller
{
    public function init(Request $request)
    {
        $data = $request->validate([
            'bank' => 'required|string',
            'account_number' => 'required|string',
            'amount' => 'required|numeric',
        ]);

        $transactionId = (string) Str::uuid();

        $result = $this->generateVietQr($data['bank'], $data['account_number'], $data['amount'], $transactionId);

        // Persist transaction
        Transaction::create([
            'transaction_id' => $transactionId,
            'bank' => $data['bank'],
            'account_number' => $data['account_number'],
            'amount' => (int) $data['amount'],
            'qr_string' => $result['qr_string'],
            'qr_base64' => $result['qr_base64'],
            'status' => 'pending',
        ]);

        return response()->json([
            'transaction_id' => $transactionId,
            'qr_string' => $result['qr_string'],
            'qr_base64' => $result['qr_base64'],
        ]);
    }

    private function generateVietQr(string $bank, string $accountNumber, $amountValue, string $transactionId): array
    {
        $buildTlv = function ($id, $value) {
            return sprintf('%02s%02d%s', $id, strlen($value), $value);
        };

        $amount = number_format($amountValue, 0, '.', '');

        $mai = '';
        $mai .= $buildTlv('00', 'VietQR');
        $mai .= $buildTlv('01', $accountNumber);
        $maiTlv = $buildTlv('26', $mai);

        $tlv = '';
        $tlv .= $buildTlv('00', '01');
        $tlv .= $buildTlv('01', '12');
        $tlv .= $maiTlv;
        $tlv .= $buildTlv('52', '0000');
        $tlv .= $buildTlv('53', '704');
        $tlv .= $buildTlv('54', $amount);
        $tlv .= $buildTlv('58', 'VN');
        $merchantName = substr($bank, 0, 25);
        $tlv .= $buildTlv('59', $merchantName);
        $tlv .= $buildTlv('60', '');

        // Additional data field template (62) with transaction reference
        $adf = $buildTlv('01', $transactionId);
        $tlv .= $buildTlv('62', $adf);

        $toCrc = $tlv . '6304';

        $crc16 = function ($str) {
            $crc = 0xFFFF;
            $len = strlen($str);
            for ($i = 0; $i < $len; $i++) {
                $crc ^= ord($str[$i]) << 8;
                for ($j = 0; $j < 8; $j++) {
                    if (($crc & 0x8000) !== 0) {
                        $crc = (($crc << 1) ^ 0x1021) & 0xFFFF;
                    } else {
                        $crc = ($crc << 1) & 0xFFFF;
                    }
                }
            }
            return sprintf('%04X', $crc);
        };

        $crc = $crc16($toCrc);
        $tlv .= $buildTlv('63', $crc);

        $qrString = $tlv;

        $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' . rawurlencode($qrString);
        $image = @file_get_contents($qrUrl);

        if ($image === false) {
            throw new \RuntimeException('Failed to generate QR image');
        }

        $qrBase64 = 'data:image/png;base64,' . base64_encode($image);

        return ['qr_string' => $qrString, 'qr_base64' => $qrBase64];
    }

    public function submit(Request $request)
    {
        return response()->json(['status' => 'submitted']);
    }

    public function status()
    {
        return response()->json(['status' => 'API is running']);
    }
}
