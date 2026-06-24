<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends Controller
{
    /**
     * Hiển thị danh sách bài viết chung.
     */
    public function index(Request $request): Response|\Illuminate\Http\RedirectResponse
    {
        $search = $request->input('search');
        $category = $request->input('category') ?? $request->route('category');

        if ($request->routeIs('posts.index') && $request->has('category') && !empty($request->input('category'))) {
            $params = [];
            if (!empty($search)) {
                $params['search'] = $search;
            }
            return redirect()->to(route('posts.category', array_merge(['category' => $request->input('category')], $params)), 301);
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

        return Inertia::render('Post/Index', [
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
    public function show(string $slug): Response
    {
        $post = Post::where('slug', $slug)->published()->firstOrFail();

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

        return Inertia::render('Post/Detail', [
            'post' => $post,
            'relatedPosts' => $relatedPosts,
            'upcomingPlates' => $upcomingPlates,
            'completedPlates' => $completedPlates,
        ]);
    }
}
