<div class="max-w-4xl mx-auto space-y-6">
    <?php
    $starterGuides = [
        [
            'category' => 'Soil & Fertility',
            'title' => 'Build a soil plan before buying inputs',
            'summary' => 'A soil test can help match fertilizer and soil amendments to the needs of your field.',
            'steps' => [
                'Sample areas with different soil, slope, or crop history separately.',
                'Follow the soil laboratory’s instructions for sample depth and preparation.',
                'Use the test results and local extension advice to choose inputs and rates.',
            ],
            'source' => 'https://kalroerepository.kalro.org/bitstreams/44fab245-2bef-4554-a07d-fc1800982c1e/download',
            'source_label' => 'KALRO soil research manual',
        ],
        [
            'category' => 'Pest Control',
            'title' => 'Scout crops before deciding what action to take',
            'summary' => 'Regular field checks help spot crop stress early and avoid treating problems that are not present.',
            'steps' => [
                'Walk a repeatable zigzag route and inspect plants in several parts of the field.',
                'Record the crop stage, affected area, symptoms, and any insects you find.',
                'Confirm the cause with a local extension officer before choosing a treatment.',
            ],
            'source' => 'https://www.fao.org/agriculture/crops/thematic-sitemap/theme/compendium/tools-guidelines/how-to-ipm/en/',
            'source_label' => 'FAO integrated pest management guide',
        ],
        [
            'category' => 'Post-harvest',
            'title' => 'Cure onions for safer storage',
            'summary' => 'Dry outer skins and necks help onions keep better after harvest.',
            'steps' => [
                'Handle bulbs gently and separate bruised or diseased onions.',
                'In dry weather, dry the crop with good airflow; use a covered, ventilated place if it is wet.',
                'Store cured onions in a dry, well-ventilated area away from damp floors.',
            ],
            'source' => 'https://www.fao.org/4/t0073e/t0073e08.htm',
            'source_label' => 'FAO onion handling guide',
        ],
        [
            'category' => 'Seed Selection',
            'title' => 'Choose seed for your farm conditions',
            'summary' => 'Variety choice should fit your local growing conditions, intended market, and season.',
            'steps' => [
                'Ask a local extension officer which varieties suit your area and planting season.',
                'Buy sealed, clearly labelled seed from a trusted supplier and check the lot and expiry details.',
                'Keep seed dry and cool, and follow the label for planting and handling.',
            ],
            'source' => null,
            'source_label' => null,
        ],
    ];
    ?>

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

    <?php if (empty($activeType) || $activeType === 'knowledge_article'): ?>
        <section aria-labelledby="starter-guides-title" class="space-y-3">
            <div class="flex flex-wrap items-end justify-between gap-2 px-1">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-green-700">FarmLink field notes</p>
                    <h2 id="starter-guides-title" class="text-xl font-extrabold text-stone-900">Practical farming guides</h2>
                </div>
                <span class="text-xs text-stone-500">Short, actionable tips for your next field visit</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php foreach ($starterGuides as $guide): ?>
                    <article class="bg-white rounded-2xl border border-stone-200 shadow-sm p-5">
                        <span class="inline-flex text-[11px] font-bold uppercase tracking-wide text-green-800 bg-emerald-50 px-2.5 py-1 rounded-full">
                            <?= htmlspecialchars($guide['category']) ?>
                        </span>
                        <h3 class="text-base font-bold text-stone-900 mt-3"><?= htmlspecialchars($guide['title']) ?></h3>
                        <p class="text-sm text-stone-600 mt-1 leading-relaxed"><?= htmlspecialchars($guide['summary']) ?></p>
                        <ol class="mt-3 space-y-2 text-xs text-stone-700 list-decimal list-inside">
                            <?php foreach ($guide['steps'] as $step): ?>
                                <li class="leading-relaxed"><?= htmlspecialchars($step) ?></li>
                            <?php endforeach; ?>
                        </ol>
                        <?php if ($guide['source']): ?>
                            <a href="<?= htmlspecialchars($guide['source']) ?>" target="_blank" rel="noopener noreferrer"
                               class="inline-flex mt-4 text-xs font-semibold text-green-700 hover:underline">
                                <?= htmlspecialchars($guide['source_label']) ?> <span aria-hidden="true" class="ml-1">↗</span>
                            </a>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

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
                <?php if ($activeType === 'knowledge_article'): ?>
                    No community guides yet. Browse the practical guides above or share one with other farmers.
                <?php else: ?>
                    No discussions posted yet. Be the first to share a farming tip or question!
                <?php endif; ?>
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
