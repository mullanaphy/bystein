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

    namespace PHY\Model;

    /**
     * For schedule dates that will show up on the schedule.
     *
     * @package PHY\Model\Schedule
     * @category PHY\Phyneapple
     * @copyright Copyright (c) 2013 Phyneapple! (http://www.phyneapple.com/)
     * @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
     * @author John Mullanaphy <john@jo.mu>
     */
    class Schedule extends Entity
    {

        private static $days = ['', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

        protected static $_source = [
            'cacheable' => true,
            'schema' => [
                'primary' => [
                    'table' => 'schedule',
                    'columns' => [
                        'day' => 'int',
                        'start' => 'decimal',
                        'end' => 'decimal',
                        'type' => 'variable',
                        'level' => 'variable',
                        'title' => 'variable',
                    ],
                    'keys' => [
                        'local' => [
                            'day' => 'index',
                        ]
                    ]
                ]
            ]
        ];

        /**
         * Get a nice pretty class name.
         *
         * @return string
         */
        public function getClassName()
        {
            $day = $this->get('day');
            if (array_key_exists($day, self::$days)) {
                $day = self::$days[$day];
            } else {
                $day = 'Saturday';
            }
            $hoursFrom = explode('.', (string)$this->get('start'));
            if (count($hoursFrom) > 1) {
                $minutesFrom = str_pad(round(60 * $hoursFrom[1] / 100), 2, '0');
            } else {
                $minutesFrom = '00';
            }
            $hoursFrom = $hoursFrom[0];
            if ($hoursFrom >= 12) {
                $from = ($hoursFrom > 12
                        ? $hoursFrom - 12
                        : $hoursFrom) . ':' . $minutesFrom . 'pm';
            } else {
                $from = (!$hoursFrom
                        ? 12
                        : $hoursFrom) . ':' . $minutesFrom . 'am';
            }
            return $this->get('title') . ' (' . $day . ' at ' . $from . ')';
        }

        /**
         * Get an array of our defined days.
         *
         * @return array
         */
        public static function getDays()
        {
            return self::$days;
        }

    }
