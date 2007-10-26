#!/bin/bash
php test.php --regressions --indented $@
php test.php --regressions --indented --suite 2 $@
php test.php --regressions --indented --suite 3 $@
php test.php --regressions --indented --suite 4 $@
