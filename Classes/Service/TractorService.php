<?php

namespace fucodo\seed\Service;

class TractorService {

    const TRACTOR_SEEDING = '
          _____
         /|_||_\\`---.
        (   _    _ _ \\      *   *   *   (seeds)
        =`-(_)--(_)-`';

    const TRACTOR_CLEANING = '
               |
          _____|_____
         [___________]
          (o)     (o)      .   .   .   (cleaned)';

    public static function getTractor() {
        return self::TRACTOR_SEEDING;
    }

    public static function getCleaningTractor() {
        return self::TRACTOR_CLEANING;
    }
}
