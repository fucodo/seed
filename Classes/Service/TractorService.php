<?php

namespace fucodo\seed\Service;

class TractorService {

    const TRACTOR = '
                __
      ______   |  |      *   *   *   (seeds)
     /|_||_\`.__|  |
    (   _    _ _  |
    =`-(_)--(_)-`';

    public static function getTractor() {
        return self::TRACTOR;
    }
}
