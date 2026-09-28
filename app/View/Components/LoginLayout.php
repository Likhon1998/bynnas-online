<?php

namespace App\View\Components;

use App\Models\SiteSetting;
use Illuminate\View\Component;
use Illuminate\View\View;

class LoginLayout extends Component
{
    public SiteSetting $settings;

    public function __construct()
    {
        $this->settings = SiteSetting::current();
    }

    public function render(): View
    {
        return view('layouts.login');
    }
}
