<div class="max-w-4xl mx-auto space-y-6">
    <div class="bg-white rounded-2xl border border-stone-200 shadow-sm p-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-green-900">Knowledge Hub & Farmer Community</h1>
            <p class="text-xs text-stone-500">Agronomic tutorials, smart-farming guides, and peer-to-peer Q&A.</p>
        </div>
        <div class="flex gap-2 text-xs font-semibold">
            <a href="<?= base_url('community') ?>" class="px-3 py-1.5 rounded-xl <?= empty($activeType) ? 'bg-green-700 text-white' : 'bg-stone-100 text-stone-700' ?>">All Posts</a>
            <a href="<?= base_url('community?type=knowledge_article') ?>" class="px-3 py-1.5 rounded-xl <?= $activeType === 'knowledge_article' ? 'bg-green-700 text-white' : 'bg-stone-100 text-stone-700' ?>">📚 Knowledge Hub</a>
            <a href="<?= base_url('community?type=community_question') ?>" class="px-3 py-1.5 rounded-xl <?= $activeType === 'community_question' ? 'bg-green-700 text-white' : 'bg-stone-100 text-stone-700' ?>">💬 Farmer Forum</a>
        </div>
    </div>

    <?php if (!empty($_SESSION['user_id'])): ?>
        <form action="<?= base_url('community/post') ?>" method="POST" id="community-post-form"
              class="bg-white rounded-2xl border border-stone-200 shadow-sm p-6 space-y-3">
            <?= csrf_field() ?>
            <h2 class="text-sm font-bold text-stone-800 uppercase tracking-wide">Start a Discussion or Share a Guide</h2>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <input type="text" name="title" required placeholder="Topic title..."
                       class="sm:col-span-2 rounded-xl border border-stone-300 px-3.5 py-2 text-sm">
                <select name="category_tag" class="rounded-xl border border-stone-300 px-3 py-2 text-sm bg-white">
                    <option value="Crop Management">Crop Management</option>
                    <option value="Soil & Fertilizer">Soil & Fertilizer</option>
                    <option value="Pest Control">Pest Control</option>
                    <option value="Market Trends">Market Trends</option>
                </select>
            </div>
            <textarea name="body" rows="3" required placeholder="Ask a question or share farming insights..."
                      class="w-full rounded-xl border border-stone-300 px-3.5 py-2 text-sm"></textarea>
            <div class="flex items-center justify-between">
                <select name="post_type" class="rounded-lg border border-stone-300 px-3 py-1.5 text-xs bg-white">
                    <option value="community_question">Community Question</option>
                    <option value="knowledge_article">Knowledge Hub Tutorial</option>
                </select>
                <button type="submit" class="bg-green-700 hover:bg-green-800 text-white text-xs font-semibold px-4 py-2 rounded-xl">
                    Publish Post
                </button>
            </div>
        </form>
    <?php endif; ?>

    <!-- Forum Threads -->
    <div class="space-y-4">
        <?php if (empty($posts)): ?>
            <div class="bg-white rounded-2xl border border-stone-200 p-8 text-center text-xs text-stone-500">
                No discussions posted yet. Be the first to share a farming tip or question!
            </div>
        <?php else: ?>
            <?php foreach ($posts as $post): ?>
                <article class="bg-white rounded-2xl border border-stone-200 shadow-sm p-6 space-y-4">
                    <div>
                        <div class="flex items-center justify-between text-xs text-stone-400 mb-1">
                            <span class="font-semibold text-green-800 bg-emerald-50 px-2.5 py-0.5 rounded-full">
                                <?= htmlspecialchars($post['category_tag']) ?>
                            </span>
                            <span><?= htmlspecialchars($post['author_name']) ?> (<?= ucfirst(htmlspecialchars($post['author_role'])) ?> • <?= htmlspecialchars($post['author_county']) ?>)</span>
                        </div>
                        <h3 class="text-lg font-bold text-stone-900 mt-1"><?= htmlspecialchars($post['title']) ?></h3>
                        <p class="text-sm text-stone-700 mt-2 leading-relaxed"><?= nl2br(htmlspecialchars($post['body'])) ?></p>
                    </div>

                    <!-- Replies -->
                    <div class="border-t border-stone-100 pt-3 space-y-2">
                        <?php foreach ($commentsByPost[(int) $post['id']] ?? [] as $comment): ?>
                            <div class="bg-stone-50 p-3 rounded-xl text-xs">
                                <p class="font-bold text-stone-800"><?= htmlspecialchars($comment['author_name']) ?> <span class="font-normal text-stone-400">• <?= ucfirst(htmlspecialchars($comment['author_role'])) ?></span></p>
                                <p class="text-stone-600 mt-0.5"><?= nl2br(htmlspecialchars($comment['comment'])) ?></p>
                            </div>
                        <?php endforeach; ?>

                        <?php if (!empty($_SESSION['user_id'])): ?>
                            <form action="<?= base_url('community/comment') ?>" method="POST" class="flex gap-2 pt-1">
                                <?= csrf_field() ?>
                                <input type="hidden" name="post_id" value="<?= (int) $post['id'] ?>">
                                <input type="text" name="comment" required placeholder="Write a reply..."
                                       class="flex-1 rounded-xl border border-stone-300 px-3 py-1.5 text-xs">
                                <button type="submit" class="bg-stone-800 text-white text-xs font-semibold px-3.5 py-1.5 rounded-xl">
                                    Reply
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
