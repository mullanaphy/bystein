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

    use PHY\Http\Response\Json;
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
            $limit = 12;
            $app = $this->getApp();
            $database = $app->get('database');
            $response = new Json;

            $request = $this->getRequest();
            $galleryId = (int)$request->get('galleryId', false);
            if (!$galleryId) {
                return $response->setData(['content' => false, 'more' => false]);
            }
            $caller = $request->get('_caller', 1) + 1;
            $start = $caller * $limit - $limit;

            $rows = [];
            /** @var \Mysqli_Result $prepare */
            $prepare = $database->query("SELECT i.*
                FROM `gallery_linked` l
                    INNER JOIN `image` i ON (l.`image_id` = i.`id`)
                WHERE l.`gallery_id` = " . (int)$galleryId . "
                ORDER BY l.`sort` ASC
                LIMIT " . (int)$start . ", " . ($limit + 1));
            while ($row = $prepare->fetch_assoc()) {
                $image = new Image($row);
                $rows[] = $markup->div($markup->div($markup->a($markup->img([
                    'src' => $image->thumbnail,
                    'alt' => $image->title,
                ]), [
                    'href' => $image->getImage(),
                    'data' => ['toggle' => 'lightbox', 'parent' => '.thumbnail-gallery'],
                ]), ['class' => 'thumbnail']), ['class' => 'col-xs-4 col-sm-2']);
            }

            if (!$rows) {
                return $response->setData(['content' => false, 'more' => false]);
            }

            $more = false;
            if (count($rows) > $limit) {
                $more = true;
                array_pop($rows);
            }

            return $response->setData([
                'content' => (string)$markup->div($markup->div($rows, [
                    'class' => 'row'
                ]), [
                    'class' => 'item'
                ]),
                'more' => $more
            ]);
        }

    }
