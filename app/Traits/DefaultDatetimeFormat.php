<?php

namespace App\Traits;

use Carbon\Carbon;
use DateTimeInterface;

if (!trait_exists('DefaultDatetimeFormat')) {
    /**
     * Trait DefaultDatetimeFormat
     * @package App\Traits
     */
    trait DefaultDatetimeFormat
    {
        /**
         * @param DateTimeInterface $date
         * @return string
         */
        protected function serializeDate(DateTimeInterface $date): string
        {
            if (version_compare(app()->version(), '7.0.0') < 0) {
                return parent::serializeDate($date);
            }

            return $date->format(Carbon::DEFAULT_TO_STRING_FORMAT);
        }
    }
}
