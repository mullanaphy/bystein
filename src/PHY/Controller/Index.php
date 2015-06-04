<?php

    /**
     * Phyneapple!
     * LICENSE
     * This source file is subject to the Open Software License (OSL 3.0)
     * that is bundled with this package in the file LICENSE.txt.
     * It is also available through the world-wide-web at this URL:
     * http://opensource.org/licenses/osl-3.0.php
     * If you did not receive a copy of the license and are unable to
     * obtain it through the world-wide-web, please send an email
     * to license@phyneapple.com so we can send you a copy immediately.

     */

    namespace PHY\Controller;

    use PHY\Model\Config as ConfigModel;
    use PHY\Model\Image;
    use PHY\Model\User;

    /**
     * Home page.
     *
     * @package PHY\Controller\Index
     * @category PHY\Phyneapple
     * @copyright Copyright (c) 2013 Phyneapple! (http://www.phyneapple.com/)
     * @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
     * @author John Mullanaphy <john@jo.mu>
     */
    class Index extends AController
    {

        /**
         * GET /
         */
        public function index_get()
        {
            $app = $this->getApp();

            /* @var \PHY\Database\IDatabase $database */
            $database = $app->get('database');
            $manager = $database->getManager();

            $description = $manager->load(['key' => 'meta_description'], new ConfigModel);

            $layout = $this->getLayout();

            $head = $layout->block('head');
            $head->setVariable('title', 'Art of Chris Stein');
            $head->setVariable('description', $description->value
                ?: 'Description!');

            /**
             * @var \PHY\View\AView $body
             */
            $body = $layout->block('layout');
            $body->setTemplate('core/layout-1col.phtml');

            $collection = $manager->getCollection('Gallery');
            $content = $layout->block('content');
            $content->setVariable('galleries', $collection);
            $content->setVariable('galleryImages', function ($id) use ($database, $manager) {
                /** @var \Mysqli_Result $prepare */
                $prepare = $database->query("SELECT i.*
                    FROM `gallery_linked` l
                        INNER JOIN `image` i ON (l.`image_id` = i.`id`)
                    WHERE l.`gallery_id` = " . (int)$id . "
                    LIMIT 6");
                $images = [];
                while ($row = $prepare->fetch_assoc()) {
                    $images[] = new Image($row);
                }
                $rows = [];
                if ($images) {
                    for ($i = 0; $i < 24; ++$i) {
                        $rows[] = $images[0];
                    }
                }
                return $rows;
                return $images;
            });

            /* @var \MySQLi_STMT $query */
            $database->multi_query("SET @image := 0, @gallery:= '';
                SELECT o.*, g.`name`
                FROM (
                    SELECT l.`gallery_id`, i.*, @image := if(@gallery = l.`gallery_id`, @image + 1, 1) AS n, @gallery := l.`gallery_id` AS g
                    FROM `gallery_linked` AS l
                        INNER JOIN `image` i ON (l.`image_id` = i.`id`)
                    ORDER BY l.`gallery_id` ASC, l.`sort` ASC
                ) AS o
                INNER JOIN `gallery` g ON (o.`gallery_id` = g.`id`)
                WHERE n <= 25
                ORDER BY g.`sort` ASC;", MYSQLI_USE_RESULT);

            do {
                if ($result = $database->store_result()) {
                    $collection = [];
                    while ($row = $result->fetch_assoc()) {
                        $item = new Image;
                        $item->set($row, true);
                        $collection[] = $item;
                    }
                    $result->free();
                }
            } while ($database->more_results() && $database->next_result());

            $body->setChild('jumbotron', [
                'template' => 'index/featured.phtml',
                'collection' => $collection
            ]);
        }

    }
