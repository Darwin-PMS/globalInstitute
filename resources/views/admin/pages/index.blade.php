@extends('layouts.admin')

@section('title', 'Pages')

@section('actions')
    <a href="{{ route('admin.pages.create') }}" class="btn btn-primary btn-sm">
        <span class="material-symbols-outlined" style="font-size:18px;">add</span> Add Page
    </a>
@endsection

@section('content')
<div class="pages-page">

    <div class="page-header">
        <div class="page-header-content">
            <h1>Pages</h1>
            <p class="page-header-desc">Manage the static pages published across your website.</p>
        </div>
        <div class="page-header-actions">
            <span class="pages-count-chip">
                <span class="material-symbols-outlined">description</span>
                {{ $stats['total'] }} {{ Str::plural('page', $stats['total']) }}
            </span>
        </div>
    </div>

    <div class="stats-bar">
        <div class="stat-item">
            <div class="stat-item-icon primary"><span class="material-symbols-outlined">description</span></div>
            <div>
                <div class="stat-item-value">{{ $stats['total'] }}</div>
                <div class="stat-item-label">Total Pages</div>
            </div>
        </div>
        <div class="stat-item">
            <div class="stat-item-icon success"><span class="material-symbols-outlined">public</span></div>
            <div>
                <div class="stat-item-value">{{ $stats['published'] }}</div>
                <div class="stat-item-label">Published</div>
            </div>
        </div>
        <div class="stat-item">
            <div class="stat-item-icon warning"><span class="material-symbols-outlined">edit_note</span></div>
            <div>
                <div class="stat-item-value">{{ $stats['draft'] }}</div>
                <div class="stat-item-label">Drafts</div>
            </div>
        </div>
        <div class="stat-item">
            <div class="stat-item-icon info"><span class="material-symbols-outlined">troubleshoot</span></div>
            <div>
                <div class="stat-item-value">{{ $stats['needs_seo'] }}</div>
                <div class="stat-item-label">Needs SEO</div>
            </div>
        </div>
    </div>

    <div class="toolbar">
        <div class="toolbar-left">
            <div class="search-wrap">
                <span class="material-symbols-outlined">search</span>
                <input
                    type="text"
                    id="pagesSearchInput"
                    class="search-input"
                    placeholder="Search by title, slug or excerpt..."
                    value="{{ request('search') }}"
                    autocomplete="off"
                >
                @if(request('search'))
                    <button type="button" class="search-clear" id="pagesSearchClear" aria-label="Clear search">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                @endif
            </div>
            <select id="pagesStatusFilter" class="filter-select" aria-label="Filter by status">
                <option value="">All Status</option>
                <option value="published" @selected(request('status') === 'published')>Published</option>
                <option value="draft" @selected(request('status') === 'draft')>Draft</option>
            </select>
            @if(request('search') || request('status'))
                <a href="{{ route('admin.pages.index') }}" class="clear-all-filters">Reset filters</a>
            @endif
        </div>
        <div class="toolbar-right">
            <span class="pages-result-count">
                Showing {{ $pages->firstItem() ?? 0 }}–{{ $pages->lastItem() ?? 0 }} of {{ $pages->total() }}
            </span>
        </div>
    </div>

    <div class="card">
        <div class="card-body-flush">
            <div class="table-responsive">
            <table class="admin-table pages-table">
                <thead>
                    <tr>
                        <th>Page</th>
                        <th style="width:220px;">Slug</th>
                        <th style="width:130px;">SEO</th>
                        <th style="width:120px;">Status</th>
                        <th style="width:150px;">Updated</th>
                        <th style="width:130px;text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pages ?? [] as $page)
                    <tr>
                        <td>
                            <div class="pages-row">
                                <div class="pages-tile {{ $page->status === 'published' ? 'is-published' : 'is-draft' }}">
                                    <span class="material-symbols-outlined">description</span>
                                </div>
                                <div class="pages-row-main">
                                    <a href="{{ route('admin.pages.edit', $page->id) }}" class="pages-row-title">{{ $page->title }}</a>
                                    <div class="pages-row-sub">
                                        @php $preview = $page->excerpt ?: trim(strip_tags((string) $page->content)); @endphp
                                        @if($preview !== '')
                                            {{ Str::limit($preview, 80) }}
                                        @else
                                            <span class="pages-row-empty">No content yet</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td><code class="pages-slug">/{{ $page->slug }}</code></td>
                        <td>
                            @php $hasSeo = filled($page->seo_title) && filled($page->seo_description); @endphp
                            @if($hasSeo)
                                <span class="pages-seo is-ok" title="SEO title and description are set">
                                    <span class="material-symbols-outlined">check_circle</span>Optimized
                                </span>
                            @else
                                <span class="pages-seo is-warn" title="SEO title or description is missing">
                                    <span class="material-symbols-outlined">error</span>Needs SEO
                                </span>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $page->status === 'published' ? 'badge-success' : 'badge-draft' }}">
                                {{ ucfirst($page->status) }}
                            </span>
                        </td>
                        <td>
                            <span class="pages-updated" title="{{ $page->updated_at->format('M d, Y H:i') }}">
                                {{ $page->updated_at->diffForHumans() }}
                            </span>
                        </td>
                        <td style="text-align:right;">
                            <div class="pages-actions">
                                @if($page->status === 'published')
                                    <a href="{{ url('/' . $page->slug) }}" target="_blank" rel="noopener" class="icon-btn" title="View live page">
                                        <span class="material-symbols-outlined">open_in_new</span>
                                    </a>
                                @endif
                                <a href="{{ route('admin.pages.edit', $page->id) }}" class="icon-btn icon-btn-edit" title="Edit">
                                    <span class="material-symbols-outlined">edit</span>
                                </a>
                                <div class="action-menu">
                                    <button type="button" class="action-menu-trigger" aria-label="More actions" aria-haspopup="true">
                                        <span class="material-symbols-outlined">more_horiz</span>
                                    </button>
                                    <div class="action-dropdown">
                                        @if($page->status === 'published')
                                            <a href="{{ url('/' . $page->slug) }}" target="_blank" rel="noopener" class="action-dropdown-item">
                                                <span class="material-symbols-outlined">open_in_new</span> View live
                                            </a>
                                            <div class="action-dropdown-divider"></div>
                                        @endif
                                        <a href="{{ route('admin.pages.edit', $page->id) }}" class="action-dropdown-item">
                                            <span class="material-symbols-outlined">edit</span> Edit
                                        </a>
                                        <div class="action-dropdown-divider"></div>
                                        <form method="POST" action="{{ route('admin.pages.destroy', $page->id) }}" onsubmit="return confirm('Delete this page? This cannot be undone.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="action-dropdown-item action-dropdown-danger">
                                                <span class="material-symbols-outlined">delete</span> Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                @if(request('search') || request('status'))
                                    <span class="material-symbols-outlined">search_off</span>
                                    <h3>No matching pages</h3>
                                    <p>Try a different search term or clear the filters.</p>
                                    <div class="empty-state-actions">
                                        <a href="{{ route('admin.pages.index') }}" class="btn btn-outline">Clear filters</a>
                                    </div>
                                @else
                                    <span class="material-symbols-outlined">description</span>
                                    <h3>No pages yet</h3>
                                    <p>Create your first page to get started.</p>
                                    <div class="empty-state-actions">
                                        <a href="{{ route('admin.pages.create') }}" class="btn btn-primary btn-sm">
                                            <span class="material-symbols-outlined" style="font-size:18px;">add</span> Add Page
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>

        @if($pages->hasPages())
            <div class="card-footer pages-pagination">
                <span class="pagination-info">Page {{ $pages->currentPage() }} of {{ $pages->lastPage() }}</span>
                {{ $pages->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
