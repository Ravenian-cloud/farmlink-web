<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ForumPost;

class CommunityController extends Controller
{
    private ForumPost $forumModel;

    public function __construct()
    {
        $this->forumModel = new ForumPost();
    }

    public function index(): void
    {
        $type = $_GET['type'] ?? null;
        $posts = $this->forumModel->getAllWithAuthors($type);
        $postIds = array_column($posts, 'id');
        $commentsByPost = $this->forumModel->getCommentsByPostIds($postIds);

        $this->view('community/index', [
            'title'          => __('community'),
            'posts'          => $posts,
            'commentsByPost' => $commentsByPost,
            'activeType'     => $type,
        ]);
    }

    public function storePost(): void
    {
        $this->verifyCsrf();

        $title = trim($_POST['title'] ?? '');
        $body  = trim($_POST['body'] ?? '');
        $tag   = trim($_POST['category_tag'] ?? 'General Farming');
        $type  = ($_POST['post_type'] ?? 'community_question') === 'knowledge_article'
            ? 'knowledge_article'
            : 'community_question';

        if ($title === '' || $body === '') {
            $_SESSION['flash_error'] = 'Title and message body are required.';
            $this->redirect('/community');
        }

        $this->forumModel->createPost([
            'author_id'    => (int) $_SESSION['user_id'],
            'post_type'    => $type,
            'category_tag' => $tag,
            'title'        => $title,
            'body'         => $body,
        ]);

        $_SESSION['flash_success'] = 'Published to FarmLink Community & Knowledge Hub.';
        $this->redirect('/community');
    }

    public function storeComment(): void
    {
        $this->verifyCsrf();

        $postId  = (int) ($_POST['post_id'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');

        if ($postId > 0 && $comment !== '') {
            $this->forumModel->addComment($postId, (int) $_SESSION['user_id'], $comment);
            $_SESSION['flash_success'] = 'Reply posted.';
        }

        $this->redirect('/community');
    }
}
