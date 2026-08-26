@extends('layouts.admin')

@section('content')

<!-- Alert Messages -->
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        <i class="fa-solid fa-circle-check me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <i class="fa-solid fa-circle-exclamation me-2"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Heading List matching Library List exactly -->
<div class="heading-list py-4 d-flex justify-content-between align-items-center">
    <h4 class="mb-0">All Blog Posts</h4>
    <a href="{{ route('add-blog') }}" class="btn btn-primary button w-15"><i class="fa-solid fa-plus"></i> Add Blog Post</a>
</div>

<!-- Table Container -->
<div class="row">
    <div class="col-lg-12">
        <div class="table-responsive bg-white p-3 rounded-3 shadow-xs border">
            <table class="table align-middle text-center datatable dataTable mb-0" id="datatable">
                <thead>
                    <tr>
                        <th style="font-size: 13px; font-weight: 600; width: 50px;">ID</th>
                        <th style="font-size: 13px; font-weight: 600; width: 75px;">Thumbnail</th>
                        <th class="text-start" style="font-size: 13px; font-weight: 600;">Post Title & Slug</th>
                        <th style="font-size: 13px; font-weight: 600;">Status</th>
                        <th style="font-size: 13px; font-weight: 600;">Views</th>
                        <th style="font-size: 13px; font-weight: 600;">Published Date</th>
                        <th style="font-size: 13px; font-weight: 600; width: 90px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($blogs as $value)
                        <tr>
                            <td><span class="fw-bold text-muted">#{{ $value->id }}</span></td>
                            <td>
                                <div style="width: 55px; height: 38px; overflow: hidden; border-radius: 6px; background: #f1f5f9;" class="border d-flex align-items-center justify-content-center mx-auto">
                                    <img src="{{ $value->header_image_url }}" 
                                         alt="Thumb" 
                                         onerror="this.onerror=null; this.src='https://placehold.co/120x80/18225f/ffffff?text=Blog';"
                                         style="width: 100%; height: 100%; object-fit: cover;">
                                </div>
                            </td>
                            <td class="text-start">
                                <div class="fw-bold text-dark mb-1" style="font-size: 0.92rem;">{{ $value->page_title }}</div>
                                <div class="small text-muted text-lowercase text-truncate" style="max-width: 320px; font-size: 0.82rem;">
                                    <i class="fa-solid fa-link text-primary me-1" style="font-size: 11px;"></i> /blog/{{ $value->page_slug }}
                                </div>
                            </td>
                            <td>
                                @if($value->status == 'published')
                                    <span class="badge bg-success px-2 py-1">Published</span>
                                @elseif($value->status == 'draft')
                                    <span class="badge bg-warning text-dark px-2 py-1">Draft</span>
                                @else
                                    <span class="badge bg-info text-dark px-2 py-1">Scheduled</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border px-2 py-1">
                                    <i class="fa-solid fa-eye text-primary me-1"></i> {{ number_format($value->views_count ?? 0) }}
                                </span>
                            </td>
                            <td>
                                <small class="text-muted">
                                    {{ $value->published_at ? $value->published_at->format('d M Y') : $value->created_at->format('d M Y') }}
                                </small>
                            </td>
                            <td>
                                <ul class="actionalbls" style="width: 80px; margin: 0 auto;">
                                    <li>
                                        <a href="{{ route('blog-detail', ['slug' => $value->page_slug]) }}" target="_blank" data-bs-toggle="tooltip" title="View Public Post">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('blog.edit', $value->id) }}" data-bs-toggle="tooltip" title="Edit Blog & SEO">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection