<?php

use App\Models\MenuModel;

if (!function_exists('getMenus')) {
    function getMenus(): array
    {
        $cacheKey = 'site_menus';

        return cache()->remember($cacheKey, 3600, function () {
            $menuModel = new MenuModel();
            return $menuModel->getActiveMenus();
        });
    }
}
