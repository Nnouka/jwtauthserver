<?php

namespace App\Http\Controllers;

use App\Services\JwtService;
use Illuminate\Http\Request;

class JwtController extends Controller
{

    private $jwtService;
    /**
     * JwtController constructor.
     */
    public function __construct()
    {
        $this->jwtService = new JwtService();
    }

    public function test() {
        return $this->jwtService->generateTest();
    }

    public function generate(Request $request) {
        return $this->jwtService->generate(
            $request->input('email'),
            $request->input('password'),
            $request->getRequestUri()
        );
    }

    public function refresh(Request $request) {
        return $this->jwtService->refresh(
            $request->input('refreshToken'),
            $request->getRequestUri()
        );
    }
}
