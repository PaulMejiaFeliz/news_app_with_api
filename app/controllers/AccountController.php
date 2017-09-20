<?php
namespace Newsapp\Controllers;

use Newsapp\Models\Users;
use Newsapp\ValidationException;
use Phalcon\Http\Response;

/**
 * Class used to manage accounts
 */
class AccountController extends BaseController
{
    /**
     * If the credentials are right, retrieves the user data
     *
     * @method POST
     * @url /account/login
     *
     * @return Phalcon\Http\Response
     */
    public function login() : Response
    {
        $email = $this->request->getPost('email');
        $password = $this->request->getPost('password');

        $user = Users::findFirst(
            [
                'conditions' => 'email = ?0',
                'bind' => [
                    $email
                ]
            ]
        );
        
        if (!$user) {
            throw new ValidationException([
                [
                    'field' => 'email',
                    'message' => 'Email not found.'
                ]
            ]);
        }

        if (!$this->security->checkHash($password, $user->password)) {
            throw new ValidationException([
                [
                    'field' => 'password',
                    'message' => "Password don't match."
                ]
            ]);
        }

        return $this->response([
            'id' => $user->id,
            'name' => $user->name,
            'lastName' => $user->lastName,
            'email' => $user->email
        ]);
    }

    /**
     * If the data fulfill the rules registers a new user
     *
     * @method POST
     * @url /account/register
     *
     * @return Phalcon\Http\Response
     */
    public function register(): Response
    {
        $user = new Users();
        $user->name = $this->request->getPost('name');
        $user->lastName = $this->request->getPost('lastName');
        $user->email = $this->request->getPost('email');
        $user->password = $this->security->hash($this->request->getPost('password'));

        if (!$user->save()) {
            $errors = [];
            foreach ($user->getMessages() as $error) {
                $errors[] = [
                    'field' => $error->getField(),
                    'message' => $error->getMessage()
                ];
            }
            throw new ValidationException($errors);
        }

        return $this->response([
            'id' => $user->id,
            'name' => $user->name,
            'lastName' => $user->lastName,
            'email' => $user->email
        ]);
    }
}
