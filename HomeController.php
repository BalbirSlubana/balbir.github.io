<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**

     * Create a new controller instance.

     *

     * @return void

     */

    public function create(Request $request)
    {
        $files = $request->input("files");
        \Zipper::make(public_path('test.zip'))->add($files)->close();
        return response()->download(public_path('test.zip'));
        ini_set('max_execution_time', 3000);
    }

    public function index()
    {
        $Path = public_path('test.zip');
        \Zipper::make($Path)->extractTo('Appdividend');
        ini_set('max_execution_time', 3000);
    }
}
