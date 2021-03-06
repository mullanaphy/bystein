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

    namespace PHY\Model\Gallery;

    use PHY\Model\Entity;

    /**
     * Gallery Linked model so we can organize images.
     *
     * @package PHY\Model\Gallery\Linked
     * @category PHY\Phyneapple
     * @copyright Copyright (c) 2013 Phyneapple! (http://www.phyneapple.com/)
     * @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
     * @author John Mullanaphy <john@jo.mu>
     */
    class Linked extends Entity
    {

        protected static $_source = [
            'schema' => [
                'primary' => [
                    'table' => 'gallery_linked',
                    'columns' => [
                        'gallery_id' => 'id',
                        'image_id' => 'id',
                        'type_id' => 'id',
                        'sort' => 'int',
                    ],
                    'keys' => [
                        'local' => [
                            'key' => 'UNIQUE INDEX (`gallery_id`, `image_id`, `type_id`)',
                        ]
                    ]
                ]
            ]
        ];

    }
