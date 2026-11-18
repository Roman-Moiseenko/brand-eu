<?php

namespace App\Modules\Chat\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ChatController extends Controller
{
    public function __construct(

    )
    {}
    public function index(Request $request)
    {

        return Inertia::render('Chat/Index', []);
    }
}
