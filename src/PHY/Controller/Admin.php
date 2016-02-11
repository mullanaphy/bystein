<?php

    /**
     * Phyneapple!
     *
     * LICENSE
     *
     * This source file is subject to the Open Software License (OSL 3.0)
     * that is bundled with this package in the file LICENSE.txt.
     * It is also available through the world-wide-web at this URL:
     * http://opensource.org/licenses/osl-3.0.php
     * If you did not receive a copy of the license and are unable to
     * obtain it through the world-wide-web, please send an email
     * to license@phyneapple.com so we can send you a copy immediately.
     *
     */

    namespace PHY\Controller;

    use PHY\App;
    use PHY\Http\Exception\Forbidden;
    use PHY\Http\Response\Json as JsonResponse;
    use PHY\Http\Response\Xml as XmlResponse;
    use PHY\Model\Authorize;
    use PHY\Model\Config;
    use PHY\Model\User;
    use PHY\Model\Image;
    use PHY\Model\Gallery;

    /**
     * Default admin panel. Gives a flavor on how to use Phyneapple based controllers.
     *
     * @package PHY\Controller\Admin
     * @category PHY\Phyneapple
     * @copyright Copyright (c) 2013 Phyneapple! (http://www.phyneapple.com/)
     * @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
     * @author John Mullanaphy <john@jo.mu>
     */
    class Admin extends AController
    {

        /**
         * {@inheritDoc}
         */
        public function __construct(App $app = null)
        {
            $app->setTheme('admin');
            parent::__construct($app);

            /* @var \PHY\Model\IUser $user */
            $user = $app->getUser();

            if (!$user->exists()) {
                $app->set('session/_redirect', '/admin');
                $this->redirect('/login');
            } else {
                /* @var \PHY\Database\IManager $manager */
                $manager = $app->get('database')->getManager();
                $authorize = Authorize::loadByRequest('controller/admin', $manager);
                if (!$authorize->exists()) {
                    $authorize->request = 'controller/admin';
                    $authorize->allow = 'user admin super-admin';
                    $authorize->deny = 'all';
                    $manager->save($authorize);
                }
                if (!$authorize->isAllowed($user)) {
                    throw new Forbidden('Sorry, not allowed in the admin section.');
                }
            }
        }

        /**
         * {@inheritDoc}
         */
        public function action($action = 'index')
        {
            $layout = $this->getLayout();
            $layout->block('layout')->setTemplate('core/layout-admin.phtml');
            $layout->block('header')->setTemplate('admin/header.phtml');
            $layout->block('modal')->setTemplate('admin/modal.phtml');

            $head = $layout->block('head');
            $files = $head->getVariable('files');
            $core = array_search('core.css', $files['css']);
            if ($core > -1) {
                unset($files['css'][$core]);
            }
            $head->setVariable('files', $files);

            $layout->buildBlocks('breadcrumb', [
                'template' => 'admin/' . ($action !== 'index'
                        ? $action . '/'
                        : '') . 'breadcrumb.phtml'
            ]);
            $layout->block('layout')->setChild('breadcrumb', null);

            parent::action($action);
        }

        /**
         * {@inheritDoc}
         */
        public function index_get()
        {
            $app = $this->getApp();
            $content = $this->getLayout()->block('content');

            /**
             * @var \PHY\Database\IDatabase $database
             */
            $database = $app->get('database');
            $manager = $database->getManager();
            $config = $manager->load(['key' => 'email'], new Config);
            $content->setVariable('email', $config->value);
            $content->setVariable('user', $app->getUser());

            $collection = $manager->getCollection('Image');
            $collection->limit(3);
            $content->setVariable('images', $collection);

            $collection = $manager->getCollection('Gallery');
            $collection->limit(3);
            $content->setVariable('galleries', $collection);
        }

        /**
         * GET /admin/authorize
         */
        public function authorize_get()
        {
            $app = $this->getApp();
            $request = $this->getRequest();
            $id = $request->get('id', false);
            $layout = $this->getLayout();
            $content = $layout->block('content');

            /**
             * @var \PHY\Database\IDatabase $database
             */
            $database = $app->get('database');
            $manager = $database->getManager();

            if ($id !== false) {
                if ($id) {
                    $item = $manager->load($id, new Authorize);
                } else {
                    $item = new Authorize($request->get('authorize', []));
                }
                $content->setTemplate('admin/authorize/item.phtml');
                $content->setVariable('item', $item);
                $breadcrumb = $layout->block('breadcrumb');
                $breadcrumb->setVariable('item', $item);
            } else {
                $pageId = (int)$request->get('pageId', 1);
                if (!$pageId) {
                    $pageId = 1;
                }
                $limit = (int)$request->get('limit', 20);
                if (!$limit) {
                    $limit = 20;
                }

                $collection = $manager->getCollection('Authorize');
                $collection->limit((($pageId * $limit) - $limit), $limit);
                $collection->where()->field('deleted')->is(false);
                $collection->order()->by('request');

                $content->setTemplate('admin/authorize/collection.phtml');
                $content->setVariable('collection', $collection);
                $content->setChild('pagination', [
                    'viewClass' => 'pagination',
                    'pageId' => $pageId,
                    'limit' => $limit,
                    'total' => $collection->count(),
                    'url' => [
                        $this->url('admin/authorize'),
                        'limit' => $limit
                    ]
                ]);
            }
            if ($message = $app->get('session/admin/authorize/message')) {
                $app->delete('session/admin/authorize/message');
                $message['template'] = 'generic/message.phtml';
                $content->setChild('message', $message);
            }
        }

        /**
         * POST /admin/authorize
         */
        public function authorize_post()
        {
            $app = $this->getApp();
            $request = $this->getRequest();

            /**
             * @var \PHY\Database\IDatabase $database
             */
            $database = $app->get('database');
            $manager = $database->getManager();

            $id = (int)$request->get('id', 0);
            $data = $request->get('authorize', [
                'request' => '',
                'deny' => '',
                'allow' => ''
            ]);
            if ($id) {
                $item = $manager->load($id, new Authorize);
                if (!$item->exists() || $item->deleted) {
                    return $this->renderResponse('authorize', [
                        'title' => 'Hmmm',
                        'type' => 'warning',
                        'message' => 'No ACL found for id: ' . $id
                    ]);
                }
            } else {
                $item = new Authorize($data);
            }
            $data['allow'] = str_replace([PHP_EOL, "\n", "\r"], ' ', $data['allow']);
            $data['allow'] = trim(preg_replace('#\s+#', ' ', $data['allow']));
            $data['deny'] = str_replace([PHP_EOL, "\n", "\r"], ' ', $data['deny']);
            $data['deny'] = trim(preg_replace('#\s+#', ' ', $data['deny']));
            $item->set($data);
            $manager->save($item);
            return $this->renderResponse('authorize', [
                'title' => 'Yeah boy!',
                'type' => 'success',
                'message' => 'Successfully updated: ' . $item->request
            ]);
        }

        /**
         * PUT /admin/authorize
         */
        public function authorize_put()
        {
            $this->authorize_post();
        }

        /**
         * DELETE /admin/authorize/id/{id}
         */
        public function authorize_delete()
        {
            $app = $this->getApp();
            $request = $this->getRequest();

            /**
             * @var \PHY\Database\IDatabase $database
             */
            $database = $app->get('database');
            $manager = $database->getManager();

            $id = (int)$request->get('id', 0);
            if ($id) {
                $item = $manager->load($id, new Authorize);
                if (!$item->exists() || $item->deleted) {
                    return $this->renderResponse('authorize', [
                        'title' => 'Oh man...',
                        'type' => 'warning',
                        'message' => 'No ACL found for id: ' . $id
                    ]);
                }
            } else {
                return $this->renderResponse('authorize', [
                    'title' => 'Well?',
                    'type' => 'warning',
                    'message' => 'No ACL id provided.'
                ]);
            }
            $requestName = $item->request;
            $manager->delete($item);
            return $this->renderResponse('authorize', [
                'title' => 'Ok.',
                'type' => 'success',
                'message' => 'Successfully removed: ' . $requestName
            ]);
        }

        /**
         * GET /admin/user
         */
        public function user_get()
        {
            $app = $this->getApp();
            $request = $this->getRequest();
            $id = $request->get('id', false);
            $layout = $this->getLayout();
            $content = $layout->block('content');

            /**
             * @var \PHY\Database\IDatabase $database
             */
            $database = $app->get('database');
            $manager = $database->getManager();

            if ($id !== false) {
                if ($id) {
                    $item = $manager->load($id, new User);
                } else {
                    $item = new User($request->get('user', []));
                }
                $content->setTemplate('admin/user/item.phtml');
                $content->setVariable('item', $item);
                $breadcrumb = $layout->block('breadcrumb');
                $breadcrumb->setVariable('item', $item);
            } else {
                $pageId = (int)$request->get('pageId', 1);
                if (!$pageId) {
                    $pageId = 1;
                }
                $limit = (int)$request->get('limit', 20);
                if (!$limit) {
                    $limit = 20;
                }

                $collection = $manager->getCollection('User');
                $collection->limit((($pageId * $limit) - $limit), $limit);
                $collection->where()->field('deleted')->is(false);
                $collection->order()->by('name');

                $content->setTemplate('admin/user/collection.phtml');
                $content->setVariable('collection', $collection);
                $content->setChild('pagination', [
                    'viewClass' => 'pagination',
                    'pageId' => $pageId,
                    'limit' => $limit,
                    'total' => $collection->count(),
                    'url' => [
                        $this->url('admin/user'),
                        'limit' => $limit
                    ]
                ]);
            }
            if ($message = $app->get('session/admin/user/message')) {
                $app->delete('session/admin/user/message');
                $message['template'] = 'generic/message.phtml';
                $content->setChild('message', $message);
            }
        }

        /**
         * POST /admin/user
         */
        public function user_post()
        {
            $app = $this->getApp();
            $request = $this->getRequest();

            /**
             * @var \PHY\Database\IDatabase $database
             */
            $database = $app->get('database');
            $manager = $database->getManager();

            $id = (int)$request->get('id', 0);
            $data = $request->get('user', [
                'group' => '',
                'username' => '',
                'name' => '',
                'title' => '',
                'bio' => '',
                'phone' => '',
                'email' => ''
            ]);
            try {
                $datetime = date('Y-m-d H:i:s');
                if ($id) {
                    $item = $manager->load($id, new User);
                    if (!$item->exists() || $item->deleted) {
                        $app->set('session/admin/user/message', [
                            'title' => 'Seriously?',
                            'type' => 'warning',
                            'message' => 'No user found for id: ' . $id
                        ]);
                        return $this->redirect('/admin/user');
                    }
                    $data['updated'] = $datetime;
                } else {
                    $item = new User($data);
                    if (!$data['password']) {
                        throw new \InvalidArgumentException('You must provide a password for new users.');
                    } else if ($data['password'] !== $data['confirm']) {
                        throw new \InvalidArgumentException('The passwords entered did not match.');
                    }
                    $data['updated'] = $datetime;
                    $data['created'] = $datetime;
                    $data['activity'] = $datetime;
                }
                if (!$data['email']) {
                    throw new \InvalidArgumentException('You must provide an email.');
                } else if (!$data['email']) {
                    throw new \InvalidArgumentException('You must provide a username.');
                }
                $data['group'] = 'user';
                if ($data['password'] && $data['password'] === $data['confirm']) {
                    $password = $data['password'];
                } else {
                    $password = false;
                }
                unset($data['password'], $data['confirm']);
                $item->set($data);
                if ($password) {
                    $item->set('password', $password);
                }
                $manager->save($item);
                return $this->renderResponse('user', [
                    'title' => 'Great Success!',
                    'type' => 'success',
                    'message' => 'Successfully updated: ' . $item->name
                ]);
            } catch (\Exception $e) {
                return $this->renderResponse('user', [
                    'title' => 'Slight error.',
                    'type' => 'warning',
                    'message' => $e->getMessage()
                ]);
            }
        }

        /**
         * PUT /admin/user
         */
        public function user_put()
        {
            $this->user_post();
        }

        /**
         * DELETE /admin/user/id/{id}
         */
        public function user_delete()
        {
            $app = $this->getApp();
            $request = $this->getRequest();

            /**
             * @var \PHY\Database\IDatabase $database
             */
            $database = $app->get('database');
            $manager = $database->getManager();

            $id = (int)$request->get('id', 0);
            if ($id) {
                $item = $manager->load($id, new User);
                if (!$item->exists() || $item->deleted) {
                    return $this->renderResponse('user', [
                        'title' => 'Fiddlesticks...',
                        'type' => 'warning',
                        'message' => 'No user found for id: ' . $id
                    ]);
                }
            } else {
                return $this->renderResponse('user', [
                    'title' => 'Cannot be found.',
                    'type' => 'warning',
                    'message' => 'No user id provided.'
                ]);
            }
            $name = $item->name;
            $manager->delete($item);
            return $this->renderResponse('user', [
                'title' => 'Bye Bye!',
                'type' => 'success',
                'message' => 'Successfully removed: ' . $name
            ]);
        }

        /**
         * GET /admin/config
         */
        public function config_get()
        {
            $app = $this->getApp();
            $request = $this->getRequest();
            $id = $request->get('id', false);
            $layout = $this->getLayout();
            $content = $layout->block('content');

            /**
             * @var \PHY\Database\IDatabase $database
             */
            $database = $app->get('database');
            $manager = $database->getManager();

            if ($id !== false) {
                if ($id) {
                    $item = $manager->load($id, new Config);
                } else {
                    $item = new Config($request->get('config', []));
                }
                $content->setTemplate('admin/config/item.phtml');
                $content->setVariable('item', $item);
                $breadcrumb = $layout->block('breadcrumb');
                $breadcrumb->setVariable('item', $item);
            } else {
                $pageId = (int)$request->get('pageId', 1);
                if (!$pageId) {
                    $pageId = 1;
                }
                $limit = (int)$request->get('limit', 20);
                if (!$limit) {
                    $limit = 20;
                }

                $collection = $manager->getCollection('Config');
                $collection->limit((($pageId * $limit) - $limit), $limit);
                $collection->order()->by('key');

                $content->setTemplate('admin/config/collection.phtml');
                $content->setVariable('collection', $collection);
                $content->setChild('pagination', [
                    'viewClass' => 'pagination',
                    'pageId' => $pageId,
                    'limit' => $limit,
                    'total' => $collection->count(),
                    'url' => [
                        $this->url('admin/config'),
                        'limit' => $limit
                    ]
                ]);
            }
            if ($message = $app->get('session/admin/config/message')) {
                $app->delete('session/admin/config/message');
                $message['template'] = 'generic/message.phtml';
                $content->setChild('message', $message);
            }
        }

        /**
         * POST /admin/config
         */
        public function config_post()
        {
            $app = $this->getApp();
            $request = $this->getRequest();

            /**
             * @var \PHY\Database\IDatabase $database
             */
            $database = $app->get('database');
            $manager = $database->getManager();

            $id = (int)$request->get('id', 0);
            $data = $request->get('config', [
                'key' => '',
                'value' => '',
            ]);
            if ($id) {
                $item = $manager->load($id, new Config);
                if (!$item->exists() || $item->deleted) {
                    return $this->renderResponse('config', [
                        'title' => 'Not Configured!',
                        'type' => 'warning',
                        'message' => 'No config found for id: ' . $id
                    ]);
                }
            } else {
                $item = new Config($data);
            }
            $item->set($data);
            $manager->save($item);
            return $this->renderResponse('config', [
                'title' => 'Configured!',
                'type' => 'success',
                'message' => 'Successfully updated: ' . $item->key
            ]);
        }

        /**
         * PUT /admin/config
         */
        public function config_put()
        {
            $this->config_post();
        }

        /**
         * DELETE /admin/config/id/{id}
         */
        public function config_delete()
        {
            $app = $this->getApp();
            $request = $this->getRequest();

            /**
             * @var \PHY\Database\IDatabase $database
             */
            $database = $app->get('database');
            $manager = $database->getManager();

            $id = (int)$request->get('id', 0);
            if ($id) {
                $item = $manager->load($id, new Config);
                if (!$item->exists() || $item->deleted) {
                    return $this->renderResponse('config', [
                        'title' => 'Wasn\'t me...',
                        'type' => 'warning',
                        'message' => 'No config found for id: ' . $id
                    ]);
                } else if (in_array($item->key, ['address', 'email', 'phone'])) {
                    return $this->renderResponse('config', [
                        'title' => 'Denied!',
                        'type' => 'warning',
                        'message' => 'Sorry, you cannot delete the config for ' . $item->key . ' since that\'s used in a lot of places.'
                    ]);
                }
            } else {
                return $this->renderResponse('config', [
                    'title' => 'Not gonna do it.',
                    'type' => 'warning',
                    'message' => 'No config id provided.'
                ]);
            }
            $key = $item->key;
            $manager->delete($item);
            return $this->renderResponse('config', [
                'title' => 'Deconfigured!',
                'type' => 'success',
                'message' => 'Successfully removed: ' . $key
            ]);
        }

        /**
         * GET /admin/schedule
         */
        public function schedule_get()
        {
            $app = $this->getApp();
            $request = $this->getRequest();
            $id = $request->get('id', false);
            $layout = $this->getLayout();
            $content = $layout->block('content');

            /**
             * @var \PHY\Database\IDatabase $database
             */
            $database = $app->get('database');
            $manager = $database->getManager();

            if ($id !== false) {
                if ($id) {
                    $item = $manager->load($id, new Schedule);
                } else {
                    $item = new Schedule($request->get('schedule', []));
                }
                $content->setTemplate('admin/schedule/item.phtml');
                $content->setVariable('item', $item);
                $breadcrumb = $layout->block('breadcrumb');
                $breadcrumb->setVariable('item', $item);
            } else {
                $pageId = (int)$request->get('pageId', 1);
                if (!$pageId) {
                    $pageId = 1;
                }
                $limit = (int)$request->get('limit', 20);
                if (!$limit) {
                    $limit = 20;
                }

                $collection = $manager->getCollection('Schedule');
                $collection->limit((($pageId * $limit) - $limit), $limit);
                $collection->order()->by('day')->by('start');

                $content->setTemplate('admin/schedule/collection.phtml');
                $content->setVariable('collection', $collection);
                $content->setChild('pagination', [
                    'viewClass' => 'pagination',
                    'pageId' => $pageId,
                    'limit' => $limit,
                    'total' => $collection->count(),
                    'url' => [
                        $this->url('admin/schedule'),
                        'limit' => $limit
                    ]
                ]);
            }
            if ($message = $app->get('session/admin/schedule/message')) {
                $app->delete('session/admin/schedule/message');
                $message['template'] = 'generic/message.phtml';
                $content->setChild('message', $message);
            }
        }

        /**
         * POST /admin/schedule
         */
        public function schedule_post()
        {
            $app = $this->getApp();
            $request = $this->getRequest();

            /**
             * @var \PHY\Database\IDatabase $database
             */
            $database = $app->get('database');
            $manager = $database->getManager();

            $id = (int)$request->get('id', 0);
            $data = $request->get('schedule', [
                'day' => 0,
                'start' => 0,
                'end' => 0,
                'type' => '',
                'level' => '',
                'title' => '',
            ]);
            if ($id) {
                $item = $manager->load($id, new Schedule);
                if (!$item->exists() || $item->deleted) {
                    return $this->renderResponse('schedule', [
                        'title' => 'Not Scheduled!',
                        'type' => 'warning',
                        'message' => 'No schedule found for id: ' . $id
                    ]);
                }
            } else {
                $item = new Schedule($data);
            }
            $data['end'] -= .5;
            $item->set($data);
            $manager->save($item);
            return $this->renderResponse('schedule', [
                'title' => 'Busy Busy!',
                'type' => 'success',
                'message' => 'Successfully updated: ' . $item->getClassName()
            ]);
        }

        /**
         * PUT /admin/schedule
         */
        public function schedule_put()
        {
            $this->schedule_post();
        }

        /**
         * DELETE /admin/schedule/id/{id}
         */
        public function schedule_delete()
        {
            $app = $this->getApp();
            $request = $this->getRequest();

            /**
             * @var \PHY\Database\IDatabase $database
             */
            $database = $app->get('database');
            $manager = $database->getManager();

            $id = (int)$request->get('id', 0);
            if ($id) {
                $item = $manager->load($id, new Schedule);
                if (!$item->exists() || $item->deleted) {
                    return $this->renderResponse('schedule', [
                        'title' => 'Blank Calendar...',
                        'type' => 'warning',
                        'message' => 'No schedule found for id: ' . $id
                    ]);
                }
            } else {
                return $this->renderResponse('schedule', [
                    'title' => 'It\'s your day off.',
                    'type' => 'warning',
                    'message' => 'No schedule id provided.'
                ]);
            }
            $className = $item->getClassName();
            $manager->delete($item);
            return $this->renderResponse('schedule', [
                'title' => 'More time off!',
                'type' => 'success',
                'message' => 'Successfully removed: ' . $className
            ]);
        }

        /**
         * GET /admin/gallery
         */
        public function gallery_get()
        {
            $app = $this->getApp();
            $request = $this->getRequest();
            $id = $request->get('id', false);
            $layout = $this->getLayout();
            $content = $layout->block('content');

            /**
             * @var \PHY\Database\IDatabase $database
             */
            $database = $app->get('database');
            $manager = $database->getManager();

            if ($id !== false) {
                if ($id) {
                    $item = $manager->load($id, new Gallery);
                } else {
                    $item = new Gallery($request->get('gallery', []));
                }
                $content->setTemplate('admin/gallery/item.phtml');
                $content->setVariable('item', $item);
                $breadcrumb = $layout->block('breadcrumb');
                $breadcrumb->setVariable('item', $item);
            } else {
                $pageId = (int)$request->get('pageId', 1);
                if (!$pageId) {
                    $pageId = 1;
                }
                $limit = (int)$request->get('limit', 20);
                if (!$limit) {
                    $limit = 20;
                }

                $collection = $manager->getCollection('Gallery');
                $collection->limit((($pageId * $limit) - $limit), $limit);
                $collection->order()->by('sort')->direction('asc');

                $content->setTemplate('admin/gallery/collection.phtml');
                $content->setVariable('collection', $collection);
                $content->setChild('pagination', [
                    'viewClass' => 'pagination',
                    'pageId' => $pageId,
                    'limit' => $limit,
                    'total' => $collection->count(),
                    'url' => [
                        $this->url('admin/gallery'),
                        'limit' => $limit
                    ]
                ]);
            }
            if ($message = $app->get('session/admin/gallery/message')) {
                $app->delete('session/admin/gallery/message');
                $message['template'] = 'generic/message.phtml';
                $content->setChild('message', $message);
            }
        }

        /**
         * POST /admin/gallery
         */
        public function gallery_post()
        {
            $app = $this->getApp();
            $request = $this->getRequest();

            /**
             * @var \PHY\Database\IDatabase $database
             */
            $database = $app->get('database');
            $manager = $database->getManager();

            $id = (int)$request->get('id', 0);
            $data = $request->get('gallery', [
                'name' => '',
                'url' => '',
            ]);
            if ($id) {
                $item = $manager->load($id, new Gallery);
                if (!$item->exists() || $item->deleted) {
                    return $this->renderResponse('gallery', [
                        'title' => 'Who is gallery?!?!',
                        'type' => 'warning',
                        'message' => 'No gallery found for id: ' . $id
                    ]);
                }
            } else {
                $item = new Gallery($data);
            }

            $item->set($data);
            $manager->save($item);
            return $this->renderResponse('gallery', [
                'title' => 'Success!',
                'type' => 'success',
                'message' => 'Successfully updated: ' . $item->name
            ]);
        }

        /**
         * PUT /admin/gallery
         */
        public function gallery_put()
        {
            $this->gallery_post();
        }

        /**
         * DELETE /admin/gallery/id/{id}
         */
        public function gallery_delete()
        {
            $app = $this->getApp();
            $request = $this->getRequest();

            /**
             * @var \PHY\Database\IDatabase $database
             */
            $database = $app->get('database');
            $manager = $database->getManager();

            $id = (int)$request->get('id', 0);
            if ($id) {
                $item = $manager->load($id, new Gallery);
                if (!$item->exists() || $item->deleted) {
                    return $this->renderResponse('gallery', [
                        'title' => 'Not Closed.',
                        'type' => 'warning',
                        'message' => 'No gallery found for id: ' . $id
                    ]);
                }
            } else {
                return $this->renderResponse('gallery', [
                    'title' => 'You must work!',
                    'type' => 'warning',
                    'message' => 'No gallery id provided.'
                ]);
            }
            $date = $item->date;
            $manager->delete($item);

            $collection = $manager->getCollection('Gallery\Linked');
            $collection->where()->field('gallery_id')->is($id);
            foreach ($collection as $linked) {
                $manager->delete($linked);
            }

            return $this->renderResponse('gallery', [
                'title' => 'Reopened!',
                'type' => 'success',
                'message' => 'Successfully removed: ' . $date
            ]);
        }

        /**
         * POST /admin/gallerySort
         */
        public function gallerySort_post()
        {
            try {

                /**
                 * @var \PHY\Database\IDatabase $database
                 */
                $database = $this->getApp()->get('database');
                /**
                 * @var \PHY\Database\IManager $manager
                 */
                $manager = $database->getManager();
                $request = $this->getRequest();
                $item = $manager->load(['id' => $request->get('id')], new Gallery);

                if (!$item || !$item->exists()) {
                    return $this->renderResponse('gallery', [
                        'type' => 'error',
                        'message' => 'Item not found.',
                    ]);
                }
                $direction = str_replace('.json', '', $request->get('sort', 'up.json'));

                /**
                 * @var \PHY\Model\Gallery\Collection $collection
                 */
                $collection = $manager->getCollection('Gallery');
                $collection->limit(1);

                $current = $item->sort;
                if ($direction === 'up') {
                    $collection->order()->by('sort')->direction('desc');
                    $collection->where()->field('sort')->lt($current);
                } else {
                    $collection->order()->by('sort')->direction('asc');
                    $collection->where()->field('sort')->gt($current);
                }
                if (!$collection->count()) {
                    return $this->renderResponse('gallery', [
                        'type' => 'success',
                    ]);
                }
                $collection->load();
                $swap = $collection->current();
                if (!$swap || !$swap->exists()) {
                    return $this->renderResponse('gallery', [
                        'type' => 'success'
                    ]);
                }
                $item->sort = $swap->sort;
                $manager->save($item);
                $swap->sort = $current;
                $manager->save($swap);
            } catch (\Exception $e) {
                var_dump($e);
            }
            return $this->renderResponse('gallery', [
                'type' => 'success',
                'direction' => $direction,
            ]);
        }

        /**
         * POST /admin/galleryImageSort
         */
        public function galleryImageSort_post()
        {
            try {

                /**
                 * @var \PHY\Database\IDatabase $database
                 */
                $database = $this->getApp()->get('database');
                /**
                 * @var \PHY\Database\IManager $manager
                 */
                $manager = $database->getManager();
                $request = $this->getRequest();
                $item = $manager->load([
                    'image_id' => $request->get('image_id'),
                    'gallery_id' => $request->get('gallery_id')
                ], new Gallery\Linked);

                if (!$item || !$item->exists()) {
                    return $this->renderResponse('gallery', [
                        'type' => 'error',
                        'message' => 'Item not found.',
                    ]);
                }
                $direction = str_replace('.json', '', $request->get('sort', 'up.json'));

                /**
                 * @var \PHY\Model\Gallery\Linked\Collection $collection
                 */
                $collection = $manager->getCollection('Gallery\Linked');
                $collection->limit(1);

                $current = $item->sort;
                if ($direction === 'up') {
                    $collection->order()->by('sort')->direction('desc');
                    $collection->where()->field('sort')->lt($current);
                } else {
                    $collection->order()->by('sort')->direction('asc');
                    $collection->where()->field('sort')->gt($current);
                }
                if (!$collection->count()) {
                    return $this->renderResponse('gallery', [
                        'type' => 'success',
                        'direction' => $direction,
                    ]);
                }
                $collection->load();
                $swap = $collection->current();
                if (!$swap || !$swap->exists()) {
                    return $this->renderResponse('gallery', [
                        'type' => 'success',
                        'direction' => $direction,
                    ]);
                }
                $item->sort = $swap->sort;
                $manager->save($item);
                $swap->sort = $current;
                $manager->save($swap);
            } catch (\Exception $e) {
                var_dump($e);
            }
            return $this->renderResponse('gallery', [
                'type' => 'success',
                'direction' => $direction,
            ]);
        }

        /**
         * DELETE /admin/galleryImage
         */
        public function galleryImage_delete()
        {
            try {

                /**
                 * @var \PHY\Database\IDatabase $database
                 */
                $database = $this->getApp()->get('database');
                /**
                 * @var \PHY\Database\IManager $manager
                 */
                $manager = $database->getManager();
                $request = $this->getRequest();
                $item = $manager->load([
                    'gallery_id' => $request->get('gallery_id'),
                    'image_id' => $request->get('image_id')
                ], new Gallery\Linked);

                if (!$item || !$item->exists()) {
                    return $this->renderResponse('gallery', [
                        'type' => 'error',
                        'message' => 'Item not found.',
                    ]);
                }

                /**
                 * @var \PHY\Model\Gallery\Collection $collection
                 */
                $collection = $manager->getCollection('Gallery\Linked');
                $collection->limit(1);

                $current = $item->sort;
                $collection->order()->by('sort')->direction('asc');
                $collection->where()->field('sort')->gt($current);
                $swap = false;
                if ($collection->count()) {
                    $collection->load();
                    $swap = $collection->current();
                    if ($swap && $swap->exists()) {
                        $swap->sort = $current;
                    }
                }
                $manager->delete($item);
                if ($swap) {
                    $manager->save($swap);
                }
            } catch (\Exception $e) {
                return $this->renderResponse('gallery', [
                    'type' => 'error',
                    'message' => $e->getMessage(),
                    'exception' => [
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'code' => $e->getCode(),
                    ]
                ]);
            }
            return $this->renderResponse('gallery', [
                'type' => 'success',
            ]);
        }

        /**
         * GET /admin/galleryImage
         */
        public function galleryImage_get()
        {
            $app = $this->getApp();
            $request = $this->getRequest();
            $id = (int)$request->get('id', 0);
            $layout = $this->getLayout();
            $content = $layout->block('content');

            /**
             * @var \PHY\Database\IDatabase $database
             */
            $database = $app->get('database');
            $manager = $database->getManager();

            $item = $manager->load(['id' => $id], new Gallery);

            $pageId = (int)$request->get('pageId', 1);
            if (!$pageId) {
                $pageId = 1;
            }
            $limit = (int)$request->get('limit', 20);
            if (!$limit) {
                $limit = 20;
            }

            /** @var \Mysqli_Result $prepare */
            $prepare = $database->query("SELECT i.*
                FROM `gallery_linked` l
                    INNER JOIN `image` i ON (l.`image_id` = i.`id`)
                WHERE l.`gallery_id` = " . (int)$id);
            $images = [];
            while ($row = $prepare->fetch_assoc()) {
                $images[] = new Image($row);
            }

            $layout->block('breadcrumb')
                ->setTemplate('admin/gallery/image/breadcrumb.phtml')
                ->setVariable('item', $item)
                ->setVariable('galleryId', $galleryId);
            $content->setTemplate('admin/gallery/image/collection.phtml');
            $content->setVariable('item', $item);
            $content->setVariable('images', $images);
            $content->setChild('pagination', [
                'viewClass' => 'pagination',
                'pageId' => $pageId,
                'limit' => $limit,
                'total' => count($images),
                'url' => [
                    $this->url('admin/galleryImage'),
                    'limit' => $limit
                ]
            ]);
            if ($message = $app->get('session/admin/image/message')) {
                $app->delete('session/admin/image/message');
                $message['template'] = 'generic/message.phtml';
                $content->setChild('message', $message);
            }
        }

        /**
         * GET /admin/image
         */
        public function image_get()
        {
            $app = $this->getApp();
            $request = $this->getRequest();
            $id = $request->get('id', false);
            $layout = $this->getLayout();
            $content = $layout->block('content');

            /**
             * @var \PHY\Database\IDatabase $database
             */
            $database = $app->get('database');
            $manager = $database->getManager();

            if ($id !== false) {
                if ($id) {
                    $item = $manager->load($id, new Image);
                } else {
                    $item = new Image($request->get('image', []));
                }
                $content->setTemplate('admin/image/item.phtml');
                $content->setVariable('item', $item);

                $collection = $manager->getCollection('Gallery');
                $galleries = [];
                foreach ($collection as $gallery) {
                    $galleries[$gallery->id()] = (object)[
                        'name' => $gallery->name,
                        'selected' => false,
                    ];
                }
                $collection = $manager->getCollection('Gallery\Linked');
                $collection->where()->field('image_id')->is($id);
                foreach ($collection as $linked) {
                    if (isset($galleries[$linked->gallery_id])) {
                        $galleries[$linked->gallery_id]->selected = true;
                    }
                }

                if ($galleryId = $request->get('gallery_id', 0)) {
                    if (isset($galleries[$galleryId])) {
                        $galleries[$galleryId]->selected = true;
                    }
                }
                $content->setVariable('galleries', $galleries);
                $content->setVariable('galleryId', $galleryId);

                $breadcrumb = $layout->block('breadcrumb');
                $breadcrumb->setVariable('item', $item);
                $breadcrumb->setVariable('galleryId', $galleryId);

            } else {
                $pageId = (int)$request->get('pageId', 1);
                if (!$pageId) {
                    $pageId = 1;
                }
                $limit = (int)$request->get('limit', 20);
                if (!$limit) {
                    $limit = 20;
                }

                $collection = $manager->getCollection('Image');
                $collection->limit((($pageId * $limit) - $limit), $limit);

                $content->setTemplate('admin/image/collection.phtml');
                $content->setVariable('collection', $collection);
                $content->setChild('pagination', [
                    'viewClass' => 'pagination',
                    'pageId' => $pageId,
                    'limit' => $limit,
                    'total' => $collection->count(),
                    'url' => [
                        $this->url('admin/image'),
                        'limit' => $limit
                    ]
                ]);
            }
            if ($message = $app->get('session/admin/image/message')) {
                $app->delete('session/admin/image/message');
                $message['template'] = 'generic/message.phtml';
                $content->setChild('message', $message);
            }
        }

        /**
         * POST /admin/image
         */
        public function image_post()
        {
            $app = $this->getApp();
            $request = $this->getRequest();

            /**
             * @var \PHY\Database\IDatabase $database
             */
            $database = $app->get('database');
            $manager = $database->getManager();

            $id = (int)$request->get('id', 0);
            $data = $request->get('image', [
                'id' => '',
                'title' => '',
                'alt' => '',
            ]);

            if ($id) {
                $item = $manager->load($id, new Image);
                if (!$item->exists() || $item->deleted) {
                    return $this->renderResponse('image', [
                        'title' => 'Image Not Found!',
                        'type' => 'warning',
                        'message' => 'No image found for id: ' . $id
                    ]);
                }
            } else {
                $item = new Image($data);
            }
            $item->set($data);

            if (isset($_FILES, $_FILES['file'], $_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
                $filename = date('YmdHis') . '-' . $_FILES['file']['name'];
                $directory = $app->getPublicDirectory() . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . 'uploaded' . DIRECTORY_SEPARATOR . 'image';
                $file = $directory . DIRECTORY_SEPARATOR . $filename;

                if (!is_writable($directory)) {
                    return $this->renderResponse('image', [
                        'title' => 'No Bueno...',
                        'type' => 'warning',
                        'message' => 'Seems ' . $directory . ' is no writable...'
                    ]);
                }

                move_uploaded_file($_FILES['file']['tmp_name'], $file);

                $item->set('file', '/media/uploaded/image/' . $filename);
            }

            $manager->save($item);

            /* First lets remove all old linked gallery/image combos */
            $collection = $manager->getCollection('Gallery\Linked');
            $collection->where()->field('image_id')->is($id);
            foreach ($collection as $linked) {
                $manager->delete($linked);
            }

            $galleries = $request->get('gallery', []);

            foreach ($galleries as $gallery_id) {
                $linked = new Gallery\Linked([
                    'gallery_id' => $gallery_id,
                    'image_id' => $item->id()
                ]);
                $manager->save($linked);
            }

            $parameters = $this->getRequest()->getParameters();
            if (isset($parameters['gallery_id'])) {
                $url = 'galleryImage/id/' . $parameters['gallery_id'];
            } else {
                $url = 'image';
            }

            return $this->renderResponse($url, [
                'title' => 'Uploaded!',
                'type' => 'success',
                'message' => 'Successfully updated: ' . $item->id()
            ]);
        }

        /**
         * PUT /admin/image
         */
        public function image_put()
        {
            $this->image_post();
        }

        /**
         * DELETE /admin/image/id/{id}
         */
        public function image_delete()
        {
            $app = $this->getApp();
            $request = $this->getRequest();

            /**
             * @var \PHY\Database\IDatabase $database
             */
            $database = $app->get('database');
            $manager = $database->getManager();

            $id = (int)$request->get('id', 0);
            if ($id) {
                $item = $manager->load($id, new Image);
                if (!$item->exists() || $item->deleted) {
                    return $this->renderResponse('image', [
                        'title' => 'Image never existed...',
                        'type' => 'warning',
                        'message' => 'No config found for id: ' . $id
                    ]);
                }
            } else {
                return $this->renderResponse('image', [
                    'title' => 'Oh well.',
                    'type' => 'warning',
                    'message' => 'No image id provided.'
                ]);
            }
            $file = $item->file;
            $manager->delete($item);
            return $this->renderResponse('image', [
                'title' => 'Image go bye bye!',
                'type' => 'success',
                'message' => 'Successfully removed: ' . $file
            ]);
        }

        public function imageCrop_post()
        {
            $request = $this->getRequest();
            $id = (int)$request->get('id', 0);
            $galleryId = (int)$request->get('gallery_id', 0);

            if (!$id) {
                return $this->renderResponse('imageCrop', [
                    'type' => 'error',
                    'message' => 'Item not found.',
                ]);
            }

            $app = $this->getApp();
            $request = $this->getRequest();

            $cropper = $request->get('crop', []);

            /**
             * @var \PHY\Database\IDatabase $database
             */
            $database = $app->get('database');
            $manager = $database->getManager();

            $item = $manager->load($id, new Image);

            if (!$item->exists() || $item->deleted) {
                return $this->renderResponse('imageCrop', [
                    'title' => 'Image Not Found!',
                    'type' => 'warning',
                    'message' => 'No image found for id: ' . $id,
                ]);
            }

            $file = $item->file;
            $file = explode('.', $file);
            $file[count($file) - 1] = 't-' . time() . $file[count($file) - 1];
            $file = implode('.', $file);

            $path = $this->getApp()->getPath()->getRoutes()['public'];
            $image = imagecreatefromjpeg($path . $item->file);
            $thumbnail = imagecreatetruecolor(80, 160);
            imagecopyresampled($thumbnail, $image, 0, 0, (float)$cropper['x'], (float)$cropper['y'], 80, 160, (float)$cropper['width'], (float)$cropper['height']);
            imagejpeg($thumbnail, $path . $file, 90);
            $item->set('thumbnail', $file);
            $manager->save($item);

            return $this->renderResponse('imageCrop', [
                'title' => 'Thumbnail created',
                'type' => 'success',
                'message' => 'Successfully cropped: ' . $item->id()
            ], '/admin/imageCrop/id/' . $item->id() . ($galleryId
                    ? '/gallery_id/' . $galleryId
                    : ''));
        }

        public function imageCrop_get()
        {
            $app = $this->getApp();
            $request = $this->getRequest();
            $id = (int)$request->get('id', 0);
            $galleryId = (int)$request->get('gallery_id', 0);

            if (!$id) {
                return $this->renderResponse('image', [
                    'title' => 'Image Not Found!',
                    'type' => 'warning',
                    'message' => 'No image found for id: ' . $id
                ]);
            }

            $layout = $this->getLayout();

            $head = $layout->block('head');
            $files = $head->getVariable('files');
            $files['css'][] = 'jcrop.min.css';
            $head->setVariable('files', $files);

            $content = $layout->block('content');

            /**
             * @var \PHY\Database\IDatabase $database
             */
            $database = $app->get('database');
            $manager = $database->getManager();

            $item = $manager->load($id, new Image);

            if (!$item->exists() || $item->deleted) {
                return $this->renderResponse('image', [
                    'title' => 'Image never existed...',
                    'type' => 'warning',
                    'message' => 'No config found for id: ' . $id
                ]);
            }

            $content->setTemplate('admin/image/crop.phtml');
            $content->setVariable('item', $item);

            $breadcrumb = $layout->block('breadcrumb');
            $breadcrumb->setTemplate('admin/image/crop/breadcrumb.phtml');
            $breadcrumb->setVariable('item', $item);
            $breadcrumb->setVariable('galleryId', $galleryId);

            if ($message = $app->get('session/admin/imageCrop/message')) {
                $app->delete('session/admin/imageCrop/message');
                $message['template'] = 'generic/message.phtml';
                $content->setChild('message', $message);
            }
        }

        private function renderResponse($action, $message, $redirect = '')
        {
            $accept = $this->getRequest()->getHeader('Accept', 'text/html;');
            if ($accept) {
                $accept = explode(',', $accept);
                foreach ($accept as $type) {
                    switch (trim($type)) {
                        case 'text/html':
                            $app = $this->getApp();
                            $app->set('session/admin/' . $action . '/message', $message);
                            $this->getResponse()->setStatusCode($message['type'] === 'success'
                                ? 200
                                : 500);
                            if ($redirect) {
                                return $this->redirect($redirect);
                            }
                            return $this->redirect('/admin/' . $action);
                            break;
                        case 'application/json':
                        case 'text/json':
                        case 'text/javascript':
                            $response = new JsonResponse;
                            $response->setData($message);
                            $response->setStatusCode($message['type'] === 'success'
                                ? 200
                                : 500);
                            $this->setResponse($response);
                            return $response;
                            break;
                        case 'application/xml':
                        case 'text/xml':
                            $response = new XmlResponse;
                            $response->setData($message);
                            $response->setStatusCode($message['type'] === 'success'
                                ? 200
                                : 500);
                            $this->setResponse($response);
                            return $response;
                            break;
                        default:
                    }
                }
            }
            $app = $this->getApp();
            $app->set('session/admin/' . $action . '/message', $message);
            $this->getResponse()->setStatusCode($message['type'] === 'success'
                ? 200
                : 500);

            if ($redirect) {
                return $this->redirect($redirect);
            }
            return $this->redirect('/admin/' . $action);
        }

    }
