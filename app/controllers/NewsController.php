<?php
namespace Newsapp\Controllers;

use Newsapp\Models\News;
use Newsapp\Models\Photos;
use Phalcon\Http\Response;
use Phalcon\Paginator\Adapter\Model as PaginatorModel;

/**
 * Class with news CRUD actions
 */
class NewsController extends BaseController
{
    /**
     * @var array
     */
    private $searchFields = [
        'title',
        'views',
        'created_at',
        'updated_at'
    ];

     /**
     * @var array
     */
    private $orderByFields = [
        'title',
        'user',
        'views',
        'created_at'
    ];

    private $photosExtensions = array(
        'image/jpeg',
        'image/png',
        'image/bmp'
    );

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
     * Gets a news pagination
     *
     * @method GET
     * @url /news
     *
     * @param string $searchField
     * @param string $searchValue
     * @param string $order
     * @return Phalcon\Http\Response
     */
    public function getAllNews(string $searchField = 'title', string $searchValue = '', string $order = '') : Response
    {
        $bind = [];
        $conditions = 'is_deleted = 0';

        if (in_array($searchField, $this->searchFields) && $searchValue) {
            $conditions .= " AND {$searchField} LIKE ?0";
            $bind[] = "%{$searchValue}%";
        }
        
        $orderParams = explode('|', $order);
        if (in_array($orderParams[0], $this->orderByFields)) {
            $order = $orderParams[0];
            if (count($orderParams) == 2 && strtolower($orderParams[1]) == 'desc') {
                $order .= ' DESC';
            }
        } else {
            $order = 'created_at DESC';
        }
        
        $page = $this->request->getQuery('page', 'int');

        $news =  News::find(
            [
                'conditions' => $conditions,
                'bind' => $bind,
                'order' => $order
            ]
        );

        $paginator = new PaginatorModel(
            [
                'data'  => $news,
                'limit' => 10,
                'page'  => $page,
            ]
        );

        return $this->response($paginator->getPaginate());
    }

    /**
     * Gets a news by it's id
     *
     * @method GET
     * @url /news/{id}
     *
     * @param int $id
     * @return Phalcon\Http\Response
     */
    public function getById(int $id): Response
    {
        $news =  News::findFirst(
            [
                'conditions' => 'id = ?0 AND is_deleted = 0',
                'bind' => [$id]
            ]
        );

        $news->views++;
        $news->save();

        if (!$news) {
            throw new \Exception('Post doesn\'t exist');
        };
        return $this->response(
            [
                "id" => $news->id,
                "title" => $news->title,
                "content" => $news->content,
                "user_id" => $news->user_id,
                "views" => $news->views,
                "created_at" => $news->created_at,
                "updated_at" => $news->updated_at,
                "is_deleted" => $news->is_deleted,
                'photos' => $news->photos->toArray()
            ]
        );
    }

    /**
     * Saves a new post in the database
     *
     * @method POST
     * @url /news
     *
     * @return Phalcon\Http\Response
     */
    public function addNews(): Response
    {
        $userId = $this->userData['id'];

        $news = new News();
        $news->title = $this->request->getPost('title');
        $news->content = $this->request->getPost('content');
        $news->user_id = $userId;

        $baseDirectory = dirname(__DIR__, 2) . '/public';
        $directory = '/imgs/' . $userId;

        if ($this->request->hasFiles()) {
            $news->photos = $this->uploadPhotos($baseDirectory, $directory);
        }

        if (!$news->save()) {
            //throw new \Exception('Data not saved');
            $errors = [];
            foreach ($news->getMessages() as $error) {
                $errors[] = $error->getMessage();
            }
            return $this->response(['status' => 'Error', 'Messages' => $errors]);
        }

        return $this->response($news);
    }

    /**
     * Updates the given information of an existing post
     *
     * @method PUT
     * @url /news/{id}
     *
     * @param int $id
     * @return Phalcon\Http\Response
     */
    public function editNews(int $id) : Response
    {
        $news = News::findFirst([
            'conditions' => 'id = ?0 AND is_deleted = 0',
            'bind' => [
                $id
            ]
        ]);

        if (!$news) {
            throw new \Exception('Post doesn\'t exist');
        }

        if ($news->user_id != $this->userData['id']) {
            throw new \Exception('You\'re not the owner of the post');
        }
    
        $news->title = $this->request->getPut('title');
        $news->content = $this->request->getPut('content');

        if (!$news->save()) {
            //throw new \Exception('Data not saved');
            $errors = [];
            foreach ($news->getMessages() as $error) {
                $errors[] = $error->getMessage();
            }
            return $this->response(['status' => 'Error', 'Messages' => $errors]);
        }

        return $this->response(['Status' => 'ok']);
    }

    /**
     * Softly deletes a post
     *
     * @method DELETE
     * @url /news
     *
     * @param int $id
     * @return Phalcon\Http\Response
     */
    public function deleteNews(int $id): Response
    {
        $news = News::findFirst([
            'conditions' => 'id = ?0 AND is_deleted = 0',
            'bind' => [
                $id
            ]
        ]);

        if (!$news) {
            throw new \Exception('Post doesn\'t exist');
        }

        if ($news->user_id != $this->userData['id']) {
            throw new \Exception('You\'re not the owner of the post');
        }

        if (!$news->softDelete()) {
            //throw new \Exception('Data not deleted');
            $errors = [];
            foreach ($news->getMessages() as $error) {
                $errors[] = $error->getMessage();
            }
            return $this->response(['status' => 'Error', 'Messages' => $errors]);
        }
        return $this->response(['Status' => 'ok']);
    }

    /**
     * Uploads the images in the current request
     *
     * @param string $baseDirectory path of the public directory
     * @param string $directory relativa path where the photos will be saved inside the public directory
     * @return array array of Newsapp/Models/Photos of the saved photos
     */
    private function uploadPhotos(string $baseDirectory, string $directory) : array
    {
        if (!is_dir($baseDirectory . $directory)) {
            mkdir($baseDirectory . $directory, 0777, true);
        }
        
        $photos = [];
        foreach ($this->request->getUploadedFiles() as $file) {
            if (!in_array($file->getRealType(), $this->photosExtensions) || $file->getError()) {
                throw new \Exception('Whrong file type');
            }

            $type = explode('/', $file->getRealType())[1];

            $photo = new Photos();
            $photo->url = $directory . '/' . rand() . '.' . $type;

            if (!$file->moveTo($baseDirectory . $photo->url)) {
                throw new \Exception("The photo couldn't be uploaded");
            }

            $photos[] = $photo;
        }

        return $photos;
    }
}
