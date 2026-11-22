<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\Learner;
use App\Models\LearnerTransaction;
use App\Models\Library;
use App\Services\LearnerPayLinkService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Pay-to-library links from the renewal / pending-payment reminders (docs/QR_PAY_LINK.md).
 * Public pages (signed URL, no login) + the library's approval of a due payment.
 */
class LearnerPayLinkController extends Controller
{
    public function __construct(private LearnerPayLinkService $links) {}

    /** GET /pay/{learner}/{for} — mobile page: amount, branch UPI QR, screenshot upload. */
    public function show(Request $request, $learnerId, $for)
    {
        if (! $request->hasValidSignature()) {
            return response()->view('qrcode.pay_link', ['expired' => true, 'branch' => $this->branchOf($learnerId)], 403);
        }
        $learner = $this->learnerOr404($learnerId, $for);
        $ctx = $this->links->context($learner, $for);

        $qrSvg = null;
        $upiLink = null;
        if ($ctx['state'] === 'pay' && $ctx['amount'] > 0) {
            $upiLink = $this->upiLink($ctx);
            $qrSvg = QrCode::size(220)->margin(0)->generate($upiLink);
        }

        return view('qrcode.pay_link', $ctx + [
            'expired' => false,
            'qrSvg' => $qrSvg,
            'upiLink' => $upiLink,
            'formUrl' => $request->fullUrl(),
        ]);
    }

    /** POST /pay/{learner}/{for} — the payment screenshot. */
    public function submit(Request $request, $learnerId, $for)
    {
        if (! $request->hasValidSignature()) {
            return response()->view('qrcode.pay_link', ['expired' => true, 'branch' => $this->branchOf($learnerId)], 403);
        }
        $learner = $this->learnerOr404($learnerId, $for);

        $request->validate([
            'payment_screenshot' => 'required|image|mimes:jpg,jpeg,png,webp|max:4096',
        ], [
            'payment_screenshot.required' => 'Please add the screenshot of your payment.',
            'payment_screenshot.image' => 'The screenshot must be a photo (JPG, PNG or WEBP).',
            'payment_screenshot.mimes' => 'The screenshot must be a photo (JPG, PNG or WEBP).',
            'payment_screenshot.max' => 'The screenshot is too big (4 MB at most).',
        ]);

        $file = $request->file('payment_screenshot');
        $folder = public_path('upload/qr_payment');
        if (! File::exists($folder)) {
            File::makeDirectory($folder, 0777, true);
        }
        $name = 'payment' . time() . '_' . $learner->id . '.' . strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $file->move($folder, $name);
        $path = 'public/upload/qr_payment/' . $name;

        try {
            $booking = $this->links->submit($learner, $for, $path);
        } catch (\RuntimeException $e) {
            File::delete(public_path('upload/qr_payment/' . $name));

            return redirect()->to($request->fullUrl())->with('error', $e->getMessage());
        }

        $this->notifyLibrary($booking);

        return redirect()->to($request->fullUrl())->with('success', 'Payment screenshot sent. The library will verify it soon.');
    }

    /**
     * Library: verify a due-payment screenshot (Dashboard → Online / QR bookings).
     * Records the money like Learners → Collect pending (online), oldest dues first.
     */
    public function approveDue(Request $request, $id, LearnerController $learners)
    {
        $booking = Booking::whereHas('branch', fn ($q) => $q->where('library_id', getLibraryId()))
            ->where('type', LearnerPayLinkService::TYPE_DUE)
            ->findOrFail($id);

        if ((int) $booking->branch_id !== (int) getCurrentBranch()) {
            return redirect()->back()->with('error', 'Switch to the branch of this payment first.');
        }

        $request->validate(['amount' => 'nullable|numeric|min:1']);

        $learnerId = LearnerTransaction::withoutGlobalScopes()->where('id', $booking->transaction_id)->value('learner_id');
        if (! $learnerId) {
            return redirect()->back()->with('error', 'The learner of this payment was not found.');
        }

        $pending = (float) LearnerTransaction::where('learner_id', $learnerId)->where('pending_amount', '>', 0)->sum('pending_amount');
        if ($pending <= 0) {
            return redirect()->back()->with('error', 'This learner has no pending amount any more. Delete this request if it is not needed.');
        }

        $amount = round((float) ($request->input('amount') ?: $booking->total_amount), 2);
        if ($amount <= 0) {
            return redirect()->back()->with('error', 'Enter the amount you received.');
        }
        if ($amount > $pending) {
            return redirect()->back()->with('error', 'Amount is more than the pending amount (₹' . rtrim(rtrim(number_format($pending, 2, '.', ''), '0'), '.') . ').');
        }

        DB::beginTransaction();
        try {
            // payment mode 1 = online (the learner paid the branch UPI)
            $learners->updateLearnerTransactionPayment($learnerId, $amount, $pending, 1, null);
            $booking->delete();
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Due payment approve failed', ['booking' => $booking->id, 'error' => $e->getMessage()]);

            return redirect()->back()->with('error', 'Could not record the payment. Please try again.');
        }

        return redirect()->back()->with('success', 'Payment of ₹' . rtrim(rtrim(number_format($amount, 2, '.', ''), '0'), '.') . ' recorded for ' . $booking->name . '.');
    }

    private function learnerOr404($learnerId, $for): Learner
    {
        abort_unless(in_array($for, LearnerPayLinkService::PURPOSES, true), 404);

        return Learner::withoutGlobalScopes()->whereNull('deleted_at')->findOrFail((int) $learnerId);
    }

    private function branchOf($learnerId): ?Branch
    {
        $branchId = Learner::withoutGlobalScopes()->where('id', (int) $learnerId)->value('branch_id');

        return $branchId ? Branch::withoutGlobalScopes()->with(['library', 'city', 'state', 'hour'])->find($branchId) : null;
    }

    private function upiLink(array $ctx): string
    {
        $branch = $ctx['branch'];
        $payee = $branch->display_name ?: ($branch->library->library_name ?? $branch->name ?? 'Library');
        $note = ($ctx['purpose'] === 'due' ? 'Dues ' : 'Renew ') . ($ctx['learner']->learner_no ?: $ctx['learner']->name);
        $amount = number_format((float) $ctx['amount'], 2, '.', '');

        return 'upi://pay?pa=' . rawurlencode(trim($branch->upi_id)) . '&pn=' . rawurlencode($payee)
            . '&am=' . $amount . '&cu=INR&tn=' . rawurlencode(mb_substr($note, 0, 50));
    }

    /** Same e-mail the QR booking screenshot sends; a mail failure never blocks the learner. */
    private function notifyLibrary(Booking $booking): void
    {
        try {
            $branch = Branch::withoutGlobalScopes()->where('id', $booking->branch_id)->first(['id', 'email', 'library_id']);
            $email = $branch?->email ?: Library::where('id', $branch?->library_id)->value('email');
            if ($email) {
                $subject = $booking->type === LearnerPayLinkService::TYPE_DUE ? 'Pending payment received — please verify' : 'Renewal payment received — please verify';
                Mail::send('email.notify-email', ['booking' => $booking], function ($message) use ($email, $subject) {
                    $message->to($email)->subject($subject);
                });
            }
        } catch (\Throwable $e) {
            Log::error('Pay link mail failed: ' . $e->getMessage());
        }
    }
}
