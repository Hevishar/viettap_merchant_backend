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
        $bankCodes = $this->getBankCode($data['bank']);
        if (empty($bankCodes)) {
            return response()->json(['error' => 'Unsupported bank'], 400);
        }

        $result = $this->generateVietQr($bankCodes, $data['account_number'], $data['amount'], $transactionId);

        // Persist transaction
        Transaction::create([
            'transaction_id' => $transactionId,
            'bank' => $bankCodes,
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

        $tlv = '';
        $mai_38 = '';
        $mai_38_01 = '';
        
        
        
        $tlv .= $buildTlv('00', '01');
        $tlv .= $buildTlv('01', '12');
        
        $mai_38_01 .= $buildTlv('00', $bank);
        $mai_38_01 .= $buildTlv('01', $accountNumber);

        $mai_38 .= $buildTlv('00', 'A000000727');
        $mai_38 .= $buildTlv('01', $mai_38_01);
        $mai_38 .= $buildTlv('02', 'QRIBFTTA');

        $tlv .= $buildTlv('38', $mai_38);

        $tlv .= $buildTlv('53', '704');
        $tlv .= $buildTlv('54', $amount);
        $tlv .= $buildTlv('58', 'VN');
        // $tlv .= $buildTlv('59', 'NapasShop');
        $tlv .= $buildTlv('62', str_replace('-', '', $transactionId));

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
        $data = $request->validate([
            'transaction_id' => 'required|string|exists:transactions,transaction_id',
            'status' => 'required|in:pending,paid,completed,cancelled,failed',
        ]);

        $tx = Transaction::where('transaction_id', $data['transaction_id'])->first();
        $tx->status = $data['status'];
        $tx->save();

        return response()->json(['success' => true]);
    }

    public function status(Request $request)
    {
        $data = $request->validate([
            'transaction_id' => 'required|string',
        ]);

        $tx = Transaction::where('transaction_id', $data['transaction_id'])->first();

        if (! $tx) {
            return response()->json(['error' => 'Transaction not found'], 404);
        }

        return response()->json([
            'transaction_id' => $tx->transaction_id,
            'status' => $tx->status,
            'bank' => $tx->bank,
            'account_number' => $tx->account_number,
            'amount' => $tx->amount,
            'created_at' => $tx->created_at,
        ]);
    }

    public function list()
    {
        $transactions = Transaction::orderBy('created_at', 'desc')->get();

        $result = $transactions->map(function ($tx) {
            return [
                'transaction_id' => $tx->transaction_id,
                'bank' => $tx->bank,
                'account_number' => $tx->account_number,
                'amount' => $tx->amount,
                'status' => $tx->status,
                'created_at' => $tx->created_at,
            ];
        });

        return response()->json($result);
    }

    public function help()
    {
        $helpText = [
            'init' => 'POST /viettap/init - Initialize a new VietQR transaction. Required parameters: bank, account_number, amount.',
            'submit' => 'POST /viettap/submit - Submit the status of a transaction. Required parameters: transaction_id, status (pending, paid, completed, cancelled, failed).',
            'status' => 'GET /viettap/status - Get the status of a transaction. Required parameter: transaction_id.',
            'list' => 'GET /viettap/list - List all transactions.',
            'help' => 'GET /viettap/help - Show this help information.',
        ];

        return response()->json($helpText);
    }

    private function getBankCode(string $bankName): string
    {
        $bankCodes = [
            'VCB' => '970436',
            'TCB' => '970407',
            'ACB' => '970416',
            'BIDV' => '970418',
            'EXB' => '970431',
            'MBB' => '970422',
            'NCB' => '970419',
            'OCB' => '970448',
            'SHB' => '970443',
            'STB' => '970403',
            'TPB' => '970423',
            'VIB' => '970441',
            'VPB' => '970432',
            'ICB' => '970415',
            'VBA' => '970405',
        ];

        return $bankCodes[$bankName] ?? '';
    }
}
