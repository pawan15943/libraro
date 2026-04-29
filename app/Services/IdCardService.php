<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Learner;
use App\Models\LearnerDetail;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class IdCardService
{
    public function findLearnerDetail(int $learnerDetailId): LearnerDetail
    {
        $learnerDetail = LearnerDetail::withoutGlobalScopes()
            ->with(['learner', 'planType'])
            ->where('id', $learnerDetailId)
            ->first();

        if (! $learnerDetail) {
            abort(404, 'Learner detail not found');
        }

        return $learnerDetail;
    }

    /**
     * @return array{learner_detail: LearnerDetail, branch: Branch|null}
     */
    public function buildViewData(LearnerDetail $learnerDetail): array
    {
        $branch = Branch::withoutGlobalScopes()
            ->where('id', $learnerDetail->branch_id)
            ->with('city', 'state')
            ->first();

        return [
            'learner_detail' => $learnerDetail,
            'branch' => $branch,
        ];
    }

    public function viewResponse(LearnerDetail $learnerDetail): Response
    {
        return response()->view('learner.id_card_template', array_merge(
            $this->buildViewData($learnerDetail),
            [
                'is_pdf_render' => false,
                'is_api_render' => true,
            ]
        ));
    }

    public function pdfResponse(LearnerDetail $learner_detail): Response
    {
        $learner_detail = LearnerDetail::where('id', $learner_detail->id)->with(['learner','planType'])->first();
        $branch = Branch::where('id', $learner_detail->branch_id)->with('city', 'state')->first();
        $pdf = Pdf::loadView('learner.id_card_template', compact('learner_detail', 'branch'));

        return $pdf->stream('id-card-' . $learner_detail->id . '.pdf');
    }

    /**
     * @return array{id_card_url: string, id_card_mobile_url: string}
     */
    public function idCardLinks(int $learnerDetailId): array
    {
        $learnerDetail = $this->findLearnerDetail($learnerDetailId);
        $pdfUrl = URL::signedRoute('idCard.signed.pdf', ['id' => $learnerDetail->id]);

        return [
            'id_card_url' => $pdfUrl,
            'id_card_pdf_url' => $pdfUrl,
        ];
    }
}
