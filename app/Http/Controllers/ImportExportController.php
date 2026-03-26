<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class ImportExportController extends Controller
{
    public function index()
    {
        return Inertia::render('ImportExport');
    }
}