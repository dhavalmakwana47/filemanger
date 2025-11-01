<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class DataTable extends Component
{
    public $id;
    public $columns;
    public $extraOptions;

    public function __construct($id, $columns, $extraOptions = [])
    {
        $this->id = $id;
        $this->columns = $columns;
        $this->extraOptions = $extraOptions;
    }


    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.data-table');
    }
}
