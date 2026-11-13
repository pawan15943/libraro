@extends('layouts.admin')

@section('body_class', 'ax-custom-page')

@section('page_actions')
    <x-ui.button variant="gray" icon="fa-solid fa-arrow-left" :href="route('page')" class="hidden sm:inline-flex">Pages</x-ui.button>
    <x-ui.button type="submit" form="pageForm" icon="fa-solid fa-check">{{ isset($page) ? 'Update' : 'Save' }}</x-ui.button>
@endsection

@push('styles')
<style>
    .cp-editor .ck-editor { width: 100%; border-radius: var(--r-md); overflow: hidden; }
    .cp-editor .ck.ck-toolbar { background: var(--surface-2); border-color: var(--sep-strong); }
    .cp-editor .ck.ck-editor__main > .ck-editor__editable { border-color: var(--sep-strong); }
    .cp-editor .ck-editor__editable { min-height: 260px; font-size: 15px; line-height: 1.65; color: var(--label); }
    .cp-editor .ck.ck-editor__editable.ck-focused { border-color: var(--tint); box-shadow: 0 0 0 3px var(--tint-ring); }
</style>
@endpush

@section('content')
<x-ui.flash />

<form action="{{ isset($page) ? route('page.store', $page->id) : route('page.store') }}" method="POST" enctype="multipart/form-data" id="pageForm">
    @csrf

    <x-ui.section label="Page">
        <x-ui.card>
            <x-ui.input name="page_title" id="page_title" label="Title" size="lg" :value="$page->page_title ?? ''" onkeyup="generateSlug()" />
            <div class="ui-fields sm:grid-cols-2">
                <x-ui.input name="page_slug" id="page_slug" label="Slug" :value="$page->page_slug ?? ''" class="ui-mono" hint="Filled in from the title" />
                <x-ui.input name="route" id="route" label="Route" :value="$page->route ?? ''" class="ui-mono" />
            </div>
            <x-ui.field label="Content" for="page_content" name="page_content" class="cp-editor">
                <textarea id="page_content" name="page_content" rows="10" class="ui-textarea @error('page_content') is-invalid @enderror">{{ old('page_content', $page->page_content ?? '') }}</textarea>
            </x-ui.field>
        </x-ui.card>
    </x-ui.section>

    <x-ui.section label="Search engine & sharing">
        <x-ui.card>
            <x-ui.input name="meta_title" id="meta_title" label="Meta title" :value="$page->meta_title ?? ''" />
            <div class="ui-fields md:grid-cols-2">
                <x-ui.textarea name="meta_description" id="meta_description" label="Meta description" :value="$page->meta_description ?? ''" />
                <x-ui.textarea name="meta_keyword" id="meta_keyword" label="Meta keywords" :value="$page->meta_keyword ?? ''" />
                <x-ui.textarea name="meta_og" id="meta_og" label="Open Graph tags" :value="$page->meta_og ?? ''" class="ui-mono" />
                <x-ui.textarea name="page_schema" id="page_schema" label="Schema (JSON-LD)" :value="$page->page_schema ?? ''" class="ui-mono" />
            </div>
            <x-slot:footer>
                <x-ui.button variant="gray" :href="route('page')">Cancel</x-ui.button>
                <x-ui.button type="submit" icon="fa-solid fa-check">{{ isset($page) ? 'Update page' : 'Save page' }}</x-ui.button>
            </x-slot:footer>
        </x-ui.card>
    </x-ui.section>
</form>

<script src="https://cdn.ckeditor.com/ckeditor5/34.2.0/classic/ckeditor.js"></script>

<script>
    ClassicEditor.create(document.querySelector('#page_content'))
        .catch(error => {
            console.error(error);
        });
</script>

<script>
    // Function to generate the slug based on page title
    function generateSlug() {
        var title = document.getElementById('page_title').value;
        var slug = title
            .toLowerCase() // Convert to lowercase
            .replace(/[^a-z0-9\s-]/g, '') // Remove special characters
            .replace(/\s+/g, '-') // Replace spaces with hyphens
            .replace(/-+/g, '-'); // Replace multiple hyphens with a single hyphen

        document.getElementById('page_slug').value = slug;
    }
</script>
@endsection
