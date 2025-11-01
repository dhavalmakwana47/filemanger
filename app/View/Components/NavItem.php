<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class NavItem extends Component
{
    public $route;
    public $activeRoute;
    public $icon;
    public $text;
    public $module;
    public $permission;

    /**
     * Create a new component instance.
     *
     * @param string $route
     * @param string $activeRoute
     * @param string $icon
     * @param string $text
     * @return void
     */
    public function __construct($route, $activeRoute, $icon, $text, $module, $permission)
    {
        $this->route = $route;
        $this->activeRoute = $activeRoute;
        $this->icon = $icon;
        $this->text = $text;
        $this->module = $module;
        $this->permission = $permission;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return auth()->user()->hasPermission($this->module, $this->permission) || in_array($this->module, ['Dashboard']) ? view('components.nav-item') : '';
    }
}
