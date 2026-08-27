@extends('layouts.admin')

@section('title', 'Gallery')

@section('actions')
    <a href="{{ route('admin.gallery.create') }}" class="btn btn-primary">
        <span class="material-symbols-outlined">add</span>
        Add Image
    </a>
@endsection

@section('content')
<div class="gallery-page">

    {{-- Page Header --}}
    <div class="gallery-page-header">
        <h1 class="gallery-page-title">Gallery</h1>
        <p class="gallery-page-desc">Manage your gallery images and categories.</p>
        <span class="gallery-total-count">{{ $totalGalleries }} {{ Str::plural('image', $totalGalleries) }}</span>
    </div>

    {{-- Toolbar --}}
    <div class="gallery-toolbar">
        <form action="{{ route('admin.gallery.index') }}" method="GET" class="gallery-toolbar-form" id="gallerySearchForm">
            @if(request('category'))
                <input type="hidden" name="category" value="{{ request('category') }}">
            @endif
            @if(request('status'))
                <input type="hidden" name="status" value="{{ request('status') }}">
            @endif
            @if(request('featured'))
                <input type="hidden" name="featured" value="{{ request('featured') }}">
            @endif
            @if(request('sort'))
                <input type="hidden" name="sort" value="{{ request('sort') }}">
            @endif
            @if(request('view'))
                <input type="hidden" name="view" value="{{ request('view') }}">
            @endif
            <div class="gallery-search-wrap">
                <span class="material-symbols-outlined gallery-search-icon">search</span>
                <input
                    type="text"
                    name="search"
                    class="gallery-search-input"
                    placeholder="Search gallery..."
                    value="{{ request('search') }}"
                    id="gallerySearchInput"
                >
                @if(request('search'))
                    <button type="button" class="gallery-search-clear" onclick="clearGallerySearch()">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                @endif
            </div>

            <select class="gallery-filter-select" onchange="applyGalleryFilter('category', this.value)">
                <option value="">All Categories</option>
                @foreach($categories as $category)
                    <option value="{{ $category->slug }}" {{ request('category') == $category->slug ? 'selected' : '' }}>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>

            <select class="gallery-filter-select" onchange="applyGalleryFilter('status', this.value)">
                <option value="">All Status</option>
                <option value="published" {{ request('status') == 'published' ? 'selected' : '' }}>Published</option>
                <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
            </select>

            <select class="gallery-filter-select" onchange="applyGalleryFilter('featured', this.value)">
                <option value="">All Featured</option>
                <option value="1" {{ request('featured') == '1' ? 'selected' : '' }}>Featured</option>
                <option value="0" {{ request('featured') == '0' ? 'selected' : '' }}>Not Featured</option>
            </select>

            <select class="gallery-filter-select" onchange="applyGalleryFilter('sort', this.value)">
                <option value="" {{ !request('sort') ? 'selected' : '' }}>Newest First</option>
                <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>Oldest First</option>
                <option value="title_asc" {{ request('sort') == 'title_asc' ? 'selected' : '' }}>Title A-Z</option>
                <option value="title_desc" {{ request('sort') == 'title_desc' ? 'selected' : '' }}>Title Z-A</option>
                <option value="recently_updated" {{ request('sort') == 'recently_updated' ? 'selected' : '' }}>Recently Updated</option>
            </select>
        </form>

        <div class="gallery-toolbar-actions">
            <div class="gallery-view-toggle">
                <button
                    type="button"
                    class="gallery-view-btn {{ request('view') != 'list' ? 'active' : '' }}"
                    onclick="switchGalleryView('grid')"
                    title="Grid View"
                >
                    <span class="material-symbols-outlined">grid_view</span>
                </button>
                <button
                    type="button"
                    class="gallery-view-btn {{ request('view') == 'list' ? 'active' : '' }}"
                    onclick="switchGalleryView('list')"
                    title="List View"
                >
                    <span class="material-symbols-outlined">view_list</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Active Filters --}}
    @if(request('search') || request('category') || request('status') || request('featured') || request('sort'))
        <div class="gallery-active-filters">
            <span class="gallery-active-filters-label">Active Filters:</span>
            @if(request('search'))
                <a href="{{ route('admin.gallery.index', request()->except('search', 'page')) }}" class="gallery-filter-tag">
                    Search: {{ request('search') }}
                    <span class="material-symbols-outlined">close</span>
                </a>
            @endif
            @if(request('category'))
                <a href="{{ route('admin.gallery.index', request()->except('category', 'page')) }}" class="gallery-filter-tag">
                    Category: {{ $categories->firstWhere('slug', request('category'))?->name ?? request('category') }}
                    <span class="material-symbols-outlined">close</span>
                </a>
            @endif
            @if(request('status'))
                <a href="{{ route('admin.gallery.index', request()->except('status', 'page')) }}" class="gallery-filter-tag">
                    Status: {{ ucfirst(request('status')) }}
                    <span class="material-symbols-outlined">close</span>
                </a>
            @endif
            @if(request('featured'))
                <a href="{{ route('admin.gallery.index', request()->except('featured', 'page')) }}" class="gallery-filter-tag">
                    Featured: {{ request('featured') == '1' ? 'Yes' : 'No' }}
                    <span class="material-symbols-outlined">close</span>
                </a>
            @endif
            @if(request('sort'))
                <a href="{{ route('admin.gallery.index', request()->except('sort', 'page')) }}" class="gallery-filter-tag">
                    Sort: {{ request('sort') }}
                    <span class="material-symbols-outlined">close</span>
                </a>
            @endif
            <a href="{{ route('admin.gallery.index') }}" class="gallery-clear-all">Clear All</a>
        </div>
    @endif

    {{-- Bulk Actions Bar --}}
    <div class="gallery-bulk-bar" id="galleryBulkBar" style="display: none;">
        <span class="gallery-bulk-count" id="galleryBulkCount">0 selected</span>
        <div class="gallery-bulk-actions">
            <button type="button" class="btn btn-sm btn-outline" onclick="bulkAction('publish')">
                <span class="material-symbols-outlined">publish</span> Publish
            </button>
            <button type="button" class="btn btn-sm btn-outline" onclick="bulkAction('unpublish')">
                <span class="material-symbols-outlined">unpublish</span> Unpublish
            </button>
            <button type="button" class="btn btn-sm btn-outline" onclick="bulkAction('feature')">
                <span class="material-symbols-outlined">star</span> Feature
            </button>
            <button type="button" class="btn btn-sm btn-outline" onclick="bulkAction('unfeature')">
                <span class="material-symbols-outlined">star_off</span> Unfeature
            </button>
            <button type="button" class="btn btn-sm btn-danger" onclick="bulkAction('delete')">
                <span class="material-symbols-outlined">delete</span> Delete
            </button>
        </div>
    </div>

    {{-- Content --}}
    @if($galleryImages->count() > 0)
        @if(request('view') != 'list')
            <div class="gallery-grid" id="galleryGridView">
                @foreach($galleryImages as $galleryImage)
                    <div class="gallery-card card" data-id="{{ $galleryImage->id }}">
                        <div class="gallery-card-image">
                            @if($galleryImage->image)
                                <img
                                    src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='400' height='250'%3E%3Crect fill='%23f1f3f5' width='400' height='250'/%3E%3C/svg%3E"
                                    data-src="{{ asset('storage/' . $galleryImage->image) }}"
                                    alt="{{ $galleryImage->alt_text ?: $galleryImage->title }}"
                                    loading="lazy"
                                    decoding="async"
                                    class="lazy-img"
                                >
                            @else
                                <div class="gallery-card-image-placeholder">
                                    <span class="material-symbols-outlined">image</span>
                                </div>
                            @endif
                            <div class="gallery-card-overlay">
                                <div class="gallery-card-overlay-actions">
                                    <button
                                        type="button"
                                        class="gallery-card-overlay-btn"
                                        onclick="viewGalleryImage({{ $galleryImage->id }})"
                                        title="View"
                                    >
                                        <span class="material-symbols-outlined">visibility</span>
                                    </button>
                                    <a
                                        href="{{ route('admin.gallery.edit', $galleryImage->id) }}"
                                        class="gallery-card-overlay-btn"
                                        title="Edit"
                                    >
                                        <span class="material-symbols-outlined">edit</span>
                                    </a>
                                </div>
                            </div>
                            @if($galleryImage->is_featured)
                                <span class="gallery-card-featured-badge">
                                    <span class="material-symbols-outlined">star</span> Featured
                                </span>
                            @endif
                        </div>
                        <div class="gallery-card-body">
                            <div class="gallery-card-header">
                                <h3 class="gallery-card-title">{{ $galleryImage->title }}</h3>
                                <div class="gallery-card-dropdown">
                                    <button
                                        type="button"
                                        class="gallery-card-menu-btn icon-btn"
                                        onclick="toggleCardMenu(this)"
                                    >
                                        <span class="material-symbols-outlined">more_vert</span>
                                    </button>
                                    <div class="gallery-card-menu">
                                        <a href="{{ route('admin.gallery.show', $galleryImage->id) }}" class="gallery-card-menu-item">
                                            <span class="material-symbols-outlined">visibility</span> View
                                        </a>
                                        <a href="{{ route('admin.gallery.edit', $galleryImage->id) }}" class="gallery-card-menu-item">
                                            <span class="material-symbols-outlined">edit</span> Edit
                                        </a>
                                        <div class="gallery-card-menu-divider"></div>
                                        <button type="button" class="gallery-card-menu-item" onclick="toggleFeatured({{ $galleryImage->id }})">
                                            @if($galleryImage->is_featured)
                                                <span class="material-symbols-outlined">star_off</span> Unfeature
                                            @else
                                                <span class="material-symbols-outlined">star</span> Feature
                                            @endif
                                        </button>
                                        <button type="button" class="gallery-card-menu-item" onclick="toggleStatus({{ $galleryImage->id }})">
                                            @if($galleryImage->status == 'published')
                                                <span class="material-symbols-outlined">unpublish</span> Unpublish
                                            @else
                                                <span class="material-symbols-outlined">publish</span> Publish
                                            @endif
                                        </button>
                                        <div class="gallery-card-menu-divider"></div>
                                        <button
                                            type="button"
                                            class="gallery-card-menu-item gallery-card-menu-danger"
                                            onclick="confirmDelete({{ $galleryImage->id }}, '{{ addslashes($galleryImage->title) }}')"
                                        >
                                            <span class="material-symbols-outlined">delete</span> Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <span class="gallery-card-category">{{ $galleryImage->galleryCategory->name ?? 'Uncategorized' }}</span>
                            <div class="gallery-card-meta">
                                <span class="gallery-card-status badge {{ $galleryImage->status == 'published' ? 'badge-success' : 'badge-draft' }}">
                                    {{ ucfirst($galleryImage->status) }}
                                </span>
                                <span class="gallery-card-date">Updated {{ $galleryImage->updated_at->diffForHumans() }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="gallery-list card" id="galleryListView">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>
                                <input type="checkbox" id="gallerySelectAll" onchange="toggleSelectAll(this)">
                            </th>
                            <th>Image</th>
                            <th>Gallery Info</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Featured</th>
                            <th>Updated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($galleryImages as $galleryImage)
                            <tr data-id="{{ $galleryImage->id }}">
                                <td>
                                    <input
                                        type="checkbox"
                                        class="gallery-checkbox"
                                        value="{{ $galleryImage->id }}"
                                        onchange="updateBulkBar()"
                                    >
                                </td>
                                <td>
                                    @if($galleryImage->image)
                                        <img
                                            src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='60' height='60'%3E%3Crect fill='%23f1f3f5' width='60' height='60'/%3E%3C/svg%3E"
                                            data-src="{{ asset('storage/' . $galleryImage->image) }}"
                                            alt="{{ $galleryImage->alt_text ?: $galleryImage->title }}"
                                            style="width: 60px; height: 60px; object-fit: cover; border-radius: 6px;"
                                            loading="lazy"
                                            decoding="async"
                                            class="lazy-img"
                                        >
                                    @else
                                        <div style="width: 60px; height: 60px; background: var(--gray-100); border-radius: 6px; display: flex; align-items: center; justify-content: center;">
                                            <span class="material-symbols-outlined" style="color: var(--gray-400);">image</span>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ $galleryImage->title }}</strong>
                                    @if($galleryImage->description)
                                        <br><small style="color: var(--gray-500);">{{ Str::limit($galleryImage->description, 60) }}</small>
                                    @endif
                                </td>
                                <td>{{ $galleryImage->galleryCategory->name ?? 'Uncategorized' }}</td>
                                <td>
                                    <button
                                        type="button"
                                        class="badge {{ $galleryImage->status == 'published' ? 'badge-success' : 'badge-draft' }}"
                                        onclick="toggleStatus({{ $galleryImage->id }})"
                                        style="cursor: pointer; border: none;"
                                    >
                                        {{ ucfirst($galleryImage->status) }}
                                    </button>
                                </td>
                                <td>
                                    <button
                                        type="button"
                                        class="icon-btn"
                                        onclick="toggleFeatured({{ $galleryImage->id }})"
                                        style="cursor: pointer; border: none; background: none;"
                                    >
                                        @if($galleryImage->is_featured)
                                            <span class="material-symbols-outlined" style="color: var(--warning, #f59e0b);">star</span>
                                        @else
                                            <span class="material-symbols-outlined" style="color: var(--gray-300);">star</span>
                                        @endif
                                    </button>
                                </td>
                                <td>{{ $galleryImage->updated_at->diffForHumans() }}</td>
                                <td>
                                    <div style="display: flex; gap: 4px;">
                                        <button
                                            type="button"
                                            class="icon-btn"
                                            onclick="viewGalleryImage({{ $galleryImage->id }})"
                                            title="View"
                                        >
                                            <span class="material-symbols-outlined">visibility</span>
                                        </button>
                                        <a
                                            href="{{ route('admin.gallery.edit', $galleryImage->id) }}"
                                            class="icon-btn icon-btn-edit"
                                            title="Edit"
                                        >
                                            <span class="material-symbols-outlined">edit</span>
                                        </a>
                                        <button
                                            type="button"
                                            class="icon-btn icon-btn-delete"
                                            onclick="confirmDelete({{ $galleryImage->id }}, '{{ addslashes($galleryImage->title) }}')"
                                            title="Delete"
                                        >
                                            <span class="material-symbols-outlined">delete</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <div class="gallery-pagination">
            <div class="gallery-pagination-info">
                Showing {{ $galleryImages->firstItem() }}-{{ $galleryImages->lastItem() }} of {{ $galleryImages->total() }} images
            </div>
            <div class="gallery-pagination-links">
                {{ $galleryImages->appends(request()->query())->links() }}
            </div>
        </div>
    @else
        <div class="gallery-empty empty-state">
            @if(request('search') || request('category') || request('status') || request('featured'))
                <span class="material-symbols-outlined gallery-empty-icon">search_off</span>
                <h3>No images found</h3>
                <p>No gallery images match your current filters.</p>
                <div class="gallery-empty-actions">
                    <a href="{{ route('admin.gallery.index') }}" class="btn btn-primary">Clear Filters</a>
                </div>
            @else
                <span class="material-symbols-outlined gallery-empty-icon">photo_library</span>
                <h3>No gallery images yet</h3>
                <p>Get started by adding your first gallery image.</p>
                <div class="gallery-empty-actions">
                    <a href="{{ route('admin.gallery.create') }}" class="btn btn-primary">
                        <span class="material-symbols-outlined">add</span> Add Image
                    </a>
                </div>
            @endif
        </div>
    @endif
</div>

{{-- View Modal --}}
<div class="modal-overlay" id="galleryViewModal" style="display: none;">
    <div class="modal">
        <div class="modal-header">
            <h2 id="galleryModalTitle">Gallery Image Details</h2>
            <button type="button" class="icon-btn" onclick="closeGalleryModal()">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="modal-body">
            <div id="galleryModalImage" style="margin-bottom: 1rem;"></div>
            <div id="galleryModalContent"></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="closeGalleryModal()">Close</button>
            <a href="#" class="btn btn-primary" id="galleryModalEditBtn">
                <span class="material-symbols-outlined">edit</span> Edit
            </a>
        </div>
    </div>
</div>

{{-- Delete Confirmation Modal --}}
<div class="modal-overlay" id="galleryDeleteModal" style="display: none;">
    <div class="modal">
        <div class="modal-header">
            <h2>Delete Gallery Image</h2>
            <button type="button" class="icon-btn" onclick="closeDeleteModal()">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to delete <strong id="galleryDeleteName"></strong>?</p>
            <p style="color: var(--gray-500); margin-top: 0.5rem;">This action cannot be undone.</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="closeDeleteModal()">Cancel</button>
            <button type="button" class="btn btn-danger" id="galleryDeleteConfirmBtn">
                <span class="material-symbols-outlined">delete</span> Delete
            </button>
        </div>
    </div>
</div>

{{-- Hidden Bulk Action Form --}}
<form
    id="galleryBulkForm"
    action="{{ route('admin.gallery.bulk-action') }}"
    method="POST"
    style="display: none;"
>
    @csrf
    <input type="hidden" name="action" id="bulkActionInput" value="">
    <input type="hidden" name="ids" id="bulkIdsInput" value="">
</form>

@endsection

@push('scripts')
<script>
(function() {
    var gallerySearchTimeout = null;
    var galleryDeleteId = null;

    document.addEventListener('DOMContentLoaded', function() {
        initGallerySearch();
        initGalleryViewMode();
    });

    document.addEventListener('spa:load', function() {
        initGallerySearch();
        initGalleryViewMode();
    });

    function initGallerySearch() {
        var input = document.getElementById('gallerySearchInput');
        if (!input) return;
        input.removeEventListener('input', handleGallerySearchInput);
        input.addEventListener('input', handleGallerySearchInput);
    }

    function handleGallerySearchInput(e) {
        clearTimeout(gallerySearchTimeout);
        var val = e.target.value;
        gallerySearchTimeout = setTimeout(function() {
            document.getElementById('gallerySearchForm').submit();
        }, 500);
    }

    function initGalleryViewMode() {
        var saved = localStorage.getItem('galleryViewMode');
        var currentUrl = new URL(window.location.href);
        var currentView = currentUrl.searchParams.get('view');
        if (!currentView && saved) {
            currentUrl.searchParams.set('view', saved);
            if (typeof loadPage === 'function') {
                loadPage(currentUrl.pathname + currentUrl.search);
            } else {
                window.location.href = currentUrl.pathname + currentUrl.search;
            }
        }
    }

    window.applyGalleryFilter = function(key, value) {
        var url = new URL(window.location.href);
        if (value) {
            url.searchParams.set(key, value);
        } else {
            url.searchParams.delete(key);
        }
        url.searchParams.delete('page');
        if (typeof loadPage === 'function') {
            loadPage(url.pathname + url.search);
        } else {
            window.location.href = url.pathname + url.search;
        }
    };

    window.switchGalleryView = function(mode) {
        localStorage.setItem('galleryViewMode', mode);
        var url = new URL(window.location.href);
        if (mode === 'grid') {
            url.searchParams.delete('view');
        } else {
            url.searchParams.set('view', mode);
        }
        url.searchParams.delete('page');
        if (typeof loadPage === 'function') {
            loadPage(url.pathname + url.search);
        } else {
            window.location.href = url.pathname + url.search;
        }
    };

    window.clearGallerySearch = function() {
        var url = new URL(window.location.href);
        url.searchParams.delete('search');
        url.searchParams.delete('page');
        if (typeof loadPage === 'function') {
            loadPage(url.pathname + url.search);
        } else {
            window.location.href = url.pathname + url.search;
        }
    };

    window.toggleCardMenu = function(btn) {
        var menu = btn.nextElementSibling;
        var isOpen = menu.classList.contains('open');
        document.querySelectorAll('.gallery-card-menu.open').forEach(function(m) {
            m.classList.remove('open');
        });
        if (!isOpen) {
            menu.classList.add('open');
        }
    };

    document.addEventListener('click', function(e) {
        if (!e.target.closest('.gallery-card-dropdown')) {
            document.querySelectorAll('.gallery-card-menu.open').forEach(function(m) {
                m.classList.remove('open');
            });
        }
    });

    window.toggleSelectAll = function(checkbox) {
        var checkboxes = document.querySelectorAll('.gallery-checkbox');
        checkboxes.forEach(function(cb) {
            cb.checked = checkbox.checked;
        });
        updateBulkBar();
    };

    window.updateBulkBar = function() {
        var checkboxes = document.querySelectorAll('.gallery-checkbox:checked');
        var count = checkboxes.length;
        var bar = document.getElementById('galleryBulkBar');
        var countEl = document.getElementById('galleryBulkCount');
        if (count > 0) {
            bar.style.display = 'flex';
            countEl.textContent = count + ' selected';
        } else {
            bar.style.display = 'none';
            countEl.textContent = '0 selected';
        }
    };

    window.bulkAction = function(action) {
        var checkboxes = document.querySelectorAll('.gallery-checkbox:checked');
        if (checkboxes.length === 0) return;
        var ids = Array.from(checkboxes).map(function(cb) { return cb.value; });
        if (action === 'delete') {
            if (!confirm('Are you sure you want to delete ' + ids.length + ' selected item(s)?')) {
                return;
            }
        }
        var form = document.getElementById('galleryBulkForm');
        var existingHidden = form.querySelectorAll('input[name="ids[]"]');
        existingHidden.forEach(function(el) { el.remove(); });
        ids.forEach(function(id) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = id;
            form.appendChild(input);
        });
        document.getElementById('bulkActionInput').value = action;
        form.submit();
    };

    window.viewGalleryImage = function(id) {
        var showUrl = '{{ url("admin/gallery") }}/' + id;
        axios.get(showUrl, { params: { _format: 'json' } })
            .then(function(response) {
                var data = response.data;
                var modal = document.getElementById('galleryViewModal');
                document.getElementById('galleryModalTitle').textContent = data.title || 'Gallery Image';

                var imageHtml = '';
                if (data.image) {
                    imageHtml = '<img src="{{ asset("storage/") }}/' + data.image + '" alt="' + (data.alt_text || data.title) + '" loading="eager" fetchpriority="high" style="width: 100%; border-radius: 8px;">';
                } else {
                    imageHtml = '<div style="background: var(--gray-100); border-radius: 8px; padding: 2rem; text-align: center;"><span class="material-symbols-outlined" style="font-size: 48px; color: var(--gray-400);">image</span></div>';
                }
                document.getElementById('galleryModalImage').innerHTML = imageHtml;

                var contentHtml = '<table style="width: 100%;">';
                contentHtml += '<tr><td style="padding: 0.5rem 0; color: var(--gray-500); width: 120px;">Category</td><td>' + (data.category_name || 'Uncategorized') + '</td></tr>';
                contentHtml += '<tr><td style="padding: 0.5rem 0; color: var(--gray-500);">Alt Text</td><td>' + (data.alt_text || '-') + '</td></tr>';
                contentHtml += '<tr><td style="padding: 0.5rem 0; color: var(--gray-500);">Status</td><td>' + data.status + '</td></tr>';
                contentHtml += '<tr><td style="padding: 0.5rem 0; color: var(--gray-500);">Featured</td><td>' + (data.is_featured ? 'Yes' : 'No') + '</td></tr>';
                contentHtml += '<tr><td style="padding: 0.5rem 0; color: var(--gray-500);">Sort Order</td><td>' + (data.sort_order || 0) + '</td></tr>';
                if (data.description) {
                    contentHtml += '<tr><td style="padding: 0.5rem 0; color: var(--gray-500);">Description</td><td>' + data.description + '</td></tr>';
                }
                contentHtml += '</table>';
                document.getElementById('galleryModalContent').innerHTML = contentHtml;

                document.getElementById('galleryModalEditBtn').href = '{{ url("admin/gallery") }}/' + id + '/edit';
                modal.style.display = 'flex';
            })
            .catch(function(error) {
                console.error('Error loading gallery image:', error);
            });
    };

    window.closeGalleryModal = function() {
        document.getElementById('galleryViewModal').style.display = 'none';
    };

    window.closeDeleteModal = function() {
        document.getElementById('galleryDeleteModal').style.display = 'none';
        galleryDeleteId = null;
    };

    window.toggleStatus = function(id) {
        var statusUrl = '{{ url("admin/gallery") }}/' + id + '/toggle-status';
        axios.post(statusUrl, {
            _token: document.querySelector('meta[name="csrf-token"]').content
        })
        .then(function() {
            if (typeof loadPage === 'function') {
                loadPage(window.location.href);
            } else {
                window.location.reload();
            }
        })
        .catch(function(error) {
            console.error('Error toggling status:', error);
        });
    };

    window.toggleFeatured = function(id) {
        var featuredUrl = '{{ url("admin/gallery") }}/' + id + '/toggle-featured';
        axios.post(featuredUrl, {
            _token: document.querySelector('meta[name="csrf-token"]').content
        })
        .then(function() {
            if (typeof loadPage === 'function') {
                loadPage(window.location.href);
            } else {
                window.location.reload();
            }
        })
        .catch(function(error) {
            console.error('Error toggling featured:', error);
        });
    };

    window.confirmDelete = function(id, name) {
        galleryDeleteId = id;
        document.getElementById('galleryDeleteName').textContent = name;
        document.getElementById('galleryDeleteModal').style.display = 'flex';

        document.getElementById('galleryDeleteConfirmBtn').onclick = function() {
            if (galleryDeleteId) {
                var deleteUrl = '{{ url("admin/gallery") }}/' + galleryDeleteId;
                axios.post(deleteUrl, {
                    _token: document.querySelector('meta[name="csrf-token"]').content,
                    _method: 'DELETE'
                })
                .then(function() {
                    closeDeleteModal();
                    if (typeof loadPage === 'function') {
                        loadPage(window.location.href);
                    } else {
                        window.location.reload();
                    }
                })
                .catch(function(error) {
                    console.error('Error deleting gallery image:', error);
                });
            }
        };
    };

})();
</script>
@endpush
