<?php

foreach (glob(base_path('routes/api/*.php')) as $routeFile) {
    require $routeFile;
}
