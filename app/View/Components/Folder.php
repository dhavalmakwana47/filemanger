<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Folder extends Component
{
    public $folder;

    public function __construct($folder)
    {
        $this->folder = $folder;
    }

    public function render()
    {
        return view('components.folder');
    }
}
