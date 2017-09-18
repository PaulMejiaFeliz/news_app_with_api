<?php
namespace Newsapp\Controllers;

use Newsapp\Models\News;
use Newsapp\Models\Comments;
use Newsapp\ValidationException;
use Phalcon\Http\Response;
use Phalcon\Paginator\Adapter\Model as PaginatorModel;

/**
 * Class with comments CRUD actions
 */
class CommentsController extends BaseController
{
    /**
     * @var array
     */
    private $userData;

    /**
     * Sets the user data
     *
     * @return void
     */
    public function onConstruct() : void
    {
        $this->userData = $this->session->get('user');
    }
    
    /**
     * Gets a comment pagination for the given news id
     *
     * @method GET
     * @url /comments/{$newsId}
     *
     * @param int $newsId
     * @return Phalcon\Http\Response
     */
    public function getAllComments(int $newsId) : Response
    {
        $news =  News::findFirst(
            [
                'conditions' => 'id = ?0 AND is_deleted = 0',
                'bind' => [$newsId]
            ]
        );

        if (!$news) {
            throw new \Exception('Post doesn\'t exist');
        }

        $currentPage = $this->request->getQuery('page', 'int');
        $limit = $this->request->getQuery('limit', 'int', 10);

        $paginator = new PaginatorModel(
            [
                'data'  => $news->getComments(['is_deleted = 0']),
                'limit' => $limit,
                'page'  => $currentPage,
            ]
        );

        $page = $paginator->getPaginate();

        $response = [
                
            "first" => $page->first,
            "before" => $page->before,
            "current" => $page->current,
            "last" => $page->last,
            "next" => $page->next,
            "total_pages" => $page->total_pages,
            "total_items" => $page->total_items,
            "limit" => $page->limit
        ];

        foreach ($page->items as $item) {
            $response['items'][] = [
                "id" => $item->id,
                "content" => $item->content,
                "user_id" => $item->user_id,
                'users' => [
                    'id' => $item->users->id,
                    'name' => $item->users->name,
                    'lastName' => $item->users->lastName,
                    'email' => $item->users->email
                ],
                "created_at" => $item->created_at,
                "updated_at" => $item->updated_at
            ];
        }

        return $this->response($response);
    }

    /**
     * Gets a comment by it's id
     *
     * @method GET
     * @url /comments/{id}
     *
     * @param int $id
     * @return Phalcon\Http\Response
     */
    public function getById(int $id): Response
    {
        $comment =  Comments::findFirst(
            [
                'conditions' => 'id = ?0  AND is_deleted = 0',
                'bind' => [$id]
            ]
        );

        if (!$comment) {
            throw new \Exception("Comment doesn't exist");
        }

        return $this->response($comment);
    }

    /**
     * Saves a comment post in the database
     *
     * @method POST
     * @url /comments
     *
     * @return Phalcon\Http\Response
     */
    public function addComment(): Response
    {
        $news =  News::findFirst(
            [
                'conditions' => 'id = ?0 AND is_deleted = 0',
                'bind' => [$this->request->getPost('news_id')]
            ]
        );

        if (!$news) {
            throw new \Exception('Post doesn\'t exist');
        }

        $comment = new Comments();
        $comment->content = $this->request->getPost('content');
        $comment->news_id = $news->id;
        $comment->user_id = $this->request->getPost('user_id');

        if (!$comment->save()) {
            $errors = [];
            foreach ($comment->getMessages() as $error) {
                $errors[] = [
                    'field' => $error->getField(),
                    'message' => $error->getMessage()
                ];
            }
            //return $this->response(['status' => 'Error', 'Messages' => $errors]);
            throw new ValidationException($errors);
        }

        return $this->response($comment);
    }

    /**
     * Updates the given information of an existing comment
     *
     * @method PUT
     * @url /comments/{id}
     *
     * @param int $id
     * @return Phalcon\Http\Response
     */
    public function editComment(int $id) : Response
    {
        $comment = Comments::findFirst([
            'conditions' => 'id = ?0 AND is_deleted = 0',
            'bind' => [
                $id
            ]
        ]);

        if (!$comment || $comment->news->is_deleted) {
            throw new \Exception('Comment doesn\'t exist');
        }

        // if ($comment->user_id != $this->userData['id']) {
        //     throw new \Exception('You\'re not the owner of the post');
        // }
    
        $comment->content = $this->request->getPut('content', "string");

        if (!$comment->save()) {
            $errors = [];
            foreach ($comment->getMessages() as $error) {
                $errors[] = [
                    'field' => $error->getField(),
                    'message' => $error->getMessage()
                ];
            }
            // return $this->response(['status' => 'Error', 'Messages' => $errors]);
            throw new ValidationException($errors);
        }

        return $this->response(['Status' => 'ok']);
    }

    /**
     * Softly deletes a comment
     *
     * @method DELETE
     * @url /comments
     *
     * @param int $id
     * @return Phalcon\Http\Response
     */
    public function deleteComment(int $id): Response
    {
        $comment = Comments::findFirst([
            'conditions' => 'id = ?0 AND is_deleted = 0',
            'bind' => [
                $id
            ]
        ]);

        if (!$comment || $comment->news->is_deleted) {
            throw new \Exception('Comment doesn\'t exist');
        }

        // if ($comment->user_id != $this->userData['id']) {
        //     throw new \Exception('You\'re not the owner of the post');
        // }

        if (!$comment->softDelete()) {
            $errors = [];
            foreach ($comment->getMessages() as $error) {
                $errors[] = [
                    'field' => $error->getField(),
                    'message' => $error->getMessage()
                ];
                throw new ValidationException($errors);
            }
            //return $this->response(['status' => 'Error', 'Messages' => $errors]);
        }
        return $this->response(['Status' => 'ok']);
    }
}
