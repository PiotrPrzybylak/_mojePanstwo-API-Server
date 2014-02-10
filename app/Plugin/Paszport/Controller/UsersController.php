<?php
App::uses('HttpSocket', 'Network/Http');

/**
 * Class UsersController
 *
 *
 */
class UsersController extends PaszportAppController
{
    public $uses = array('Paszport.User', 'Paszport.UserAdditionalData');
    public $components = array('Session', 'Paszport.Image2');

    /**
     * Sets permissions
     */
    public function beforeFilter()
    {
        parent::beforeFilter();

//        $this->Auth->allow(array('login', 'add', 'gate', 'api_ping', 'forgot', 'reset', 'fblogin', 'api_gate', 'import','api_fblogin', 'twitterlogin', 'twitter','failed','client'));
//        $this->Auth->deny(array('index'));
//        $this->OAuth->allow();
//        $this->OAuth->deny('me');
        if ($this->params->action == 'login' && $this->Auth->loggedIn()) {
            $this->redirect(array('action' => 'index'));
        }


    }

    public function avatar($id = null)
    {
        $id = $this->user_id;
        if ($this->data) {
            $allowed_types = array(
                'image/jpeg' => 'jpg',
                'image/jpg' => 'jpg',
                'image/gif' => 'gif',
                'image/png' => 'png',
            );
            if (!in_array(strtolower($this->data['User']['photo']['type']), array_keys($allowed_types))) {
                exit();
            } else {
                $filename = md5(time() . rand(0, 999)) . '.' . $allowed_types[$this->data['User']['photo']['type']];
                $filename_small = md5(time() . rand(0, 999)) . '_small.' . $allowed_types[$this->data['User']['photo']['type']];
                $path = ROOT . DS . 'app' . DS . 'Plugin' . DS . 'Paszport' . DS . 'webroot' . DS . 'uploads' . DS . 'avatars' . DS;

                file_put_contents($path . $filename, base64_decode($this->data['User']['photo']['binary']));
                file_put_contents($path . $filename_small, base64_decode($this->data['User']['photo']['binary']));
                $filename = $this->Image2->source('uploads/avatars/' . $filename)->crop(190, 190)->imagePath();
                $filename_small = $this->Image2->source('uploads/avatars/' . $filename_small)->crop(27, 27)->imagePath();
                $this->User->id = $id;
                $this->User->saveField('photo', $filename);
                $this->User->saveField('photo_small', $filename_small);
                $this->User->read();
                $this->info($id);
            }
        }
    }

    public function avatarinline($id)
    {
        $user = $this->User->find('first', array('conditions' => array('User.id' => $id), 'recursive' => -2));
        if ($user['User']['photo_small']) {
            echo json_encode(array($this->Image2->source(str_replace(FULL_BASE_URL . '/paszport/', '', $user['User']['photo_small']))->crop(27, 27)->inlineImage()));
        } else {
            echo json_encode(array($this->Image2->source(str_replace(FULL_BASE_URL . '/paszport/', '', 'default.jpg'))->crop(27, 27)->inlineImage()));
        }

        exit();
    }

    public function info()
    {
    
    	$user = false;
    	$streams = array();
    	$applications = ClassRegistry::init('Application')->find('all');
    	
    	
    	if( $this->user_id )
		{
			
			$user = $this->User->find('first', array('conditions' => array('User.id' => $this->user_id)));
			$user = ( $user && isset($user['User']) ) ? $user['User'] : false;
		
		}		        

        if( $user )
        {
            $this->UserAdditionalData->id = $user['id'];
            $data = $this->UserAdditionalData->read(null, $user['id']);
            
            if ($data['UserAdditionalData']['group'] == '2')
            {
                $streams = $this->UserAdditionalData->Stream->find('list', array('fields' => array('id', 'name')));
            }
            else
            {
                foreach ($data['Stream'] as $stream) {
                    $streams[$stream['id']] = $stream['name'];
                }
            }
            
            $user['unread_count'] = $data['UserAdditionalData']['alerts_unread_count'];
            $user['group'] = $data['UserAdditionalData']['group'];
        }
        
        $this->set('user', $user);
        $this->set('applications', $applications);
        $this->set('streams', $streams);
        $this->set('_serialize', array('user', 'applications', 'streams'));

    }

    public function index($id = null)
    {
        $id = $this->user_id;
        $this->data = $this->User->find('first', array(
            'conditions' => array('User.id' => $id),
            'contain' => array('Language', 'UserExpand', 'Group'),
        ));
        $this->set(array(
            'user' => $this->data,
            '_serialize' => array('user', 'info'),
        ));
    }


    public function login()
    {
        if ($this->data) {
            $data = $this->data;
            $data['User']['password'] = $this->Auth->password($data['User']['password']);
            $user = $this->User->find('first', array('conditions' => array('User.email' => $data['User']['email'], 'User.password' => $data['User']['password'])));
            if ($user) {
                $this->set(array(
                    'user' => $user['User'],
                    '_serialize' => array('user'),
                ));
            } else {
                $user = $this->User->checkAndLoginAgainstPostImport($data, $this->Auth->password($data['User']['password']));
                if ($user) {
                    $this->set(array(
                        'user' => $user['User'],
                        '_serialize' => array('user'),
                    ));
                } else {
                    $this->set(array(
                        'user' => null,
                        '_serialize' => array('user'),
                    ));
                }

            }
        }

    }

    /**
     * forces password for just registered FB users
     */
    public function setpassword($id = null)
    {
        $id = $this->user_id;
        $user = $this->User->find('first', array('recursive' => -2, 'conditions' => array('User.id' => $id)));
        if ($user['User']['password_set']) {
            $this->redirect(array('action' => 'index'));
        }
        CakeLog::debug(print_r($this->data, true));
        if ($this->request->isPost()) {
            $this->User->id = $id;
            if ($this->User->save(array('password' => $this->Auth->password($this->data['User']['password']), 'password_set' => 1))) {
                // @TODO : some kind of response
            } else {
                exit();
            }
        }
        $this->set('title_for_layout', __('LC_PASZPORT_SET_PASSWORD', true));
    }

    /**
     * Switcher to attach profiles
     * @param string|null $profile
     */
    public function attachprofile($profile = null)
    {
        if (is_null($profile)) {
            exit();
        }

        switch ($profile) {
            case "facebook":
                $this->__attachFacebook();
                break;
            case "gplus":
                //@TODO add gplus
                break;

        }
        exit();
    }

    /**
     * Switcher to attach profiles
     * @param string|null $profile
     */
    public function deattachprofile($profile = null)
    {
        if (is_null($profile)) {
            exit();
        }

        switch ($profile) {
            case "facebook":
                $this->__attachFacebook(true);
                break;
            case "gplus":
                //@TODO add gplus
                break;

        }
        exit();
    }


    /**
     * @param bool $deattach - on true deletes the relation
     * @return bool
     */
    public function __attachFacebook($deattach = false, $redirect = null)
    {
        if ($deattach) {
            $this->User->id = $this->Auth->user('id');
            if ($this->User->saveField('facebook_id', null)) {
                $this->_log(array('msg' => 'LC_PASZPORT_LOG_FB_DEATTACHED', 'ip' => $this->request->clientIp(), 'user_agent' => env('HTTP_USER_AGENT')));
            }
            return true;
        }
        # check if user has already given permissions to the app
        $user_data = $this->Connect->FB->api('/me/?fields=id,first_name,last_name,email,gender,picture.type(square).width(200),birthday,locale');
        if ($user_data['id']) { # merge, save, inform
            $this->User->id = $this->Auth->user('id');
            $to_save = array(
                'User' => array(
                    'facebook_id' => $user_data['id'],
                ),
            );
            $user_photo = $this->User->find('first', array('conditions' => array('User.id' => $this->Auth->user('id')), 'recursive' => -2, 'fields' => array('User.photo')));
            if (!$user_photo['User']['photo']) {
                $this->User->Behaviors->load('Upload.Upload', array('photo' => array('path' => '{ROOT}webroot{DS}uploads{DS}{model}{DS}{field}{DS}')));
                $to_save['User']['photo'] = preg_replace('/https/', 'http', $user_data['picture']['data']['url']);
            }
            if ($this->User->save($to_save)) {
                $this->_log(array('msg' => 'LC_PASZPORT_LOG_FB_ACCOUNT_MERGED', 'ip' => $this->request->clientIp(), 'user_agent' => env('HTTP_USER_AGENT')));
            } else {
                exit();
            }
        } else {
            exit();
        }
    }


    /**
     *
     * Converts FB male|female to our int representatives
     * @param string $gender
     * @return int
     */
    public function __fbGender($gender)
    {
        switch ($gender) {
            case "male":
                return 1;
                break;
            case "female":
                return 2;
                break;
            default:
                return 0;
                break;
        }
    }

    /**
     * Converts rfc language definitions country_LOCALE into our models
     *
     * @param string $rfc_lang
     * @return int
     */
    public function __fbLanguage($rfc_lang)
    {
        $lang = $this->User->Language->find('first', array('recursive' => -2, 'conditions' => array('rfc_code' => $rfc_lang)));
        if ($lang) {
            return $lang['Language']['id'];
        } else {
            return 2; # english
        }
    }

    /**
     *
     * Generates token, sends mail, validates token and redirect to password changing method
     * @return bool
     */
    public function forgot()
    {
        App::uses('CakeEmail', 'Network/Email');
        if ($this->request->isPost()) { # if post then someone sent form, we should find user with given e-mail
            $user = $this->User->find('first', array('conditions' => array('User.email' => $this->data['User']['email']), 'recursive' => -2));
            if ($user) { # if user exists send email
                $Email = new CakeEmail();
                $Email->config('smtp');

                $Email->to($user['User']['email']);
                $Email->subject(__('LC_PASZPORT_MAIL_RESET_PASS_SUBJECT', true));
                $e = new Encryption(MCRYPT_BlOWFISH, MCRYPT_MODE_CBC);
                $data = json_encode(array('email' => $user['User']['email'], 'expires' => strtotime('+24 hours')));
                $hash = base64_encode($e->encrypt($data, Configure::read('Security.salt')));
                $Email->viewVars(array('hash' => urlencode($hash)));
                if ($Email->send()) {
                    $this->_log(array('msg' => 'LC_PASZPORT_LOG_MAIL_RESET_PASS_SENT', 'ip' => $this->request->clientIp(), 'user_agent' => env('HTTP_USER_AGENT')));
                    $this->User->id = $user['User']['id'];
                    $this->User->saveField('reset_hash', urlencode($hash));
                }
            } else { # if not display error
                exit();
            }
        } else { # if the request was not post
            if (isset($this->request->query['token'])) { # but it has $hash sent, we are going to change user's password
                $hash = $this->request->query['token'];
                $hash = str_replace(' ', '+', urldecode($hash));
                $e = new Encryption(MCRYPT_BlOWFISH, MCRYPT_MODE_CBC);
                $token_data = json_decode($e->decrypt(base64_decode($hash), Configure::read('Security.salt')), true);
                $user_email = $token_data['email'];
                $expires = $token_data['expires'];

                if (time() > $expires) {
                    return false;
                } else {
                    $user = $this->User->find('first', array('recursive' => -2, 'conditions' => array('User.email' => $user_email, 'User.reset_hash' => urlencode($this->request->query['token']))));
                    if (!$user) {
                        exit();
                    }
                }

            }
        }
    }

    /**
     *
     * Sets password given by user
     * clears the reset_hash field in DB
     */
    public function reset()
    {
        if ($this->data) {
            $to_save = $this->data;
            $to_save['User']['reset_hash'] = ''; # clean the reset hash
            $to_save['User']['password'] = $this->Auth->password($this->data['User']['password']); # hash the password
            $to_save['User']['repassword'] = $this->Auth->password($this->data['User']['repassword']);
            if ($this->User->save($to_save)) {
                $this->_log(array('msg' => 'LC_PASZPORT_LOG_PASSWORD_RESET_SUCCESS', 'ip' => $this->request->clientIp(), 'user_agent' => env('HTTP_USER_AGENT')));
                $this->set(array(
                    'user' => $this->User->data,
                    '_serialize' => array('user'),
                ));
            } else {
                exit();
            }
        }
    }


    /**
     * Logout
     */
    public function logout()
    {
        if ($this->request->isAjax()) {
            $this->requestAction($this->Auth->logout());
            echo json_encode(array('error' => '', 'status' => 200, 'msg' => __('LC_PASZPORT_LOGOUT', true)));
            die();
        }
        $this->_log(array('msg' => 'LC_PASZPORT_LOG_LOGOUT', 'ip' => $this->request->clientIp(), 'user_agent' => env('HTTP_USER_AGENT')));
    }

    /**
     * Register
     */
    public function add()
    {
        if ($this->data) {
            $to_save = $this->data;
            $to_save['User']['password'] = $this->Auth->password($this->data['User']['password']);
            $to_save['User']['repassword'] = $this->Auth->password($this->data['User']['repassword']);
            if ($this->User->save($to_save)) {
                $this->info($this->User->id);
            } else {
                $this->set(array(
                    'errors' => $this->User->validationErrors,
                    '_serialize' => array('errors'),
                ));
            }
        }
    }

    /**
     * Saves changes to one field in model
     * return json response about success or failure
     */
    public function field($id = null)
    {
        $id = $this->user_id;
        $forbiddenFields = array('id', 'password');
        CakeLog::debug(print_r($this->data, true));
        if ($this->data) {
            $to_save = array();
            if (isset($this->data['User']['pass'])) {
                if ($this->verifyPasswords()) {
                    $to_save['User']['password'] = $this->Auth->password($this->data['User']['newpass']);
                    $this->User->id = $id;
                    $this->User->save($to_save);
                    echo json_encode(array(
                        array('status' => 200),
                        array('alerts' => array(
                            'success' => array(__('LC_PASZPORT_SAVED', true)),
                        )),
                    ));
                } else {
                    echo json_encode(array(
                        array('status' => 500),
                        array('alerts' => array(
                            'error' => array(
                                __('LC_PASZPORT_FAILED_TO_VERIFY_PASSWORDS', true)
                            ),
                        )),
                    ));
                }
            } else {
                foreach ($this->data['User'] as $field => $value) {
                    if (!in_array($field, $forbiddenFields)) {
                        $to_save['User'][$field] = $value;
                        $this->User->id = $id;
                        if ($this->User->save($to_save)) {
//                            $this->_log(array('msg' => array('label' => 'LC_PASZPORT_LOG_SAVED', 'info' => $field . ':' . $value), 'ip' => $this->request->clientIp(), 'user_agent' => env('HTTP_USER_AGENT')));
                            echo json_encode(array(
                                array('status' => 200),
                                array('alerts' => array(
                                    'success' => array(__('LC_PASZPORT_SAVED', true)),
                                )),
                            ));
                        } else {
                            $error = $this->User->validationErrors;
                            $error = array_pop($error);
                            echo json_encode(array(
                                array('status' => 500),
                                array('alerts' => array(
                                    'error' => $error,
                                )),
                            ));
                        }
                    }
                }
            }
        } else {
            echo json_encode(array('status' => '500', 'msg' => __('LC_PASZPORT_NO_DATA', true)));
        }
        exit();

    }

    /**
     * deletes currently logged acount
     * verifies users based on password he has retyped
     */
    public function delete($id = null)
    {
        $id = $this->user_id;
        $exists = $this->User->find('count', array('conditions' => array('User.id' => $id, 'User.password' => $this->Auth->password($this->data['User']['password']))));
        if ($exists > 0) {
            $this->User->delete($id);
        }
    }

    public function bar($logged = false)
    {
        $this->autoRender = false;
        $this->layout = 'plain';
        if ($this->data) {
            if ($logged) {
                echo json_encode(array('topbar' => file_get_contents(ROOT . DS . 'app' . DS . 'Plugin' . DS . 'Paszport' . DS . 'webroot' . DS . 'bar_logged.ctp')));
                exit();

            } else {
                if ($this->data['Topbar']['md5'] == md5(file_get_contents(ROOT . DS . 'app' . DS . 'Plugin' . DS . 'Paszport' . DS . 'webroot' . DS . 'bar.ctp'))) {
                    echo json_encode(array('status' => 'nochange'));
                    die();
                } else {
                    echo json_encode(array('topbar' => file_get_contents(ROOT . DS . 'app' . DS . 'Plugin' . DS . 'Paszport' . DS . 'webroot' . DS . 'bar.ctp')));
                    exit();
                }
            }
        }
        exit();
    }

}
