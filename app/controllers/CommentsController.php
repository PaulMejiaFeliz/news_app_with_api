<?php
namespace Newsapp\Controllers;

use Newsapp\Models\News;
use Newsapp\Models\Comments;
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

        $page = $this->request->getQuery('page', 'int');

        $paginator = new PaginatorModel(
            [
                'data'  => $news->getComments(['is_deleted = 0']),
                'limit' => 10,
                'page'  => $page,
            ]
        );

        return $this->response($paginator->getPaginate());
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
        $comment->user_id = $this->userData['id'];

        if (!$comment->save()) {
            //throw new \Exception('Data not saved');
            $errors = [];
            foreach ($comment->getMessages() as $error) {
                $errors[] = $error->getMessage();
            }
            return $this->response(['status' => 'Error', 'Messages' => $errors]);
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

        if ($comment->user_id != $this->userData['id']) {
            throw new \Exception('You\'re not the owner of the post');
        }
    
        $comment->content = $this->request->getPut('content', "string");

        if (!$comment->save()) {
            //throw new \Exception('Data not saved');
            $errors = [];
            foreach ($comment->getMessages() as $error) {
                $errors[] = $error->getMessage();
            }
            return $this->response(['status' => 'Error', 'Messages' => $errors]);
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

        if ($comment->user_id != $this->userData['id']) {
            throw new \Exception('You\'re not the owner of the post');
        }

        if (!$comment->softDelete()) {
            //throw new \Exception('Data not deleted');
            $errors = [];
            foreach ($comment->getMessages() as $error) {
                $errors[] = $error->getMessage();
            }
            return $this->response(['status' => 'Error', 'Messages' => $errors]);
        }
        return $this->response(['Status' => 'ok']);
    }
}
