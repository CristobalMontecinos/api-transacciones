<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Exception;

class TransactionController extends Controller
{
    const DAILY_LIMIT = 5000;

    // GET /api/transactions
    public function index()
    {
        try {
            $transactions = Transaction::with(['sender:id,name,email', 'receiver:id,name,email'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json($transactions, 200);
        } catch (Exception $e) {
            return response()->json(
                [
                    'message' => 'Error al obtener las transacciones',
                    'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
                ],
                500
            );
        }
    }

    // POST /api/transactions
    public function store(Request $request)
    {
        try {
            $validated = $request->validate(
                [
                    'sender_id' => 'required|exists:users,id',
                    'receiver_id' => 'required|exists:users,id|different:sender_id',
                    'amount' => 'required|numeric|min:0.01|max:999999.99',
                    'description' => 'nullable|string|max:255'
                ],
                [
                    'sender_id.exists' => 'El usuario emisor no existe',
                    'sender_id.required' => 'El campo emisor es requerido',
                    'receiver_id.required' => 'El campo receptor es requerido',
                    'amount.required' => 'El campo monto es requerido',
                    'amount.numeric' => 'El monto debe ser numérico',
                    'receiver_id.different' => 'No puedes transferir dinero a ti mismo',
                    'receiver_id.exists' => 'El usuario receptor no existe',
                    'amount.min' => 'El monto debe ser mayor a 0',
                    'amount.max' => 'El monto excede el límite permitido'
                ]
            );

            DB::beginTransaction();

            $sender = User::lockForUpdate()->findOrFail($validated['sender_id']);
            $receiver = User::lockForUpdate()->findOrFail($validated['receiver_id']);

            // V1: Verificar saldo suficiente
            $currentBalance = $sender->getCurrentBalance();
            if ($currentBalance < $validated['amount']) {
                throw ValidationException::withMessages([
                    'amount' => ["Saldo insuficiente. Saldo disponible: $" . number_format($currentBalance, 2)]
                ]);
            }

            // V2: Verificar límite diario
            $todayTransferred = $sender->getTodayTransferredAmount();
            $newTotal = $todayTransferred + $validated['amount'];

            if ($newTotal > self::DAILY_LIMIT) {
                $remaining = self::DAILY_LIMIT - $todayTransferred;
                throw ValidationException::withMessages([
                    'amount' => [
                        "Límite diario excedido. Has transferido $" .
                        number_format($todayTransferred, 2) .
                        " hoy. Límite restante: $" .
                        number_format($remaining, 2) .
                        " Saldo disponible: $" .
                        number_format($currentBalance, 2) .
                        '.'
                    ]
                ]);
            }

            // V3: Evitar transacciones duplicadas (mismo hash en los últimos 5 minutos)
            $transactionHash = $this->generateTransactionHash($validated);
            $recentDuplicate = Transaction::where('transaction_hash', $transactionHash)
                ->where('created_at', '>', now()->subMinutes(5))
                ->exists();

            if ($recentDuplicate) {
                throw ValidationException::withMessages([
                    'transaction' => ['Transacción duplicada detectada. Por favor, espera unos minutos.']
                ]);
            }

            $transaction = Transaction::create([
                'sender_id' => $validated['sender_id'],
                'receiver_id' => $validated['receiver_id'],
                'amount' => $validated['amount'],
                'description' => $validated['description'] ?? null,
                'status' => 'completed',
                'transaction_hash' => $transactionHash,
                'completed_at' => now()
            ]);

            //Actualizar monto de usuario
            $sender->saldo_inicial = $currentBalance - $validated['amount'];
            $receiver->saldo_inicial = $receiver->saldo_inicial + $validated['amount'];

            $sender->save();
            $receiver->save();

            DB::commit();

            $transaction->load(['sender:id,name,email', 'receiver:id,name,email']);

            return response()->json(
                [
                    'message' => 'Transacción realizada exitosamente',
                    'data' => $transaction
                ],
                201
            );
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json(
                [
                    'message' => 'Error de validación',
                    'errors' => $e->errors()
                ],
                422
            );
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(
                [
                    'message' => 'Error al procesar la transacción',
                    'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
                ],
                500
            );
        }
    }

    // GET /api/transactions/{id}
    public function show(Transaction $transaction)
    {
        try {
            $transaction->load(['sender:id,name,email', 'receiver:id,name,email']);
            return response()->json($transaction, 200);
        } catch (Exception $e) {
            return response()->json(
                [
                    'message' => 'Error al obtener la transacción',
                    'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
                ],
                500
            );
        }
    }

    // PUT /api/transactions/{id} - Solo para actualizar descripción o estado
    public function update(Request $request, Transaction $transaction)
    {
        try {
            $validated = $request->validate([
                'description' => 'sometimes|nullable|string|max:255',
                'status' => 'sometimes|in:pending,completed,failed'
            ]);

            $transaction->update($validated);

            return response()->json(
                [
                    'message' => 'Transacción actualizada exitosamente',
                    'data' => $transaction
                ],
                200
            );
        } catch (ValidationException $e) {
            return response()->json(
                [
                    'message' => 'Error de validación',
                    'errors' => $e->errors()
                ],
                422
            );
        } catch (Exception $e) {
            return response()->json(
                [
                    'message' => 'Error al actualizar la transacción',
                    'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
                ],
                500
            );
        }
    }

    public function getUserTransactions($userId)
    {
        try {
            $transactions = Transaction::where('sender_id', $userId)
                ->with(['sender:id,name'])
                ->where('status', 'completed')
                ->orderBy('created_at', 'desc')
                ->get()
                ->groupBy('sender.name')
                ->map(function ($group) {
                    //Sumar todos los valores de la transacción de este usuario
                    return $group->sum('amount');
                });

            return response()->json(
                count($transactions) ? $transactions : 'Este usuario no registra transacciones',
                200
            );
        } catch (Exception $e) {
            return response()->json(
                [
                    'message' => 'Error al obtener las transacciones del usuario',
                    'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
                ],
                500
            );
        }
    }

    private function generateTransactionHash(array $data)
    {
        $string = $data['sender_id'] . $data['receiver_id'] . $data['amount'] . now()->format('Y-m-d H:i');

        return hash('sha256', $string);
    }

    // GET /api/transactions/export/csv
    public function exportCSV()
    {
        try {
            $transactions = Transaction::with(['sender:id,name,email', 'receiver:id,name,email'])
                ->orderBy('created_at', 'desc')
                ->get();

            $csvData = [];

            $headers = [
                'ID',
                'Emisor ID',
                'Emisor Nombre',
                'Emisor Email',
                'Receptor ID',
                'Receptor Nombre',
                'Receptor Email',
                'Monto',
                'Descripción',
                'Estado',
                'Hash Transacción',
                'Fecha Completada',
                'Fecha Creación'
            ];

            $csvData[] = implode(';', $headers);

            foreach ($transactions as $transaction) {
                $row = [
                    $transaction->id,
                    $transaction->sender_id,
                    $transaction->sender->name ?? 'N/A',
                    $transaction->sender->email ?? 'N/A',
                    $transaction->receiver_id,
                    $transaction->receiver->name ?? 'N/A',
                    $transaction->receiver->email ?? 'N/A',
                    number_format($transaction->amount, 2, '.', ''),
                    $transaction->description ?? '',
                    $transaction->status,
                    $transaction->transaction_hash,
                    $transaction->completed_at ? $transaction->completed_at->format('Y-m-d H:i:s') : 'N/A',
                    $transaction->created_at->format('Y-m-d H:i:s')
                ];

                //Escapar punto y coma en los valores
                $row = array_map(function ($value) {
                    if (
                        strpos($value, ';') !== false ||
                        strpos($value, '"') !== false ||
                        strpos($value, "\n") !== false
                    ) {
                        return '"' . str_replace('"', '""', $value) . '"';
                    }
                    return $value;
                }, $row);

                $csvData[] = implode(';', $row);
            }

            $csv = implode("\n", $csvData);

            $filename = 'transacciones_' . date('Y-m-d_His') . '.csv';

            return response($csv, 200)
                ->header('Content-Type', 'text/csv; charset=UTF-8')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');
        } catch (Exception $e) {
            return response()->json(
                [
                    'message' => 'Error al exportar las transacciones',
                    'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
                ],
                500
            );
        }
    }

    // GET /api/transactions/stats/{userId}
    public function getUserStatistics($userId)
    {
        try {
            $user = User::findOrFail($userId);

            // Transacciones enviadas
            $sentStats = Transaction::where('sender_id', $userId)
                ->where('status', 'completed')
                ->selectRaw(
                    '
                COUNT(*) as total_transacciones_enviadas,
                COALESCE(SUM(amount), 0) as total_monto_transferido,
                COALESCE(AVG(amount), 0) as promedio_monto_transferido,
                COALESCE(MAX(amount), 0) as monto_maximo_transferido,
                COALESCE(MIN(amount), 0) as monto_minimo_transferido
            '
                )
                ->first();

            $currentBalance = $user->getCurrentBalance();

            $todayTransferred = $user->getTodayTransferredAmount();

            return response()->json(
                [
                    'message' => 'Estadísticas del usuario obtenidas exitosamente',
                    'data' => [
                        'user' => [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email,
                            'balance_actual' => number_format($currentBalance, 2, '.', '')
                        ],
                        'sent_transactions' => [
                            'cantidad' => (int) $sentStats->total_transacciones_enviadas,
                            'monto_total' => number_format($sentStats->total_monto_transferido, 2, '.', ''),
                            'monto_promedio' => number_format($sentStats->promedio_monto_transferido, 2, '.', ''),
                            'monto_maximo_transferido' => number_format($sentStats->monto_maximo_transferido, 2, '.', ''),
                            'monto_minimo_transferido' => number_format($sentStats->monto_minimo_transferido, 2, '.', '')
                        ],
                        'hoy' => [
                            'monto_transferido' => number_format($todayTransferred, 2, '.', ''),
                            'monto_restante_hoy' => number_format(self::DAILY_LIMIT - $todayTransferred, 2, '.', ''),
                            'limite_diario' => number_format(self::DAILY_LIMIT, 2, '.', '')
                        ]
                    ]
                ],
                200
            );
        } catch (ModelNotFoundException $e) {
            return response()->json(
                [
                    'message' => 'Usuario no encontrado'
                ],
                404
            );
        } catch (Exception $e) {
            return response()->json(
                [
                    'message' => 'Error al obtener las estadísticas del usuario',
                    'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
                ],
                500
            );
        }
    }
}
