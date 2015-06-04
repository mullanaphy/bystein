<?php

    /**
     * PHY\Variable
     * https://github.com/mullanaphy/variable
     *
     * LICENSE
     * DO WHAT THE FUCK YOU WANT TO PUBLIC LICENSE
     * http://www.wtfpl.net/
     */

    namespace PHY\Variable;

    /**
     * Encapsulated Array class.
     *
     * @package PHY\Variable\Arr
     * @category PHY\Variable
     * @license http://www.wtfpl.net/
     * @author John Mullanaphy <john@jo.mu>
     */
    class Arr extends \PHY\Variable\AVar implements \ArrayAccess, \Iterator, \Countable
    {

        protected $types = [
            'array'
        ];
        protected $original = [];
        protected $current = [];

        /**
         * {@inheritDoc}
         */
        public function toObj()
        {
            $array = $this->get();
            foreach ($array as $key => $value) {
                if (is_array($value)) {
                    $array[$key] = (new \PHY\Variable\Arr($value))->toObj();
                }
            }
            return new \PHY\Variable\Obj((object)$array);
        }

        /**
         * Recursively convert our array into a stdClass.
         *
         * @return \stdClass
         */
        public function toObject()
        {
            $array = $this->get();
            foreach ($array as $key => $value) {
                if (is_array($value)) {
                    $array[$key] = (new \PHY\Variable\Arr($value))->toObject();
                }
            }
            return (object)$array;
        }

        /**
         * {@inheritDoc}
         */
        public function toStr()
        {
            return new \PHY\Variable\Str(json_encode($this->get()));
        }

        /**
         * {@inheritDoc}
         */
        public function toInt()
        {
            return new \PHY\Variable\Int((int)$this->count());
        }

        /**
         * {@inheritDoc}
         */
        public function toFloat()
        {
            return new \PHY\Variable\Float((float)$this->count());
        }

        /**
         * {@inheritDoc}
         */
        public function toBool()
        {
            return new \PHY\Variable\Bool(!empty($this->get()));
        }

        /**
         * {@inheritDoc}
         */
        public function count()
        {
            return count($this->get());
        }

        /**
         * Grab the current row from our Array cursor.
         * 
         * @return mixed
         */
        public function current()
        {
            $get = $this->chaining
                ? 'chain'
                : 'current';
            return current($this->$get);
        }

        /**
         * Grab the current key from our Array cursor.
         *
         * @return scalar
         */
        public function key()
        {
            $get = $this->chaining
                ? 'chain'
                : 'current';
            return key($this->$get);
        }

        /**
         * Move our Array cursor to the next element.
         */
        public function next()
        {
            $get = $this->chaining
                ? 'chain'
                : 'current';
            next($this->$get);
        }

        /**
         * See if an Array offset exists in our Array.
         *
         * @param scalar $offset
         * @return boolean
         */
        public function offsetExists($offset)
        {
            $get = $this->chaining
                ? 'chain'
                : 'current';
            return array_key_exists($offset, $this->$get);
        }

        /**
         * Grab an offset if it's defined.
         *
         * @param scalar $offset
         * @return mixed
         */
        public function offsetGet($offset)
        {
            $get = $this->chaining
                ? 'chain'
                : 'current';
            return array_key_exists($offset, $this->$get)
                ? $this->$get[$offset]
                : null;
        }

        /**
         * Set an offset.
         *
         * @param scalar $offset
         * @param mixed $value
         */
        public function offsetSet($offset, $value)
        {
            $get = $this->chaining
                ? 'chain'
                : 'current';
            $this->$get[$offset] = $value;
        }

        /**
         * Unset an offset.
         *
         * @param scalar $offset
         */
        public function offsetUnset($offset)
        {
            $get = $this->chaining
                ? 'chain'
                : 'current';
            unset($this->$get[$offset]);
        }

        /**
         * Reset our Array cursor.
         */
        public function rewind()
        {
            $get = $this->chaining
                ? 'chain'
                : 'current';
            reset($this->$get);
        }

        /**
         * See if an element exists at this cursor.
         *
         * @return boolean
         */
        public function valid()
        {
            $get = $this->chaining
                ? 'chain'
                : 'current';
            return null !== key($this->$get);
        }

        /**
         * Randomly pick $num_req elements out of an Array.
         *
         * @param int $num_req
         * @return \PHY\Variable\Arr
         */
        public function rand($num_req = 1)
        {
            $array = array_rand($this->get(), $num_req);
            $this->update($array);
            return $this;
        }

        /**
         * Sort our Array.
         *
         * @param int $sort_flags
         * @return \PHY\Variable\Arr
         */
        public function sort($sort_flags = SORT_REGULAR)
        {
            $array = $this->get();
            sort($array, $sort_flags);
            $this->update($array);
            return $this;
        }

        /**
         * Slice an Array.
         *
         * @param int $offset
         * @param int|null $length
         * @param boolean $preserve_keys
         * @return \PHY\Variable\Arr
         */
        public function slice($offset = 0, $length = null, $preserve_keys = false)
        {
            $array = array_slice($this->get(), $offset, $length, $preserve_keys);
            $this->update($array);
            return $this;
        }

        /**
         * Splice an array.
         *
         * @param int $offset
         * @param int $length
         * @param mixed $replacement
         * @return \PHY\Variable\Arr
         */
        public function splice($offset = 0, $length = 0, $replacement = null)
        {
            $array = $this->get();
            if (null !== $replacement) {
                array_splice($array, $offset, $length, $replacement);
            } else {
                array_splice($array, $offset, $length);
            }
            $this->update($array);
            return $this;
        }

        /**
         * Map an array.
         *
         * @param callable $function
         * @return \PHY\Variable\Arr
         */
        public function map($function)
        {
            $array = array_map($function, $this->get());
            $this->update($array);
            return $this;
        }

        /**
         * Reduce an array.
         *
         * @param callable $function
         * @param mixed $initial If you wish to have a default value.
         * @return mixed
         */
        public function reduce($function, $initial = null)
        {
            $array = $this->get();
            return array_reduce($array, $function, $initial);
        }

    }
