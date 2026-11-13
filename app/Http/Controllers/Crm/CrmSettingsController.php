<?php

namespace App\Http\Controllers\Crm;

use App\Models\Crm\CrmDisposition;
use App\Models\Crm\CrmLead;
use App\Models\Crm\CrmStage;
use App\Models\Crm\CrmTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** Stages + sub-stages, call dispositions and WhatsApp / SMS templates. */
class CrmSettingsController extends CrmBaseController
{
    public function index(Request $request)
    {
        return view('crm.settings', [
            'tab' => in_array($request->get('tab'), ['stages', 'dispositions', 'templates'], true) ? $request->get('tab') : 'stages',
            'allStages' => CrmStage::main()->with('children')->withCount('children')->get(),
            'leadCounts' => CrmLead::selectRaw('stage_id, COUNT(*) n')->groupBy('stage_id')->pluck('n', 'stage_id'),
            'subCounts' => CrmLead::selectRaw('sub_stage_id, COUNT(*) n')->whereNotNull('sub_stage_id')->groupBy('sub_stage_id')->pluck('n', 'sub_stage_id'),
            'allDispositions' => CrmDisposition::with(['stage', 'subStage'])->orderBy('sort')->get(),
            'allTemplates' => CrmTemplate::orderBy('channel')->orderBy('sort')->get(),
        ]);
    }

    public function storeStage(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'parent_id' => ['nullable', Rule::exists('crm_stages', 'id')->whereNull('parent_id')],
            'color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'outcome' => ['nullable', 'in:open,won,lost'],
        ]);
        $parent = ! empty($data['parent_id']) ? CrmStage::find($data['parent_id']) : null;
        $outcome = $data['outcome'] ?? 'open';

        CrmStage::create([
            'parent_id' => $parent?->id,
            'name' => $data['name'],
            'slug' => Str::slug(($parent ? $parent->name.' ' : '').$data['name']).'-'.Str::lower(Str::random(4)),
            'color' => $parent?->color ?? ($data['color'] ?? '#64748b'),
            'sort' => (int) CrmStage::where('parent_id', $parent?->id)->max('sort') + 10,
            'is_closed' => $parent ? $parent->is_closed : $outcome !== 'open',
            'is_won' => $parent ? $parent->is_won : $outcome === 'won',
        ]);

        return redirect()->route('crm.settings.index', ['tab' => 'stages'])->with('success', ($parent ? 'Sub-stage' : 'Stage').' added.');
    }

    public function updateStage(Request $request, int $id)
    {
        $stage = CrmStage::findOrFail($id);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'sort' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $stage->update([
            'name' => $data['name'],
            'color' => $data['color'] ?? $stage->color,
            'sort' => $data['sort'] ?? $stage->sort,
            'is_active' => $request->boolean('is_active'),
        ]);
        if (! $stage->parent_id && ! empty($data['color'])) {
            $stage->children()->update(['color' => $data['color']]);
        }

        return redirect()->route('crm.settings.index', ['tab' => 'stages'])->with('success', 'Saved.');
    }

    public function storeDisposition(Request $request)
    {
        $data = $this->dispositionData($request);
        $data['slug'] = Str::slug($data['name']).'-'.Str::lower(Str::random(4));
        $data['sort'] = (int) CrmDisposition::max('sort') + 10;
        CrmDisposition::create($data);

        return redirect()->route('crm.settings.index', ['tab' => 'dispositions'])->with('success', 'Disposition added.');
    }

    public function updateDisposition(Request $request, int $id)
    {
        CrmDisposition::findOrFail($id)->update($this->dispositionData($request) + ['is_active' => $request->boolean('is_active')]);

        return redirect()->route('crm.settings.index', ['tab' => 'dispositions'])->with('success', 'Saved.');
    }

    private function dispositionData(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'is_connected' => ['nullable', 'boolean'],
            'stage_id' => ['nullable', Rule::exists('crm_stages', 'id')->whereNull('parent_id')],
            'sub_stage_id' => ['nullable', 'integer'],
            'follow_up_minutes' => ['nullable', 'integer', 'min:5', 'max:43200'],
            'color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);
        if (! empty($data['sub_stage_id']) && ! CrmStage::where('id', $data['sub_stage_id'])->where('parent_id', $data['stage_id'] ?? 0)->exists()) {
            $data['sub_stage_id'] = null;
        }

        return [
            'name' => $data['name'],
            'is_connected' => $request->boolean('is_connected'),
            'stage_id' => $data['stage_id'] ?? null,
            'sub_stage_id' => $data['sub_stage_id'] ?? null,
            'follow_up_minutes' => $data['follow_up_minutes'] ?? null,
            'requires_follow_up' => ! empty($data['follow_up_minutes']),
            'color' => $data['color'] ?? '#64748b',
        ];
    }

    public function storeTemplate(Request $request)
    {
        CrmTemplate::create($this->templateData($request) + ['sort' => (int) CrmTemplate::max('sort') + 10]);

        return redirect()->route('crm.settings.index', ['tab' => 'templates'])->with('success', 'Template added.');
    }

    public function updateTemplate(Request $request, int $id)
    {
        CrmTemplate::findOrFail($id)->update($this->templateData($request) + ['is_active' => $request->boolean('is_active')]);

        return redirect()->route('crm.settings.index', ['tab' => 'templates'])->with('success', 'Saved.');
    }

    public function destroyTemplate(int $id)
    {
        CrmTemplate::findOrFail($id)->delete();

        return redirect()->route('crm.settings.index', ['tab' => 'templates'])->with('success', 'Template deleted.');
    }

    private function templateData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'channel' => ['required', 'in:whatsapp,sms'],
            'body' => ['required', 'string', 'max:2000'],
        ]);
    }
}
