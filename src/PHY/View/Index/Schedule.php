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

    namespace PHY\View\Index;

    use PHY\View\AView;

    use PHY\Model\Closing;

    /**
     * Create a list of the schedule.
     *
     * @package PHY\View\Index\Schedule
     * @category PHY\Phyneapple
     * @copyright Copyright (c) 2013 Phyneapple! (http://www.phyneapple.com/)
     * @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
     * @author John Mullanaphy <john@jo.mu>
     */
    class Schedule extends AView
    {

        /**
         * {@inheritDoc}
         */
        public function structure()
        {
            $app = $this->getLayout()->getController()->getApp();
            /**
             * @var \PHY\Database\IDatabase $database
             */
            $database = $app->get('database');
            $manager = $database->getManager();
            $collection = $manager->getCollection('Schedule');
            $collection->limit(0, 10);
            $collection->where()->field('day', 'primary')->is(date('w'));
            $this->setVariable('collection', $collection);

            $closing = $manager->load(['date' => date('Y-m-d')], new Closing);
            $this->setVariable('closing', $closing);
        }

    }
