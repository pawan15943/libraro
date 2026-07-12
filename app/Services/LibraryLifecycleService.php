<?php
namespace App\Services;

use App\Models\Expense;
use App\Models\LearnerTransactionActivity;
use Illuminate\Support\Facades\Auth;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LibraryLifecycleService
{
    public function storeExpense($request)
{
    /*
    |--------------------------------------------------------------------------
    | Get Expense Particular
    |--------------------------------------------------------------------------
    */

    $expense = Expense::find($request->expense_id);

    if (!$expense) {

        throw new \Exception('Expense not found');
    }

    $particular = $expense->name;

    /*
    |--------------------------------------------------------------------------
    | Other Expense Remark
    |--------------------------------------------------------------------------
    */

    if (
        strtolower($expense->name) == 'other' 
    ) {
        $particular = "Other";
    }

    /*
    |--------------------------------------------------------------------------
    | Payment Mode
    |--------------------------------------------------------------------------
    */

    $mode = match ((int) $request->payment_mode) {

        1 => 'ONLINE',

        2 => 'OFFLINE',

        3 => 'PAYLATER',

        default => 'OTHER',
    };

    /*
    |--------------------------------------------------------------------------
    | Generate Transaction ID
    |--------------------------------------------------------------------------
    */

    $year = date('Y');

    $last = LearnerTransactionActivity::where(
            'transaction_id',
            'like',
            $year . '000%'
        )
        ->latest('id')
        ->first();

    if ($last && $last->transaction_id) {

        $lastSeq = (int) substr($last->transaction_id, -4);

        $newSeq = str_pad(
            $lastSeq + 1,
            4,
            '0',
            STR_PAD_LEFT
        );

    } else {

        $newSeq = '0001';
    }

    $transactionId = $year . '0000' . $newSeq;

    /*
    |--------------------------------------------------------------------------
    | Final Data
    |--------------------------------------------------------------------------
    */

    $data = [

        'branch_id' => getCurrentBranch(),

        'learner_id' => null,

        'date' => $request->date,

        'particular' => $particular,

        'payment_type' => 'EXPENSE',

        'payment_mode' => $mode,

        'amount' => $request->amount,

        'dr_cr' => 'Dr',

        'transaction_id' => $transactionId,
    ];

    /*
    |--------------------------------------------------------------------------
    | Update / Create
    |--------------------------------------------------------------------------
    */

    if ($request->id) {

        $transaction = LearnerTransactionActivity::findOrFail(
            $request->id
        );

        $transaction->update($data);

        return [

            'message' => 'Expense updated successfully'
        ];
    }

    LearnerTransactionActivity::create($data);

    return [

        'message' => 'Expense created successfully'
    ];
}
}
