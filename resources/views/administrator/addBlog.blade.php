@extends('layouts.admin')

@section('body_class', 'ax-custom-page')

@section('page_actions')
    <x-ui.button variant="gray" icon="fa-solid fa-arrow-left" :href="route('blogs')" class="hidden sm:inline-flex">All articles</x-ui.button>
    <x-ui.button type="submit" form="blogForm" name="post_action" value="published" icon="fa-solid fa-paper-plane">{{ isset($data) ? 'Update' : 'Publish' }}</x-ui.button>
@endsection

@push('styles')
<style>
    /* CKEditor 5 */
    .ab-editor .ck-editor { width: 100%; border-radius: var(--r-md); overflow: hidden; }
    .ab-editor .ck.ck-toolbar { background: var(--surface-2); border-color: var(--sep-strong); padding: 4px 8px; }
    .ab-editor .ck.ck-editor__main > .ck-editor__editable { border-color: var(--sep-strong); }
    .ab-editor .ck.ck-editor__editable_inline { min-height: 420px; font-size: 16px; line-height: 1.7; color: var(--label); padding: 4px 20px; background: var(--surface); }
    .ab-editor .ck.ck-editor__editable.ck-focused { border-color: var(--tint); box-shadow: 0 0 0 3px var(--tint-ring); }
    /* Tagify */
    .ab-tags .tagify { --tags-border-color: transparent; --tags-hover-border-color: transparent; --tags-focus-border-color: var(--tint); --tag-bg: var(--tint); --tag-hover: var(--tint-hover); --tag-text-color: #fff; --tag-remove-btn-color: #fff; --tag-remove-bg: rgba(255,255,255,.2); --tag-pad: 3px 10px; --placeholder-color: var(--placeholder);
        width: 100%; min-height: var(--h-md); border-radius: var(--r-md); background: var(--fill-4); padding: 3px 4px; font-size: 14px; }
    .ab-tags .tagify--focus { background: var(--surface); box-shadow: inset 0 0 0 1px var(--tint), 0 0 0 4px var(--tint-ring); }
    .ab-tags .tagify__tag > div { border-radius: var(--r-pill); font-weight: 590; font-size: 13px; }
    .ab-tags .tagify__tag > div::before { border-radius: var(--r-pill); }
    /* header image */
    .ab-drop { border: 1.5px dashed var(--sep-strong); background: var(--surface-2); border-radius: var(--r-lg); padding: 28px 16px; text-align: center; cursor: pointer; transition: border-color var(--dur-2) var(--ease), background-color var(--dur-2) var(--ease); }
    .ab-drop:hover { border-color: var(--tint); background: var(--tint-soft); }
    .ab-preview { position: relative; border-radius: var(--r-lg); overflow: hidden; box-shadow: var(--sh-0); }
    .ab-preview img { display: block; width: 100%; height: 190px; object-fit: cover; }
    .ab-preview__bar { position: absolute; inset: auto 0 0 0; display: flex; justify-content: center; gap: 8px; padding: 10px; background: linear-gradient(to top, rgba(0,0,0,.6), transparent); }
    /* Google result preview (Google's own type and colours on purpose) */
    .ab-serp { background: var(--surface-2); border-radius: var(--r-lg); padding: 16px; }
    .ab-serp__card { background: #fff; border-radius: var(--r-md); box-shadow: var(--sh-0); padding: 14px 16px; max-width: 600px; font-family: Arial, sans-serif; transition: max-width var(--dur-3) var(--ease); }
    .ab-serp__url { color: #202124; font-size: 13px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .ab-serp__title { color: #1a0dab; font-size: 19px; line-height: 1.3; margin: 4px 0; font-weight: 400; }
    .ab-serp__desc { color: #4d5156; font-size: 14px; line-height: 1.58; }
    .ab-cats { max-height: 180px; overflow-y: auto; display: flex; flex-direction: column; gap: 10px; padding: 12px 14px; border-radius: var(--r-md); background: var(--fill-4); }
</style>
@endpush

@section('content')
<x-ui.flash />

<form action="{{ isset($data) ? route('blog.store', $data->id) : route('blog.store') }}" method="POST" enctype="multipart/form-data" id="blogForm">
    @csrf

    <div class="grid gap-x-5 lg:grid-cols-[minmax(0,1fr)_360px] lg:items-start">
        {{-- Main column --}}
        <div class="min-w-0">
            <x-ui.section label="Article">
                <x-ui.card>
                    <x-ui.input name="page_title" id="page_title" label="Title" size="lg" :value="$data->page_title ?? ''" placeholder="e.g. 10 best library management apps in India (2026)" required />
                    <div class="ui-field">
                        <label class="ui-label" for="page_slug">Link<span class="req">*</span></label>
                        <div class="flex items-stretch overflow-hidden rounded-md bg-fill-4 focus-within:shadow-[inset_0_0_0_1px_var(--tint),0_0_0_4px_var(--tint-ring)]">
                            <span class="hidden items-center pl-3 text-sm text-label-3 sm:flex">{{ url('blog') }}/</span>
                            <input type="text" id="page_slug" name="page_slug" class="ui-input !bg-transparent !shadow-none ui-mono @error('page_slug') is-invalid @enderror" value="{{ old('page_slug', $data->page_slug ?? '') }}" required>
                        </div>
                        @error('page_slug')<div class="ui-error"><i class="fa-solid fa-circle-exclamation"></i>{{ $message }}</div>@enderror
                    </div>
                    <x-ui.textarea name="excerpt" id="excerpt" label="Summary" rows="3" :value="$data->excerpt ?? ''" placeholder="A short overview for previews and search results" hint="Shown as the lead paragraph and in search result snippets." />
                    <x-ui.field label="Body" for="editor" name="page_content" required class="ab-editor">
                        <textarea id="editor" name="page_content" class="ui-textarea @error('page_content') is-invalid @enderror">{{ old('page_content', $data->page_content ?? '') }}</textarea>
                    </x-ui.field>
                </x-ui.card>
            </x-ui.section>

            <x-ui.section label="Search engine">
                <x-ui.card>
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <x-ui.input name="focus_keyword" id="focus_keyword" label="Focus keyword" :value="$data->focus_keyword ?? ''" placeholder="e.g. library management software" hint="The search term this article should rank for." fieldClass="flex-1 !mb-0" />
                        <span class="ui-badge ui-badge--green ui-badge--pill self-start mt-7" id="seoOverallScore">SEO</span>
                    </div>

                    <div class="ab-serp mb-4">
                        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                            <span class="ui-subhead"><i class="fa-brands fa-google mr-1.5" style="color:#4285F4"></i>Google preview</span>
                            <div class="ui-seg ui-seg--sm" role="tablist">
                                <a href="javascript:void(0)" id="btnPreviewDesktop" class="is-active" onclick="setPreviewMode('desktop')"><i class="fa-solid fa-desktop mr-1"></i>Desktop</a>
                                <a href="javascript:void(0)" id="btnPreviewMobile" onclick="setPreviewMode('mobile')"><i class="fa-solid fa-mobile-screen mr-1"></i>Mobile</a>
                            </div>
                        </div>
                        <div id="serpPreviewBox" class="ab-serp__card">
                            <div class="flex items-center gap-2">
                                <img src="{{ asset('public/img/favicon.ico') }}" alt="" width="16" height="16" onerror="this.src='https://google.com/favicon.ico'">
                                <div class="ab-serp__url" id="serpUrlDisplay">{{ url('blog') }} › <span id="serpSlugSpan">{{ $data->page_slug ?? 'your-post-slug' }}</span></div>
                            </div>
                            <h5 class="ab-serp__title" id="serpTitleDisplay">{{ $data->meta_title ?? ($data->page_title ?? 'Your Post Title Will Appear Here') }}</h5>
                            <div class="ab-serp__desc" id="serpDescDisplay">{{ $data->meta_description ?? ($data->excerpt ?? 'Provide a descriptive meta snippet to attract user clicks on Google search results page.') }}</div>
                        </div>
                    </div>

                    <div class="ui-label">Checklist</div>
                    <ul class="ui-list mb-5 overflow-hidden rounded-md shadow-0" id="seoChecklist">
                        @foreach([
                            'chkFocusKeyword' => ['Focus keyword set', null],
                            'chkTitleKeyword' => ['Focus keyword in the title', null],
                            'chkTitleLength' => ['Title is 40–65 characters', 'lblTitleLen'],
                            'chkDescLength' => ['Description is 110–165 characters', 'lblDescLen'],
                            'chkSlugKeyword' => ['Focus keyword in the link', null],
                            'chkImgAlt' => ['Header image has alt text', null],
                        ] as $chkId => [$chkLabel, $lenId])
                            <li id="{{ $chkId }}">
                                <div class="ui-row !min-h-11 !py-2">
                                    <span class="ui-row__main text-sm">{{ $chkLabel }}@if($lenId) <span class="ui-footnote">· <span id="{{ $lenId }}">0</span> chars</span>@endif</span>
                                    <span class="ui-row__trail"><span class="js-seo-badge ui-badge">Check</span></span>
                                </div>
                            </li>
                        @endforeach
                    </ul>

                    <div class="ui-fields sm:grid-cols-2">
                        <x-ui.input name="meta_title" id="meta_title" label="Meta title" :value="$data->meta_title ?? ''" placeholder="Leave blank to use the title" fieldClass="ui-span-all" />
                        <x-ui.textarea name="meta_description" id="meta_description" label="Meta description" rows="2" :value="$data->meta_description ?? ''" placeholder="Leave blank to use the summary" fieldClass="ui-span-all" />
                        <x-ui.input type="url" name="canonical_url" id="canonical_url" label="Canonical URL" :value="$data->canonical_url ?? ''" placeholder="Leave blank for the article link" />
                        <x-ui.select name="meta_robots" id="meta_robots" label="Robots" :value="$data->meta_robots ?? ''"
                            :options="['index, follow' => 'Index, follow (recommended)', 'noindex, follow' => 'Noindex, follow', 'index, nofollow' => 'Index, nofollow', 'noindex, nofollow' => 'Noindex, nofollow']" />
                        <x-ui.select name="schema_type" id="schema_type" label="Schema type" :value="$data->schema_type ?? ''"
                            :options="['BlogPosting' => 'BlogPosting (default)', 'Article' => 'Article', 'NewsArticle' => 'NewsArticle']" />
                        <x-ui.input name="meta_keyword" id="meta_keyword" label="Meta keywords" :value="$data->meta_keyword ?? ''" placeholder="Comma separated" />
                    </div>
                </x-ui.card>
            </x-ui.section>
        </div>

        {{-- Sidebar --}}
        <div class="min-w-0 lg:sticky lg:top-20">
            <x-ui.section label="Publish">
                <x-ui.card>
                    <x-ui.select name="status" id="status" label="Status" :value="$data->status ?? 'published'" :options="['published' => 'Published', 'draft' => 'Draft', 'scheduled' => 'Scheduled']" />
                    <x-ui.input type="datetime-local" name="published_at" id="published_at" label="Publish date" :value="isset($data->published_at) ? $data->published_at->format('Y-m-d\TH:i') : ''" />
                    <x-ui.input name="author_name" id="author_name" label="Author" :value="$data->author_name ?? 'Libraro Team'" />
                    <x-ui.switch name="is_featured" id="is_featured" :checked="$data->is_featured ?? 0">Featured article</x-ui.switch>
                    <x-slot:footer>
                        <div class="grid w-full gap-2">
                            <x-ui.button type="submit" name="post_action" value="published" icon="fa-solid fa-paper-plane" block>{{ isset($data) ? 'Update article' : 'Publish article' }}</x-ui.button>
                            <x-ui.button type="submit" name="post_action" value="draft" variant="gray" icon="fa-solid fa-file-pen" block>Save as draft</x-ui.button>
                        </div>
                    </x-slot:footer>
                </x-ui.card>
            </x-ui.section>

            <x-ui.section label="Header image">
                <x-ui.card>
                    <input type="file" id="header_image" name="header_image" class="hidden" onchange="readImageURL(this);" accept="image/*">
                    <div class="ab-drop mb-4 {{ isset($data) && $data->header_image ? 'hidden' : '' }}" id="dropzoneBox" onclick="document.getElementById('header_image').click();">
                        <i class="fa-solid fa-cloud-arrow-up text-2xl text-tint"></i>
                        <div class="ui-headline mt-2">Choose an image</div>
                        <div class="ui-footnote">PNG, JPG or WebP · 1200 × 630 px works best</div>
                    </div>
                    <div id="previewWrapper" class="ab-preview mb-4 {{ isset($data) && $data->header_image ? '' : 'hidden' }}">
                        <img src="{{ isset($data) && $data->header_image ? asset('public/' . $data->header_image) : '' }}" alt="Header preview" id="headerImgPreview">
                        <div class="ab-preview__bar">
                            <x-ui.button size="sm" variant="outline" icon="fa-solid fa-arrows-rotate" onclick="document.getElementById('header_image').click();">Change</x-ui.button>
                            <x-ui.button size="sm" variant="danger" icon="fa-regular fa-trash-can" onclick="removeImage();">Remove</x-ui.button>
                        </div>
                    </div>
                    @error('header_image')<div class="ui-error mb-3"><i class="fa-solid fa-circle-exclamation"></i>{{ $message }}</div>@enderror
                    <x-ui.input name="image_alt" id="image_alt" label="Alt text" :value="$data->image_alt ?? ''" placeholder="Describe the image" />
                </x-ui.card>
            </x-ui.section>

            <x-ui.section label="Categories & tags">
                <x-ui.card>
                    @php
                        $selectedCats = old('categories_id', isset($data->categories_id) ? (is_array($data->categories_id) ? $data->categories_id : json_decode($data->categories_id, true)) : []);
                        $tagsVal = old('tags');
                        if (!$tagsVal && isset($data->tags)) {
                            $tagsVal = is_array($data->tags) ? implode(',', $data->tags) : implode(',', json_decode($data->tags, true) ?? []);
                        }
                    @endphp
                    <x-ui.field label="Categories">
                        <div class="ab-cats">
                            @forelse($categories as $category)
                                <x-ui.check name="categories_id[]" :value="$category->id" id="cat_{{ $category->id }}" :checked="in_array($category->id, $selectedCats ?? [])">{{ $category->name }}</x-ui.check>
                            @empty
                                <span class="ui-footnote">No categories yet.</span>
                            @endforelse
                        </div>
                    </x-ui.field>
                    <x-ui.field label="Tags" for="tags" hint="Type a tag and press Enter." class="ab-tags">
                        <input id="tags" name="tags" value="{{ $tagsVal }}" placeholder="Type tag and press enter">
                    </x-ui.field>
                </x-ui.card>
            </x-ui.section>
        </div>
    </div>
</form>

<script src="https://cdn.ckeditor.com/ckeditor5/34.2.0/classic/ckeditor.js"></script>

<script>
    let editorInstance = null;

    document.addEventListener("DOMContentLoaded", function() {
        // 1. Tagify Tags Init (Auto Height)
        if (document.querySelector('#tags')) {
            new Tagify(document.querySelector('#tags'), {
                placeholder: "Type tag and press enter..."
            });
        }

        // 2. Instant Real-Time Auto Slug Generator on Input Event
        const pageTitleInput = document.getElementById('page_title');
        const pageSlugInput = document.getElementById('page_slug');

        if (pageTitleInput && pageSlugInput) {
            pageTitleInput.addEventListener('input', function() {
                let title = this.value;
                let slug = title
                    .toLowerCase()
                    .trim()
                    .replace(/[^a-z0-9\s-]/g, '')
                    .replace(/\s+/g, '-')
                    .replace(/-+/g, '-');

                pageSlugInput.value = slug;
                updateSeoPreview();
            });

            pageSlugInput.addEventListener('input', function() {
                updateSeoPreview();
            });
        }

        // Event Listeners for SEO Inputs
        ['meta_title', 'meta_description', 'excerpt', 'focus_keyword', 'image_alt'].forEach(id => {
            let elem = document.getElementById(id);
            if (elem) {
                elem.addEventListener('input', function() {
                    updateSeoPreview();
                });
            }
        });

        // 3. CKEditor 5 Initialization with Multi-Global Fallback
        const editorTarget = document.querySelector('#editor');
        if (editorTarget) {
            let createClassicEditor = null;
            if (typeof ClassicEditor !== 'undefined') {
                createClassicEditor = ClassicEditor;
            } else if (typeof CKEDITOR !== 'undefined' && CKEDITOR.ClassicEditor) {
                createClassicEditor = CKEDITOR.ClassicEditor;
            }

            if (createClassicEditor) {
                createClassicEditor
                    .create(editorTarget, {
                        placeholder: 'Compose rich blog article content here (Type paragraphs, headings, tables, quotes, code blocks)...'
                    })
                    .then(editor => {
                        editorInstance = editor;
                        editor.model.document.on('change:data', () => {
                            editorTarget.value = editor.getData();
                            runSeoCheck();
                        });
                    })
                    .catch(error => {
                        console.error("CKEditor initialization error:", error);
                        editorTarget.style.display = 'block';
                        editorTarget.style.minHeight = '350px';
                    });
            } else {
                editorTarget.style.display = 'block';
                editorTarget.style.minHeight = '350px';
            }
        }

        // Form Submit Sync Guarantee
        const blogForm = document.getElementById('blogForm');
        if (blogForm) {
            blogForm.addEventListener('submit', function() {
                if (editorInstance) {
                    document.querySelector('#editor').value = editorInstance.getData();
                }
            });
        }

        updateSeoPreview();
        runSeoCheck();
    });

    function readImageURL(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('headerImgPreview').src = e.target.result;
                let previewWrapper = document.getElementById('previewWrapper');
                let dropzoneBox = document.getElementById('dropzoneBox');
                if (previewWrapper) previewWrapper.classList.remove('hidden');
                if (dropzoneBox) dropzoneBox.classList.add('hidden');
                if (typeof runSeoCheck === 'function') runSeoCheck();
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    function removeImage() {
        document.getElementById('header_image').value = '';
        document.getElementById('headerImgPreview').src = '';
        let previewWrapper = document.getElementById('previewWrapper');
        let dropzoneBox = document.getElementById('dropzoneBox');
        if (previewWrapper) previewWrapper.classList.add('hidden');
        if (dropzoneBox) dropzoneBox.classList.remove('hidden');
        if (typeof runSeoCheck === 'function') runSeoCheck();
    }

    function setPreviewMode(mode) {
        let box = document.getElementById('serpPreviewBox');
        let btnDesk = document.getElementById('btnPreviewDesktop');
        let btnMob = document.getElementById('btnPreviewMobile');

        box.style.maxWidth = mode === 'mobile' ? '360px' : '600px';
        btnMob.classList.toggle('is-active', mode === 'mobile');
        btnDesk.classList.toggle('is-active', mode !== 'mobile');
    }

    function updateSeoPreview() {
        let titleVal = document.getElementById('meta_title').value || document.getElementById('page_title').value || 'Your Post Title Will Appear Here';
        let descVal = document.getElementById('meta_description').value || document.getElementById('excerpt').value || 'Provide a descriptive meta snippet to attract user clicks on Google search results page.';
        let slugVal = document.getElementById('page_slug').value || 'your-post-slug';

        document.getElementById('serpTitleDisplay').innerText = titleVal;
        document.getElementById('serpDescDisplay').innerText = descVal;
        
        let slugSpan = document.getElementById('serpSlugSpan');
        if (slugSpan) slugSpan.innerText = slugVal;

        document.getElementById('lblTitleLen').innerText = titleVal.length;
        document.getElementById('lblDescLen').innerText = descVal.length;

        runSeoCheck();
    }

    function runSeoCheck() {
        let keyword = (document.getElementById('focus_keyword').value || '').toLowerCase().trim();
        let title = (document.getElementById('meta_title').value || document.getElementById('page_title').value || '').toLowerCase();
        let slug = (document.getElementById('page_slug').value || '').toLowerCase();
        let desc = (document.getElementById('meta_description').value || document.getElementById('excerpt').value || '').toLowerCase();
        let imgAlt = (document.getElementById('image_alt').value || '').trim();

        // 1. Focus Keyword set
        setBadge('chkFocusKeyword', keyword.length > 0);

        // 2. Keyword in Title
        setBadge('chkTitleKeyword', keyword.length > 0 && title.includes(keyword));

        // 3. Title length
        let titleLen = title.length;
        setBadge('chkTitleLength', titleLen >= 40 && titleLen <= 65);

        // 4. Desc length
        let descLen = desc.length;
        setBadge('chkDescLength', descLen >= 110 && descLen <= 165);

        // 5. Keyword in Slug
        setBadge('chkSlugKeyword', keyword.length > 0 && slug.includes(keyword.replace(/\s+/g, '-')));

        // 6. Image Alt
        setBadge('chkImgAlt', imgAlt.length > 0);

        // overall score in the card head
        let good = document.querySelectorAll('#seoChecklist .js-seo-badge.ui-badge--green').length;
        let overall = document.getElementById('seoOverallScore');
        if (overall) {
            overall.className = 'ui-badge ui-badge--pill ' + (good >= 5 ? 'ui-badge--green' : (good >= 3 ? 'ui-badge--orange' : 'ui-badge--red'));
            overall.textContent = 'SEO ' + good + '/6';
        }
    }

    function setBadge(elemId, isSuccess) {
        let li = document.getElementById(elemId);
        if (!li) return;
        let badge = li.querySelector('.js-seo-badge');
        if (!badge) return;
        if (isSuccess) {
            badge.className = 'js-seo-badge ui-badge ui-badge--green';
            badge.innerHTML = '<i class="fa-solid fa-check"></i>Good';
        } else {
            badge.className = 'js-seo-badge ui-badge ui-badge--orange';
            badge.innerHTML = 'Improve';
        }
    }
</script>
@endsection
