<?php

    /**
     * Briggs Academy of Mixed Martial Arts
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

    use PHY\Model\Blog as Model;
    use Michelf\Markdown;
    use PHY\Variable\Str;

    /**
     * Blog page.
     *
     * @package PHY\Controller\News
     * @category PHY\Briggs
     * @copyright Copyright (c) 2014 Briggs Academy of Mixed Martial Arts (http://www.briggsmma.com/)
     * @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
     * @author John Mullanaphy <john@jo.mu>
     */
    class News extends AController
    {

        /**
         * GET /news
         */
        public function index_get()
        {

            $layout = $this->getLayout();
            $head = $layout->block('head');
            $content = $layout->block('content');

            $app = $this->getApp();

            /* @var \PHY\Database\IDatabase $database */
            $database = $app->get('database');
            $manager = $database->getManager();

            $request = $this->getRequest();
            $action = $request->getActionName();

            $slug = $this->getRequest()->getActionName();
            if ($slug !== '__index') {
                $model = new Model;
                $item = $manager->load(['slug' => $slug], $model);
                if (!$item->exists()) {
                    return $this->redirect('/news');
                }
                $head->setVariable('title', $item->title . '| Gym News');

                $content->setTemplate('news/view.phtml');
                $content->setVariable('item', $item);
                $head->setVariable('description', (new Str($item->title . ': ' . $item->content))->toShorten(160));

                $cache = $app->get('cache');
                if (!$post = $cache->get('blog/' . $item->id() . '/rendered')) {
                    $post = Markdown::defaultTransform($item->content);
                    $cache->set('blog/' . $item->id() . '/rendered', $post, 86400 * 31);
                }

                $content->setVariable('content', $post);
            } else {
                $head->setVariable('title', 'Gym News');
                $head->setVariable('description', 'See all of our updates and new happenings here at Briggs MMA.');

                /* @var \PHY\Model\User\Collection $collection */
                $collection = $manager->getCollection('Blog');

                $content->setVariable('collection', $collection);

                $count = $collection->count();
                $request = $this->getRequest();
                if ($count > $limit = $request->get('limit', 10)) {
                    $pages = ceil($count / $limit);
                    if ($action === 'page') {
                        $pageId = $slug;
                        if (!$pageId) {
                            $pageId = 1;
                        } else if ($pageId > $pages) {
                            $pageId = $pages;
                        }
                    } else {
                        $pageId = 1;
                    }
                    $offset = ($pageId * $limit) - $limit;
                    $collection->limit($offset, $limit);
                    $content->setChild('news/pagination', [
                        'viewClass' => 'pagination',
                        'limit' => $limit,
                        'total' => $count,
                        'pageId' => $pageId,
                        'url' => '/news/page/[%i]'
                    ]);
                }
            }
        }

    }
