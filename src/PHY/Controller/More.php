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

    use PHY\Markup\HTML5 as Markup;
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
    class More extends AController
    {

        /**
         * GET /
         */
        public function index_get()
        {
            $markup = new Markup;
            $limit = 24;
            $app = $this->getApp();
            $database = $app->get('database');

            $request = $this->getRequest();
            $galleryId = (int)$request->getActionName();
            $caller = 1;
            $start = $caller * $limit - $limit;

            /** @var \Mysqli_Result $prepare */
            $prepare = $database->query("SELECT i.*
                FROM `gallery_linked` l
                    INNER JOIN `image` i ON (l.`image_id` = i.`id`)
                WHERE l.`gallery_id` = " . (int)$galleryId . "
                ORDER BY l.`sort` ASC
                LIMIT " . (int)$start . ", " . $limit);
            while ($row = $prepare->fetch_assoc()) {
                $image = new Image($row);
                $content[] = $markup->div($markup->div($markup->a($markup->img([
                    'src' => $image->getImage(),
                    'alt' => $image->title
                ]), ['href' => $image->getImage()]), ['class' => 'thumbnail']), ['class' => 'col-sm-1']);
            }

            $caller = $request->get('_caller', 1);
            $rows = [];
            for ($i = 0; $i < 24; ++$i) {
                $rows[] = $content[$caller];
            }

            header('Content-Type: application/json');
            echo json_encode([
                'content' => (string)$markup->div($markup->div($rows, [
                    'class' => 'row'
                ]), [
                    'class' => 'item'
                ]),
                'more' => $caller < 4
            ]);
            exit;
        }

    }
