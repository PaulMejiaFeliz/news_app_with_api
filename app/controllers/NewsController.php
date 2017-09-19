<?php
namespace Newsapp\Controllers;

use Newsapp\Models\News;
use Newsapp\Models\Photos;
use Newsapp\ValidationException;
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
        'user_id',
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

    /**
     * Accepted image extensions
     *
     * @var array
     */
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
     * @param string $order
     * @return Phalcon\Http\Response
     */
    public function getAllNews() : Response
    {
        $bind = [];
        $conditions = 'is_deleted = 0';

        $i = 0;
        foreach ($this->searchFields as $field) {
            if ($this->request->hasQuery($field)) {
                $conditions .= " AND {$field} LIKE ?" . $i++;
                $bind[] = '%' . $this->request->getQuery($field, 'string') . '%';
            }
        }
        
        $order = 'created_at DESC';
        if ($this->request->hasQuery('sort')) {
            $sort = $this->request->getQuery('sort', 'string');
            if (in_array(trim($sort, '-'), $this->orderByFields)) {
                $order = trim($sort, '-');
                if (substr($sort, 0, 1) === '-') {
                    $order .= ' DESC';
                }
            }
        }
        
        $currentPage = $this->request->getQuery('page', 'int', 1);
        $limit = $this->request->getQuery('limit', 'int', 10);

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
                "title" => $item->title,
                "content" => $item->content,
                "user_id" => $item->user_id,
                'users' => [
                    'id' => $item->users->id,
                    'name' => $item->users->name,
                    'lastName' => $item->users->lastName,
                    'email' => $item->users->email
                ],
                "views" => $item->views,
                "created_at" => $item->created_at,
                "updated_at" => $item->updated_at
            ];
        }

        return $this->response($response);
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
        
        if (!$news) {
            throw new \Exception('Post doesn\'t exist');
        };

        $news->views++;
        $news->save();
        
        return $this->response(
            [
                "id" => $news->id,
                "title" => $news->title,
                "content" => $news->content,
                "user_id" => $news->user_id,
                'users' => [
                    'id' => $news->users->id,
                    'name' => $news->users->name,
                    'lastName' => $news->users->lastName,
                    'email' => $news->users->email
                ],
                "views" => $news->views,
                "created_at" => $news->created_at,
                "updated_at" => $news->updated_at,
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
        $userId = $this->request->getPost('user_id');

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
            $errors = [];
            foreach ($news->getMessages() as $error) {
                $errors[] = [
                    'field' => $error->getField(),
                    'message' => $error->getMessage()
                ];
            }
            //return $this->response(['status' => 'Error', 'Messages' => $errors]);
            throw new ValidationException($errors);
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

        // if ($news->user_id != $this->userData['id']) {
        //     throw new \Exception('You\'re not the owner of the post');
        // }
    
        $news->title = $this->request->getPut('title');
        $news->content = $this->request->getPut('content');

        if (!$news->save()) {
            $errors = [];
            foreach ($news->getMessages() as $error) {
                $errors[] = [
                    'field' => $error->getField(),
                    'message' => $error->getMessage()
                ];
            }
           // return $this->response(['status' => 'Error', 'Messages' => $errors]);
            throw new \Exception($errors);
        }

        return $this->response([
            "id" => $news->id,
            "title" => $news->title,
            "content" => $news->content,
            "user_id" => $news->user_id,
            "views" => $news->views,
            "created_at" => $news->created_at,
            "updated_at" => $news->updated_at,
            'photos' => $news->photos->toArray()
        ]);
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

        // if ($news->user_id != $this->userData['id']) {
        //     throw new \Exception('You\'re not the owner of the post');
        // }

        if (!$news->softDelete()) {
            $errors = [];
            foreach ($news->getMessages() as $error) {
                $errors[] = [
                    'field' => $error->getField(),
                    'message' => $error->getMessage()
                ];
            }
            //return $this->response(['status' => 'Error', 'Messages' => $errors]);
            throw new ValidationException($errors);
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
                throw new MediaTypeException();
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
