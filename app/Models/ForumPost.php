<?php

namespace App\Models;

use App\Core\Model;

class ForumPost extends Model
{
    protected string $table = 'forum_posts';

    public function getAllWithAuthors(?string $postType = null): array
    {
        $params = [];
        $where = '';
        if ($postType !== null && in_array($postType, ['knowledge_article', 'community_question'], true)) {
            $where = 'WHERE p.post_type = :post_type';
            $params['post_type'] = $postType;
        }

        $sql = "SELECT p.*, u.full_name AS author_name, u.role AS author_role, u.county AS author_county,
                       (SELECT COUNT(*) FROM forum_comments c WHERE c.post_id = p.id) AS comment_count
                FROM forum_posts p
                INNER JOIN users u ON p.author_id = u.id
                {$where}
                ORDER BY p.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getCommentsByPostIds(array $postIds): array
    {
        if (empty($postIds)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($postIds), '?'));
        $sql = "SELECT c.*, u.full_name AS author_name, u.role AS author_role
                FROM forum_comments c
                INNER JOIN users u ON c.author_id = u.id
                WHERE c.post_id IN ({$placeholders})
                ORDER BY c.created_at ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($postIds);

        $grouped = [];
        foreach ($stmt->fetchAll() as $row) {
            $grouped[(int) $row['post_id']][] = $row;
        }
        return $grouped;
    }

    public function createPost(array $data): int
    {
        if (!empty($data['offline_sync_uuid'])) {
            $check = $this->db->prepare("SELECT id FROM {$this->table} WHERE offline_sync_uuid = :uuid LIMIT 1");
            $check->execute(['uuid' => $data['offline_sync_uuid']]);
            if ($existing = $check->fetch()) {
                return (int) $existing['id'];
            }
        }

        $stmt = $this->db->prepare(
            "INSERT INTO {$this->table} (author_id, post_type, category_tag, title, body, offline_sync_uuid)
             VALUES (:author_id, :post_type, :category_tag, :title, :body, :uuid)"
        );
        $stmt->execute([
            'author_id'    => (int) $data['author_id'],
            'post_type'    => $data['post_type'] ?? 'community_question',
            'category_tag' => trim($data['category_tag']),
            'title'        => trim($data['title']),
            'body'         => trim($data['body']),
            'uuid'         => $data['offline_sync_uuid'] ?? null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function addComment(int $postId, int $authorId, string $comment): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO forum_comments (post_id, author_id, comment)
             VALUES (:post_id, :author_id, :comment)"
        );
        $stmt->execute([
            'post_id'   => $postId,
            'author_id' => $authorId,
            'comment'   => trim($comment),
        ]);
        return (int) $this->db->lastInsertId();
    }
}
