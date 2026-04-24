<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\SiteRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Models\Site;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    public function __construct(
        private SiteRepositoryInterface $sites
    ) {}

    public function index()
    {
        $sites = $this->sites->getAll();
        return view('admin.sites.index', compact('sites'));
    }

    public function create()
    {
        return view('admin.sites.create');
    }

    public function edit(int $id)
    {
        $site = $this->sites->findById($id);
        
        if (!$site) {
            return redirect()->route('admin.sites.index')
                ->with('error', 'Site not found');
        }

        return view('admin.sites.edit', compact('site'));
    }
}
