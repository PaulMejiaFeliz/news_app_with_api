<?php
namespace Newsapp\Controllers;

use Newsapp\Models\Users;
use Phalcon\Http\Response;

/**
 * Class used to manage accounts
 */
class AccountController extends BaseController
{
    /**
     * If the credentials are right, lets the user login
     *
     * @method GET
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
            throw new \Exception('User not found.');
        }

        if (!$this->security->checkHash($password, $user->password)) {
            throw new \Exception('Password don\'t match');
        }

        $this->loginUser($user);
        return $this->response(['Status' => 'OK']);
    }

    /**
     * Logouts the current user if there is any
     *
     * @method GET
     * @url /account/logout
     *
     * @return Phalcon\Http\Response
     */
    public function logout(): Response
    {
        $this->session->destroy();
        return $this->response(['Status' => 'OK']);
    }

    /**
     * If the data fulfill the rules registers a new user
     *
     * @method GET
     * @url /account/register
     *
     * @return Phalcon\Http\Response
     */
    public function register(): Response
    {
        $user = new Users();
        $user->assign($this->request->getPost());

        if (!$user->save()) {
            //throw new \Exception('Data not saved');
            $errors = [];
            foreach ($user->getMessages() as $error) {
                $errors[] = $error->getMessage();
            }
            return $this->response(['status' => 'Error', 'Messages' => $errors]);
        }

        $this->loginUser($user);
        return $this->response($user);
    }

    /**
     * Saves a user in the session
     *
     * @param Users $user
     * @return void
     */
    private function loginUser(Users $user) : void
    {
        $this->session->set(
            'user',
            [
                'id' => $user->id,
                'name' => $user->name,
                'lastName' => $user->lastName,
                'email' => $user->email
            ]
        );
    }
}
