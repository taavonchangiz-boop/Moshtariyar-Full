<?php

namespace Modules\Core\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Modules\Core\Entities\KbArticle;
use Modules\Core\Entities\KbCategory;

class KnowledgeBaseController extends Controller
{
    public function index(Request $request)
    {
        $query = KbArticle::with('category')->latest('id');

        if ($search = trim((string) $request->get('search'))) {
            $query->where(function ($where) use ($search) {
                $where->where('title', 'like', "%{$search}%")
                    ->orWhere('question', 'like', "%{$search}%")
                    ->orWhere('answer', 'like', "%{$search}%");
            });
        }

        if ($categoryId = $request->get('category_id')) {
            $query->where('category_id', $categoryId);
        }

        if ($request->filled('visibility')) {
            $query->where('visibility', (string) $request->input('visibility'));
        }

        if ($request->filled('status')) {
            match ((string) $request->input('status')) {
                'active' => $query->where('is_active', true),
                'inactive' => $query->where('is_active', false),
                'review' => $query->where(function ($q) {
                    $q->whereNull('question')
                        ->orWhere('question', '')
                        ->orWhereRaw('CHAR_LENGTH(answer) < 120');
                }),
                'popular' => $query->where('views', '>=', 10),
                default => null,
            };
        }

        $articles = $query->paginate(20)->withQueryString();
        $boardArticles = (clone $query)->limit(250)->get();
        $categories = KbCategory::withCount('articles')->orderBy('order')->get();

        $summary = [
            'total' => KbArticle::count(),
            'active' => KbArticle::where('is_active', true)->count(),
            'public' => KbArticle::where('is_active', true)->where('visibility', 'public')->count(),
            'staff' => KbArticle::where('is_active', true)->where('visibility', 'staff')->count(),
            'inactive' => KbArticle::where('is_active', false)->count(),
            'review' => KbArticle::where(function ($q) {
                $q->whereNull('question')->orWhere('question', '')->orWhereRaw('CHAR_LENGTH(answer) < 120');
            })->count(),
            'popular' => KbArticle::where('views', '>=', 10)->count(),
            'views' => KbArticle::sum('views'),
            'categories' => KbCategory::count(),
        ];

        $boardGroups = collect([
            'published' => $boardArticles->filter(fn ($article) => $article->is_active && $article->visibility === 'public')->values(),
            'internal' => $boardArticles->filter(fn ($article) => $article->is_active && $article->visibility === 'staff')->values(),
            'review' => $boardArticles->filter(fn ($article) => empty($article->question) || mb_strlen(strip_tags((string) $article->answer)) < 120)->values(),
            'popular' => $boardArticles->filter(fn ($article) => (int) $article->views >= 10)->values(),
            'draft' => $boardArticles->filter(fn ($article) => ! $article->is_active)->values(),
        ]);

        return view('app.kb.index', compact('articles', 'categories', 'summary', 'boardGroups'));
    }

    public function create()
    {
        $categories = KbCategory::orderBy('order')->get();
        return view('app.kb.form', ['article' => new KbArticle(), 'categories' => $categories]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['slug'] ?: $data['title']);
        $data['tags'] = $this->tags($request->input('tags_text'));
        KbArticle::create($data);
        return redirect('/app/kb')->with('status', 'مقاله پایگاه دانش با موفقیت ثبت شد.');
    }

    public function edit(KbArticle $article)
    {
        $categories = KbCategory::orderBy('order')->get();
        return view('app.kb.form', compact('article', 'categories'));
    }

    public function update(Request $request, KbArticle $article)
    {
        $data = $this->validated($request);
        $data['slug'] = $article->slug === $data['slug'] ? $article->slug : $this->uniqueSlug($data['slug'] ?: $data['title'], $article->id);
        $data['tags'] = $this->tags($request->input('tags_text'));
        $article->update($data);
        return redirect('/app/kb')->with('status', 'مقاله پایگاه دانش به‌روزرسانی شد.');
    }

    public function destroy(KbArticle $article)
    {
        $article->delete();
        return back()->with('status', 'مقاله حذف شد.');
    }

    public function categories(Request $request)
    {
        $categories = KbCategory::withCount('articles')->orderBy('order')->get();
        $summary = [
            'total' => $categories->count(),
            'active' => $categories->where('is_active', true)->count(),
            'inactive' => $categories->where('is_active', false)->count(),
            'articles' => $categories->sum('articles_count'),
        ];
        return view('app.kb.categories', compact('categories', 'summary'));
    }

    public function storeCategory(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:120',
            'slug' => 'nullable|string|max:140',
            'description' => 'nullable|string|max:255',
            'order' => 'nullable|integer',
        ]);
        $data['slug'] = $this->uniqueCategorySlug($data['slug'] ?: $data['title']);
        KbCategory::create($data + ['is_active' => true]);
        return back()->with('status', 'دسته‌بندی ساخته شد.');
    }

    public function toggleCategory(KbCategory $category)
    {
        $category->update(['is_active' => ! $category->is_active]);
        return back()->with('status', 'وضعیت دسته‌بندی تغییر کرد.');
    }

    public function portal(Request $request)
    {
        $categories = KbCategory::where('is_active', true)->with(['articles' => fn ($q) => $q->where('is_active', true)->where('visibility', 'public')->latest('id')])->orderBy('order')->get();
        $articles = collect();
        if ($search = trim((string) $request->get('search'))) {
            $articles = KbArticle::with('category')->where('is_active', true)->where('visibility', 'public')
                ->where(fn ($where) => $where->where('title', 'like', "%{$search}%")->orWhere('question', 'like', "%{$search}%")->orWhere('answer', 'like', "%{$search}%"))->limit(30)->get();
        }
        return view('loyalty::portal.kb', compact('categories', 'articles'));
    }

    public function portalShow(KbArticle $article)
    {
        abort_unless($article->is_active && $article->visibility === 'public', 404);
        $article->increment('views');
        return view('loyalty::portal.kb_show', compact('article'));
    }

    public function ajax(Request $request)
    {
        $search = trim((string) $request->get('q'));
        if (mb_strlen($search) < 2) return response()->json(['items' => []]);
        $items = KbArticle::where('is_active', true)->where('visibility', 'public')
            ->where(fn ($where) => $where->where('title', 'like', "%{$search}%")->orWhere('question', 'like', "%{$search}%")->orWhere('answer', 'like', "%{$search}%"))->limit(10)->get()
            ->map(fn ($article) => ['title' => $article->title, 'desc' => Str::limit(strip_tags($article->answer), 90), 'url' => route('club.kb.show', $article)]);
        return response()->json(['items' => $items]);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'category_id' => 'nullable|exists:kb_categories,id',
            'title' => 'required|string|max:191',
            'slug' => 'nullable|string|max:191',
            'question' => 'nullable|string|max:500',
            'answer' => 'required|string',
            'visibility' => 'required|in:public,staff',
            'is_active' => 'nullable|boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active');
        return $data;
    }

    private function tags(?string $tags): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) $tags))));
    }

    private function uniqueSlug(string $value, ?int $ignore = null): string
    {
        $base = Str::slug($value) ?: 'article';
        $slug = $base;
        $index = 2;
        while (KbArticle::where('slug', $slug)->when($ignore, fn ($q) => $q->where('id', '!=', $ignore))->exists()) {
            $slug = $base . '-' . $index++;
        }
        return $slug;
    }

    private function uniqueCategorySlug(string $value): string
    {
        $base = Str::slug($value) ?: 'category';
        $slug = $base;
        $index = 2;
        while (KbCategory::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $index++;
        }
        return $slug;
    }
}