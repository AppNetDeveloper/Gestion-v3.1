<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GeminiAPI\Laravel\Facades\Gemini;

class DebugController extends Controller
{
    public function index()
{

   dd(Gemini::countTokens('que es php?'), Gemini::generateText('que es php?'));
}
}
