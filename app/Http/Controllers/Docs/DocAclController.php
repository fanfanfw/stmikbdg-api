<?php

namespace App\Http\Controllers\Docs;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DocAclController extends Controller
{
    public function index() {
        return view('docs.contents.acl.index', [
            'title' => 'Access Control Level'
        ]);
    }

    public function aclTabs($name) {
        switch (strtolower($name)) {
            case 'admin':
                $content = view('docs.contents.acl.admin')->render();
                break;
            default:
                $content = 'Tab not found.';
                break;
        }

        return response()->json([
            'content' => $content
        ]);
    }
}
