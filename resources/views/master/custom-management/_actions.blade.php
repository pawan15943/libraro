{{-- Row actions. $locked rows (active learners assigned) can't be edited, deactivated or deleted.
     $lockDelete rows keep Edit but can't be deactivated or deleted. --}}
@if(!empty($locked))
    <span class="cm-locked" data-bs-toggle="tooltip" title="Active learners are assigned, so this can't be changed.">
        <i class="fa-solid fa-lock"></i> In use
    </span>
@else
    <ul class="actionalbls">
        <li>
            <a href="javascript:void(0)" data-cm-edit="{{ $modal }}" data-id="{{ $id }}" data-record="{{ json_encode($record) }}" data-bs-toggle="tooltip" title="Edit" aria-label="Edit">
                <i class="fa-solid fa-pen"></i>
            </a>
        </li>
        @if(!empty($lockDelete))
        <li>
            <span class="cm-locked" data-bs-toggle="tooltip" title="{{ $lockDeleteTitle ?? 'Active learners are assigned, so this can\'t be deactivated or deleted.' }}">
                <i class="fa-solid fa-lock"></i> In use
            </span>
        </li>
        @else
        <li>
            <a href="javascript:void(0)" class="{{ $inactive ? 'cm-act-on' : 'cm-act-off' }}" data-cm-status="{{ $table }}" data-id="{{ $id }}" data-name="{{ $name ?? '' }}" data-state="{{ $inactive ? 'inactive' : 'active' }}" data-bs-toggle="tooltip" title="{{ $inactive ? 'Activate' : 'Deactivate' }}" aria-label="{{ $inactive ? 'Activate' : 'Deactivate' }}">
                <i class="fa-solid {{ $inactive ? 'fa-rotate-left' : 'fa-ban' }}"></i>
            </a>
        </li>
        <li>
            <a href="javascript:void(0)" class="cm-act-delete" data-cm-delete="{{ $table }}" data-id="{{ $id }}" data-name="{{ $name ?? '' }}" data-bs-toggle="tooltip" title="Delete" aria-label="Delete">
                <i class="fa-solid fa-trash"></i>
            </a>
        </li>
        @endif
    </ul>
@endif
