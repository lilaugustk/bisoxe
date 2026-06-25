<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Contracts\View\View;

class PostController extends Controller
{
    /**
     * Hiển thị danh sách bài viết chung.
     */
    public function index(Request $request): View|\Illuminate\Http\RedirectResponse
    {
        $search = $request->input('search') ?? $request->route('search');
        $category = $request->input('category') ?? $request->route('category');

        if ($request->has('search') && !empty($request->input('search'))) {
            $searchVal = $request->input('search');
            $params = [];
            $limit = $request->input('limit');
            if (!empty($limit)) {
                $params['limit'] = $limit;
            }
            if (!empty($category)) {
                $queryString = count($params) ? '?' . http_build_query($params) : '';
                return redirect()->to(url('/c/' . $category . '/' . \Illuminate\Support\Str::slug($searchVal) . $queryString), 301);
            }
            return redirect()->to(route('posts.show', array_merge(['slug' => \Illuminate\Support\Str::slug($searchVal)], $params)), 301);
        }

        if ($request->routeIs('posts.index') && $request->has('category') && !empty($request->input('category'))) {
            return redirect()->to(route('posts.category', ['category' => $request->input('category')]), 301);
        }

        $query = Post::query()->published()->latest();

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('summary', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        if (! empty($category)) {
            $query->byCategory($category);
        }

        $limit = (int) $request->input('limit', 12);
        if (! in_array($limit, [12, 24, 48])) {
            $limit = 12;
        }

        $paginated = $query->paginate($limit)->withQueryString();

        return view('post.index', [
            'posts' => $paginated,
            'filters' => [
                'search' => $search,
                'category' => $category,
                'limit' => $limit,
            ],
        ]);
    }

    /**
     * Hiển thị nội dung chi tiết bài viết chung.
     */
    public function show(Request $request, string $slug): View|\Illuminate\Http\RedirectResponse
    {
        $post = Post::where('slug', $slug)->published()->first();

        if (!$post) {
            $category = $request->input('category') ?? $request->route('category');
            if (!empty($category)) {
                $params = $request->except(['category', 'search']);
                $queryString = count($params) ? '?' . http_build_query($params) : '';
                return redirect()->to(url('/c/' . $category . '/' . $slug . $queryString), 301);
            }

            $search = str_replace('-', ' ', $slug);

            $query = Post::query()->published()->latest();

            $query->where(function ($q) use ($search, $slug) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$slug}%")
                    ->orWhere('summary', 'like', "%{$search}%")
                    ->orWhere('summary', 'like', "%{$slug}%")
                    ->orWhere('content', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$slug}%");
            });

            $limit = (int) $request->input('limit', 12);
            if (! in_array($limit, [12, 24, 48])) {
                $limit = 12;
            }

            $paginated = $query->paginate($limit)->withQueryString();

            return view('post.index', [
                'posts' => $paginated,
                'filters' => [
                    'search' => $search,
                    'category' => null,
                    'limit' => $limit,
                ],
            ]);
        }

        // Tăng lượt xem
        $post->increment('view_count');

        // Lấy danh sách bài viết liên quan (cùng chuyên mục hoặc mới nhất)
        $relatedPosts = Post::published()
            ->where('id', '!=', $post->id)
            ->where('category', $post->category)
            ->latest()
            ->limit(4)
            ->get();

        if ($relatedPosts->isEmpty()) {
            $relatedPosts = Post::published()
                ->where('id', '!=', $post->id)
                ->latest()
                ->limit(4)
                ->get();
        }

        // Lấy danh sách biển số sắp đấu giá và đã đấu giá của tỉnh thành liên kết
        $upcomingPlates = collect();
        $completedPlates = collect();
        if (!empty($post->province_code)) {
            $upcomingPlates = \App\Models\LicensePlate::with('seoArticle')
                ->where('province_code', $post->province_code)
                ->whereIn('status', ['waiting_auction', 'announced'])
                ->orderBy('auction_start_time', 'asc')
                ->limit(6)
                ->get();

            $completedPlates = \App\Models\LicensePlate::with('seoArticle')
                ->where('province_code', $post->province_code)
                ->where('status', 'completed')
                ->orderBy('auction_start_time', 'desc')
                ->limit(6)
                ->get();
        }

        return view('post.detail', [
            'post' => $post,
            'relatedPosts' => $relatedPosts,
            'upcomingPlates' => $upcomingPlates,
            'completedPlates' => $completedPlates,
        ]);
    }
}
