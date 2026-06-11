<?php

namespace App\View\Components;

use App\Models\Laboratory;
use Illuminate\View\Component;


class Header extends Component
{
    public $laboratoriesNav;

    public function __construct()
    {
        $this->laboratoriesNav = Laboratory::orderBy('name')->get();
    }

    public function render()
    {
        return view('components.header');
    }
}